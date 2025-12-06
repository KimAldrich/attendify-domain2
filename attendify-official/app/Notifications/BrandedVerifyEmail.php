<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class BrandedVerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Verify your email address')
            ->markdown('emails/auth/verify-email', [
                'link' => $url,
            ]);
    }


    protected function verificationUrl($notifiable): string
    {
        $params = [
            'id'   => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ];

        $expire = Carbon::now()->addMinutes(
            Config::get('auth.verification.expire', 60)
        );

        return URL::temporarySignedRoute('verification.verify', $expire, $params);
    }
}
