<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $suratJalan->nomor_surat_jalan }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 10px;
            color: #666;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-decoration: underline;
        }

        .info-section {
            margin-bottom: 20px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
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

        .content-section {
            margin: 30px 0;
            border: 1px solid #333;
            min-height: 200px;
            padding: 15px;
        }

        .content-title {
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .signature-section {
            margin-top: 50px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 60px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 50px;
        }

        .signature-name {
            font-size: 10px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .address-box {
            border: 1px solid #333;
            padding: 10px;
            background-color: #f9f9f9;
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
    <div class="title">SURAT JALAN</div>

    <!-- Info Section -->
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
                <td class="label">Nomor PO</td>
                <td class="separator">:</td>
                <td><strong>{{ $po->nomor_po }}</strong></td>
                <td class="label" style="text-align: right;">Tanggal PO</td>
                <td class="separator">:</td>
                <td>{{ $po->tanggal_po ? \Carbon\Carbon::parse($po->tanggal_po)->format('d F Y') : '-' }}</td>
            </tr>
        </table>
    </div>

    <!-- Customer Info -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td class="label">Kepada</td>
                <td class="separator">:</td>
                <td colspan="4">
                    <strong>{{ $customer->nama }}</strong><br>
                    @if($customer->email)
                        Email: {{ $customer->email }}<br>
                    @endif
                    @if($customer->telepon)
                        Telp: {{ $customer->telepon }}<br>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Alamat Pengiriman -->
    <div class="info-section">
        <div class="label">Alamat Pengiriman:</div>
        <div class="address-box">
            {{ $suratJalan->alamat_pengiriman }}
        </div>
    </div>

    <!-- Content Section untuk detail barang -->
    <div class="content-section">
        <div class="content-title">RINCIAN BARANG:</div>
        <div style="margin-top: 15px;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #333;">
                        <th style="text-align: left; padding: 8px; width: 5%;">No</th>
                        <th style="text-align: left; padding: 8px; width: 40%;">Nama Barang</th>
                        <th style="text-align: center; padding: 8px; width: 15%;">Jumlah</th>
                        <th style="text-align: center; padding: 8px; width: 15%;">Satuan</th>
                        <th style="text-align: left; padding: 8px; width: 25%;">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Jika ada detail produk dari PO, tampilkan di sini
                        // Sesuaikan dengan struktur data PO Customer Anda
                        $details = $po->details ?? []; // Asumsi ada relasi details
                    @endphp

                    @if(count($details) > 0)
                        @foreach($details as $index => $detail)
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $index + 1 }}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $detail->nama_produk ?? '-' }}</td>
                            <td style="padding: 8px; text-align: center; border-bottom: 1px solid #eee;">{{ $detail->jumlah ?? '-' }}</td>
                            <td style="padding: 8px; text-align: center; border-bottom: 1px solid #eee;">{{ $detail->satuan ?? '-' }}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $detail->keterangan ?? '-' }}</td>
                        </tr>
                        @endforeach
                    @else
                        <!-- Baris kosong untuk diisi manual -->
                        @for($i = 1; $i <= 10; $i++)
                        <tr>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;">{{ $i }}</td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;"></td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;"></td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;"></td>
                            <td style="padding: 8px; border-bottom: 1px solid #eee;"></td>
                        </tr>
                        @endfor
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Catatan -->
    <div class="info-section">
        <div class="label">Catatan:</div>
        <div style="border: 1px solid #ccc; padding: 10px; min-height: 50px; margin-top: 5px;">
            <!-- Space untuk catatan tambahan -->
        </div>
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-title">Pengirim</div>
            <div class="signature-line"></div>
            <div class="signature-name">{{ $user->name ?? 'Admin' }}</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Kurir/Driver</div>
            <div class="signature-line"></div>
            <div class="signature-name">(.......................)</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Penerima</div>
            <div class="signature-line"></div>
            <div class="signature-name">(.......................)</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Dokumen ini digenerate secara otomatis pada {{ $generated_at }}</div>
        <div>{{ $company['name'] }} - Sistem Manajemen Surat Jalan</div>
    </div>
</body>
</html>
