<?php

namespace App\Helpers;

use App\Models\TransaksiKeuangan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class KeuanganHelper
{
    /**
     * Hitung total pemasukan untuk periode tertentu
     */
    public static function totalPemasukan(?Carbon $start = null, ?Carbon $end = null): float
    {
        $query = TransaksiKeuangan::pemasukan();

        if ($start && $end) {
            $query->whereBetween('tanggal', [$start, $end]);
        }

        return (float) $query->sum('jumlah');
    }

    /**
     * Hitung total pengeluaran untuk periode tertentu
     */
    public static function totalPengeluaran(?Carbon $start = null, ?Carbon $end = null): float
    {
        $query = TransaksiKeuangan::pengeluaran();

        if ($start && $end) {
            $query->whereBetween('tanggal', [$start, $end]);
        }

        return (float) $query->sum('jumlah');
    }

    /**
     * Hitung keuntungan/rugi untuk periode tertentu
     */
    public static function keuntungan(?Carbon $start = null, ?Carbon $end = null): float
    {
        return self::totalPemasukan($start, $end) - self::totalPengeluaran($start, $end);
    }

    /**
     * Hitung saldo kas kumulatif
     */
    public static function saldoKas(): float
    {
        return self::keuntungan();
    }

    /**
     * Dapatkan ringkasan keuangan untuk periode tertentu
     */
    public static function ringkasanKeuangan(?Carbon $start = null, ?Carbon $end = null): array
    {
        $pemasukan = self::totalPemasukan($start, $end);
        $pengeluaran = self::totalPengeluaran($start, $end);
        $keuntungan = $pemasukan - $pengeluaran;

        return [
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'keuntungan' => $keuntungan,
            'persentase_keuntungan' => $pemasukan > 0 ? ($keuntungan / $pemasukan) * 100 : 0,
            'formatted' => [
                'pemasukan' => 'Rp ' . number_format($pemasukan, 0, ',', '.'),
                'pengeluaran' => 'Rp ' . number_format($pengeluaran, 0, ',', '.'),
                'keuntungan' => 'Rp ' . number_format($keuntungan, 0, ',', '.'),
            ]
        ];
    }

    /**
     * Dapatkan tren transaksi harian untuk chart
     */
    public static function trendHarian(int $days = 30): array
    {
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $pemasukan = TransaksiKeuangan::pemasukan()
                ->whereDate('tanggal', $date)
                ->sum('jumlah');

            $pengeluaran = TransaksiKeuangan::pengeluaran()
                ->whereDate('tanggal', $date)
                ->sum('jumlah');

            $data[] = [
                'tanggal' => $date->format('Y-m-d'),
                'tanggal_formatted' => $date->format('d/m'),
                'pemasukan' => (float) $pemasukan,
                'pengeluaran' => (float) $pengeluaran,
                'keuntungan' => (float) ($pemasukan - $pengeluaran),
            ];
        }

        return $data;
    }

    /**
     * Dapatkan tren bulanan untuk tahun berjalan
     */
    public static function trendBulanan(): array
    {
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $pemasukan = TransaksiKeuangan::pemasukan()
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah');

            $pengeluaran = TransaksiKeuangan::pengeluaran()
                ->whereMonth('tanggal', $month)
                ->whereYear('tanggal', now()->year)
                ->sum('jumlah');

            $data[] = [
                'bulan' => $month,
                'bulan_nama' => Carbon::createFromDate(now()->year, $month, 1)->format('M'),
                'pemasukan' => (float) $pemasukan,
                'pengeluaran' => (float) $pengeluaran,
                'keuntungan' => (float) ($pemasukan - $pengeluaran),
            ];
        }

        return $data;
    }

    /**
     * Dapatkan breakdown transaksi per rekening
     */
    public static function breakdownPerRekening(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        $query = TransaksiKeuangan::with('rekening');

        if ($start && $end) {
            $query->whereBetween('tanggal', [$start, $end]);
        }

        return $query->get()
            ->groupBy('id_rekening')
            ->map(function ($transaksi, $rekeningId) {
                $rekening = $transaksi->first()->rekening;
                $pemasukan = $transaksi->where('jenis', 'pemasukan')->sum('jumlah');
                $pengeluaran = $transaksi->where('jenis', 'pengeluaran')->sum('jumlah');

                return [
                    'rekening' => $rekening,
                    'nama_bank' => $rekening->nama_bank,
                    'nomor_rekening' => $rekening->nomor_rekening,
                    'pemasukan' => $pemasukan,
                    'pengeluaran' => $pengeluaran,
                    'saldo' => $pemasukan - $pengeluaran,
                    'jumlah_transaksi' => $transaksi->count(),
                ];
            })
            ->values();
    }

    /**
     * Validasi apakah transaksi sudah ada untuk PO atau Invoice
     */
    public static function cekTransaksiExists($referenceType, $referenceId): bool
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
     * Format angka menjadi rupiah
     */
    public static function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Dapatkan persentase perubahan
     */
    public static function persentasePerubahan(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'percentage' => $current > 0 ? 100 : 0,
                'direction' => $current > 0 ? 'naik' : 'sama',
                'formatted' => $current > 0 ? '+100%' : '0%'
            ];
        }

        $percentage = (($current - $previous) / abs($previous)) * 100;

        return [
            'percentage' => abs($percentage),
            'direction' => $percentage > 0 ? 'naik' : ($percentage < 0 ? 'turun' : 'sama'),
            'formatted' => ($percentage >= 0 ? '+' : '') . number_format($percentage, 1) . '%'
        ];
    }

    /**
     * Prediksi kas untuk bulan depan berdasarkan rata-rata
     */
    public static function prediksiKas(): array
    {
        // Ambil data 3 bulan terakhir untuk prediksi
        $bulanSekarang = now()->month;
        $tahunSekarang = now()->year;

        $rataRataPemasukan = 0;
        $rataRataPengeluaran = 0;
        $bulanDenganData = 0;

        for ($i = 0; $i < 3; $i++) {
            $bulan = $bulanSekarang - $i;
            $tahun = $tahunSekarang;

            if ($bulan <= 0) {
                $bulan += 12;
                $tahun--;
            }

            $pemasukan = TransaksiKeuangan::pemasukan()
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');

            $pengeluaran = TransaksiKeuangan::pengeluaran()
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');

            if ($pemasukan > 0 || $pengeluaran > 0) {
                $rataRataPemasukan += $pemasukan;
                $rataRataPengeluaran += $pengeluaran;
                $bulanDenganData++;
            }
        }

        if ($bulanDenganData > 0) {
            $rataRataPemasukan = $rataRataPemasukan / $bulanDenganData;
            $rataRataPengeluaran = $rataRataPengeluaran / $bulanDenganData;
        }

        $prediksiKeuntungan = $rataRataPemasukan - $rataRataPengeluaran;
        $saldoSaatIni = self::saldoKas();
        $prediksiSaldo = $saldoSaatIni + $prediksiKeuntungan;

        return [
            'prediksi_pemasukan' => $rataRataPemasukan,
            'prediksi_pengeluaran' => $rataRataPengeluaran,
            'prediksi_keuntungan' => $prediksiKeuntungan,
            'saldo_saat_ini' => $saldoSaatIni,
            'prediksi_saldo' => $prediksiSaldo,
            'confidence' => $bulanDenganData >= 2 ? 'tinggi' : ($bulanDenganData == 1 ? 'sedang' : 'rendah'),
            'formatted' => [
                'prediksi_pemasukan' => self::formatRupiah($rataRataPemasukan),
                'prediksi_pengeluaran' => self::formatRupiah($rataRataPengeluaran),
                'prediksi_keuntungan' => self::formatRupiah($prediksiKeuntungan),
                'saldo_saat_ini' => self::formatRupiah($saldoSaatIni),
                'prediksi_saldo' => self::formatRupiah($prediksiSaldo),
            ]
        ];
    }
}
