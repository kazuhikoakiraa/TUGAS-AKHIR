<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('View Details')
                ->icon('heroicon-o-eye'),

            Actions\DeleteAction::make()
                ->label('Delete Supplier')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete Supplier')
                ->modalDescription('Are you sure you want to delete this supplier? This action cannot be undone.')
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
            ->title('Supplier successfully updated')
            ->body('Supplier data has been successfully updated.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clean and format data before saving
        $data['nama'] = trim($data['nama']);
        $data['alamat'] = trim($data['alamat']);
        $data['telepon'] = preg_replace('/[^0-9\-\+\(\)\s]/', '', $data['telepon']);
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
