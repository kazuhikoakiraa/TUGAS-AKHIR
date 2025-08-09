<?php

namespace App\Filament\Resources\PoSupplierResource\Pages;

use App\Filament\Resources\PoSupplierResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePoSupplier extends CreateRecord
{
    protected static string $resource = PoSupplierResource::class;

    protected static ?string $title = 'Tambah Purchase Order Supplier';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'PO Supplier berhasil dibuat';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set user yang membuat PO
        $data['id_user'] = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;

        // Hitung total dari detail items
        $totalSebelumPajak = 0;
        if (isset($data['details'])) {
            foreach ($data['details'] as &$detail) {
                $detail['total'] = $detail['jumlah'] * $detail['harga_satuan'];
                $totalSebelumPajak += $detail['total'];
            }
        }

        // Set total sebelum pajak dan pajak
        $data['total_sebelum_pajak'] = $totalSebelumPajak;
        $data['total_pajak'] = $totalSebelumPajak * 0.11; // 11% pajak

        return $data;
    }
}
