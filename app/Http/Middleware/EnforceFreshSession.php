<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceFreshSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = $request->user();

            // When did this session get issued?
            $issuedAt = session('login_issued_at');

            // If we have a password_changed_at and this session is older → logout
            if ($user->password_changed_at && ($issuedAt === null || now()->lt($user->password_changed_at))) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Your session has expired due to a recent password change. Please sign in again.']);
            }
        }
        return $next($request);
    }
}
