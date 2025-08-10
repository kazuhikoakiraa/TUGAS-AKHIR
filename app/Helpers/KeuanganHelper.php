<?php

namespace App\Helpers;

use App\Models\TransaksiKeuangan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KeuanganHelper
{
    /**
     * Calculate total income for a specific period
     */
    public static function totalIncome(?Carbon $start = null, ?Carbon $end = null): float
    {
        $query = TransaksiKeuangan::pemasukan();

        if ($start && $end) {
            $query->whereBetween('tanggal', [$start, $end]);
        }

        return (float) $query->sum('jumlah');
    }

    /**
     * Calculate total expenses for a specific period
     */
    public static function totalExpenses(?Carbon $start = null, ?Carbon $end = null): float
    {
        $query = TransaksiKeuangan::pengeluaran();

        if ($start && $end) {
            $query->whereBetween('tanggal', [$start, $end]);
        }

        return (float) $query->sum('jumlah');
    }

    /**
     * Calculate profit/loss for a specific period
     */
    public static function profit(?Carbon $start = null, ?Carbon $end = null): float
    {
        return self::totalIncome($start, $end) - self::totalExpenses($start, $end);
    }

    /**
     * Calculate cumulative cash balance
     */
    public static function cashBalance(): float
    {
        return self::profit();
    }

    /**
     * Get financial summary for a specific period
     */
    public static function financialSummary(?Carbon $start = null, ?Carbon $end = null): array
    {
        $income = self::totalIncome($start, $end);
        $expenses = self::totalExpenses($start, $end);
        $profit = $income - $expenses;

        return [
            'income' => $income,
            'expenses' => $expenses,
            'profit' => $profit,
            'profit_percentage' => $income > 0 ? ($profit / $income) * 100 : 0,
            'formatted' => [
                'income' => 'Rp ' . number_format($income, 0, ',', '.'),
                'expenses' => 'Rp ' . number_format($expenses, 0, ',', '.'),
                'profit' => 'Rp ' . number_format($profit, 0, ',', '.'),
            ]
        ];
    }

    /**
     * Get daily transaction trends for charts
     */
    public static function dailyTrends(int $days = 30): array
    {
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $income = TransaksiKeuangan::pemasukan()
                ->whereDate('tanggal', $date)
                ->sum('jumlah');

            $expenses = TransaksiKeuangan::pengeluaran()
                ->whereDate('tanggal', $date)
                ->sum('jumlah');

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'date_formatted' => $date->format('d/m'),
                'income' => (float) $income,
                'expenses' => (float) $expenses,
                'profit' => (float) ($income - $expenses),
            ];
        }

        return $data;
    }

    /**
     * Get monthly trends for current year
     */
    public static function monthlyTrends(): array
    {
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $income = TransaksiKeuangan::pemasukan()
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah');

            $expenses = TransaksiKeuangan::pengeluaran()
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah');

            $data[] = [
                'month' => $month,
                'month_name' => Carbon::createFromDate(now()->year, $month, 1)->format('M'),
                'income' => (float) $income,
                'expenses' => (float) $expenses,
                'profit' => (float) ($income - $expenses),
            ];
        }

        return $data;
    }

    /**
     * Get transaction breakdown per bank account
     */
    public static function breakdownByAccount(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $query = TransaksiKeuangan::with('rekening');

        if ($start && $end) {
            $query->whereBetween('tanggal', [$start, $end]);
        }

        return $query->get()
            ->groupBy('id_rekening')
            ->map(function ($transactions, $accountId) {
                $account = $transactions->first()->rekening;
                $income = $transactions->where('jenis', 'pemasukan')->sum('jumlah');
                $expenses = $transactions->where('jenis', 'pengeluaran')->sum('jumlah');

                return [
                    'account' => $account,
                    'bank_name' => $account->nama_bank,
                    'account_number' => $account->nomor_rekening,
                    'income' => $income,
                    'expenses' => $expenses,
                    'balance' => $income - $expenses,
                    'transaction_count' => $transactions->count(),
                ];
            })
            ->values();
    }

    /**
     * Validate if transaction already exists for PO or Invoice
     */
    public static function checkTransactionExists($referenceType, $referenceId): bool
    {
        if ($referenceType === 'po_supplier') {
            return TransaksiKeuangan::where('id_po_supplier', $referenceId)->exists();
        }

        if ($referenceType === 'invoice') {
            return TransaksiKeuangan::where('keterangan', 'like', "%INV-%")
                ->where('jenis', 'pemasukan')
                ->exists();
        }

        return false;
    }

    /**
     * Format number to rupiah
     */
    public static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Get percentage change
     */
    public static function percentageChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'percentage' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'up' : 'same',
                'formatted' => $current > 0 ? '+100%' : '0%'
            ];
        }

        $percentage = (($current - $previous) / abs($previous)) * 100;

        return [
            'percentage' => abs($percentage),
            'direction' => $percentage > 0 ? 'up' : ($percentage < 0 ? 'down' : 'same'),
            'formatted' => ($percentage >= 0 ? '+' : '') . number_format($percentage, 1) . '%'
        ];
    }

    /**
     * Predict cash flow for next month based on average
     */
    public static function predictCashFlow(): array
    {
        // Get data from last 3 months for prediction
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $averageIncome = 0;
        $averageExpenses = 0;
        $monthsWithData = 0;

        for ($i = 0; $i < 3; $i++) {
            $month = $currentMonth - $i;
            $year = $currentYear;

            if ($month <= 0) {
                $month += 12;
                $year--;
            }

            $income = TransaksiKeuangan::pemasukan()
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->sum('jumlah');

            $expenses = TransaksiKeuangan::pengeluaran()
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', $year)
                ->sum('jumlah');

            if ($income > 0 || $expenses > 0) {
                $averageIncome += $income;
                $averageExpenses += $expenses;
                $monthsWithData++;
            }
        }

        if ($monthsWithData > 0) {
            $averageIncome = $averageIncome / $monthsWithData;
            $averageExpenses = $averageExpenses / $monthsWithData;
        }

        $predictedProfit = $averageIncome - $averageExpenses;
        $currentBalance = self::cashBalance();
        $predictedBalance = $currentBalance + $predictedProfit;

        return [
            'predicted_income' => $averageIncome,
            'predicted_expenses' => $averageExpenses,
            'predicted_profit' => $predictedProfit,
            'current_balance' => $currentBalance,
            'predicted_balance' => $predictedBalance,
            'confidence' => $monthsWithData >= 2 ? 'high' : ($monthsWithData == 1 ? 'medium' : 'low'),
            'formatted' => [
                'predicted_income' => self::formatRupiah($averageIncome),
                'predicted_expenses' => self::formatRupiah($averageExpenses),
                'predicted_profit' => self::formatRupiah($predictedProfit),
                'current_balance' => self::formatRupiah($currentBalance),
                'predicted_balance' => self::formatRupiah($predictedBalance),
            ]
        ];
    }

    // Legacy method aliases for backward compatibility
    public static function totalPemasukan(?Carbon $start = null, ?Carbon $end = null): float
    {
        return self::totalIncome($start, $end);
    }

    public static function totalPengeluaran(?Carbon $start = null, ?Carbon $end = null): float
    {
        return self::totalExpenses($start, $end);
    }

    public static function keuntungan(?Carbon $start = null, ?Carbon $end = null): float
    {
        return self::profit($start, $end);
    }

    public static function saldoKas(): float
    {
        return self::cashBalance();
    }

    public static function ringkasanKeuangan(?Carbon $start = null, ?Carbon $end = null): array
    {
        return self::financialSummary($start, $end);
    }

    public static function trendHarian(int $days = 30): array
    {
        return self::dailyTrends($days);
    }

    public static function trendBulanan(): array
    {
        return self::monthlyTrends();
    }

    public static function breakdownPerRekening(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        return self::breakdownByAccount($start, $end);
    }

    public static function cekTransaksiExists($referenceType, $referenceId): bool
    {
        return self::checkTransactionExists($referenceType, $referenceId);
    }

    public static function persentasePerubahan(float $current, float $previous): array
    {
        return self::percentageChange($current, $previous);
    }

    public static function prediksiKas(): array
    {
        return self::predictCashFlow();
    }
}
