<?php

namespace App\Filament\Resources\PenawaranResource\Pages;

use App\Filament\Resources\PenawaranResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewPenawaran extends ViewRecord
{
    protected static string $resource = PenawaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $newRecord = $this->record->replicate();
                    $newRecord->nomor_penawaran = null; // Will be auto-generated
                    $newRecord->status = 'draft';
                    $newRecord->save();

                    Notification::make()
                        ->success()
                        ->title('Quotation duplicated')
                        ->body('The quotation has been duplicated successfully.')
                        ->send();

                    return redirect(static::getResource()::getUrl('edit', ['record' => $newRecord]));
                })
                ->requiresConfirmation()
                ->modalHeading('Duplicate Quotation')
                ->modalDescription('Are you sure you want to duplicate this quotation?'),

            Actions\Action::make('send')
                ->label('Send to Customer')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function () {
                    $this->record->update(['status' => 'sent']);

                    Notification::make()
                        ->success()
                        ->title('Quotation sent')
                        ->body('The quotation has been sent to customer.')
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Send Quotation')
                ->modalDescription('Are you sure you want to send this quotation to customer?'),

            Actions\Action::make('mark_accepted')
                ->label('Mark as Accepted')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === 'sent')
                ->action(function () {
                    $this->record->update(['status' => 'accepted']);

                    Notification::make()
                        ->success()
                        ->title('Quotation accepted')
                        ->body('The quotation has been marked as accepted.')
                        ->send();
                })
                ->requiresConfirmation(),

            Actions\Action::make('mark_rejected')
                ->label('Mark as Rejected')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'sent')
                ->action(function () {
                    $this->record->update(['status' => 'rejected']);

                    Notification::make()
                        ->warning()
                        ->title('Quotation rejected')
                        ->body('The quotation has been marked as rejected.')
                        ->send();
                })
                ->requiresConfirmation(),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }
}
