<?php

namespace App\Filament\Resources\PoCustomerResource\Pages;

use App\Filament\Resources\PoCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPoCustomers extends ListRecords
{
    protected static string $resource = PoCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
