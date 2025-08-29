<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Primary action - Edit
            Actions\EditAction::make()
                ->label('Edit User')
                ->icon('heroicon-o-pencil-square')
                ->color('warning'),  // Edit menggunakan warning/amber

            // Grouped secondary actions
            Actions\ActionGroup::make([
                // Email-related actions
                Actions\Action::make('sendPasswordResetEmail')
                    ->label('Send Password Reset')
                    ->icon('heroicon-o-key')
                    ->color('info')  // Info actions menggunakan blue
                    ->requiresConfirmation()
                    ->modalHeading('Send Password Reset Email')
                    ->modalDescription('A password reset email will be sent to the user\'s email address.')
                    ->modalSubmitActionLabel('Send Email')
                    ->action(function ($record) {
                        $status = Password::sendResetLink(['email' => $record->email]);

                        if ($status === Password::RESET_LINK_SENT) {
                            $this->logActivity($record, 'password_reset_sent', 'Password reset email has been sent to user');

                            Notification::make()
                                ->success()
                                ->title('Password reset email sent')
                                ->body('The user will receive an email with a link to reset their password.')
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to send email')
                                ->body('An error occurred while sending the password reset email.')
                                ->send();
                        }
                    }),

                Actions\Action::make('sendVerificationEmail')
                    ->label('Send Email Verification')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')  // Info actions menggunakan blue
                    ->requiresConfirmation()
                    ->modalHeading('Send Email Verification')
                    ->modalDescription('A verification email will be sent to the user\'s email address.')
                    ->modalSubmitActionLabel('Send Email')
                    ->visible(fn ($record) => !$record->email_verified_at)
                    ->action(function ($record) {
                        try {
                            $record->sendEmailVerificationNotification();
                            $this->logActivity($record, 'verification_email_sent', 'Email verification has been sent to user');

                            Notification::make()
                                ->success()
                                ->title('Verification email sent')
                                ->body('The user will receive an email to verify their email address.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to send email')
                                ->body('An error occurred while sending the verification email.')
                                ->send();
                        }
                    }),

                Actions\Action::make('verifyEmailManually')
                    ->label('Verify Email Manually')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')  // Success actions menggunakan green
                    ->requiresConfirmation()
                    ->modalHeading('Manual Email Verification')
                    ->modalDescription('The user\'s email will be marked as verified manually.')
                    ->modalSubmitActionLabel('Verify Now')
                    ->visible(fn ($record) => !$record->email_verified_at)
                    ->action(function ($record) {
                        $record->update(['email_verified_at' => now()]);
                        $this->logActivity($record, 'email_verified_manually', 'Email verified manually by admin');

                        Notification::make()
                            ->success()
                            ->title('Email verified')
                            ->body('The user\'s email has been successfully verified manually.')
                            ->send();
                    }),
            ])
            ->label('Email Actions')
            ->icon('heroicon-o-envelope')
            ->color('gray')  // Group button menggunakan gray
            ->button()
            ->outlined(),

            // Destructive action - Delete
            Actions\DeleteAction::make()
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')  // Destructive menggunakan red
                ->requiresConfirmation()
                ->modalHeading('Delete User')
                ->modalDescription('Are you sure you want to delete this user? This action cannot be undone and will remove all associated data.')
                ->modalSubmitActionLabel('Yes, Delete User')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index'))
                ->outlined(),
        ];
    }

    /**
     * Helper method untuk logging activity
     */
    private function logActivity($record, string $event, string $description): void
    {
        ActivityLog::create([
            'subject_type' => get_class($record),
            'subject_id' => $record->id,
            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
            'causer_id' => Auth::id(),
            'event' => $event,
            'description' => $description,
            'properties' => json_encode([
                'email' => $record->email,
                'performed_by' => Auth::user()?->name ?? 'System',
                'performed_at' => now(),
            ]),
        ]);
    }
}
