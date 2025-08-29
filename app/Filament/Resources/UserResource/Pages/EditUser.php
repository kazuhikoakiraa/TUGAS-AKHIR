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
            // Quick view action
            Actions\ViewAction::make()
                ->label('View Details')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->outlined(),

            // Quick actions group untuk mengurangi clutter
            Actions\ActionGroup::make([
                Actions\Action::make('sendPasswordResetEmail')
                    ->label('Send Password Reset')
                    ->icon('heroicon-o-key')
                    ->color('warning')
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
            ])
            ->label('Quick Actions')
            ->icon('heroicon-o-bolt')
            ->color('gray')
            ->button()
            ->outlined(),

            // Delete action terpisah
            Actions\DeleteAction::make()
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete User')
                ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete User')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index'))
                ->outlined(),
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
