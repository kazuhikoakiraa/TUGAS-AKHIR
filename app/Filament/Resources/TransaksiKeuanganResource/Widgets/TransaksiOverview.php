<?php

namespace App\Filament\Resources\TransaksiKeuanganResource\Widgets;

use App\Models\TransaksiKeuangan;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class TransaksiOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Calculate statistics for this month
        $pemasukanBulanIni = TransaksiKeuangan::pemasukan()->bulanIni()->sum('jumlah');
        $pengeluaranBulanIni = TransaksiKeuangan::pengeluaran()->bulanIni()->sum('jumlah');
        $keuntunganBulanIni = $pemasukanBulanIni - $pengeluaranBulanIni;

        // Calculate statistics for this year
        $pemasukanTahunIni = TransaksiKeuangan::pemasukan()->tahunIni()->sum('jumlah');
        $pengeluaranTahunIni = TransaksiKeuangan::pengeluaran()->tahunIni()->sum('jumlah');
        $keuntunganTahunIni = $pemasukanTahunIni - $pengeluaranTahunIni;

        // Calculate percentage change from last month
        $pemasukanBulanLalu = TransaksiKeuangan::pemasukan()
            ->whereMonth('tanggal', now()->subMonth()->month)
            ->whereYear('tanggal', now()->subMonth()->year)
            ->sum('jumlah');

        $pengeluaranBulanLalu = TransaksiKeuangan::pengeluaran()
            ->whereMonth('tanggal', now()->subMonth()->month)
            ->whereYear('tanggal', now()->subMonth()->year)
            ->sum('jumlah');

        $pemasukanChange = $pemasukanBulanLalu > 0
            ? (($pemasukanBulanIni - $pemasukanBulanLalu) / $pemasukanBulanLalu) * 100
            : 0;

        $pengeluaranChange = $pengeluaranBulanLalu > 0
            ? (($pengeluaranBulanIni - $pengeluaranBulanLalu) / $pengeluaranBulanLalu) * 100
            : 0;

        return [
            Stat::make('Total Income (This Month)', 'Rp ' . number_format($pemasukanBulanIni, 0, ',', '.'))
                ->description($this->getChangeDescription($pemasukanChange, 'from last month'))
                ->descriptionIcon($pemasukanChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($pemasukanChange >= 0 ? 'success' : 'danger')
                ->chart($this->getIncomeChart()),

            Stat::make('Total Expense (This Month)', 'Rp ' . number_format($pengeluaranBulanIni, 0, ',', '.'))
                ->description($this->getChangeDescription($pengeluaranChange, 'from last month'))
                ->descriptionIcon($pengeluaranChange <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($pengeluaranChange <= 0 ? 'success' : 'danger')
                ->chart($this->getExpenseChart()),

            Stat::make('Profit/Loss (This Month)', 'Rp ' . number_format($keuntunganBulanIni, 0, ',', '.'))
                ->description($keuntunganBulanIni >= 0 ? 'Profit' : 'Loss')
                ->descriptionIcon($keuntunganBulanIni >= 0 ? 'heroicon-m-face-smile' : 'heroicon-m-face-frown')
                ->color($keuntunganBulanIni >= 0 ? 'success' : 'danger'),

            Stat::make('Total Transactions (This Month)', TransaksiKeuangan::bulanIni()->count())
                ->description('Number of transactions')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Profit/Loss (This Year)', 'Rp ' . number_format($keuntunganTahunIni, 0, ',', '.'))
                ->description($keuntunganTahunIni >= 0 ? 'Current year profit' : 'Current year loss')
                ->descriptionIcon($keuntunganTahunIni >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($keuntunganTahunIni >= 0 ? 'success' : 'danger'),

            Stat::make('Cash Balance', 'Rp ' . number_format($pemasukanTahunIni - $pengeluaranTahunIni, 0, ',', '.'))
                ->description('Cumulative balance this year')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pemasukanTahunIni - $pengeluaranTahunIni >= 0 ? 'success' : 'warning'),
        ];
    }

    private function getChangeDescription(float $percentage, string $suffix): string
    {
        if ($percentage == 0) {
            return "No change {$suffix}";
        }

        $sign = $percentage >= 0 ? '+' : '';
        return $sign . number_format($percentage, 1) . "% {$suffix}";
    }

    private function getIncomeChart(): array
    {
        // Get income data for last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $amount = TransaksiKeuangan::pemasukan()
                ->whereDate('tanggal', $date)
                ->sum('jumlah');
            $data[] = (float) $amount;
        }
        return $data;
    }

    private function getExpenseChart(): array
    {
        // Get expense data for last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $amount = TransaksiKeuangan::pengeluaran()
                ->whereDate('tanggal', $date)
                ->sum('jumlah');
            $data[] = (float) $amount;
        }
        return $data;
    }
}
