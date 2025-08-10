<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class CustomerStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::whereHas('poCustomers')->count();
        $newCustomersThisMonth = Customer::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $newCustomersLastMonth = Customer::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        // Calculate percentage change for new customers
        $newCustomersChange = 0;
        if ($newCustomersLastMonth > 0) {
            $newCustomersChange = (($newCustomersThisMonth - $newCustomersLastMonth) / $newCustomersLastMonth) * 100;
        } elseif ($newCustomersThisMonth > 0) {
            $newCustomersChange = 100;
        }

        // Calculate active customers percentage
        $activePercentage = $totalCustomers > 0 ? ($activeCustomers / $totalCustomers) * 100 : 0;

        return [
            Stat::make('Total Customers', $totalCustomers)
                ->description('All registered customers')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary')
                ->chart($this->getCustomerGrowthChart()),

            Stat::make('Active Customers', $activeCustomers)
                ->description(number_format($activePercentage, 1) . '% of total customers')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('New Customers This Month', $newCustomersThisMonth)
                ->description($newCustomersChange >= 0 ?
                    '+' . number_format(abs($newCustomersChange), 1) . '% from last month' :
                    '-' . number_format(abs($newCustomersChange), 1) . '% from last month'
                )
                ->descriptionIcon($newCustomersChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($newCustomersChange >= 0 ? 'success' : 'danger'),

            Stat::make('Prospect Customers', Customer::whereHas('penawaran')->whereDoesntHave('poCustomers')->count())
                ->description('Customers with quotes but no PO')
                ->descriptionIcon('heroicon-m-eye')
                ->color('warning'),
        ];
    }

    private function getCustomerGrowthChart(): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = Customer::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            $data[] = $count;
        }

        return $data;
    }
}
