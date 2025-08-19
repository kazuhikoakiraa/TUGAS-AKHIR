<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->nomor_penawaran }}</title>
    <style>
        @page {
            margin: 20mm;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        /* Company Header/Kop */
        .company-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .company-left {
            display: flex;
            align-items: flex-start;
        }

        .company-logo {
            margin-right: 15px;
        }

        .company-logo img {
            max-width: 80px;
            max-height: 80px;
        }

        .company-info {
            flex: 1;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #4a90e2;
            margin: 0 0 5px 0;
            letter-spacing: 1px;
        }

        .company-address {
            font-size: 10px;
            color: #000;
            line-height: 1.4;
            margin-bottom: 3px;
        }

        .company-contact {
            font-size: 10px;
            color: #000;
        }

        .company-contact a {
            color: #4a90e2;
            text-decoration: none;
        }

        /* Separator Line */
        .separator-line {
            border-bottom: 3px solid #4a90e2;
            margin-bottom: 20px;
        }

        /* Header Section */
        .header {
            margin-bottom: 20px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .to-section {
            flex: 1;
        }

        .date-section {
            text-align: right;
            min-width: 200px;
        }

        .company-info-customer {
            margin-bottom: 15px;
        }

        .company-name-customer {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .company-address-customer {
            font-size: 11px;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .attn {
            margin-bottom: 15px;
        }

        /* Subject Section */
        .subject {
            margin-bottom: 20px;
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #000;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: left;
            font-size: 11px;
        }

        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .items-table .col-no {
            width: 5%;
            text-align: center;
        }

        .items-table .col-item {
            width: 35%;
        }

        .items-table .col-qty {
            width: 8%;
            text-align: center;
        }

        .items-table .col-unit {
            width: 8%;
            text-align: center;
        }

        .items-table .col-price {
            width: 22%;
            text-align: right;
        }

        .items-table .col-total {
            width: 22%;
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* Terms Section */
        .terms-section {
            margin-bottom: 30px;
        }

        .terms-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .terms-content {
            font-size: 11px;
            line-height: 1.5;
        }

        .terms-content p {
            margin: 3px 0;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 30px;
            text-align: left;
        }

        .signature-text {
            margin-bottom: 50px;
        }

        .company-signature {
            font-weight: bold;
        }

        /* Utilities */
        .money {
            font-family: Arial, sans-serif;
        }

        /* Print Styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Company Header/Kop - Baris 1 -->
    <div class="company-header">
        <div class="company-left">
            <div class="company-logo">
                <img src="{{ public_path('images/logo.png') }}" alt="Company Logo" />
            </div>
            <div class="company-info">
                <h1 class="company-name">PT. SENTRA ALAM ANANDANA</h1>
                <div class="company-address">
                    Jl. Pelita 1 Ujung No. 36 Labuhan Ratu, Kedaton, Bandar Lampung
                </div>
                <div class="company-contact">
                    0822 8258 4263, email : <a href="mailto:sales.sentra@sentra-alam.com">sales.sentra@sentra-alam.com</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Separator Line - Baris 2 -->
    <div class="separator-line"></div>

    <!-- Header - Baris 3 -->
    <div class="header">
        <div class="header-row">
            <div class="to-section">
                <strong>To :</strong><br>
                <div class="company-info-customer">
                    <div class="company-name-customer">{{ $quotation->customer->nama }}</div>
                    <div class="company-address-customer">{{ $quotation->customer->alamat }}</div>
                </div>
                <div class="attn">
                    <strong>Attn :</strong> {{ $quotation->customer->nama }}
                </div>
            </div>
            <div class="date-section">
                <strong>Quo date :</strong> {{ $quotation->tanggal->format('d F Y') }}<br>
                <strong>Qtn No :</strong> {{ $quotation->nomor_penawaran }}
            </div>
        </div>
    </div>

    <!-- Subject -->
    <div class="subject">
        <strong>Subject :</strong> {{ $quotation->subject ?? 'Quotation for Products/Services' }}
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="col-no">No</th>
                <th class="col-item">ITEM<br>Pesanan</th>
                <th class="col-qty">Qty</th>
                <th class="col-unit">Unit</th>
                <th class="col-price">Unit Price<br>Rp.</th>
                <th class="col-total">Total<br>Rp.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->details as $index => $detail)
            <tr>
                <td class="col-no text-center">{{ $index + 1 }}.</td>
                <td class="col-item">
                    {{ $detail->nama_produk ?: ($detail->product->name ?? 'Product Name') }}
                    @if($detail->deskripsi)
                        <br><span style="font-size: 10px; color: #666;">{{ $detail->deskripsi }}</span>
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
        <div class="terms-title">Terms & Conditions :</div>
        <div class="terms-content">
            @if($quotation->terms_conditions)
                @foreach(explode("\n", $quotation->terms_conditions) as $index => $line)
                    @if(trim($line))
                        <p>{{ ($index + 1) }}. {{ trim($line) }}</p>
                    @endif
                @endforeach
            @else
                <p>1. Harga belum termasuk PPN {{ $quotation->tax_rate ?? 11 }}%</p>
                <p>2. Tempat Pengiriman : {{ $quotation->customer->nama }}</p>
                <p>3. Metode Pembayaran : Cash / Tunai,<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;Acc No : 0098 0100 2824 560 (BRI)<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;Sentra Alam Anandana</p>
                <p>4. Delivery Time : 4 – 7 hari setelah pembayaran</p>
            @endif
        </div>
    </div>

    <!-- Signature -->
    <div class="signature-section">
        <div class="signature-text">Salam Hormat,</div>
        <div class="company-signature">PT. SENTRA ALAM ANANDANA</div>
    </div>
</body>
</html>
