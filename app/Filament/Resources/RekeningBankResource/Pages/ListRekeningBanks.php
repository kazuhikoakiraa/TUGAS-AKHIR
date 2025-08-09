<?php

namespace App\Filament\Resources\RekeningBankResource\Pages;

use App\Filament\Resources\RekeningBankResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRekeningBank extends ListRecords
{
    protected static string $resource = RekeningBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Rekening Bank')
                ->icon('heroicon-o-plus')
                ->modalWidth('2xl'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Rekening')
                ->badge(fn () => $this->getModel()::count()),

            'recent' => Tab::make('Terbaru')
                ->badge(fn () => $this->getModel()::where('created_at', '>=', now()->subDays(7))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(7))),

            'with_transactions' => Tab::make('Ada Transaksi')
                ->badge(fn () => $this->getModel()::has('transaksiKeuangan')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->has('transaksiKeuangan')),

            'without_transactions' => Tab::make('Belum Ada Transaksi')
                ->badge(fn () => $this->getModel()::doesntHave('transaksiKeuangan')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->doesntHave('transaksiKeuangan')),
        ];
    }
}
