<?php

namespace App\Filament\Resources\TransaksiKeuanganResource\Pages;

use App\Filament\Resources\TransaksiKeuanganResource;
use App\Models\TransaksiKeuangan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ListTransaksiKeuangan extends ListRecords
{
    protected static string $resource = TransaksiKeuanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Transaksi')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua Transaksi')
                ->icon('heroicon-o-list-bullet')
                ->badge(TransaksiKeuangan::count()),

            'pemasukan' => Tab::make('Pemasukan')
                ->icon('heroicon-o-arrow-trending-up')
                ->iconPosition(IconPosition::Before)
                ->modifyQueryUsing(fn (Builder $query) => $query->pemasukan())
                ->badge(TransaksiKeuangan::pemasukan()->count())
                ->badgeColor('success'),

            'pengeluaran' => Tab::make('Pengeluaran')
                ->icon('heroicon-o-arrow-trending-down')
                ->iconPosition(IconPosition::Before)
                ->modifyQueryUsing(fn (Builder $query) => $query->pengeluaran())
                ->badge(TransaksiKeuangan::pengeluaran()->count())
                ->badgeColor('danger'),

            'minggu_ini' => Tab::make('Minggu Ini')
                ->icon('heroicon-o-calendar-days')
                ->modifyQueryUsing(fn (Builder $query) => $query->mingguIni())
                ->badge(TransaksiKeuangan::mingguIni()->count()),

            'bulan_ini' => Tab::make('Bulan Ini')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->bulanIni())
                ->badge(TransaksiKeuangan::bulanIni()->count()),

            'tahun_ini' => Tab::make('Tahun Ini')
                ->icon('heroicon-o-calendar')
                ->modifyQueryUsing(fn (Builder $query) => $query->tahunIni())
                ->badge(TransaksiKeuangan::tahunIni()->count()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransaksiKeuanganResource\Widgets\TransaksiOverview::class,
        ];
    }
}
