<?php

namespace App\Filament\Resources\RekeningBankResource\Pages;

use App\Filament\Resources\RekeningBankResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateRekeningBank extends CreateRecord
{
    protected static string $resource = RekeningBankResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Rekening bank berhasil ditambahkan')
            ->body('Data rekening bank baru telah berhasil disimpan ke sistem.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Bersihkan dan format data sebelum disimpan
        $data['nama_bank'] = trim($data['nama_bank']);
        $data['nomor_rekening'] = preg_replace('/\D/', '', $data['nomor_rekening']); // Remove non-numeric characters
        $data['nama_pemilik'] = trim($data['nama_pemilik']);

        if (isset($data['keterangan'])) {
            $data['keterangan'] = trim($data['keterangan']);
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Simpan Rekening Bank'),
            $this->getCreateAnotherFormAction()
                ->label('Simpan & Tambah Lagi'),
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
