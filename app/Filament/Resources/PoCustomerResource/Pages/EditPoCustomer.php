<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use App\Models\PoCustomerDetail;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class EditPoCustomer extends EditRecord
{
    protected static string $resource = PoCustomerResource::class;

    protected static ?string $title = 'Edit Customer Purchase Order';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->canBeDeleted()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Customer PO updated successfully';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing details untuk form dengan struktur yang benar
        $record = $this->record;
        if ($record->details->count() > 0) {
        $details = $record->details->map(function ($detail) {
            return [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'nama_produk' => $detail->nama_produk,
                'deskripsi' => $detail->deskripsi,
                'jumlah' => $detail->jumlah, // Pastikan ini ter-load dengan benar
                'satuan' => $detail->satuan,
                'harga_satuan' => (float) $detail['harga_satuan'],
                'total' => (float) $detail['total'],
                'keterangan' => $detail['keterangan'] ?? null,
            ];
        })->toArray();
        $data['details'] = $details;

        // Send detailed success notification
        Notification::make()
            ->title('PO Updated Successfully!')
            ->body("PO #{$record->nomor_po} has been updated with " . count($details) . " items.")
            ->success()
            ->duration(5000)
            ->send();
        }

        return $data;
    }

    /**
     * Handle validation errors with better user feedback
     */
    protected function getFormActions(): array
    {
        return array_merge(parent::getFormActions(), [
            // Add custom validation action if needed
        ]);
    }

    /**
     * Custom validation before save
     */
    protected function beforeSave(): void
    {
        $data = $this->data;

        // Validate that we have at least one detail
        if (empty($data['details']) || count($data['details']) === 0) {
            Notification::make()
                ->title('Validation Error')
                ->body('Please add at least one item to the PO.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Validate PO type consistency
        if (!empty($data['details'])) {
            foreach ($data['details'] as $detail) {
                if ($data['jenis_po'] === 'Product' && empty($detail['product_id'])) {
                    Notification::make()
                        ->title('Validation Error')
                        ->body('Product PO must have valid products selected.')
                        ->danger()
                        ->send();

                    $this->halt();
                }
            }
        }
    }
}