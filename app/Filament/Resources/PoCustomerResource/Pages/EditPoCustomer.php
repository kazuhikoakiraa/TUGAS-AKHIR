<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditPoCustomer extends EditRecord
{
    protected static string $resource = PoCustomerResource::class;

    protected static ?string $title = 'Edit Customer Purchase Order';

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
        return 'Customer PO updated successfully';
    }

   protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing details untuk form
        $record = $this->record;
        if ($record->details->count() > 0) {
            $data['details'] = $record->details->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'deskripsi' => $detail->deskripsi,
                    'jumlah' => $detail->jumlah,
                    'harga_satuan' => $detail->harga_satuan,
                    'total' => $detail->jumlah * $detail->harga_satuan,
                ];
            })->toArray();
        }

        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Recalculate total from detail items
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

    protected function afterSave(): void
    {
        // Setelah save, pastikan details juga tersimpan dengan benar
        $record = $this->record;
        $details = $this->data['details'] ?? [];

        // Delete existing details first
        $record->details()->delete();

        // Create new details
        foreach ($details as $detail) {
            $record->details()->create([
                'deskripsi' => $detail['deskripsi'],
                'jumlah' => $detail['jumlah'],
                'harga_satuan' => $detail['harga_satuan'],
                'total' => $detail['jumlah'] * $detail['harga_satuan'],
            ]);
        }

        // Recalculate totals
        $totalSebelumPajak = $record->details()->sum(DB::raw('jumlah * harga_satuan'));
        $totalPajak = $totalSebelumPajak * 0.11;

        $record->update([
            'total_sebelum_pajak' => $totalSebelumPajak,
            'total_pajak' => $totalPajak,
        ]);
    }
}
