<?php

namespace App\Exports;

use App\Models\TransaksiKeuangan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Database\Eloquent\Collection;

class TransaksiKeuanganExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    private $collection;
    private $totalPemasukan = 0;
    private $totalPengeluaran = 0;

    public function __construct($collection = null)
    {
        $this->collection = $collection ?? TransaksiKeuangan::with(['rekening', 'poSupplier.supplier'])->orderBy('tanggal', 'desc')->get();

        // Hitung total
        $this->totalPemasukan = $this->collection->where('jenis', 'pemasukan')->sum('jumlah');
        $this->totalPengeluaran = $this->collection->where('jenis', 'pengeluaran')->sum('jumlah');
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Jenis Transaksi',
            'Bank',
            'Nomor Rekening',
            'Keterangan',
            'Referensi PO/Invoice',
            'Debit (Pengeluaran)',
            'Kredit (Pemasukan)',
            'Saldo'
        ];
    }

    public function map($transaksi): array
    {
        static $no = 1;
        static $saldo = 0;

        // Hitung saldo running
        if ($transaksi->jenis === 'pemasukan') {
            $saldo += $transaksi->jumlah;
            $debit = '';
            $kredit = number_format($transaksi->jumlah, 2, ',', '.');
        } else {
            $saldo -= $transaksi->jumlah;
            $debit = number_format($transaksi->jumlah, 2, ',', '.');
            $kredit = '';
        }

        // Tentukan referensi
        $referensi = '';
        if ($transaksi->poSupplier) {
            $referensi = $transaksi->poSupplier->nomor_po . ' (' . $transaksi->poSupplier->supplier->nama . ')';
        } elseif ($transaksi->invoice) {
            $customerName = $transaksi->invoice->poCustomer?->customer?->nama ?? 'Unknown';
            $referensi = $transaksi->invoice->nomor_invoice . ' (' . $customerName . ')';
        }

        return [
            $no++,
            $transaksi->tanggal->format('d/m/Y'),
            $transaksi->jenis === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran',
            $transaksi->rekening->nama_bank,
            $transaksi->rekening->nomor_rekening,
            $transaksi->keterangan,
            $referensi,
            $debit,
            $kredit,
            number_format($saldo, 2, ',', '.')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header styling
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,   // No
            'B' => 12,  // Tanggal
            'C' => 15,  // Jenis
            'D' => 15,  // Bank
            'E' => 18,  // No Rekening
            'F' => 30,  // Keterangan
            'G' => 25,  // Referensi
            'H' => 18,  // Debit
            'I' => 18,  // Kredit
            'J' => 18,  // Saldo
        ];
    }

    public function title(): string
    {
        return 'Laporan Transaksi Keuangan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Add border to all data
                $sheet->getStyle("A1:J{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Add summary section
                $summaryStartRow = $lastRow + 3;

                // Header summary
                $sheet->setCellValue("A{$summaryStartRow}", 'RINGKASAN TRANSAKSI');
                $sheet->mergeCells("A{$summaryStartRow}:D{$summaryStartRow}");
                $sheet->getStyle("A{$summaryStartRow}:D{$summaryStartRow}")
                    ->getFont()
                    ->setBold(true)
                    ->setSize(14);

                $sheet->getStyle("A{$summaryStartRow}:D{$summaryStartRow}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setRGB('E5E7EB');

                // Detail summary
                $detailStartRow = $summaryStartRow + 2;

                $sheet->setCellValue("A{$detailStartRow}", 'Total Pemasukan:');
                $sheet->setCellValue("B{$detailStartRow}", 'Rp ' . number_format($this->totalPemasukan, 2, ',', '.'));

                $sheet->setCellValue("A" . ($detailStartRow + 1), 'Total Pengeluaran:');
                $sheet->setCellValue("B" . ($detailStartRow + 1), 'Rp ' . number_format($this->totalPengeluaran, 2, ',', '.'));

                $sheet->setCellValue("A" . ($detailStartRow + 2), 'Keuntungan/Rugi:');
                $sheet->setCellValue("B" . ($detailStartRow + 2), 'Rp ' . number_format($this->totalPemasukan - $this->totalPengeluaran, 2, ',', '.'));

                // Style summary
                $summaryRange = "A{$detailStartRow}:B" . ($detailStartRow + 2);
                $sheet->getStyle($summaryRange)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("A{$detailStartRow}:A" . ($detailStartRow + 2))
                    ->getFont()
                    ->setBold(true);

                // Color keuntungan/rugi
                $keuntungan = $this->totalPemasukan - $this->totalPengeluaran;
                $sheet->getStyle("B" . ($detailStartRow + 2))
                    ->getFont()
                    ->setBold(true)
                    ->getColor()
                    ->setRGB($keuntungan >= 0 ? '059669' : 'DC2626');

                // Add metadata
                $metaStartRow = $detailStartRow + 5;
                $sheet->setCellValue("A{$metaStartRow}", 'Diekspor pada: ' . now()->format('d/m/Y H:i:s'));
                $sheet->setCellValue("A" . ($metaStartRow + 1), 'Periode: ' . $this->collection->min('tanggal')?->format('d/m/Y') . ' - ' . $this->collection->max('tanggal')?->format('d/m/Y'));
                $sheet->setCellValue("A" . ($metaStartRow + 2), 'Total Data: ' . $this->collection->count() . ' transaksi');

                // Auto size columns
                foreach (range('A', 'J') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(false);
                }
            },
        ];
    }
}
