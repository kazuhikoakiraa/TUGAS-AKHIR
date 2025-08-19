<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePoCustomer extends CreateRecord
{
    protected static string $resource = PoCustomerResource::class;

    protected static ?string $title = 'Create Customer Purchase Order';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Customer PO created successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set user ID
        $data['id_user'] = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;

        // Process details berdasarkan jenis PO
        $totalSebelumPajak = 0;
        if (isset($data['details']) && !empty($data['details'])) {
            foreach ($data['details'] as &$detail) {
                if ($data['jenis_po'] === 'Product') {
                    // Product PO: pastikan ada product_id dan quantity
                    if (empty($detail['jumlah']) || $detail['jumlah'] < 1) {
                        $detail['jumlah'] = 1;
                    }

                    // Ensure types are correct
                    $detail['jumlah'] = (int) $detail['jumlah'];
                    $detail['harga_satuan'] = (float) $detail['harga_satuan'];

                    // Pastikan total sudah benar
                    $detail['total'] = $detail['jumlah'] * $detail['harga_satuan'];

                } else {
                    // Service PO: set defaults untuk service
                    $detail['product_id'] = null;
                    $detail['satuan'] = 'service';
                    $detail['jumlah'] = 1; // Service selalu qty 1
                    $detail['harga_satuan'] = (float) $detail['harga_satuan'];
                    // Untuk service, total = harga_satuan (qty selalu 1)
                    $detail['total'] = $detail['harga_satuan'];
                }

                $totalSebelumPajak += $detail['total'];
            }
        }

        // Calculate tax with validation
        $taxRate = ($data['tax_rate'] ?? 11) / 100;
        $data['total_sebelum_pajak'] = $totalSebelumPajak;
        $data['total_pajak'] = $totalSebelumPajak * $taxRate;

        // Handle attachment name
        if (!empty($data['attachment_path'])) {
            $data['attachment_name'] = basename($data['attachment_path']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Update totals after creation to ensure consistency
        $this->record->updateTotalsWithoutEvents();

        // Send success notification with details
        Notification::make()
            ->title('PO Created Successfully!')
            ->body("PO #{$this->record->nomor_po} for {$this->record->customer->nama} has been created.")
            ->success()
            ->duration(5000)
            ->send();
    }

    /**
     * Custom validation before create
     */
    protected function beforeCreate(): void
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

                // Validate quantity untuk Product
                if ($data['jenis_po'] === 'Product' && (empty($detail['jumlah']) || $detail['jumlah'] < 1)) {
                    Notification::make()
                        ->title('Validation Error')
                        ->body('Product quantity must be at least 1.')
                        ->danger()
                        ->send();

                    $this->halt();
                }
            }
        }
    }
}
