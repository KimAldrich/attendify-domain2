<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Kreait\Firebase\Auth as FirebaseAuth;
use RealRashid\SweetAlert\Facades\Alert;
use App\Jobs\SendFirebaseResetLink;
use App\Models\User;

class FirebaseResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, FirebaseAuth $auth)
    {
        // normalize email
        $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);

        $validator = Validator::make($request->all(), [
            'email' => ['required','string','email:rfc','max:255'],
        ]);

        if ($validator->fails()) {
            Alert::toast('Please enter a valid email address.', 'error')->autoClose(6000);
            return back()->withErrors($validator)->withInput();
        }

        $email = $validator->validated()['email'];

        // Require that the account exists locally and is verified
        $user = User::where('email', $email)->first();
        if (! $user) {
            Alert::toast('No account found with that email.', 'error')->autoClose(6000);
            return back()->withErrors(['email' => 'No account found with that email.'])->withInput();
        }
        if (! $user->hasVerifiedEmail()) {
            Alert::toast('This email is registered but not verified. Please verify first.', 'warning')->autoClose(7000);
            return back()->withErrors(['email' => 'Email is not verified. Please verify first.'])->withInput();
        }

        // Throttle: 1 send per 5 minutes per email
        $key = 'pwreset:'.mb_strtolower($email);
        $windowSeconds = 300;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $availableIn = RateLimiter::availableIn($key);
            $mins = max(1, (int) ceil($availableIn / 60));
            Log::info('[PasswordReset] Throttled request', [
                'email' => $email,
                'available_in' => $availableIn,
            ]);

            Alert::toast("We’ve recently sent a reset link. Please check your inbox or try again in about {$mins} minute(s).", 'warning')->autoClose(7000);
            return back()
                ->withErrors(['email' => "Please try again in about {$mins} minute(s)."])
                ->withInput();
        }

        RateLimiter::hit($key, $windowSeconds);

        try {
            // Queue the job that asks Firebase for a reset link and emails your branded template
            SendFirebaseResetLink::dispatch($email);

            Alert::toast('We’ve sent a password reset link to your email.', 'success')->autoClose(6000);
            return back();

        } catch (\Throwable $e) {
            Log::error('[PasswordReset] Failed to dispatch job', ['email' => $email, 'err' => $e->getMessage()]);
            // optional: clear the limiter hit since we didn’t actually send anything
            RateLimiter::clear($key);

            Alert::toast('Unable to send reset link right now. Please try again shortly.', 'error')->autoClose(6000);
            return back()->withInput();
        }
    }
}
