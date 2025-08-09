<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPoCustomer extends EditRecord
{
    protected static string $resource = PoCustomerResource::class;

    protected static ?string $title = 'Edit Purchase Order Customer';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'PO Customer berhasil diupdate';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hitung ulang total dari detail items
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
