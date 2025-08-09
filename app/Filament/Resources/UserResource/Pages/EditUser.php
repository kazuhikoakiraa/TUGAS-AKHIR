<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Password;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Details')
                ->icon('heroicon-o-eye'),

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
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('User updated successfully')
            ->body('User data has been successfully updated.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clean and format data before saving
        $data['name'] = trim($data['name']);
        $data['email'] = strtolower(trim($data['email']));

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Save Changes'),
            $this->getCancelFormAction()
                ->label('Cancel'),
        ];
    }
}
