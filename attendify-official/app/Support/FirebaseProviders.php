<?php

// app/Support/FirebaseProviders.php
namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Kreait\Firebase\Auth as FirebaseAuth;

class FirebaseProviders
{
    public static function userHasPasswordProvider(?string $firebaseUid, FirebaseAuth $auth): bool
    {
        if (!$firebaseUid) return false;

        return Cache::remember("fb:has_password:{$firebaseUid}", 300, function () use ($firebaseUid, $auth) {
            try {
                $fbUser = $auth->getUser($firebaseUid);
                $providers = collect($fbUser->providerData ?? [])->pluck('providerId')->all();
                return in_array('password', $providers, true);
            } catch (\Throwable) {
                return false;
            }
        });
    }
}
