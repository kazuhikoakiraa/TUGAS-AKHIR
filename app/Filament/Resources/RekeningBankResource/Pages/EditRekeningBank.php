<?php

namespace App\Filament\Resources\RekeningBankResource\Pages;

use App\Filament\Resources\RekeningBankResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRekeningBank extends EditRecord
{
    protected static string $resource = RekeningBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Lihat Detail')
                ->icon('heroicon-o-eye'),

            Actions\DeleteAction::make()
                ->label('Hapus Rekening Bank')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Rekening Bank')
                ->modalDescription('Apakah Anda yakin ingin menghapus rekening bank ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, Hapus')
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
            ->title('Rekening bank berhasil diperbarui')
            ->body('Data rekening bank telah berhasil diperbarui.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
            $this->getSaveFormAction()
                ->label('Simpan Perubahan'),
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
