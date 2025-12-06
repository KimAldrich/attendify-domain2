<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\UserProvider;

class AccountSecurityController extends Controller
{
    public function index()
    {
        // your existing page render
        return view('settings.security');
    }

    public function linked(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['ok' => false, 'msg' => 'Unauthenticated'], 401);
        }

        // Clear the 5-min cache used by ProfilePage::detectHasPasswordProvider
        if ($user->firebase_uid) {
            Cache::forget("fb:has_password:{$user->firebase_uid}");
        }

        // Also reflect the change in your local audit table
        if ($user->firebase_uid) {
            UserProvider::updateOrCreate(
                ['provider' => 'password', 'provider_uid' => $user->firebase_uid],
                [
                    'user_id'      => $user->id,
                    'email'        => $user->email,
                    'display_name' => $user->name,
                    'avatar_url'   => null,
                    'linked_at'    => now(),
                ]
            );
        }

        Log::info('[Security] Password provider linked + cache cleared', [
            'user_id' => $user->id,
            'uid'     => $user->firebase_uid,
        ]);

        // You can also set a flash for SweetAlert if this route is hit from a form.
        return response()->json(['ok' => true]);
    }
}
