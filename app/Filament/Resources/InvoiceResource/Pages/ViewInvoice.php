<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('print_pdf')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    return InvoiceResource::printInvoicePdf($this->record);
                })
                ->visible(fn () => $this->record->status !== 'draft'),

            Actions\Action::make('mark_as_sent')
                ->label('Mark as Sent')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->action(function () {
                    $this->record->update(['status' => 'sent']);

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Status Updated')
                        ->body('Invoice has been marked as sent.')
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'draft')
                ->requiresConfirmation(),

            Actions\Action::make('mark_as_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->update(['status' => 'paid']);

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Status Updated')
                        ->body('Invoice has been marked as paid.')
                        ->send();
                })
                ->visible(fn () => in_array($this->record->status, ['sent', 'overdue']))
                ->requiresConfirmation(),

            Actions\DeleteAction::make(),
        ];
    }
}
