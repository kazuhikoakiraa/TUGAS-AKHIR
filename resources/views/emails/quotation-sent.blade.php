<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation Sent</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .quotation-info {
            background-color: #f1f5f9;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .quotation-info h3 {
            color: #1e40af;
            margin-top: 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #4b5563;
        }
        .price {
            font-size: 1.2em;
            font-weight: bold;
            color: #059669;
        }
        .description {
            background-color: #f9fafb;
            padding: 15px;
            border-left: 4px solid #3b82f6;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.9em;
        }
        .button {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 Quotation Sent</h1>
        <p>Thank you for your interest in our services</p>
    </div>

    <div class="content">
        <h2>Dear {{ $customer->nama }},</h2>

        <p>We are pleased to provide you with the following quotation for your consideration:</p>

        <div class="quotation-info">
            <h3>Quotation Details</h3>
            <div class="info-row">
                <span class="info-label">Quotation Number:</span>
                <span>{{ $quotation->nomor_penawaran }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span>{{ $quotation->tanggal->format('d F Y') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Sales Person:</span>
                <span>{{ $quotation->user->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Price:</span>
                <span class="price">IDR {{ number_format($quotation->harga, 0, ',', '.') }}</span>
            </div>
        </div>

        @if($quotation->deskripsi)
        <div class="description">
            <h4>Description:</h4>
            <p>{{ $quotation->deskripsi }}</p>
        </div>
        @endif

        <p>Please find the detailed quotation attached as a PDF document. This quotation is valid for 30 days from the date issued.</p>

        <p>If you have any questions or would like to discuss this quotation further, please don't hesitate to contact us.</p>

        <div style="text-align: center;">
            <strong>We look forward to working with you!</strong>
        </div>
    </div>

    <div class="footer">
        <p>This is an automated email. Please do not reply to this email address.</p>
        <p>If you need assistance, please contact our sales team.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>
</html>
