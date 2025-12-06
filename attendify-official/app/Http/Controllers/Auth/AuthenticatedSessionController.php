<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Exception\AuthException;
use Kreait\Firebase\Exception\FirebaseException;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\RateLimiter;


class AuthenticatedSessionController extends Controller
{
    private int $verifyResendWindowSeconds = 300;
    private function throttleKeyForVerification(string $email): string
    {
        return 'verify-resend:'.mb_strtolower(trim($email));
    }    
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request, FirebaseAuth $firebaseAuth)
    {
        // Normalize inputs
        $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);

        $online = $this->isOnline();

        // Basic form validation
        $validator = Validator::make($request->all(), [
            'email'    => ['required','string','email:rfc'.($online ? ',dns' : ''),'max:255'],
            'password' => ['required','string'],
        ]);

        if ($validator->fails()) {
            Alert::toast('Please check your email and password.', 'error')->autoClose(6000);
            return back()->withErrors($validator)->withInput();
        }

        if (! $online) {
            Alert::toast('You appear to be offline. Sign-in may fail until you reconnect.', 'warning')->autoClose(6000);
        }

        $data  = $validator->validated();

        $email = $data['email'];
        $pass  = $data['password'];

        Log::info('[Login][Hybrid] Attempt', [
            'email' => $email,
            'ip'    => $request->ip(),
            'ua'    => substr((string) $request->userAgent(), 0, 255),
        ]);

        try {
            // 1) Firebase sign-in (credentials source of truth)
            $signIn = $firebaseAuth->signInWithEmailAndPassword($email, $pass);

            // 2) Find local user row
            $user = User::where('email', $email)->first();
            if (! $user) {
                Log::warning('[Login][Hybrid] Local user missing while Firebase sign-in succeeded', ['email' => $email]);
                Alert::toast('Your account exists in Firebase but not in our system. Please contact support.', 'error')->autoClose(6000);

                return back()
                    ->withErrors(['email' => 'Account not found locally. Please contact support.'])
                    ->withInput();
            }

            // 3) Verification check: **use MySQL only** for speed (no extra Firebase calls)
            if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
                // Rate-limit verification resends (same as in registration)
                $key          = $this->throttleKeyForVerification($email);
                $decaySeconds = $this->verifyResendWindowSeconds;

                if (RateLimiter::tooManyAttempts($key, 1)) {
                    $availableIn = RateLimiter::availableIn($key);
                    Log::info('[Login][Hybrid] Verification resend throttled', [
                        'email'            => $email,
                        'available_in_sec' => $availableIn,
                    ]);

                    $mins = max(1, (int) ceil($availableIn / 60));
                    Alert::toast("We’ve recently sent a verification link. Please check your inbox or try again in about {$mins} minute(s).", 'warning')->autoClose(7000);

                    return back()
                        ->withErrors(['email' => "A verification link was sent recently. Please try again in about {$mins} minute(s)."])
                        ->withInput();
                }

                RateLimiter::hit($key, $decaySeconds);

                try {
                    $user->sendEmailVerificationNotification();
                    Log::info('[Login][Hybrid] Resent Laravel verification email', ['user_id' => $user->id, 'email' => $email]);
                    Alert::toast('Please verify your email. We’ve just sent you a new verification link.', 'warning')->autoClose(6000);
                } catch (\Throwable $mailEx) {
                    Log::warning('[Login][Hybrid] Could not send verification mail', [
                        'email' => $email,
                        'err'   => $mailEx->getMessage(),
                    ]);
                    Alert::toast('Email not verified. We tried to resend the link but ran into an issue. Please try again shortly.', 'warning')->autoClose(6000);
                }

                return back()
                    ->withErrors(['email' => 'Email not verified. Check your inbox for a fresh verification link.'])
                    ->withInput();
            }

            // 4) All good — log them into Laravel
            Auth::login($user, $request->boolean('remember'));
            session(['login_issued_at' => now()]);
            
            // Session/cookie for Firebase tokens (unchanged)
            session([
                'firebase_id_token' => $signIn->idToken(),
            ]);

            $refresh = $signIn->refreshToken();
            session(['fb_refresh' => encrypt($refresh)]);

            Cookie::queue(
                cookie(
                    name: 'fb_refresh',
                    value: encrypt($refresh),
                    minutes: 60 * 24 * 60,
                    path: '/',
                    domain: null,
                    secure: app()->environment('production'),
                    httpOnly: true,
                    raw: false,
                    sameSite: 'lax'
                )
            );

            $role = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'guest') : 'guest';

            Alert::toast("Welcome back, {$user->display_name}!", 'success')->autoClose(3000);
            Log::info('[Login][Hybrid] Success', ['email' => $email, 'role' => $role]);

            return redirect()->intended(route('dashboard'));

        } catch (AuthException|FirebaseException $e) {
            // Network/transport check FIRST
            if ($this->isNetworkError($e)) {
                Log::warning('[Login][Hybrid] Firebase auth failed (network)', [
                    'email' => $email,
                    'code'  => $e->getCode(),
                    'msg'   => $e->getMessage(),
                ]);
                Alert::toast('Authentication failed. Please check your internet connection and try again.', 'error')->autoClose(6000);

                return back()
                    ->withErrors(['email' => 'You appear to be offline. Please check your internet connection.'])
                    ->withInput();
            }

            // Map Firebase errors to friendly messages
            $msg   = $e->getMessage();
            $error = 'Invalid credentials.';
            if (stripos($msg, 'EMAIL_NOT_FOUND') !== false) {
                $error = 'No account found with that email.';
            } elseif (stripos($msg, 'USER_DISABLED') !== false) {
                $error = 'This account has been disabled.';
            } elseif (stripos($msg, 'INVALID_PASSWORD') !== false) {
                $error = 'The password you entered is incorrect.';
            } elseif (stripos($msg, 'INVALID_LOGIN_CREDENTIALS') !== false) {
                $error = 'Email or password is incorrect.';
            }

            $inline = [];
            if ($error === 'No account found with that email.' || $error === 'This account has been disabled.') {
                $inline['email'] = $error;
            } elseif ($error === 'The password you entered is incorrect.') {
                $inline['password'] = $error;
            } else {
                $inline['email'] = $error;
            }

            Log::warning('[Login][Hybrid] Firebase auth failed', [
                'email' => $email,
                'map'   => $error,
                'msg'   => $msg,
            ]);

            Alert::toast($error, 'error')->autoClose(6000);

            return back()->withErrors($inline)->withInput();

        } catch (\Throwable $e) {
            if ($this->isNetworkError($e)) {
                Log::warning('[Login][Hybrid] Generic network failure', ['email' => $email, 'msg' => $e->getMessage()]);
                Alert::toast('Cannot reach the authentication service. Check your internet connection and try again.', 'error')->autoClose(6000);

                return back()
                    ->withErrors(['email' => 'You appear to be offline or the auth service is temporarily unavailable.'])
                    ->withInput();
            }

            Log::error('[Login][Hybrid] Unexpected failure', ['email' => $email, 'err' => $e->getMessage()]);
            Alert::toast('Unable to sign in right now. Please try again shortly.', 'error')->autoClose(6000);

            return back()
                ->withErrors(['email' => config('app.debug') ? $e->getMessage() : 'Login failed. Please try again.'])
                ->withInput();
        }
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Cookie::queue(Cookie::forget(Auth::getRecallerName()));
        Cookie::queue(Cookie::forget('fb_refresh')); 

        Alert::toast('Signed out.', 'success')->autoClose(2000);

        return redirect()->route('login');
    }

    /**
     * Heuristics to detect network/transport issues from wrapped exceptions.
     */
    private function isNetworkError(\Throwable $e): bool
    {
        $needleMsgs = [
            'Could not resolve host',     // DNS
            'Connection timed out',       // timeout
            'Failed to connect',          // connect()
            'SSL',                        // TLS issues (optional)
            'cURL error',                 // generic curl
        ];

        $cur = $e;
        while ($cur) {
            $msg = $cur->getMessage();
            foreach ($needleMsgs as $needle) {
                if (stripos($msg, $needle) !== false) {
                    return true;
                }
            }
            // Guzzle ConnectException/RequestException often indicate transport problems
            if ($cur instanceof \GuzzleHttp\Exception\ConnectException) return true;

            $cur = $cur->getPrevious();
        }
        return false;
    }

    private function isOnline(): bool
    {
        return (bool) @dns_get_record('google.com', DNS_A);
    }
}
