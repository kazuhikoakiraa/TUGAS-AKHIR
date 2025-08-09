<?php

namespace App\Filament\Resources\SuratJalanResource\Pages;

use App\Filament\Resources\SuratJalanResource;
use App\Models\SuratJalan;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSuratJalans extends ListRecords
{
    protected static string $resource = SuratJalanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Surat Jalan')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->badge(SuratJalan::count())
                ->badgeColor('primary'),

            'hari_ini' => Tab::make('Hari Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal', today()))
                ->badge(SuratJalan::whereDate('tanggal', today())->count())
                ->badgeColor('success'),

            'minggu_ini' => Tab::make('Minggu Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('tanggal', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]))
                ->badge(SuratJalan::whereBetween('tanggal', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count())
                ->badgeColor('info'),

            'bulan_ini' => Tab::make('Bulan Ini')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year))
                ->badge(SuratJalan::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)->count())
                ->badgeColor('warning'),

            'lewat_jatuh_tempo' => Tab::make('Lewat Tanggal')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal', '<', today()))
                ->badge(SuratJalan::whereDate('tanggal', '<', today())->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['poCustomer.customer', 'user']);
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-truck';
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Belum ada Surat Jalan';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Mulai buat surat jalan pertama Anda dengan mengklik tombol "Buat Surat Jalan" di atas.';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Surat Jalan')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
