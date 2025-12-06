<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Kreait\Firebase\Auth as FirebaseAuth;
use App\Jobs\SendFirebaseResetLink;
use App\Models\User;

class InAppResetLinkController extends Controller
{
    public function store(Request $request, FirebaseAuth $auth)
{
    $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);

    $validator = Validator::make($request->all(), [
        'email' => ['required','string','email:rfc','max:255'],
    ]);

    if ($validator->fails()) {
        return back()
            ->withErrors($validator)
            ->withInput()
            ->with('error', 'Please enter a valid email address.');
    }

    $email = $validator->validated()['email'];

    $user = User::where('email', $email)->first();
    if (! $user) {
        return back()
            ->withErrors(['email' => 'No account found with that email.'])
            ->withInput()
            ->with('error', 'No account found with that email.');
    }

    if (! $user->hasVerifiedEmail()) {
        return back()
            ->withErrors(['email' => 'Email is not verified.'])
            ->withInput()
            ->with('warning', 'Email is not verified.');
    }

    // Throttle: 1 send per 5 minutes per email
    $key = 'pwreset:'.mb_strtolower($email);
    $windowSeconds = 300;

    if (RateLimiter::tooManyAttempts($key, 1)) {
        $availableIn = RateLimiter::availableIn($key);
        $mins = max(1, (int) ceil($availableIn / 60));
        Log::info('[InAppReset] Throttled request', ['email' => $email, 'available_in' => $availableIn]);

        return back()
            ->withErrors(['email' => "We’ve recently sent a reset link. Please try again in about {$mins} minute(s)."])
            ->withInput()
            ->with('warning', "We’ve recently sent a reset link. Please try again in about {$mins} minute(s).");
    }

    RateLimiter::hit($key, $windowSeconds);

    try {
        SendFirebaseResetLink::dispatch($email);
        Log::info('[InAppReset] Reset link dispatched', ['email' => $email]);
        return back()->with('success', 'We’ve sent a password reset link to your email.');
    } catch (\Throwable $e) {
        Log::error('[InAppReset] Failed', ['email' => $email, 'err' => $e->getMessage()]);
        RateLimiter::clear($key);
        return back()->with('error', 'Unable to send reset link right now. Please try again.');
    }
}

}
