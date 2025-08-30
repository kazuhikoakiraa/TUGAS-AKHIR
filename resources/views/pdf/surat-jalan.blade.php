<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $suratJalan->nomor_surat_jalan }}</title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        /* Company Header */
        .company-header {
            width: 100%;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .header-content {
            width: 100%;
            display: table;
        }

        .logo-section {
            display: table-cell;
            width: 100px;
            vertical-align: top;
            padding-right: 15px;
        }

        .logo-section img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            display: block;
        }

        .company-info {
            display: table-cell;
            vertical-align: top;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c5aa0;
            margin: 0 0 8px 0;
            letter-spacing: 0.5px;
        }

        .company-address {
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin-bottom: 5px;
        }

        .company-contact {
            font-size: 11px;
            color: #333;
        }

        .company-contact a {
            color: #2c5aa0;
            text-decoration: none;
        }

        /* Separator Line */
        .separator-line {
            border-bottom: 2px solid #2c5aa0;
            margin-bottom: 25px;
        }

        /* Document Title */
        .document-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            text-transform: uppercase;
        }

        /* Document Info */
        .document-info {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 5px 0;
            vertical-align: top;
        }

        .info-left {
            width: 25%;
            font-weight: bold;
        }

        .info-colon {
            width: 5%;
        }

        .info-value {
            width: 35%;
        }

        .info-right-label {
            width: 15%;
            font-weight: bold;
            text-align: right;
        }

        .info-right-value {
            width: 20%;
            text-align: right;
        }

        /* Customer Section */
        .customer-section {
            margin: 25px 0;
            border: 1px solid #333;
            padding: 15px;
            background-color: #f9f9f9;
        }

        .customer-title {
            font-weight: bold;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .customer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .customer-table td {
            vertical-align: top;
            padding: 2px 0;
        }

        .customer-left {
            width: 50%;
            padding-right: 20px;
        }

        .customer-right {
            width: 50%;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin: 20px 0;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #333;
            padding: 10px 8px;
            text-align: left;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .items-table td {
            font-size: 11px;
        }

        .col-no {
            width: 6%;
            text-align: center;
        }

        .col-desc {
            width: 38%;
        }

        .col-qty {
            width: 12%;
            text-align: center;
        }

        .col-unit {
            width: 10%;
            text-align: center;
        }

        .col-price {
            width: 17%;
            text-align: right;
        }

        .col-total {
            width: 17%;
            text-align: right;
        }

        /* Summary Section */
        .summary-section {
            width: 350px;
            margin: 20px 0 20px auto;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
        }

        .summary-table td {
            border: 1px solid #333;
            padding: 8px;
        }

        .summary-label {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
            width: 60%;
        }

        .summary-value {
            text-align: right;
            font-weight: bold;
            width: 40%;
        }

        .total-row {
            background-color: #e0e0e0;
        }

        /* Notes Section */
        .notes-section {
            margin: 25px 0;
            border: 1px solid #333;
            padding: 15px;
            min-height: 80px;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .notes-content {
            font-size: 11px;
            line-height: 1.5;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 40px;
            width: 100%;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-box {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 15px;
            border: 1px solid #333;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 50px;
        }

        .signature-name {
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 11px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 15px;
        }

        /* Print Adjustments */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
   <!-- Company Header -->
    <div class="company-header">
        <div class="header-content">
            <div class="logo-section">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/logo.png'))) }}" alt="Company Logo" />
                @else
                    <img src="{{ public_path('images/logo.png') }}" alt="Company Logo" />
                @endif
            </div>
            <div class="company-info">
                <h1 class="company-name">PT. SENTRA ALAM ANANDANA</h1>
                <div class="company-address">
                    Jl. Pelita 1 Ujung No. 36 Labuhan Ratu, Kedaton, Bandar Lampung
                </div>
                <div class="company-contact">
                    Telp: 0822 8258 4263 | Email: <a href="mailto:sales.sentra@sentra-alam.com">sales.sentra@sentra-alam.com</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Separator Line -->
    <div class="separator-line"></div>

    <!-- Document Title -->
    <div class="document-title">Surat Jalan</div>

    <!-- Document Info -->
    <div class="document-info">
        <table class="info-table">
            <tr>
                <td class="info-left">Nomor Surat Jalan</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong>{{ $suratJalan->nomor_surat_jalan }}</strong></td>
                <td class="info-right-label">Tanggal</td>
                <td class="info-colon">:</td>
                <td class="info-right-value"><strong>{{ $suratJalan->tanggal->format('d F Y') }}</strong></td>
            </tr>
            <tr>
                <td class="info-left">Nomor PO Customer</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong>{{ $po->nomor_po }}</strong></td>
                <td class="info-right-label">Tanggal PO</td>
                <td class="info-colon">:</td>
                <td class="info-right-value">{{ $po->tanggal_po ? \Carbon\Carbon::parse($po->tanggal_po)->format('d F Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="info-left">Status PO</td>
                <td class="info-colon">:</td>
                <td class="info-value"><strong>{{ $po->status_po->value ?? 'APPROVED' }}</strong></td>
                <td class="info-right-label">Dibuat Oleh</td>
                <td class="info-colon">:</td>
                <td class="info-right-value">{{ $user->name ?? 'System' }}</td>
            </tr>
        </table>
    </div>

    <!-- Customer Info -->
    <div class="customer-section">
        <div class="customer-title">KEPADA:</div>
        <table class="customer-table">
            <tr>
                <td class="customer-left">
                    <strong>{{ $customer->nama }}</strong><br>
                    @if($customer->email)
                        Email: {{ $customer->email }}<br>
                    @endif
                    @if($customer->telepon)
                        Telp: {{ $customer->telepon }}<br>
                    @endif
                </td>
                <td class="customer-right">
                    <strong>Alamat Pengiriman:</strong><br>
                    {{ $suratJalan->alamat_pengiriman }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-desc">Deskripsi Barang</th>
                <th class="col-qty">Jumlah</th>
                <th class="col-unit">Satuan</th>
                <th class="col-price">Harga Satuan</th>
                <th class="col-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $details = $po->details ?? collect();
            @endphp

            @if($details->count() > 0)
                @foreach($details as $index => $detail)
                <tr>
                    <td class="col-no">{{ $index + 1 }}</td>
                    <td class="col-desc">{{ $detail->deskripsi ?? $detail->nama_produk ?? '-' }}</td>
                    <td class="col-qty">{{ number_format($detail->jumlah ?? 0, 0, ',', '.') }}</td>
                    <td class="col-unit">{{ $detail->satuan ?? 'pcs' }}</td>
                    <td class="col-price">Rp {{ number_format($detail->harga_satuan ?? 0, 0, ',', '.') }}</td>
                    <td class="col-total">Rp {{ number_format($detail->total ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td class="col-no">1</td>
                    <td class="col-desc">-</td>
                    <td class="col-qty">-</td>
                    <td class="col-unit">-</td>
                    <td class="col-price">-</td>
                    <td class="col-total">-</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Summary Section -->
    @if($details->count() > 0)
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Subtotal</td>
                <td class="summary-value">Rp {{ number_format($po->total_sebelum_pajak ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="summary-label">PPN ({{ $po->tax_rate ?? 11 }}%)</td>
                <td class="summary-value">Rp {{ number_format($po->total_pajak ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td class="summary-label">TOTAL</td>
                <td class="summary-value">Rp {{ number_format($po->total ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Notes Section -->
    <div class="notes-section">
        <div class="notes-title">Catatan:</div>
        <div class="notes-content">
            • Barang yang sudah diterima tidak dapat dikembalikan<br>
            • Penerima wajib melakukan pengecekan kondisi barang<br>
            • Laporan kerusakan/kekurangan maksimal 1x24 jam setelah barang diterima<br>
            • Surat jalan ini merupakan bukti sah penyerahan barang
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td class="signature-box">
                    <div class="signature-title">Pengirim</div>
                    <div class="signature-name">{{ $user->name ?? 'Admin' }}</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
