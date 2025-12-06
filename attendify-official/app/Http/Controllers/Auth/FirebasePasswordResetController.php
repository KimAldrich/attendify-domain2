<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Auth as FirebaseAuth;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Schema;

class FirebasePasswordResetController extends Controller
{
    public function create(Request $request, FirebaseAuth $auth)
    {
        $oobCode = $request->query('oobCode');
        $email   = mb_strtolower(trim((string) $request->query('email')));

        if (!$oobCode) {
            Alert::toast('Invalid or missing reset code.', 'error')->autoClose(6000);
            return redirect()->route('password.request');
        }

        try {
            $verifiedEmail = $auth->verifyPasswordResetCode($oobCode); // returns email tied to code
            if (!$email) $email = $verifiedEmail;
            if (mb_strtolower($verifiedEmail) !== $email) {
                // If provided email doesn't match; show what Firebase says
                $email = $verifiedEmail;
            }
            return view('auth.reset-password', compact('oobCode','email'));
        } catch (\Throwable $e) {
            Log::warning('[PasswordReset] verifyPasswordResetCode failed', ['err' => $e->getMessage()]);
            Alert::toast('This reset link is invalid or expired. Please request a new one.', 'error')->autoClose(6000);
            return redirect()->route('password.request');
        }
    }

    public function store(Request $request, FirebaseAuth $auth)
    {
        $online = (bool) @dns_get_record('google.com', DNS_A);

        $rule = Password::min(8)->mixedCase()->numbers();
        if ($online && app()->isProduction()) {
            $rule = $rule->uncompromised(10000);
        }

        $data = $request->validate([
            'oobCode'               => ['required','string'],
            'password'              => ['required','confirmed', $rule],
            'password_confirmation' => ['required','string'],
        ]);

        try {
            $email = $auth->verifyPasswordResetCode($data['oobCode']); // re-verify
            $auth->confirmPasswordReset($data['oobCode'], $data['password']);
            Log::info('[PasswordReset] Password updated', ['email' => $email]);

            // 🔐 1) Revoke Firebase refresh tokens so all tokens issued before now become invalid
            try {
                $fbUser = $auth->getUserByEmail($email);
                $auth->revokeRefreshTokens($fbUser->uid);
                Log::info('[PasswordReset] Firebase refresh tokens revoked', ['uid' => $fbUser->uid]);
            } catch (\Throwable $e) {
                Log::warning('[PasswordReset] Could not revoke Firebase tokens', ['email' => $email, 'err' => $e->getMessage()]);
            }

            // 🔐 2) Update local user for Laravel-side invalidation
            if ($user = \App\Models\User::where('email', $email)->first()) {
                // (a) rotate remember_token so any “remember me” cookie becomes invalid
                $user->setRememberToken(\Illuminate\Support\Str::random(60));
                // (b) record the time of password change
                if (Schema::hasColumn('users', 'password_changed_at')) {
                    $user->forceFill(['password_changed_at' => now()])->saveQuietly();
                } else {
                    $user->saveQuietly();
                }
            }

            Alert::toast('Your password has been reset. Please sign in.', 'success')->autoClose(6000);
            return redirect()->route('login');

        } catch (\Throwable $e) {
            Log::error('[PasswordReset] confirmPasswordReset failed', ['err' => $e->getMessage()]);
            if ($this->isNetworkError($e)) {
                Alert::toast('Cannot reach the authentication service. Try again shortly.', 'error')->autoClose(6000);
                return back()->withErrors(['password' => 'Service unavailable. Please retry.'])->withInput();
            }

            Alert::toast('Unable to reset password. Your link may be invalid or expired.', 'error')->autoClose(6000);
            return back()
                ->withErrors(['password' => 'Reset failed. Please request a new link.'])
                ->withInput(['oobCode' => $data['oobCode']]);
        }
    }

    private function isNetworkError(\Throwable $e): bool
    {
        $needles = ['Could not resolve host', 'Connection timed out', 'Failed to connect', 'cURL error', 'SSL'];
        do {
            $msg = $e->getMessage();
            foreach ($needles as $needle) {
                if (stripos($msg, $needle) !== false) return true;
            }
            if ($e instanceof \GuzzleHttp\Exception\ConnectException) return true;
            $e = $e->getPrevious();
        } while ($e);
        return false;
    }
}
