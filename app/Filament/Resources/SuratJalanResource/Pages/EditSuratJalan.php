<?php

namespace App\Filament\Resources\SuratJalanResource\Pages;

use App\Filament\Resources\SuratJalanResource;
use App\Models\SuratJalan;
use App\Models\PoCustomer;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSuratJalan extends EditRecord
{
    protected static string $resource = SuratJalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->color('info'),

            Actions\Action::make('print_pdf')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn (): string => route('surat-jalan.pdf', $this->record))
                ->openUrlInNewTab()
                ->tooltip('Print Delivery Note in PDF format'),

            Actions\DeleteAction::make()
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Delivery Note')
                ->modalDescription('Are you sure you want to delete this delivery note? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Delivery Note Deleted')
                        ->body('Delivery note has been successfully deleted.')
                ),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): \Filament\Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Save Changes')
            ->icon('heroicon-o-check');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            // Additional validation for edit
            $this->validatePoCustomerForEdit($data['id_po_customer'] ?? null, $record->id);

            $record->update($data);

            // Success notification
            Notification::make()
                ->title('Delivery Note Updated Successfully')
                ->body("Delivery note {$record->nomor_surat_jalan} has been updated successfully.")
                ->success()
                ->send();

            return $record;

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Updating Delivery Note')
                ->body('An error occurred while updating the delivery note: ' . $e->getMessage())
                ->danger()
                ->send();

            throw new ValidationException(validator([], []), ['error' => 'Failed to update delivery note: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate Customer PO for edit
     */
    private function validatePoCustomerForEdit(?int $poCustomerId, int $currentRecordId): void
    {
        if (!$poCustomerId) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Customer PO must be selected.']);
        }

        // Check if Customer PO exists and is valid
        $po = PoCustomer::with('customer')->find($poCustomerId);

        if (!$po) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Customer PO not found.']);
        }

        // Check if PO already has another delivery note (excluding the one being edited)
        $existingSuratJalan = SuratJalan::where('id_po_customer', $poCustomerId)
                                       ->where('id', '!=', $currentRecordId)
                                       ->first();

        if ($existingSuratJalan) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'This Customer PO already has another delivery note with number: ' . $existingSuratJalan->nomor_surat_jalan]);
        }

        // Check PO status
        if ($po->status_po !== \App\Enums\PoStatus::APPROVED) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Customer PO must have APPROVED status.']);
        }

        if ($po->jenis_po !== 'Produk') {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Only POs with type "Produk" can have delivery notes created.']);
        }
    }

    // If you need date validation, move it to handleRecordUpdate or another appropriate method.

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }
}
