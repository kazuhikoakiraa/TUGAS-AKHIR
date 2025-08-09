<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->color('warning'),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Customer')
                ->modalDescription('Are you sure you want to delete this customer? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, Delete'),
        ];
    }
}
