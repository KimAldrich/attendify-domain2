<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use App\Services\FirestoreRest;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Kreait Auth binding
        $this->app->singleton(FirebaseAuth::class, function () {
            // read from config, not env()
            $creds    = config('services.firebase.credentials');   // string (path or inline JSON) or null
            $project  = config('services.firebase.project_id');    // optional

            $factory = new Factory();

            if ($creds) {
                // If it's inline JSON (starts with '{'), decode it
                if (Str::startsWith(ltrim($creds), '{')) {
                    $factory = $factory->withServiceAccount(json_decode($creds, true));
                } else {
                    // Otherwise treat it as a path. If not absolute, resolve from base_path().
                    $isAbsolute = Str::startsWith($creds, ['/', '\\']) || preg_match('/^[A-Za-z]:[\\/]/', $creds);
                    $path = $isAbsolute ? $creds : base_path($creds);
                    $factory = $factory->withServiceAccount($path);
                }
            }

            if ($project) {
                $factory = $factory->withProjectId($project);
            }

            return $factory->createAuth();
        });

        // Firestore REST service
        $this->app->singleton(FirestoreRest::class, fn () => new FirestoreRest());
        $this->app->alias(FirestoreRest::class, 'firestore.http');
    }
}
