<?php

namespace App\Filament\Resources\TransaksiKeuanganResource\Pages;

use App\Filament\Resources\TransaksiKeuanganResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Spatie\Activitylog\Facades\LogBatch;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\ActivityLogger;
use function activity;
use Illuminate\Support\Facades\Auth;

class CreateTransaksiKeuangan extends CreateRecord
{
    protected static string $resource = TransaksiKeuanganResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Transaksi berhasil dibuat')
            ->body('Transaksi keuangan telah berhasil dicatat.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        // Update saldo rekening atau logic tambahan lainnya
        $record = $this->record;

        // Contoh: Log activity atau trigger event lainnya
        activity()
            ->causedBy(Auth::user())
            ->log("Transaksi {$record->jenis} sebesar Rp " . number_format($record->jumlah, 0, ',', '.') . " berhasil dicatat");
    }
}
