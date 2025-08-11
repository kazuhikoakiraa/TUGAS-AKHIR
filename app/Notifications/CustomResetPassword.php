<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(config('app.url').route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Verifikasi Reset Password - PT. Sentra Alam Anandana')
            ->view('emails.custom-reset-password', [
                'user' => $notifiable,
                'url' => $url,
                'expireMinutes' => $expireMinutes
            ]);
    }
}
