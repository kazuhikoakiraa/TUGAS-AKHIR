<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation {{ $quotation->nomor_penawaran }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 28px;
            margin: 0 0 10px 0;
        }
        .header p {
            color: #6b7280;
            margin: 0;
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }
        .quotation-details {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .quotation-details .left,
        .quotation-details .right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .quotation-details .right {
            text-align: right;
        }
        .info-box {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 14px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #4b5563;
            display: inline-block;
            width: 120px;
        }
        .quotation-number {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }
        .description-section {
            margin: 30px 0;
        }
        .description-section h3 {
            background-color: #2563eb;
            color: white;
            padding: 10px;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .description-content {
            padding: 15px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            min-height: 100px;
        }
        .price-section {
            margin-top: 30px;
            text-align: right;
        }
        .price-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .price-table td {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
        }
        .price-table .label {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .price-table .total {
            background-color: #2563eb;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        .terms {
            margin-top: 40px;
            padding: 20px;
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .terms h4 {
            color: #92400e;
            margin: 0 0 10px 0;
        }
        .terms p {
            margin: 5px 0;
            color: #78350f;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 10px;
        }
        .signature {
            margin-top: 50px;
        }
        .signature-box {
            display: table;
            width: 100%;
        }
        .signature-left,
        .signature-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>QUOTATION</h1>
        <p>{{ config('app.name') }} - Business Quotation</p>
    </div>

    <div class="company-info">
        <h3>{{ config('app.name') }}</h3>
        <p>Your Business Address Here</p>
        <p>Phone: +62 XXX XXX XXXX | Email: info@company.com</p>
    </div>

    <div class="quotation-details">
        <div class="left">
            <div class="info-box">
                <h3>Bill To:</h3>
                <div><strong>{{ $customer->nama }}</strong></div>
                <div>{{ $customer->alamat }}</div>
                <div>Phone: {{ $customer->telepon }}</div>
                <div>Email: {{ $customer->email }}</div>
            </div>
        </div>

        <div class="right">
            <div class="info-box">
                <h3>Quotation Details:</h3>
                <div class="info-row">
                    <span class="info-label">Number:</span>
                    <span class="quotation-number">{{ $quotation->nomor_penawaran }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span>{{ $quotation->tanggal->format('d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Valid Until:</span>
                    <span>{{ $quotation->tanggal->addDays(30)->format('d F Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Sales Person:</span>
                    <span>{{ $quotation->user->name }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="description-section">
        <h3>Description of Products/Services</h3>
        <div class="description-content">
            {{ $quotation->deskripsi ?: 'No description provided.' }}
        </div>
    </div>

    <div class="price-section">
        <table class="price-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td>IDR {{ number_format($quotation->harga, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Tax (0%):</td>
                <td>IDR 0</td>
            </tr>
            <tr>
                <td class="total">TOTAL:</td>
                <td class="total">IDR {{ number_format($quotation->harga, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="terms">
        <h4>Terms & Conditions:</h4>
        <p>• This quotation is valid for 30 days from the date of issue.</p>
        <p>• Prices are subject to change without prior notice.</p>
        <p>• Payment terms: Net 30 days from invoice date.</p>
        <p>• All prices are in Indonesian Rupiah (IDR).</p>
        <p>• Additional terms and conditions may apply.</p>
    </div>

    <div class="signature">
        <div class="signature-box">
            <div class="signature-left">
                <div><strong>Customer Acceptance:</strong></div>
                <div class="signature-line">
                    <div>{{ $customer->nama }}</div>
                    <div>Date: _______________</div>
                </div>
            </div>
            <div class="signature-right">
                <div><strong>Authorized Signature:</strong></div>
                <div class="signature-line">
                    <div>{{ $quotation->user->name }}</div>
                    <div>Date: {{ $quotation->tanggal->format('d F Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Thank you for considering our services. We look forward to working with you!</p>
        <p>Generated on {{ now()->format('d F Y H:i') }}</p>
    </div>
</body>
</html>
