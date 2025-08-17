<?php

namespace App\Filament\Resources\PenawaranResource\Pages;

use App\Filament\Resources\PenawaranResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditPenawaran extends EditRecord
{
    protected static string $resource = PenawaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\Action::make('send')
                ->label('Save & Send')
                ->icon('heroicon-o-paper-airplane')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'draft')
                ->action(function () {
                    $this->save();
                    $this->record->update(['status' => 'sent']);

                    Notification::make()
                        ->success()
                        ->title('Quotation sent')
                        ->body('The quotation has been saved and sent to customer.')
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Save & Send Quotation')
                ->modalDescription('Are you sure you want to save and send this quotation to customer?'),

            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'draft'),
        ];
    }

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
}