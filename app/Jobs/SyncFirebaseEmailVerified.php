<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Log;

class SyncFirebaseEmailVerified implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId) {}

    public function handle(FirebaseAuth $auth): void
    {
        $user = User::find($this->userId);
        if (! $user || empty($user->firebase_uid)) {
            return;
        }

        try {
            $auth->updateUser($user->firebase_uid, ['emailVerified' => true]);
            Log::info('[VerifyEmail][Job] Firebase emailVerified synced', ['uid' => $user->firebase_uid]);
        } catch (\Throwable $e) {
            Log::warning('[VerifyEmail][Job] Failed to sync Firebase emailVerified', [
                'uid' => $user->firebase_uid,
                'err' => $e->getMessage(),
            ]);
        }
    }
}
