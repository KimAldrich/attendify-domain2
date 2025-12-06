<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Google\Cloud\Firestore\FirestoreClient;

class FirebaseRestProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('firestore.rest', function () {
            $projectId = env('FIREBASE_PROJECT_ID');
            $credPath  = env('FIREBASE_CREDENTIALS'); // absolute path to attendify.json

            if (!$projectId) {
                throw new \RuntimeException('FIREBASE_PROJECT_ID missing in .env');
            }
            if (!$credPath || !is_readable($credPath)) {
                throw new \RuntimeException("FIREBASE_CREDENTIALS not readable: {$credPath}");
            }

            $keyFile = json_decode(file_get_contents($credPath), true);
            if (!is_array($keyFile)) {
                throw new \RuntimeException('Service account JSON malformed.');
            }

            // Force REST (no gRPC)
            return new FirestoreClient([
                'projectId' => $projectId,
                'keyFile'   => $keyFile,
                'transport' => 'rest',
            ]);
        });
    }
}
