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
            Actions\EditAction::make()
                ->label('Edit User')
                ->icon('heroicon-o-pencil-square')
                ->modalWidth('2xl'),

            Actions\DeleteAction::make()
                ->label('Delete User')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete User')
                ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index')),

            Actions\Action::make('sendPasswordResetEmail')
                ->label('Send Password Reset')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Send Password Reset Email')
                ->modalDescription('A password reset email will be sent to the user\'s email address. The user will receive a link to change their password themselves.')
                ->modalSubmitActionLabel('Yes, Send Email')
                ->action(function ($record) {
                    // Send password reset notification
                    $status = Password::sendResetLink(
                        ['email' => $record->email]
                    );

                    if ($status === Password::RESET_LINK_SENT) {
                        // Log activity
                        ActivityLog::create([
                            'subject_type' => get_class($record),
                            'subject_id' => $record->id,
                            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                            'causer_id' => Auth::id(),
                            'event' => 'password_reset_sent',
                            'description' => 'Password reset email has been sent to user',
                            'properties' => json_encode([
                                'email' => $record->email,
                                'sent_by' => Auth::user()?->name ?? 'System',
                                'sent_at' => now(),
                            ]),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Password reset email sent successfully')
                            ->body('The user will receive an email with a link to reset their password.')
                            ->duration(5000)
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Failed to send email')
                            ->body('An error occurred while sending the password reset email.')
                            ->duration(5000)
                            ->send();
                    }
                }),

            Actions\Action::make('sendVerificationEmail')
                ->label('Send Email Verification')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Send Email Verification')
                ->modalDescription('A verification email will be sent to the user\'s email address.')
                ->modalSubmitActionLabel('Yes, Send Email')
                ->visible(fn ($record) => !$record->email_verified_at)
                ->action(function ($record) {
                    try {
                        // Send email verification notification
                        $record->sendEmailVerificationNotification();

                        // Log activity
                        ActivityLog::create([
                            'subject_type' => get_class($record),
                            'subject_id' => $record->id,
                            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                            'causer_id' => Auth::id(),
                            'event' => 'verification_email_sent',
                            'description' => 'Email verification has been sent to user',
                            'properties' => json_encode([
                                'email' => $record->email,
                                'sent_by' => Auth::user()?->name ?? 'System',
                                'sent_at' => now(),
                            ]),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Verification email sent successfully')
                            ->body('The user will receive an email to verify their email address.')
                            ->duration(5000)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Failed to send email')
                            ->body('An error occurred while sending the verification email.')
                            ->duration(5000)
                            ->send();
                    }
                }),

            Actions\Action::make('verifyEmailManually')
                ->label('Manual Verification')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Manual Email Verification')
                ->modalDescription('The user\'s email will be marked as verified manually.')
                ->modalSubmitActionLabel('Yes, Verify')
                ->visible(fn ($record) => !$record->email_verified_at)
                ->action(function ($record) {
                    $record->update([
                        'email_verified_at' => now()
                    ]);

                    // Log activity
                    ActivityLog::create([
                        'subject_type' => get_class($record),
                        'subject_id' => $record->id,
                        'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
                        'causer_id' => Auth::id(),
                        'event' => 'email_verified_manually',
                        'description' => 'Email verified manually by admin',
                        'properties' => json_encode([
                            'email' => $record->email,
                            'verified_by' => Auth::user()?->name ?? 'System',
                            'verified_at' => now(),
                        ]),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Email verified successfully')
                        ->body('The user\'s email has been successfully verified manually.')
                        ->duration(5000)
                        ->send();
                }),
        ];
    }


}
