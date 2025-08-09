<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Customer')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Customers')
                ->badge(fn () => $this->getModel()::count()),

            'recent' => Tab::make('Recent')
                ->badge(fn () => $this->getModel()::where('created_at', '>=', now()->subDays())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays())),

            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::whereHas('poCustomers')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('poCustomers')),
        ];
    }
}
