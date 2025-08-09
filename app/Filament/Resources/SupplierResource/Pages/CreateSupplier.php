<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateSupplier extends CreateRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Supplier berhasil ditambahkan')
            ->body('Data supplier baru telah berhasil disimpan ke sistem.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Bersihkan dan format data sebelum disimpan
        $data['nama'] = trim($data['nama']);
        $data['alamat'] = trim($data['alamat']);
        $data['telepon'] = preg_replace('/[^0-9\-\+\(\)\s]/', '', $data['telepon']);
        $data['email'] = strtolower(trim($data['email']));

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Supplier'),
            $this->getCreateAnotherFormAction()
                ->label('Simpan & Tambah Lagi'),
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
