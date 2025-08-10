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
            ->title('Transaction created successfully')
            ->body('Financial transaction has been successfully recorded.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If expense type and has id_po_supplier, remove invoice_id
        if ($data['jenis'] === 'pengeluaran') {
            unset($data['invoice_id']);
        }

        // If income type and has invoice_id, remove id_po_supplier
        if ($data['jenis'] === 'pemasukan') {
            unset($data['id_po_supplier']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Update account balance or other additional logic
        $record = $this->record;

        // Example: Log activity or trigger other events
        activity()
            ->causedBy(Auth::user())
            ->log("Transaction {$record->jenis} of Rp " . number_format($record->jumlah, 0, ',', '.') . " successfully recorded");
    }
}
