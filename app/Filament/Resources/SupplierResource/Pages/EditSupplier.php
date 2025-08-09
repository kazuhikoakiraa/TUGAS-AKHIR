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
                ->label('Lihat Detail')
                ->icon('heroicon-o-eye'),

            Actions\DeleteAction::make()
                ->label('Hapus Supplier')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Hapus Supplier')
                ->modalDescription('Apakah Anda yakin ingin menghapus supplier ini? Tindakan ini tidak dapat dibatalkan.')
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
            ->title('Supplier berhasil diperbarui')
            ->body('Data supplier telah berhasil diperbarui.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
            $this->getSaveFormAction()
                ->label('Simpan Perubahan'),
            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
