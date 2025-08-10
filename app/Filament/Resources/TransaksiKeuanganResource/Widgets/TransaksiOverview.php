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
        // Hitung statistik untuk bulan ini
        $pemasukanBulanIni = TransaksiKeuangan::pemasukan()->bulanIni()->sum('jumlah');
        $pengeluaranBulanIni = TransaksiKeuangan::pengeluaran()->bulanIni()->sum('jumlah');
        $keuntunganBulanIni = $pemasukanBulanIni - $pengeluaranBulanIni;

        // Hitung statistik untuk tahun ini
        $pemasukanTahunIni = TransaksiKeuangan::pemasukan()->tahunIni()->sum('jumlah');
        $pengeluaranTahunIni = TransaksiKeuangan::pengeluaran()->tahunIni()->sum('jumlah');
        $keuntunganTahunIni = $pemasukanTahunIni - $pengeluaranTahunIni;

        // Hitung persentase perubahan dari bulan sebelumnya
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
            Stat::make('Total Pemasukan (Bulan Ini)', 'Rp ' . number_format($pemasukanBulanIni, 0, ',', '.'))
                ->description($this->getChangeDescription($pemasukanChange, 'dari bulan lalu'))
                ->descriptionIcon($pemasukanChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($pemasukanChange >= 0 ? 'success' : 'danger')
                ->chart($this->getPemasukanChart()),

            Stat::make('Total Pengeluaran (Bulan Ini)', 'Rp ' . number_format($pengeluaranBulanIni, 0, ',', '.'))
                ->description($this->getChangeDescription($pengeluaranChange, 'dari bulan lalu'))
                ->descriptionIcon($pengeluaranChange <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($pengeluaranChange <= 0 ? 'success' : 'danger')
                ->chart($this->getPengeluaranChart()),

            Stat::make('Keuntungan/Rugi (Bulan Ini)', 'Rp ' . number_format($keuntunganBulanIni, 0, ',', '.'))
                ->description($keuntunganBulanIni >= 0 ? 'Keuntungan' : 'Rugi')
                ->descriptionIcon($keuntunganBulanIni >= 0 ? 'heroicon-m-face-smile' : 'heroicon-m-face-frown')
                ->color($keuntunganBulanIni >= 0 ? 'success' : 'danger'),

            Stat::make('Total Transaksi (Bulan Ini)', TransaksiKeuangan::bulanIni()->count())
                ->description('Jumlah transaksi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Keuntungan/Rugi (Tahun Ini)', 'Rp ' . number_format($keuntunganTahunIni, 0, ',', '.'))
                ->description($keuntunganTahunIni >= 0 ? 'Keuntungan tahun berjalan' : 'Rugi tahun berjalan')
                ->descriptionIcon($keuntunganTahunIni >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($keuntunganTahunIni >= 0 ? 'success' : 'danger'),

            Stat::make('Saldo Kas', 'Rp ' . number_format($pemasukanTahunIni - $pengeluaranTahunIni, 0, ',', '.'))
                ->description('Saldo kumulatif tahun ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($pemasukanTahunIni - $pengeluaranTahunIni >= 0 ? 'success' : 'warning'),
        ];
    }

    private function getChangeDescription(float $percentage, string $suffix): string
    {
        if ($percentage == 0) {
            return "Tidak ada perubahan {$suffix}";
        }

        $sign = $percentage >= 0 ? '+' : '';
        return $sign . number_format($percentage, 1) . "% {$suffix}";
    }

    private function getPemasukanChart(): array
    {
        // Ambil data pemasukan 7 hari terakhir
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

    private function getPengeluaranChart(): array
    {
        // Ambil data pengeluaran 7 hari terakhir
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
