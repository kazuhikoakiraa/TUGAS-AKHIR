<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePoCustomer extends CreateRecord
{
    protected static string $resource = PoCustomerResource::class;

    protected static ?string $title = 'Tambah Purchase Order Customer';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'PO Customer berhasil dibuat';
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
