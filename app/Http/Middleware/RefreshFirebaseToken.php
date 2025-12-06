<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Contracts\Encryption\DecryptException;
use Kreait\Firebase\Exception\AuthException as FirebaseAuthException;
use GuzzleHttp\Exception\GuzzleException;

class RefreshFirebaseToken
{
    public function __construct(private FirebaseAuth $auth) {}

    private function carbonFromExp(mixed $exp): CarbonImmutable
    {
        if ($exp instanceof DateTimeInterface) {
            return CarbonImmutable::instance($exp);
        }
        if (is_numeric($exp)) {
            return CarbonImmutable::createFromTimestamp((int) $exp);
        }
        // Kreait can also return null if token is odd; treat as expired
        throw new \RuntimeException('Unexpected exp claim type: '.gettype($exp));
    }

    private function fetchRefreshToken(Request $request): ?string
    {
        if ($enc = Cookie::get('fb_refresh')) {
            try { return decrypt($enc); } catch (\Throwable) {}
        }
        if ($enc = session('fb_refresh')) {
            try { return decrypt($enc); } catch (\Throwable) {}
        }
        return null;
    }

    private function setRefreshToken(string $refreshToken): void
    {
        $enc = encrypt($refreshToken);
        session(['fb_refresh' => $enc]);
        Cookie::queue(cookie(
            'fb_refresh', $enc,
            60 * 24 * 60, // minutes
            '/',
            null,
            app()->environment('production'),
            true,  // HttpOnly
            false, // raw
            'lax'
        ));
    }

