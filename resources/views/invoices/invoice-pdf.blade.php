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
            font-size: 10px;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 8mm;
            background: #fff;
        }

        .invoice-wrapper {
            border: 2px solid #000;
            padding: 0;
            background: #fff;
            height: auto;
        }

        .header {
            text-align: center;
            padding: 10px 15px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #000;
            margin-bottom: 3px;
        }

        .invoice-number {
            font-size: 10px;
            color: #000;
            margin-bottom: 12px;
        }

        .company-info-section {
            display: table;
            width: 100%;
        }

        .company-details {
            display: table-cell;
            width: 65%;
            vertical-align: top;
            text-align: left;
            padding-right: 15px;
        }

        .logo-section {
            display: table-cell;
            width: 35%;
            vertical-align: top;
            text-align: center;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        .company-address {
            font-size: 9px;
            color: #000;
            line-height: 1.3;
        }

        .logo-container {
            width: 70px;
            height: 70px;
            border: 2px solid #000;
            border-radius: 50%;
            background: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            overflow: hidden;
        }

        .logo-container img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .logo-text {
            font-size: 8px;
            text-align: center;
            color: #666;
            margin-top: 3px;
        }

        .invoice-details {
            padding: 10px 15px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        .details-section {
            display: table;
            width: 100%;
        }

        .details-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .details-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .detail-group {
            margin-bottom: 8px;
        }

        .detail-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
            color: #000;
        }

        .detail-row {
            margin-bottom: 2px;
            font-size: 9px;
            display: table;
            width: 100%;
        }

        .detail-label {
            display: table-cell;
            width: 65px;
            font-weight: normal;
        }

        .detail-colon {
            display: table-cell;
            width: 10px;
        }

        .detail-value {
            display: table-cell;
        }

        .items-section {
            padding: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .items-table th {
            background: #e8e8e8;
            border: 1px solid #000;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
        }

        .items-table td {
            border: 1px solid #000;
            padding: 8px 6px;
            vertical-align: middle;
        }

        .items-table .desc-col {
            width: 45%;
            text-align: left;
        }

        .items-table .qty-col {
            width: 10%;
            text-align: center;
        }

        .items-table .price-col {
            width: 20%;
            text-align: right;
        }

        .items-table .total-col {
            width: 25%;
            text-align: right;
        }

        .product-name {
            font-weight: bold;
            color: #000;
            margin-bottom: 3px;
        }

        .product-desc {
            font-size: 8px;
            color: #666;
            line-height: 1.2;
        }

        .totals-section {
            padding: 8px 15px;
            text-align: right;
        }

        .totals-table {
            margin-left: auto;
            width: 280px;
            font-size: 9px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 3px 8px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }

        .totals-table .label-col {
            text-align: left;
            font-weight: bold;
            width: 140px;
        }

        .totals-table .amount-col {
            text-align: right;
            width: 140px;
        }

        .total-row {
            border-top: 2px solid #000 !important;
            font-weight: bold;
        }

        .payment-section {
            padding: 10px 15px;
            border-top: 1px solid #000;
            background: #fff;
        }

        .payment-info {
            font-size: 9px;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .bank-info {
            margin: 6px 0;
            font-size: 9px;
        }

        .contact-info {
            margin: 8px 0;
            font-size: 9px;
        }

        .footer {
            text-align: center;
            padding: 8px;
            margin-top: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .signature-section {
            text-align: right;
            padding: 12px 15px;
            border-top: 1px solid #000;
        }

        .signature-block {
            display: inline-block;
            text-align: center;
            width: 160px;
        }

        .signature-line {
            margin-top: 35px;
            border-bottom: 1px solid #000;
            width: 140px;
            margin-bottom: 8px;
            margin-left: auto;
            margin-right: auto;
        }

        .company-footer {
            text-align: center;
            padding: 8px;
            background: #000;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
        }

        @page {
            margin: 8mm;
            size: A4;
        }

        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                font-size: 9px;
            }
            .container {
                max-width: none;
                margin: 0;
                padding: 0;
            }
            .invoice-wrapper {
                height: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="invoice-wrapper">
            <!-- Header -->
            <div class="header">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">No. {{ $invoice->nomor_invoice }}</div>

                <div class="company-info-section">
                    <div class="company-details">
                        <div class="company-name">PT. SENTRA ALAM ANANDANA</div>
                        <div class="company-address">
                            Jl. Pelita 1 Ujung No. 36 Labuhan Ratu<br>
                            Kedaton, Bandar Lampung<br>
                            +62 822 8258 4263 | +62 812 7926 2498<br>
                            sales.sentra@sentra-alam.com
                        </div>
                    </div>
                    <div class="logo-section">
                        <div class="logo-container">
                            <img src="{{ public_path('images/logo.png') }}" alt="Company Logo" />
                        </div>
                        <div class="logo-text">PT. SENTRA ALAM ANANDANA</div>
                    </div>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="invoice-details">
                <div class="details-section">
                    <div class="details-left">
                        <div class="detail-group">
                            <div class="detail-row">
                                <span class="detail-label">DATE</span>
                                <span class="detail-colon">:</span>
                                <span class="detail-value">{{ $invoice->tanggal->format('d F Y') }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">PO Number</span>
                                <span class="detail-colon">:</span>
                                <span class="detail-value">{{ $invoice->poCustomer->nomor_po ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="detail-group">
                            <div class="detail-title">BILL TO</div>
                            @if($invoice->poCustomer && $invoice->poCustomer->customer)
                            <div class="detail-row">
                                <span class="detail-label">Customer</span>
                                <span class="detail-colon">:</span>
                                <span class="detail-value">{{ $invoice->poCustomer->customer->nama }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Address</span>
                                <span class="detail-colon">:</span>
                                <span class="detail-value">{{ $invoice->poCustomer->customer->alamat ?? '-' }}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Phone</span>
                                <span class="detail-colon">:</span>
                                <span class="detail-value">{{ $invoice->poCustomer->customer->telepon ?? '-' }}</span>
                            </div>
                            @else
                            <div class="detail-row">
                                <span class="detail-label">Customer</span>
                                <span class="detail-colon">:</span>
                                <span class="detail-value">-</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="details-right">
                        <div class="detail-group">
                            <div class="detail-title">FOR</div>
                            <div class="detail-row">
                                <span class="detail-value">{{ $invoice->keterangan ?? 'Product Supply & Services' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="items-section">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th class="desc-col">Product Name ▼</th>
                            <th class="qty-col">Qty ▼</th>
                            <th class="price-col">Unit Price ▼</th>
                            <th class="total-col">Total ▼</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($invoice->poCustomer && $invoice->poCustomer->details && $invoice->poCustomer->details->count() > 0)
                            @foreach($invoice->poCustomer->details as $detail)
                            <tr>
                                <td class="desc-col">
                                    <div class="product-name">{{ $detail->nama_produk ?? $detail->deskripsi }}</div>
                                    @if($detail->deskripsi && $detail->nama_produk)
                                        <div class="product-desc">{{ $detail->deskripsi }}</div>
                                    @endif
                                </td>
                                <td class="qty-col">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                                <td class="price-col">{{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                                <td class="total-col">{{ number_format($detail->total, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        @else
                            <tr>
                                <td class="desc-col">
                                    <div class="product-name">Product/Service as per PO {{ $invoice->poCustomer->nomor_po ?? '' }}</div>
                                </td>
                                <td class="qty-col">1</td>
                                <td class="price-col">{{ number_format($invoice->total_sebelum_pajak, 0, ',', '.') }}</td>
                                <td class="total-col">{{ number_format($invoice->total_sebelum_pajak, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="totals-section">
                    <table class="totals-table">
                        <tr>
                            <td class="label-col">SUBTOTAL</td>
                            <td class="amount-col">IDR {{ number_format($invoice->total_sebelum_pajak, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">TAX RATE 11%</td>
                            <td class="amount-col">IDR {{ number_format($invoice->total_pajak, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="label-col">OTHER</td>
                            <td class="amount-col">: ........................</td>
                        </tr>
                        <tr class="total-row">
                            <td class="label-col">TOTAL</td>
                            <td class="amount-col">IDR {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Payment Information -->
            <div class="payment-section">
                <div class="payment-info">
                    <strong>Make all checks payable to PT. SENTRA ALAM ANANDANA</strong>
                </div>

                <div class="bank-info">
                    @if($invoice->rekeningBank)
                    <strong>Account No:</strong> {{ $invoice->rekeningBank->nomor_rekening }} {{ $invoice->rekeningBank->nama_pemilik }}<br>
                    <strong>Account Bank:</strong> {{ $invoice->rekeningBank->nama_bank }}<br>
                    <strong>Account Name:</strong> {{ $invoice->rekeningBank->nama_pemilik }}
                    @else
                    <strong>Account No:</strong> 0098 0100 2824 560 PT. Sentra Alam Anandana<br>
                    <strong>Account Bank:</strong> Bank Rakyat Indonesia<br>
                    <strong>Account Name:</strong> PT. Sentra Alam Anandana
                    @endif
                </div>

                <div class="contact-info">
                    <p>If you have any questions concerning this invoice, use the following contact information:</p>
                    <p>+62 822 8258 4263 / +62 812 7926 2498, sales.sentra@sentra-alam.com</p>
                </div>

                <div class="footer">
                    <p>THANK YOU FOR YOUR BUSINESS!</p>
                </div>

                <!-- Signature -->
                <div class="signature-section">
                    <div class="signature-block">
                        <p>Best Regards,</p>
                        <div class="signature-line"></div>
                        <p><strong>PT. SENTRA ALAM ANANDANA</strong></p>
                    </div>
                </div>
            </div>

            <!-- Company Footer -->
            <div class="company-footer">
                PT. SENTRA ALAM ANANDANA
            </div>
        </div>
    </div>
</body>
</html>
