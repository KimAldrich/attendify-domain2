<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth as FirebaseAuth;
use RealRashid\SweetAlert\Facades\Alert;

class IdTokenLoginController extends Controller
{
    public function store(Request $request, FirebaseAuth $firebaseAuth)
    {
        // EARLY TRACE
        Log::info('[IdTokenLogin] POST hit', [
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $data = $request->validate([
            'id_token'       => ['required','string'],
            'refresh_token'  => ['nullable','string'],
            'remember'       => ['nullable','boolean'],
        ]);

        // Never log raw tokens; lengths are safe to log.
        Log::debug('[IdTokenLogin] Payload received', [
            'id_token_len'      => strlen($data['id_token'] ?? ''),
            'has_refresh_token' => !empty($data['refresh_token']),
            'remember'          => array_key_exists('remember', $data) ? (bool)$data['remember'] : null,
        ]);

        try {
            // Verify Google/Firebase ID token
            $verified = $firebaseAuth->verifyIdToken($data['id_token']);
            $claims   = $verified->claims();

            // Prefer 'user_id' then 'sub'
            $uid   = (string) ($claims->get('user_id') ?? $claims->get('sub'));
            $email = mb_strtolower(trim((string) ($claims->get('email') ?? '')));
            $emailVerified = (bool) ($claims->get('email_verified') ?? false);
            $name  = (string) ($claims->get('name') ?? '');
            $avatar = (string) ($claims->get('picture') ?? '');

            Log::info('[IdTokenLogin] Token verified', [
                'uid'            => $uid,
                'email'          => $email ?: null,
                'email_verified' => $emailVerified,
            ]);

            if (!$uid) {
                Log::warning('[IdTokenLogin] Missing UID after verification');
                Alert::toast('Sign-in failed. Invalid credential.', 'error')->autoClose(6000);
                return back()->withErrors(['email' => 'Invalid credential.']);
            }

            // Find or create user
            $user = User::where('firebase_uid', $uid)->first();
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            $created = false;

            if (!$user) {
                $user = User::create([
                    'firebase_uid' => $uid,
                    'email'        => $email ?: null,
                    'name'         => $name ?: ($email ?: 'User '.$uid),
                    'first_name'   => $this->firstNameFromFull($name),
                    'last_name'    => $this->lastNameFromFull($name),
                    'password'     => null,   // social login → no local password
                    'photo_path'   => null,
                ]);

                $created = true;

                if (method_exists($user, 'assignRole')) {
                    $user->assignRole('guest'); // default
                }

                Log::info('[IdTokenLogin] Local user created', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                    'uid'     => $uid,
                ]);
            } else {
                // Backfill UID and basic names if missing
                $updates = [];

                if (!$user->firebase_uid) {
                    $user->firebase_uid = $uid;
                    $updates[] = 'firebase_uid';
                }
                if ($name && !$user->first_name && !$user->last_name) {
                    $user->first_name = $this->firstNameFromFull($name);
                    $user->last_name  = $this->lastNameFromFull($name);
                    $user->name       = trim($user->first_name.' '.$user->last_name) ?: ($user->name ?? $email);
                    $updates[] = 'name_parts';
                }

                if (!empty($updates)) {
                    $user->saveQuietly();
                    Log::info('[IdTokenLogin] Local user updated', [
                        'user_id' => $user->id,
                        'changes' => $updates,
                    ]);
                }
            }

            // Mirror verified email locally (first login can grant verified status)
            if ($email && $emailVerified && ! $user->hasVerifiedEmail()) {
                $user->email = $email;
                $user->email_verified_at = now();
                $user->saveQuietly();

                Log::info('[IdTokenLogin] Local email verified via provider', [
                    'user_id' => $user->id,
                    'email'   => $email,
                ]);
            }

            // Upsert providers row (so you can audit/inspect later)
            UserProvider::updateOrCreate(
                ['provider' => 'google', 'provider_uid' => $uid],
                [
                    'user_id'      => $user->id,
                    'email'        => $email ?: null,
                    'display_name' => $name ?: null,
                    'avatar_url'   => $avatar ?: null,
                    'linked_at'    => now(),
                ]
            );

            // Respect remember flag (default true for social)
            $remember = array_key_exists('remember', $data) ? (bool)$data['remember'] : true;
            Auth::login($user, $remember);

            // Store Firebase tokens (optional)
            session(['firebase_id_token' => $data['id_token']]);

            if (!empty($data['refresh_token'])) {
                $enc = encrypt($data['refresh_token']);
                session(['fb_refresh' => $enc]);
                Cookie::queue(cookie(
                    'fb_refresh',
                    $enc,
                    60 * 24 * 60, // minutes (60 days)
                    '/',
                    null,
                    app()->environment('production'),
                    true,   // HttpOnly
                    false,  // raw
                    'lax'
                ));
            }

            Log::info('[IdTokenLogin] Authenticated & redirecting', [
                'user_id' => $user->id,
                'remember'=> $remember,
                'created' => $created,
            ]);

            Alert::toast("Welcome, {$user->display_name}!", 'success')->autoClose(3000);
            return redirect()->intended(route('dashboard'));

        } catch (\Throwable $e) {
            Log::warning('[IdTokenLogin] Failed', ['err' => $e->getMessage()]);
            Alert::toast('Sign-in failed. Please try again.', 'error')->autoClose(6000);
            return back()->withErrors(['email' => 'Sign-in failed. Please try again.']);
        }
    }

    private function firstNameFromFull(?string $full): ?string
    {
        $full = trim((string) $full);
        if ($full === '') return null;
        $first = Str::of($full)->beforeLast(' ')->value();
        return $first !== '' ? $first : $full;
    }

    private function lastNameFromFull(?string $full): ?string
    {
        $full = trim((string) $full);
        if ($full === '') return null;
        $last = Str::of($full)->afterLast(' ')->value();
        return $last !== $full ? $last : null;
    }
}
