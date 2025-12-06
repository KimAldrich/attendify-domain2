<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FirestoreRest;

class FirestoreHttpProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirestoreRest::class, fn () => new FirestoreRest());
        // Optional string accessor
        $this->app->singleton('firestore.http', fn ($app) => $app->make(FirestoreRest::class));
    }
}
