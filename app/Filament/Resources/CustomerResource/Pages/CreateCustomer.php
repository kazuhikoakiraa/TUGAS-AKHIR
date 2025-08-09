<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Customer berhasil ditambahkan')
            ->body('Data customer telah berhasil disimpan ke sistem.');
    }
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Customer'),
            $this->getCreateAnotherFormAction()
                ->label('Simpan & Tambah Lagi'),
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}