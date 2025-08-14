<?php

namespace App\Exports;

use App\Models\TransaksiKeuangan;
use App\Models\RekeningBank;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Database\Eloquent\Collection;

class TransaksiKeuanganExport implements
    FromArray,
    WithStyles,
    WithTitle,
    WithColumnWidths,
    WithEvents
{
    private $collection;
    private $totalPemasukan = 0;
    private $totalPengeluaran = 0;
    private $dateRange = '';
    private $accountFilter = null;
    private $filters = [];

    public function __construct($collection = null, $filters = [])
    {
        $this->filters = $filters;

        if ($collection instanceof Collection) {
            $this->collection = $collection;
        } else {
            $this->collection = TransaksiKeuangan::with(['rekening', 'poSupplier.supplier'])
                ->orderBy('tanggal', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        // Hitung total
        $this->totalPemasukan = $this->collection->where('jenis', 'pemasukan')->sum('jumlah');
        $this->totalPengeluaran = $this->collection->where('jenis', 'pengeluaran')->sum('jumlah');

        // Set date range
        if (!$this->collection->isEmpty()) {
            $startDate = $this->collection->min('tanggal');
            $endDate = $this->collection->max('tanggal');

            // Handle date formatting
            if (is_string($startDate)) {
                $startDate = \Carbon\Carbon::parse($startDate);
            }
            if (is_string($endDate)) {
                $endDate = \Carbon\Carbon::parse($endDate);
            }

            $this->dateRange = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        } else {
            $this->dateRange = 'No Data';
        }

        // Set account filter info
        if (isset($filters['account_id']) && $filters['account_id']) {
            $account = RekeningBank::find($filters['account_id']);
            $this->accountFilter = $account ? $account->nama_bank . ' - ' . $account->nomor_rekening : null;
        }
    }

    public function array(): array
    {
        $data = [];

        // Company header
        $data[] = ['PT. Sentra Alam Anandana', '', '', '', '', '', ''];
        $data[] = ['BUKU BESAR TRANSAKSI KEUANGAN', '', '', '', '', '', ''];
        $data[] = ['Periode: ' . $this->dateRange, '', '', '', '', '', ''];

        if ($this->accountFilter) {
            $data[] = ['Rekening: ' . $this->accountFilter, '', '', '', '', '', ''];
        }

        $data[] = ['', '', '', '', '', '', '']; // Empty row

        // Table headers
        $data[] = ['TGL', 'KODE', 'KETERANGAN', 'REF', 'DEBIT', 'KREDIT', 'SALDO'];

        // Data rows
        $saldo = 0;
        foreach ($this->collection as $transaksi) {
            // Pastikan tanggal adalah Carbon instance
            $tanggal = $transaksi->tanggal;
            if (is_string($tanggal)) {
                $tanggal = \Carbon\Carbon::parse($tanggal);
            }

            // Hitung saldo running
            if ($transaksi->jenis === 'pemasukan') {
                $saldo += $transaksi->jumlah;
                $debit = '';
                $kredit = $transaksi->jumlah;
            } else {
                $saldo -= $transaksi->jumlah;
                $debit = $transaksi->jumlah;
                $kredit = '';
            }

            // Generate kode transaksi
            $kode = $this->generateTransactionCode($transaksi);

            // Tentukan referensi
            $referensi = $this->getReferensiInfo($transaksi);

            $data[] = [
                $tanggal->format('d/m/Y'),
                $kode,
                $transaksi->keterangan ?? '',
                $referensi,
                $debit,
                $kredit,
                $saldo
            ];
        }

        // Empty rows before summary
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', ''];

        // Summary section
        $data[] = ['RINGKASAN TRANSAKSI', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['Total Pemasukan (Credit):', '', $this->totalPemasukan, '', '', '', ''];
        $data[] = ['Total Pengeluaran (Debit):', '', $this->totalPengeluaran, '', '', '', ''];
        $data[] = ['Saldo Bersih:', '', $this->totalPemasukan - $this->totalPengeluaran, '', '', '', ''];

        // Footer
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['Dibuat pada: ' . now()->format('d/m/Y H:i:s'), '', '', '', 'Total Transaksi: ' . $this->collection->count(), '', ''];
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['Dibuat oleh,', '', '', '', 'Disetujui oleh,', '', ''];
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['', '', '', '', '', '', ''];
        $data[] = ['(___________________)', '', '', '', '(___________________)', '', ''];
        $data[] = ['Finance Staff', '', '', '', 'Finance Manager', '', ''];

        return $data;
    }

    private function generateTransactionCode($transaksi): string
    {
        $prefix = $transaksi->jenis === 'pemasukan' ? 'IN' : 'OUT';
        $tanggal = $transaksi->tanggal;
        if (is_string($tanggal)) {
            $tanggal = \Carbon\Carbon::parse($tanggal);
        }
        return $prefix . '-' . $tanggal->format('Ymd') . '-' . str_pad($transaksi->id, 4, '0', STR_PAD_LEFT);
    }

    private function getReferensiInfo($transaksi): string
    {
        if ($transaksi->poSupplier) {
            return 'PO-' . $transaksi->poSupplier->nomor_po;
        }

        if ($transaksi->referensi_type === 'invoice' && $transaksi->referensi_id) {
            $invoice = \App\Models\Invoice::find($transaksi->referensi_id);
            return $invoice ? 'INV-' . $invoice->nomor_invoice : '';
        }

        return 'MANUAL';
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 12,  // Tanggal
            'B' => 20,  // Kode
            'C' => 40,  // Keterangan
            'D' => 15,  // Referensi
            'E' => 18,  // Debit
            'F' => 18,  // Kredit
            'G' => 18,  // Saldo
        ];
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->styleSheet($sheet);
            },
        ];
    }

    private function styleSheet($sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Determine header row
        $headerRow = $this->accountFilter ? 6 : 5;
        $firstDataRow = $headerRow + 1;
        $lastDataRow = $firstDataRow + $this->collection->count() - 1;

        // Company header styling
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        if ($this->accountFilter) {
            $sheet->mergeCells('A4:G4');
        }

        $sheet->getStyle('A1:G4')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $sheet->getStyle('A1')->getFont()->setSize(16);
        $sheet->getStyle('A2')->getFont()->setSize(14);

        // Table header styling
        $sheet->getStyle("A{$headerRow}:G{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '374151']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        // Data rows styling (only if we have data)
        if ($this->collection->count() > 0) {
            $sheet->getStyle("A{$firstDataRow}:G{$lastDataRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]
                ]
            ]);

            // Date and code columns center alignment
            $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$firstDataRow}:B{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$firstDataRow}:D{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Number formatting for currency columns
            $sheet->getStyle("E{$firstDataRow}:G{$lastDataRow}")->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0.00']
            ]);

            // Alternate row colors
            for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                if (($row - $firstDataRow) % 2 == 1) {
                    $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F9FAFB']
                        ]
                    ]);
                }
            }

            // Highlight saldo colors
            for ($row = $firstDataRow; $row <= $lastDataRow; $row++) {
                $saldoValue = $sheet->getCell("G{$row}")->getValue();
                if (is_numeric($saldoValue)) {
                    $color = $saldoValue >= 0 ? '10B981' : 'EF4444';
                    $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB($color);
                    $sheet->getStyle("G{$row}")->getFont()->setBold(true);
                }
            }
        }

        // Summary section styling
        $summaryStartRow = $lastDataRow + 3;
        $sheet->mergeCells("A{$summaryStartRow}:G{$summaryStartRow}");
        $sheet->getStyle("A{$summaryStartRow}:G{$summaryStartRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        // Summary details styling
        $summaryDetailStart = $summaryStartRow + 2;
        for ($i = 0; $i < 3; $i++) {
            $row = $summaryDetailStart + $i;
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("C{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0.00']
            ]);

            // Color for amounts
            $value = $sheet->getCell("C{$row}")->getValue();
            if (is_numeric($value)) {
                $color = $value >= 0 ? '10B981' : 'EF4444';
                $sheet->getStyle("C{$row}")->getFont()->getColor()->setRGB($color);
            }

            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                ]
            ]);
        }

        // Footer styling
        $footerRow = $highestRow - 7;
        $sheet->getStyle("A{$footerRow}")->getFont()->setSize(10)->setItalic(true);
        $sheet->getStyle("E{$footerRow}")->applyFromArray([
            'font' => ['size' => 10, 'italic' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
        ]);

        // Signature styling
        $signatureRow = $highestRow - 5;
        $sheet->getStyle("A{$signatureRow}:G" . ($highestRow))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
