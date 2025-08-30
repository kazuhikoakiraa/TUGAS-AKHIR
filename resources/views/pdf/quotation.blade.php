<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->nomor_penawaran }}</title>
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

        /* Document Info */
        .document-info {
            width: 100%;
            margin-bottom: 25px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .info-left {
            width: 60%;
            padding-right: 30px;
        }

        .info-right {
            width: 40%;
            text-align: right;
        }

        .customer-name {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .customer-address {
            font-size: 11px;
            line-height: 1.4;
            margin-bottom: 12px;
            color: #444;
        }

        .label-bold {
            font-weight: bold;
        }

        /* Subject */
        .subject-section {
            margin-bottom: 20px;
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            border: 1px solid #333;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #333;
            padding: 10px 8px;
            font-size: 11px;
        }

        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
            color: #333;
        }

        .col-no {
            width: 6%;
            text-align: center;
        }

        .col-item {
            width: 38%;
            text-align: left;
        }

        .col-qty {
            width: 10%;
            text-align: center;
        }

        .col-unit {
            width: 10%;
            text-align: center;
        }

        .col-price {
            width: 18%;
            text-align: right;
        }

        .col-total {
            width: 18%;
            text-align: right;
        }

        .item-description {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
            font-style: italic;
        }

        /* Terms Section */
        .terms-section {
            margin-bottom: 35px;
        }

        .terms-title {
            font-weight: bold;
            margin-bottom: 12px;
            font-size: 13px;
        }

        .terms-content {
            font-size: 11px;
            line-height: 1.5;
        }

        .terms-item {
            margin: 5px 0;
            padding-left: 0;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 40px;
        }

        .signature-text {
            margin-bottom: 60px;
            font-size: 12px;
        }

        .company-signature {
            font-weight: bold;
            font-size: 12px;
        }

        /* Utilities */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .money {
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }

        /* Print Adjustments */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .company-header,
            .separator-line,
            .document-info,
            .items-table,
            .terms-section,
            .signature-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- Company Header -->
    <div class="company-header">
        <div class="header-content">
            <div class="logo-section">
                <img src="{{ public_path('images/logo.png') }}" alt="Company Logo" />
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

    <!-- Document Information -->
    <div class="document-info">
        <table class="info-table">
            <tr>
                <td class="info-left">
                    <div class="label-bold">Kepada:</div>
                    <div class="customer-name">{{ $quotation->customer->nama }}</div>
                    <div class="customer-address">{{ $quotation->customer->alamat }}</div>
                    <div><span class="label-bold">Attention:</span> {{ $quotation->customer->nama }}</div>
                </td>
                <td class="info-right">
                    <div><span class="label-bold">Tanggal:</span> {{ $quotation->tanggal->format('d F Y') }}</div>
                    <div style="margin-top: 8px;"><span class="label-bold">No. Penawaran:</span> {{ $quotation->nomor_penawaran }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Subject -->
    <div class="subject-section">
        <span class="label-bold">Hal:</span> {{ $quotation->subject ?? 'Penawaran Harga Produk/Jasa' }}
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-item">Nama Item</th>
                <th class="col-qty">Qty</th>
                <th class="col-unit">Satuan</th>
                <th class="col-price">Harga Satuan<br>(Rp)</th>
                <th class="col-total">Jumlah<br>(Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->details as $index => $detail)
            <tr>
                <td class="col-no text-center">{{ $index + 1 }}</td>
                <td class="col-item">
                    {{ $detail->nama_produk ?: ($detail->product->name ?? 'Nama Produk') }}
                    @if($detail->deskripsi)
                        <div class="item-description">{{ $detail->deskripsi }}</div>
                    @endif
                </td>
                <td class="col-qty text-center">{{ number_format($detail->jumlah, 0) }}</td>
                <td class="col-unit text-center">{{ $detail->satuan }}</td>
                <td class="col-price text-right money">{{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                <td class="col-total text-right money">{{ number_format($detail->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Terms & Conditions -->
    <div class="terms-section">
        <div class="terms-title">Syarat & Ketentuan:</div>
        <div class="terms-content">
            @if($quotation->terms_conditions)
                @foreach(explode("\n", $quotation->terms_conditions) as $index => $line)
                    @if(trim($line))
                        <div class="terms-item">{{ ($index + 1) }}. {{ trim($line) }}</div>
                    @endif
                @endforeach
            @else
                <div class="terms-item">1. Harga belum termasuk PPN {{ $quotation->tax_rate ?? 11 }}%</div>
                <div class="terms-item">2. Tempat pengiriman: {{ $quotation->customer->nama }}</div>
                <div class="terms-item">3. Pembayaran: Transfer Bank BRI No. Rek: 0098 0100 2824 560 a.n PT. Sentra Alam Anandana</div>
                <div class="terms-item">4. Waktu pengiriman: 4-7 hari kerja setelah pembayaran diterima</div>
            @endif
        </div>
    </div>

    <!-- Signature -->
    <div class="signature-section">
        <div class="signature-text">Hormat kami,</div>
        <div class="company-signature">PT. SENTRA ALAM ANANDANA</div>
    </div>
</body>
</html>
