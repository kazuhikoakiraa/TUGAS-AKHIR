<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSuppliers extends ListRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Supplier')
                ->icon('heroicon-o-plus')
                ->modalWidth('2xl'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Supplier')
                ->badge(fn () => $this->getModel()::count()),

            'recent' => Tab::make('Terbaru')
                ->badge(fn () => $this->getModel()::where('created_at', '>=', now()->subDays())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays())),

            'active' => Tab::make('Aktif')
                ->badge(fn () => $this->getModel()::whereHas('poSuppliers')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('poSuppliers')),
        ];
    }
}