    public function handle(Request $request, Closure $next)
    {
        // 0) Only for authenticated users
        if (!Auth::check()) {
            return $next($request);
        }

        /**
         * 1) CRITICAL: Never refresh during Livewire/AJAX/JSON requests.
         *    These requests should be side-effect free re: session/cookie,
         *    or Livewire can get stuck in an infinite loading state.
         */
        if ($request->header('X-Livewire') || $request->expectsJson() || $request->ajax()) {
            return $next($request);
        }

        /**
         * 2) Determine whether we truly need a refresh.
         *    If we were previously marked offline, FORCE a refresh attempt
         *    (override the “<= 60s to expiry” rule) and IGNORE cooldown.
         */
        $wasOffline = session('firebase_offline') === true;

        $needsRefresh = false;
        $idToken   = session('firebase_id_token');
        $expiresAt = session('firebase_id_token_expires_at');

        if (!$idToken) {
            $needsRefresh = true;
        } elseif ($expiresAt instanceof \Carbon\CarbonInterface) {
            $needsRefresh = $wasOffline
                || $expiresAt->isPast()
                || $expiresAt->diffInRealSeconds(now()) <= 60;
        } else {
            // Recompute exp once if not stored
            try {
                $verified  = $this->auth->verifyIdToken($idToken, false);
                $expClaim  = $verified->claims()->get('exp'); // may be DateTimeImmutable
                $expiresAt = $this->carbonFromExp($expClaim);
                session(['firebase_id_token_expires_at' => $expiresAt]);

                $needsRefresh = $wasOffline
                    || $expiresAt->isPast()
                    || $expiresAt->diffInRealSeconds(now()) <= 60;
            } catch (\Throwable $e) {
                $needsRefresh = true;
            }
        }

        if ($needsRefresh) {
            /**
             * 3) Per-user throttle/lock to avoid “refresh storms”.
             *    - Long cooldown (45s) only on SUCCESS
             *    - Short cooldown (5s) on any FAILURE
             *    - If we were offline, ignore existing cooldown so we can retry immediately.
             */
            $userId      = (string) Auth::id();
            $cooldownKey = "fb_refresh_cooldown:{$userId}";
            $lockKey     = "fb_refresh_lock:{$userId}";

            $cooldownOnSuccess = 20; // seconds
            $cooldownOnFailure = 5;  // seconds

            // Honor cooldown only if we weren't offline
            if (!$wasOffline && Cache::has($cooldownKey)) {
                return $next($request);
            }

            $lock = Cache::lock($lockKey, 20); // seconds
            if ($lock->get()) {
                try {
                    $refreshToken = $this->fetchRefreshToken($request);

                    if ($refreshToken) {
                        try {
                            // 1) Ask Firebase for a fresh ID token using the refresh token
                            $result     = $this->auth->signInWithRefreshToken($refreshToken);
                            $newIdToken = $result->idToken();
                            $newRefresh = $result->refreshToken();

                            // 2) Verify ID token → compute expiry
                            try {
                                $verified  = $this->auth->verifyIdToken($newIdToken);
                                $expClaim  = $verified->claims()->get('exp');
                                $expiresAt = $this->carbonFromExp($expClaim);
                            } catch (\Throwable $verifyEx) {
                                $msg = strtolower($verifyEx->getMessage() ?? '');

                                // ✅ Clock skew: do NOT clear tokens; short cooldown and continue
                                if (str_contains($msg, 'issued in the future')) {
                                    Log::warning('[FB] ID token verification failed due to clock skew; will retry shortly', [
                                        'ex' => $verifyEx->getMessage(),
                                    ]);
                                    session(['firebase_offline' => true]);
                                    Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));
                                    return $next($request);
                                }

                                // Other verify failures → treat as transient unless recurring
                                Log::warning('[FB] ID token verification failed', ['ex' => $verifyEx->getMessage()]);
                                session(['firebase_offline' => true]);
                                Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));
                                return $next($request);
                            }

                            // 3) Persist new tokens
                            session([
                                'firebase_id_token'            => $newIdToken,
                                'firebase_id_token_expires_at' => $expiresAt,
                            ]);

                            // 4) Rotate/ensure refresh token persistence
                            if (!empty($newRefresh) && $newRefresh !== $refreshToken) {
                                $this->setRefreshToken($newRefresh);
                            } else {
                                // ensure cookie exists if we came from the session backup
                                $this->setRefreshToken($refreshToken);
                            }

                            // 5) Success → clear offline flag, set success cooldown
                            session()->forget('firebase_offline');
                            Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnSuccess));
                            Log::info('[FB] Token refreshed', ['exp' => (string) $expiresAt]);

                        } catch (DecryptException $e) {
                            Log::warning('[FB] Refresh cookie decrypt failed; clearing', ['msg' => $e->getMessage()]);
                            Cookie::queue(Cookie::forget('fb_refresh'));
                            session(['firebase_offline' => true]);
                            Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));

                        } catch (FirebaseAuthException $e) {
                            $msg  = strtolower($e->getMessage() ?? '');
                            $prev = $e->getPrevious()?->getMessage();

                            $looksNetwork =
                                str_contains($msg, 'curl error') ||
                                str_contains($msg, 'could not resolve host') ||
                                str_contains($msg, 'connection timed out') ||
                                ($prev && str_contains(strtolower($prev), 'curl error'));

                            if ($looksNetwork) {
                                Log::warning('[FB] Network error while refreshing', ['msg' => $e->getMessage(), 'prev' => $prev]);
                                session(['firebase_offline' => true]);
                                Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));
                            } else {
                                // ⚠️ Do NOT clear tokens on clock-skew
                                if (str_contains($msg, 'issued in the future')) {
                                    Log::warning('[FB] Clock skew detected during refresh; keeping token, retry soon', ['msg' => $e->getMessage()]);
                                    session(['firebase_offline' => true]);
                                    Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));
                                } else {
                                    // True invalid/revoked → clear both cookie & session backup
                                    Log::warning('[FB] Refresh token invalid/revoked; clearing', ['msg' => $e->getMessage()]);
                                    Cookie::queue(Cookie::forget('fb_refresh'));
                                    session()->forget('fb_refresh');
                                    session(['firebase_offline' => true]);
                                    Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));
                                }
                            }
                        } catch (GuzzleException $e) {
                            Log::warning('[FB] Network error while refreshing', ['msg' => $e->getMessage()]);
                            session(['firebase_offline' => true]);
                            Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));

                        } catch (\Throwable $e) {
                            Log::warning('[FB] Unexpected error while refreshing', [
                                'ex_class' => $e::class, 'msg' => $e->getMessage(),
                            ]);
                            session(['firebase_offline' => true]);
                            Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));
                        }
                    } else {
                        // No cookie AND no session backup → we can’t refresh at all
                            if (!session()->has('fb_need_reauth')) {
                                session(['fb_need_reauth' => true]);
                                Log::warning('[FB] No refresh token available (cookie+session missing); user must re-authenticate.');
                            }
                            session(['firebase_offline' => true]);
                            Cache::put($cooldownKey, 1, now()->addSeconds($cooldownOnFailure));
                    }
                } finally {
                    optional($lock)->release();
                }
            }
        }

        return $next($request);
    }

    private function retry(int $times, callable $fn) {
        $attempt = 0;
        retry:
        try { return $fn(); }
        catch (\Throwable $e) {
            if (++$attempt < $times) { usleep(200_000); goto retry; }
            throw $e;
        }
    }
}
