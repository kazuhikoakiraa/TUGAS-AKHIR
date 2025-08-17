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
}
