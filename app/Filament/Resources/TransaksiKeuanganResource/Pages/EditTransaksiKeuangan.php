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

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        $record = $this->record;

        // Log activity for changes
        activity()
            ->performedOn($record)
            ->causedBy(filament()->auth()->user())
            ->log("Transaction {$record->jenis} of Rp " . number_format($record->jumlah, 0, ',', '.') . " successfully updated");
    }
}
