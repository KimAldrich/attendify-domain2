<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Auth\CreateActionLink\FailedToCreateActionLink;
use App\Mail\FirebaseResetPasswordMail;

class SendFirebaseResetLink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** The target email (lowercased/trimmed). */
    public string $email;

    /** Retry policy for transient failures. */
    public int $tries = 3;

    /** Backoff between retries (seconds). */
    public $backoff = [30, 120, 300];

    public function __construct(string $email)
    {
        $this->email = mb_strtolower(trim($email));
    }

    public function handle(): void
    {
        /** @var FirebaseAuth $auth */
        $auth = app(FirebaseAuth::class);

        try {
            // 1) Ask Firebase for a password-reset link (contains ?oobCode=...)
            $firebaseLink = $auth->getPasswordResetLink($this->email);
        } catch (FailedToCreateActionLink $e) {
            $msg = $e->getMessage() ?? '';

            // Non-retryable cases — just log and stop the job gracefully.
            if (stripos($msg, 'RESET_PASSWORD_EXCEED_LIMIT') !== false) {
                Log::notice('[Job][ResetLink] Quota/limit reached; not sending another reset email right now', [
                    'email' => $this->email,
                ]);
                return; // do not throw → no retry
            }
            if (stripos($msg, 'EMAIL_NOT_FOUND') !== false) {
                Log::notice('[Job][ResetLink] Email not found in Firebase; suppressing send', [
                    'email' => $this->email,
                ]);
                return; // do not throw → no retry
            }

            // Other Firebase create-link errors → let the queue retry
            throw $e;
        } catch (\Throwable $e) {
            // Network/transient errors → allow retry by rethrowing
            throw $e;
        }

        // 2) Extract the oobCode from Firebase URL
        parse_str(parse_url($firebaseLink, PHP_URL_QUERY) ?: '', $qs);
        $oobCode = $qs['oobCode'] ?? null;
        if (!$oobCode) {
            // Treat as transient; a retry may produce a proper link.
            throw new \RuntimeException('Missing oobCode from Firebase link.');
        }

        // 3) Build your app’s reset page URL (the branded page you control)
        $appLink = route('password.reset', [
            'oobCode' => $oobCode,
            'email'   => $this->email,
        ]);

        // 4) Send branded email
        Mail::to($this->email)->send(new FirebaseResetPasswordMail($appLink));

        Log::info('[Job][ResetLink] Password reset email sent', ['email' => $this->email]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[Job][ResetLink] Failed permanently', [
            'email' => $this->email,
            'err'   => $e->getMessage(),
        ]);
    }
}
