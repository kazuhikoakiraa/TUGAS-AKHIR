<?php

namespace App\Filament\Widgets;

use App\Enums\PoStatus;
use App\Models\PoSupplier;
use Illuminate\Support\Carbon;
use Filament\Widgets\StatsOverviewWidget\Stat;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class PoSupplierStatsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Calculate PO statistics
        $totalPo = PoSupplier::count();
        $pendingPo = PoSupplier::where('status_po', PoStatus::PENDING->value)->count();
        $approvedPo = PoSupplier::where('status_po', PoStatus::APPROVED->value)->count();
        $rejectedPo = PoSupplier::where('status_po', PoStatus::REJECTED->value)->count();

        // Calculate total PO value this month - use correct column
        $totalNilaiThisMonth = PoSupplier::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('total_sebelum_pajak'); // Changed from 'total' to 'total_sebelum_pajak'

        // Calculate total approved PO value - use correct column
        $totalNilaiApproved = PoSupplier::where('status_po', PoStatus::APPROVED->value)
            ->sum('total_sebelum_pajak'); // Changed from 'total' to 'total_sebelum_pajak'

        // Calculate PO this month vs last month
        $poThisMonth = PoSupplier::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        $poLastMonth = PoSupplier::whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();

        $poTrend = $poLastMonth > 0 ? round((($poThisMonth - $poLastMonth) / $poLastMonth) * 100, 1) : 0;

        return [
            Stat::make('Total Supplier PO', number_format($totalPo))
                ->description('Total all Purchase Orders')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary'),

            Stat::make('Pending Supplier PO', number_format($pendingPo))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Approved Supplier PO', number_format($approvedPo))
                ->description('Already approved')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Supplier PO This Month', number_format($poThisMonth))
                ->description($poTrend >= 0 ? "+{$poTrend}% from last month" : "{$poTrend}% from last month")
                ->descriptionIcon($poTrend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($poTrend >= 0 ? 'success' : 'danger'),

            Stat::make('Supplier PO Value This Month', 'Rp ' . number_format($totalNilaiThisMonth, 0, ',', '.'))
                ->description('Total PO value this month')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Approved Supplier PO Value', 'Rp ' . number_format($totalNilaiApproved, 0, ',', '.'))
                ->description('Total value of approved POs')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
