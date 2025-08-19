<?php

namespace App\Filament\Resources\PenawaranResource\Pages;

use App\Filament\Resources\PenawaranResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePenawaran extends CreateRecord
{
    protected static string $resource = PenawaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Quotation created')
            ->body('The quotation has been created successfully.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set user ID
        $data['id_user'] = \Illuminate\Support\Facades\Auth::user()->id;

        // Calculate totals from details
        $subtotal = 0;
        if (isset($data['details']) && is_array($data['details'])) {
            foreach ($data['details'] as $detail) {
                // Ensure each detail has a proper total
                $qty = (float) ($detail['jumlah'] ?? 0);
                $price = (float) ($detail['harga_satuan'] ?? 0);
                $detailTotal = $qty * $price;
                $subtotal += $detailTotal;
            }
        }

        // Calculate tax and grand total
        $taxRate = (float) ($data['tax_rate'] ?? 11) / 100;
        $pajak = $subtotal * $taxRate;
        $grandTotal = $subtotal + $pajak;

        // Set calculated values
        $data['total_sebelum_pajak'] = $subtotal;
        $data['total_pajak'] = $pajak;
        $data['harga'] = $grandTotal;
        $data['tax_rate'] = $data['tax_rate'] ?? 11.00;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Recalculate totals after all details are saved
        $record = $this->record;
        $record->load('details');

        $subtotal = $record->details->sum('total');
        $taxRate = (float) $record->tax_rate / 100;
        $pajak = $subtotal * $taxRate;
        $grandTotal = $subtotal + $pajak;

        $record->update([
            'total_sebelum_pajak' => $subtotal,
            'total_pajak' => $pajak,
            'harga' => $grandTotal,
        ]);
    }
}
