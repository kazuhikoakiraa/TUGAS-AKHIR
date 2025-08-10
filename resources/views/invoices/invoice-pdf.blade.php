<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->nomor_invoice }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-info {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .company-details {
            display: table-cell;
            vertical-align: top;
            width: 60%;
        }

        .invoice-title {
            display: table-cell;
            vertical-align: top;
            width: 40%;
            text-align: right;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-address {
            color: #666;
            line-height: 1.5;
        }

        .invoice-title h1 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2563eb;
        }

        .invoice-meta {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .invoice-details, .customer-details {
            display: table-cell;
            vertical-align: top;
            width: 48%;
            padding-right: 2%;
        }

        .invoice-details h3, .customer-details h3 {
            color: #2563eb;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }

        .detail-row {
            margin-bottom: 5px;
            display: table;
            width: 100%;
        }

        .detail-label {
            font-weight: bold;
            display: table-cell;
            width: 40%;
        }

        .detail-value {
            display: table-cell;
            width: 60%;
        }

        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status.draft { background: #f3f4f6; color: #374151; }
        .status.sent { background: #fef3c7; color: #d97706; }
        .status.paid { background: #d1fae5; color: #059669; }
        .status.overdue { background: #fee2e2; color: #dc2626; }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .items-table th {
            background: #2563eb;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals {
            width: 100%;
            margin-top: 20px;
        }

        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-table .total-label {
            font-weight: bold;
            text-align: right;
            background: #f9fafb;
        }

        .totals-table .total-amount {
            text-align: right;
            width: 120px;
        }

        .grand-total {
            font-size: 16px;
            font-weight: bold;
            background: #2563eb;
            color: white;
        }

        .grand-total td {
            border: none;
        }

        .notes {
            margin-top: 30px;
            padding: 20px;
            background: #f9fafb;
            border-left: 4px solid #2563eb;
        }

        .notes h4 {
            color: #2563eb;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #666;
            font-size: 11px;
        }

        .payment-info {
            margin-top: 30px;
            padding: 20px;
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
        }

        .payment-info h4 {
            color: #0369a1;
            margin-bottom: 15px;
        }

        .bank-details {
            display: table;
            width: 100%;
        }

        .bank-item {
            display: table-cell;
            width: 50%;
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #e0f2fe;
            margin-right: 10px;
        }

        .bank-name {
            font-weight: bold;
            color: #0369a1;
            margin-bottom: 5px;
        }

        @page {
            margin: 1cm;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .container {
                max-width: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-details">
                    <div class="company-name">PT. NAMA PERUSAHAAN ANDA</div>
                    <div class="company-address">
                        Alamat Perusahaan Lengkap<br>
                        Kota, Provinsi, Kode Pos<br>
                        Telp: (021) 1234-5678<br>
                        Email: info@perusahaan.com<br>
                        Website: www.perusahaan.com
                    </div>
                </div>
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                    <div style="font-size: 14px; color: #666;">
                        {{ $invoice->nomor_invoice }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice & Customer Details -->
        <div class="invoice-meta">
            <div class="invoice-details">
                <h3>Detail Invoice</h3>
                <div class="detail-row">
                    <span class="detail-label">Nomor Invoice:</span>
                    <span class="detail-value">{{ $invoice->nomor_invoice }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Tanggal:</span>
                    <span class="detail-value">{{ $invoice->tanggal->format('d F Y') }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status {{ $invoice->status }}">
                            {{ $invoice->status_text }}
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">PO Number:</span>
                    <span class="detail-value">{{ $invoice->poCustomer->nomor_po ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Dibuat Oleh:</span>
                    <span class="detail-value">{{ $invoice->user->name ?? '-' }}</span>
                </div>
            </div>

            <div class="customer-details">
                <h3>Detail Customer</h3>
                @if($invoice->poCustomer && $invoice->poCustomer->customer)
                <div class="detail-row">
                    <span class="detail-label">Nama:</span>
                    <span class="detail-value">{{ $invoice->poCustomer->customer->nama }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Alamat:</span>
                    <span class="detail-value">{{ $invoice->poCustomer->customer->alamat ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Telp:</span>
                    <span class="detail-value">{{ $invoice->poCustomer->customer->telepon ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $invoice->poCustomer->customer->email ?? '-' }}</span>
                </div>
                @else
                <div class="detail-row">
                    <span class="detail-value" style="color: #dc2626;">Data customer tidak tersedia</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 45%">Deskripsi</th>
                    <th style="width: 10%" class="text-center">Qty</th>
                    <th style="width: 15%" class="text-right">Harga Satuan</th>
                    <th style="width: 25%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @if($invoice->poCustomer && $invoice->poCustomer->details && $invoice->poCustomer->details->count() > 0)
                    @foreach($invoice->poCustomer->details as $index => $detail)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $detail->deskripsi }}</td>
                        <td class="text-center">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($detail->total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="text-center">1</td>
                        <td>Jasa/Barang sesuai PO {{ $invoice->poCustomer->nomor_po ?? '' }}</td>
                        <td class="text-center">1</td>
                        <td class="text-right">Rp {{ number_format($invoice->total_sebelum_pajak, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($invoice->total_sebelum_pajak, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table class="totals-table">
                <tr>
                    <td class="total-label">Subtotal:</td>
                    <td class="total-amount">Rp {{ number_format($invoice->total_sebelum_pajak, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="total-label">PPN 11%:</td>
                    <td class="total-amount">Rp {{ number_format($invoice->total_pajak, 0, ',', '.') }}</td>
                </tr>
                <tr class="grand-total">
                    <td class="total-label">GRAND TOTAL:</td>
                    <td class="total-amount">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        @if($invoice->keterangan)
        <div class="notes">
            <h4>Catatan:</h4>
            <p>{{ $invoice->keterangan }}</p>
        </div>
        @endif

        <!-- Payment Information -->
        <div class="payment-info">
            <h4>Informasi Pembayaran</h4>
            @if($invoice->rekeningBank)
            <div class="bank-details">
                <div class="bank-item">
                    <div class="bank-name">{{ $invoice->rekeningBank->nama_bank }}</div>
                    <div>No. Rek: {{ $invoice->rekeningBank->nomor_rekening }}</div>
                    <div>A.n: {{ $invoice->rekeningBank->nama_pemilik }}</div>
                    @if($invoice->rekeningBank->kode_bank)
                    <div>Kode Bank: {{ $invoice->rekeningBank->kode_bank }}</div>
                    @endif
                    @if($invoice->rekeningBank->keterangan)
                    <div style="font-size: 11px; color: #666; margin-top: 5px;">
                        {{ $invoice->rekeningBank->keterangan }}
                    </div>
                    @endif
                </div>
            </div>
            @else
            <div class="bank-details">
                <div class="bank-item">
                    <div class="bank-name">Bank Central Asia (BCA)</div>
                    <div>No. Rek: 1234567890</div>
                    <div>A.n: PT. Nama Perusahaan</div>
                </div>
            </div>
            @endif
            <p style="margin-top: 15px; font-size: 11px; color: #666;">
                Harap transfer sesuai dengan nominal yang tertera dan konfirmasi pembayaran ke email kami.
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih atas kepercayaan Anda kepada kami.</p>
            <p>Invoice ini dibuat secara otomatis pada {{ now()->format('d F Y, H:i') }} WIB</p>
        </div>
    </div>
</body>
</html>
