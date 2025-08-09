<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // Disable default notification, we create custom in afterCreate
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = trim($data['name']);
        $data['email'] = strtolower(trim($data['email']));
        unset($data['password_confirmation']);
        return $data;
    }

    protected function afterCreate(): void
    {
        // Send email verification automatically after user is created
        $emailSent = $this->record->sendEmailVerificationNotification();

        if ($emailSent) {
            Notification::make()
                ->success()
                ->title('User successfully added')
                ->body('New user data has been saved and verification email has been sent to ' . $this->record->email)
                ->duration(5000)
                ->send();
        } else {
            Notification::make()
                ->warning()
                ->title('User successfully added')
                ->body('User was created successfully, but verification email failed to send. You can resend it from the user detail page.')
                ->duration(7000)
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Save User'),
            $this->getCreateAnotherFormAction()->label('Save & Add Another'),
            $this->getCancelFormAction()->label('Cancel'),
        ];
    }
}
