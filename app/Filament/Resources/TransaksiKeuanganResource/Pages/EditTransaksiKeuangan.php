<?php

namespace App\Filament\Resources\TransaksiKeuanganResource\Pages;

use App\Filament\Resources\TransaksiKeuanganResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTransaksiKeuangan extends EditRecord
{
    protected static string $resource = TransaksiKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Hapus Transaksi')
                ->modalDescription('Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Hapus')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Transaksi berhasil dihapus')
                        ->body('Transaksi keuangan telah berhasil dihapus dari sistem.')
                ),
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
            ->title('Transaksi berhasil diperbarui')
            ->body('Transaksi keuangan telah berhasil diperbarui.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Jika jenis pengeluaran dan ada id_po_supplier, hapus invoice_id
        if ($data['jenis'] === 'pengeluaran') {
            unset($data['invoice_id']);
        }

        // Jika jenis pemasukan dan ada invoice_id, hapus id_po_supplier
        if ($data['jenis'] === 'pemasukan') {
            unset($data['id_po_supplier']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Log activity untuk perubahan
        activity()
            ->performedOn($record)
            ->causedBy(filament()->auth()->user())
            ->log("Transaksi {$record->jenis} sebesar Rp " . number_format($record->jumlah, 0, ',', '.') . " berhasil diperbarui");
    }
}