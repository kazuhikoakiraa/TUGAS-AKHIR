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
                ->modalHeading('Delete Transaction')
                ->modalDescription('Are you sure you want to delete this transaction? This action cannot be undone.')
                ->modalSubmitActionLabel('Delete')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Transaction deleted successfully')
                        ->body('Financial transaction has been successfully removed from the system.')
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
            ->title('Transaction updated successfully')
            ->body('Financial transaction has been successfully updated.')
            ->duration(5000);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // For editing, we need to populate the invoice_reference field
        // if this transaction has an invoice reference
        if ($data['referensi_type'] === 'invoice' && $data['referensi_id']) {
            $data['invoice_reference'] = $data['referensi_id'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clean up data based on transaction type
        if ($data['jenis'] === 'pengeluaran') {
            // For expense, remove invoice reference if exists
            unset($data['invoice_reference']);

            // If has PO Supplier, set referensi accordingly
            if (!empty($data['id_po_supplier'])) {
                $data['referensi_type'] = 'po_supplier';
                $data['referensi_id'] = $data['id_po_supplier'];
            } else {
                $data['referensi_type'] = 'manual';
                $data['referensi_id'] = null;
            }
        }

        if ($data['jenis'] === 'pemasukan') {
            // For income, remove PO supplier reference
            unset($data['id_po_supplier']);

            // Handle invoice reference from custom field
            if (!empty($data['invoice_reference'])) {
                $data['referensi_type'] = 'invoice';
                $data['referensi_id'] = $data['invoice_reference'];
            } else {
                $data['referensi_type'] = 'manual';
                $data['referensi_id'] = null;
            }
            unset($data['invoice_reference']); // Remove the temporary field
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        activity()
            ->performedOn($record)
            ->causedBy(filament()->auth()->user())
            ->withProperties([
                'jenis' => $record->jenis,
                'jumlah' => $record->jumlah,
                'referensi_type' => $record->referensi_type,
                'referensi_id' => $record->referensi_id,
                'changes' => $record->getChanges(),
            ])
            ->log("Transaction {$record->jenis} of Rp " . number_format($record->jumlah, 0, ',', '.') . " successfully updated");
    }
}
