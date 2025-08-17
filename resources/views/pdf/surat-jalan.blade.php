<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan - {{ $suratJalan->nomor_surat_jalan }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 11px;
            color: #666;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info-table td {
            padding: 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 150px;
        }

        .separator {
            width: 10px;
            text-align: center;
        }

        .customer-section {
            margin: 20px 0;
            border: 1px solid #333;
            padding: 15px;
            background-color: #f9f9f9;
        }

        .customer-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            margin: 20px 0;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }

        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }

        .item-no {
            width: 40px;
            text-align: center;
        }

        .item-desc {
            width: 35%;
        }

        .item-qty {
            width: 80px;
            text-align: center;
        }

        .item-unit {
            width: 60px;
            text-align: center;
        }

        .item-price {
            width: 100px;
            text-align: right;
        }

        .item-total {
            width: 100px;
            text-align: right;
        }

        .summary-section {
            float: right;
            width: 300px;
            margin: 20px 0;
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
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: right;
            width: 60%;
        }

        .summary-value {
            text-align: right;
            font-weight: bold;
            width: 40%;
        }

        .notes-section {
            clear: both;
            margin: 30px 0;
            border: 1px solid #333;
            padding: 15px;
            min-height: 80px;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .signature-section {
            margin-top: 50px;
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
            padding: 20px 10px;
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

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 15px;
        }

        @media print {
            body { margin: 0; }
            .page-break { page-break-before: always; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ $company['name'] }}</div>
        <div class="company-info">
            {{ $company['address'] }}<br>
            Telp: {{ $company['phone'] }} | Email: {{ $company['email'] }}
        </div>
    </div>

    <!-- Title -->
    <div class="title">Surat Jalan</div>

    <!-- Document Info -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Nomor Surat Jalan</td>
                <td class="separator">:</td>
                <td><strong>{{ $suratJalan->nomor_surat_jalan }}</strong></td>
                <td class="label" style="text-align: right;">Tanggal</td>
                <td class="separator">:</td>
                <td><strong>{{ $suratJalan->tanggal->format('d F Y') }}</strong></td>
            </tr>
            <tr>
                <td class="label">Nomor PO Customer</td>
                <td class="separator">:</td>
                <td><strong>{{ $po->nomor_po }}</strong></td>
                <td class="label" style="text-align: right;">Tanggal PO</td>
                <td class="separator">:</td>
                <td>{{ $po->tanggal_po ? \Carbon\Carbon::parse($po->tanggal_po)->format('d F Y') : '-' }}</td>
            </tr>
            <tr>
                <td class="label">Status PO</td>
                <td class="separator">:</td>
                <td><strong>{{ $po->status_po->value ?? 'APPROVED' }}</strong></td>
                <td class="label" style="text-align: right;">Dibuat Oleh</td>
                <td class="separator">:</td>
                <td>{{ $user->name ?? 'System' }}</td>
            </tr>
        </table>
    </div>

    <!-- Customer Info -->
    <div class="customer-section">
        <div class="customer-title">KEPADA:</div>
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <strong>{{ $customer->nama }}</strong><br>
                    @if($customer->email)
                        Email: {{ $customer->email }}<br>
                    @endif
                    @if($customer->telepon)
                        Telp: {{ $customer->telepon }}<br>
                    @endif
                </td>
                <td style="width: 50%; vertical-align: top;">
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
                <th class="item-no">No</th>
                <th class="item-desc">Deskripsi Barang</th>
                <th class="item-qty">Jumlah</th>
                <th class="item-unit">Satuan</th>
                <th class="item-price">Harga Satuan</th>
                <th class="item-total">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $details = $po->details ?? collect();
                $totalRows = max(1, $details->count());
            @endphp

            @if($details->count() > 0)
                @foreach($details as $index => $detail)
                <tr>
                    <td class="item-no">{{ $index + 1 }}</td>
                    <td class="item-desc">{{ $detail->deskripsi ?? $detail->nama_produk ?? '-' }}</td>
                    <td class="item-qty">{{ number_format($detail->jumlah ?? 0, 0, ',', '.') }}</td>
                    <td class="item-unit">{{ $detail->satuan ?? 'pcs' }}</td>
                    <td class="item-price">Rp {{ number_format($detail->harga_satuan ?? 0, 0, ',', '.') }}</td>
                    <td class="item-total">Rp {{ number_format($detail->total ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach

                @for($i = $details->count(); $i < $totalRows; $i++)
                <tr>
                    <td class="item-no">{{ $i + 1 }}</td>
                    <td class="item-desc"></td>
                    <td class="item-qty"></td>
                    <td class="item-unit"></td>
                    <td class="item-price"></td>
                    <td class="item-total"></td>
                </tr>
                @endfor
            @else
                @for($i = 1; $i <= $totalRows; $i++)
                <tr>
                    <td class="item-no">{{ $i }}</td>
                    <td class="item-desc"></td>
                    <td class="item-qty"></td>
                    <td class="item-unit"></td>
                    <td class="item-price"></td>
                    <td class="item-total"></td>
                </tr>
                @endfor
            @endif
        </tbody>
    </table>

    <!-- Summary Section -->
    @if($details->count() > 0)
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="summary-label">Subtotal:</td>
                <td class="summary-value">Rp {{ number_format($po->total_sebelum_pajak ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="summary-label">PPN (11%):</td>
                <td class="summary-value">Rp {{ number_format($po->total_pajak ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #e0e0e0;">
                <td class="summary-label">TOTAL:</td>
                <td class="summary-value">Rp {{ number_format($po->total ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Notes Section -->
    <div class="notes-section">
        <div class="notes-title">Catatan:</div>
        <div style="margin-top: 10px;">
            • Barang yang sudah diterima tidak dapat dikembalikan<br>
            • Penerima wajib melakukan pengecekan kondisi barang<br>
            • Laporan kerusakan/kekurangan maksimal 1x24 jam<br>
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
                <td class="signature-box">
                    <div class="signature-title">Kurir</div>
                    <div class="signature-name">(.......................)</div>
                </td>
                <td class="signature-box">
                    <div class="signature-title">Penerima</div>
                    <div class="signature-name">(.......................)</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div><strong>{{ $company['name'] }}</strong></div>
        <div>Dokumen digenerate pada {{ $generated_at }}</div>
        <div style="font-size: 9px; margin-top: 5px;">
            {{ $suratJalan->nomor_surat_jalan }} - Sistem Manajemen Surat Jalan
        </div>
    </div>
</body>
</html>
