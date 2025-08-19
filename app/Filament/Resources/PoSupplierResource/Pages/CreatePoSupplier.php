<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePoSupplier extends CreateRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected static ?string $title = 'Create Supplier Purchase Order';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Supplier PO created successfully';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['id_user'] = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;

        $totalSebelumPajak = 0;
        if (isset($data['details'])) {
            foreach ($data['details'] as &$detail) {
                $detail['total'] = $detail['jumlah'] * $detail['harga_satuan'];
                $totalSebelumPajak += $detail['total'];
            }
        }

        $data['total_sebelum_pajak'] = $totalSebelumPajak;

        // Set default tax rate jika tidak ada
        if (empty($data['tax_rate'])) {
            $data['tax_rate'] = 11.00;
        }

        // Hitung pajak berdasarkan tax rate
        $data['total_pajak'] = $totalSebelumPajak * ($data['tax_rate'] / 100);

        return $data;
    }
}
