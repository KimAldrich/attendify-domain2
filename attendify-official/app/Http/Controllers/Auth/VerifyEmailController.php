<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use App\Jobs\SyncFirebaseEmailVerified;

class VerifyEmailController extends Controller
{
    public function guest(Request $request, string $id, string $hash): RedirectResponse
    {
        // 1) Validate signed URL
        if (! URL::hasValidSignature($request)) {
            Alert::toast('Verification link is invalid or expired.', 'error')->autoClose(6000);
            return redirect()->route('login')->withErrors(['email' => 'Verification link is invalid or expired.']);
        }

        // 2) Load user
        /** @var \App\Models\User|null $user */
        $user = User::find($id);
        if (! $user) {
            Alert::toast('User not found for verification.', 'error')->autoClose(6000);
            return redirect()->route('login')->withErrors(['email' => 'User not found for verification.']);
        }

        // 3) Ensure the hash matches this email
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            Alert::toast('Verification link is invalid.', 'error')->autoClose(6000);
            return redirect()->route('login')->withErrors(['email' => 'Verification link is invalid.']);
        }

        // Already verified?
        if ($user->hasVerifiedEmail()) {
            // Be tidy with session (even if guest route)
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Alert::toast('Email already verified. Please sign in.', 'info')->autoClose(4000);
            return redirect()->route('login')->with('success', 'Email already verified. Please sign in.');
        }

        // 4) Mark verified in MySQL
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // 4.5) Promote to admin if in config (kept from your code)
        $adminEmails = array_map('strtolower', (array) config('attendify.admin_emails', []));
        if (in_array(strtolower($user->email), $adminEmails, true) && method_exists($user, 'syncRoles')) {
            $user->syncRoles(['admin']);
            $user->refresh();
        }

        if (! empty($user->firebase_uid)) {
            try {
                SyncFirebaseEmailVerified::dispatchSync($user->id);
            } catch (\Throwable $e) {
                Log::warning('[VerifyEmail] Failed to queue Firebase mirror', ['uid' => $user->firebase_uid, 'err' => $e->getMessage()]);
            }
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Alert::toast('Email verified! You can now sign in.', 'success')->autoClose(5000);
        return redirect()->route('login')->with('success', 'Email verified! You can now sign in.');
    }
}
