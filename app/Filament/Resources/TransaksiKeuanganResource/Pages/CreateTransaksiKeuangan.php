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
        // Clean up data based on transaction type
        if ($data['jenis'] === 'pengeluaran') {
            // For expense, remove invoice reference if exists
            unset($data['invoice_reference']);

            // If has PO Supplier, set referensi accordingly
            if (!empty($data['id_po_supplier'])) {
                $data['referensi_type'] = 'po_supplier';
                $data['referensi_id'] = $data['id_po_supplier'];
            }
        }

        if ($data['jenis'] === 'pemasukan') {
            // For income, remove PO supplier reference
            unset($data['id_po_supplier']);

            // Handle invoice reference from custom field
            if (!empty($data['invoice_reference'])) {
                $data['referensi_type'] = 'invoice';
                $data['referensi_id'] = $data['invoice_reference'];
            }
            unset($data['invoice_reference']); // Remove the temporary field
        }

        // Set manual type if no reference
        if (empty($data['referensi_type'])) {
            $data['referensi_type'] = 'manual';
            $data['referensi_id'] = null;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Log activity
        activity()
            ->causedBy(Auth::user())
            ->performedOn($record)
            ->withProperties([
                'jenis' => $record->jenis,
                'jumlah' => $record->jumlah,
                'referensi_type' => $record->referensi_type,
                'referensi_id' => $record->referensi_id,
            ])
            ->log("Manual transaction {$record->jenis} of Rp " . number_format($record->jumlah, 0, ',', '.') . " successfully recorded");

        // Additional notification based on reference type
        if ($record->referensi_type === 'invoice') {
            Notification::make()
                ->info()
                ->title('Invoice Transaction Recorded')
                ->body("Income transaction linked to invoice has been recorded.")
                ->duration(3000)
                ->send();
        } elseif ($record->referensi_type === 'po_supplier') {
            Notification::make()
                ->info()
                ->title('PO Supplier Transaction Recorded')
                ->body("Expense transaction linked to PO Supplier has been recorded.")
                ->duration(3000)
                ->send();
        }
    }
}
