<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class CustomResetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public $token;
    public $expireMinutes;

    public function __construct($token)
    {
        $this->token = $token;
        $this->expireMinutes = Config::get('auth.passwords.users.expire', 60);

        // Log untuk debugging
        Log::info('CustomResetPasswordNotification created', [
            'token' => substr($token, 0, 10) . '...', // Log partial token untuk security
            'expire_minutes' => $this->expireMinutes
        ]);
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        Log::info('Preparing reset password email', [
            'user_email' => $notifiable->email,
            'user_id' => $notifiable->id
        ]);

        // Generate URL dengan signed route yang benar
        $url = URL::temporarySignedRoute(
            'password.reset',
            Carbon::now()->addMinutes($this->expireMinutes),
            [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]
        );

        Log::info('Reset password URL generated', [
            'url_length' => strlen($url),
            'expires_at' => Carbon::now()->addMinutes($this->expireMinutes)->toDateTimeString()
        ]);

        try {
            $mailMessage = (new MailMessage)
                ->subject('Reset Password - PT. Sentra Alam Anandana')
                ->view('emails.custom-reset-password', [
                    'user' => $notifiable,
                    'url' => $url,
                    'expireMinutes' => $this->expireMinutes,
                ]);

            Log::info('Mail message created successfully for reset password');

            return $mailMessage;

        } catch (\Exception $e) {
            Log::error('Error creating reset password mail message', [
                'error' => $e->getMessage(),
                'user_email' => $notifiable->email
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error('CustomResetPasswordNotification failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
