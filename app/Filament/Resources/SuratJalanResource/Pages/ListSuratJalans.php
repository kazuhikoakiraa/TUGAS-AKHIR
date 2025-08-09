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
                ->label('Create Delivery Note')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(SuratJalan::count())
                ->badgeColor('primary'),

            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('tanggal', today()))
                ->badge(SuratJalan::whereDate('tanggal', today())->count())
                ->badgeColor('success'),

            'this_week' => Tab::make('This Week')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('tanggal', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]))
                ->badge(SuratJalan::whereBetween('tanggal', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count())
                ->badgeColor('info'),

            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year))
                ->badge(SuratJalan::whereMonth('tanggal', now()->month)
                    ->whereYear('tanggal', now()->year)->count())
                ->badgeColor('warning'),

            'overdue' => Tab::make('Overdue')
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
        return 'No Delivery Notes Yet';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Start by creating your first delivery note using the "Create Delivery Note" button above.';
    }

    protected function getTableEmptyStateActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Delivery Note')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }
}
