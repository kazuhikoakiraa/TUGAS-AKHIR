<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class SupplierStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::whereHas('poSuppliers')->count();
        $newSuppliersThisMonth = Supplier::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newSuppliersLastMonth = Supplier::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        // Calculate percentage change for new Suppliers
        $newSuppliersChange = 0;
        if ($newSuppliersLastMonth > 0) {
            $newSuppliersChange = (($newSuppliersThisMonth - $newSuppliersLastMonth) / $newSuppliersLastMonth) * 100;
        } elseif ($newSuppliersThisMonth > 0) {
            $newSuppliersChange = 100;
        }

        // Calculate active Suppliers percentage
        $activePercentage = $totalSuppliers > 0 ? ($activeSuppliers / $totalSuppliers) * 100 : 0;

        return [
            Stat::make('Total Suppliers', $totalSuppliers)
                ->description('All registered suppliers')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart($this->getSupplierGrowthChart()),

            Stat::make('Active Suppliers', $activeSuppliers)
                ->description(number_format($activePercentage, 1) . '% of total suppliers')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('New Suppliers This Month', $newSuppliersThisMonth)
                ->description($newSuppliersChange >= 0 ?
                    '+' . number_format(abs($newSuppliersChange), 1) . '% from last month' :
                    '-' . number_format(abs($newSuppliersChange), 1) . '% from last month'
                )
                ->descriptionIcon($newSuppliersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($newSuppliersChange >= 0 ? 'success' : 'danger')
        ];
    }

    private function getSupplierGrowthChart(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Supplier::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $data[] = $count;
        }

        return $data;
    }
}
