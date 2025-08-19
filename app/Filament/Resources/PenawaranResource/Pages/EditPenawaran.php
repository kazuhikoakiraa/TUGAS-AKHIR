<?php

namespace App\Filament\Resources\PenawaranResource\Pages;

use App\Filament\Resources\PenawaranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPenawaran extends EditRecord
{
    protected static string $resource = PenawaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Quotation updated')
            ->body('The quotation has been updated successfully.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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

        return $data;
    }

    protected function afterSave(): void
    {
        // Recalculate totals after all details are saved to ensure accuracy
        $record = $this->record;
        $record->load('details');

        $subtotal = $record->details->sum('total');
        $taxRate = (float) $record->tax_rate / 100;
        $pajak = $subtotal * $taxRate;
        $grandTotal = $subtotal + $pajak;

        // Only update if values are different to avoid infinite loops
        if ($record->harga != $grandTotal || $record->total_sebelum_pajak != $subtotal || $record->total_pajak != $pajak) {
            $record->updateQuietly([
                'total_sebelum_pajak' => $subtotal,
                'total_pajak' => $pajak,
                'harga' => $grandTotal,
            ]);
        }
    }
}
