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
        $data['id_user'] = \Illuminate\Support\Facades\Auth::user()->id;

        return $data;
    }
}
