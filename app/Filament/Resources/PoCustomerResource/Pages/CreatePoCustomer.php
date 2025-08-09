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
        // Set the user who created the PO
        $data['id_user'] = \Illuminate\Support\Facades\Auth::check() ? \Illuminate\Support\Facades\Auth::id() : null;

        // Calculate total from detail items
        $totalSebelumPajak = 0;
        if (isset($data['details'])) {
            foreach ($data['details'] as &$detail) {
                $detail['total'] = $detail['jumlah'] * $detail['harga_satuan'];
                $totalSebelumPajak += $detail['total'];
            }
        }

        // Set subtotal and tax
        $data['total_sebelum_pajak'] = $totalSebelumPajak;
        $data['total_pajak'] = $totalSebelumPajak * 0.11; // 11% tax

        return $data;
    }
}
