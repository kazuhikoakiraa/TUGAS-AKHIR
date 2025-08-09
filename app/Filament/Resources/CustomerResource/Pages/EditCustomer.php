<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Details')
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->label('Delete Customer')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete Customer')
                ->modalDescription('Are you sure you want to delete this customer? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successRedirectUrl(fn () => static::getResource()::getUrl('index')),
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
            ->title('Customer updated successfully')
            ->body('Customer data changes have been successfully saved.');
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
