<?php

namespace App\Filament\Resources\SuratJalanResource\Pages;

use App\Filament\Resources\SuratJalanResource;
use App\Models\SuratJalan;
use App\Models\PoCustomer;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSuratJalan extends CreateRecord
{
    protected static string $resource = SuratJalanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Delivery Note')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel');
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Additional validation before creating record
            $this->validatePoCustomer($data['id_po_customer'] ?? null);

            // Set user ID if not present
            if (empty($data['id_user'])) {
                $data['id_user'] = \Illuminate\Support\Facades\Auth::id();
            }

            $record = static::getModel()::create($data);

            // Success notification
            Notification::make()
                ->title('Delivery Note Created Successfully')
                ->body("Delivery note with number {$record->nomor_surat_jalan} has been created successfully.")
                ->success()
                ->send();

            return $record;

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Creating Delivery Note')
                ->body('An error occurred while creating the delivery note: ' . $e->getMessage())
                ->danger()
                ->send();

            throw new ValidationException(validator([], []), ['error' => 'Failed to create delivery note: ' . $e->getMessage()]);
        }
    }

    /**
     * Validate Customer PO
     */
    private function validatePoCustomer(?int $poCustomerId): void
    {
        if (!$poCustomerId) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Customer PO must be selected.']);
        }

        // Check if Customer PO exists and is valid
        $po = PoCustomer::with('customer')->find($poCustomerId);

        if (!$po) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Customer PO not found.']);
        }

        // Check if PO already has a delivery note
        $existingSuratJalan = SuratJalan::where('id_po_customer', $poCustomerId)->first();
        if ($existingSuratJalan) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'This Customer PO already has a delivery note with number: ' . $existingSuratJalan->nomor_surat_jalan]);
        }

        // Check PO status
        if ($po->status_po !== \App\Enums\PoStatus::APPROVED) {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Customer PO must have APPROVED status to create a delivery note.']);
        }

        // Check PO type
        if ($po->jenis_po !== 'Product') {
            throw new ValidationException(validator([], []), ['id_po_customer' => 'Only POs with type "Produk" can have delivery notes created.']);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure user_id is set
        $data['id_user'] = \Illuminate\Support\Facades\Auth::id();

        // Validate date
        if (isset($data['tanggal'])) {
            $tanggal = \Carbon\Carbon::parse($data['tanggal']);
            if ($tanggal->isPast() && $tanggal->diffInDays(now()) > 7) {
                throw new ValidationException(validator([], []), ['tanggal' => 'Delivery date cannot be more than 7 days in the past.']);
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Back to List')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
