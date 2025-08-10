<?php

namespace App\Filament\Widgets;

use App\Models\PoCustomer;
use App\Enums\PoStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PoCustomerStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Calculate PO statistics
        $totalPo = PoCustomer::count();
        $pendingPo = PoCustomer::where('status_po', PoStatus::PENDING->value)->count();
        $approvedPo = PoCustomer::where('status_po', PoStatus::APPROVED->value)->count();
        $rejectedPo = PoCustomer::where('status_po', PoStatus::REJECTED->value)->count();

        // Calculate total PO value this month - use correct column
        $totalNilaiThisMonth = PoCustomer::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_sebelum_pajak'); // Changed from 'total' to 'total_sebelum_pajak'

        // Calculate total approved PO value - use correct column
        $totalNilaiApproved = PoCustomer::where('status_po', PoStatus::APPROVED->value)
            ->sum('total_sebelum_pajak'); // Changed from 'total' to 'total_sebelum_pajak'

        // Calculate PO this month vs last month
        $poThisMonth = PoCustomer::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $poLastMonth = PoCustomer::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        $poTrend = $poLastMonth > 0 ? round((($poThisMonth - $poLastMonth) / $poLastMonth) * 100, 1) : 0;

        return [
            Stat::make('Total Customer PO', number_format($totalPo))
                ->description('Total all Purchase Orders')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Pending Customer PO', number_format($pendingPo))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approved Customer PO', number_format($approvedPo))
                ->description('Already approved')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Customer PO This Month', number_format($poThisMonth))
                ->description($poTrend >= 0 ? "+{$poTrend}% from last month" : "{$poTrend}% from last month")
                ->descriptionIcon($poTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($poTrend >= 0 ? 'success' : 'danger'),

            Stat::make('Customer PO Value This Month', 'Rp ' . number_format($totalNilaiThisMonth, 0, ',', '.'))
                ->description('Total PO value this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Approved Customer PO Value', 'Rp ' . number_format($totalNilaiApproved, 0, ',', '.'))
                ->description('Total value of approved POs')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
