<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - PT. Sentra Alam Anandana</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            color: white;
            padding: 30px 40px;
            text-align: center;
        }
        .logo {
            max-width: 180px;
            height: 60px;
            margin-bottom: 15px;
            background-color: white;
            padding: 10px;
            border-radius: 8px;
            object-fit: contain;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .content {
            padding: 40px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            margin-bottom: 20px;
            color: #555555;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            display: inline-block;
            background: linear-gradient(135deg, #059669, #10b981);
            color: #ffffff !important;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            border: none;
            cursor: pointer;
        }
        .verify-button:hover {
            background: linear-gradient(135deg, #047857, #059669);
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
            text-decoration: none;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #92400e;
        }
        .info {
            background-color: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #1e40af;
        }
        .url-section {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .url-text {
            font-size: 14px;
            color: #666666;
            margin-bottom: 10px;
        }
        .url-link {
            word-break: break-all;
            color: #1e40af;
            font-family: monospace;
            font-size: 12px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 25px 40px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .footer-text {
            font-size: 14px;
            color: #666666;
            margin: 0;
        }
        .company-info {
            font-size: 12px;
            color: #999999;
            margin-top: 15px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 0;
            }
            .content, .header, .footer {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <!-- Logo perusahaan dengan fallback -->
            <div style="margin-bottom: 15px;">
                <img src="{{ asset('images/logo.png') }}"
                     alt="PT. Sentra Alam Anandana"
                     class="logo"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <div style="display: none; background-color: white; color: #1e40af; padding: 15px; border-radius: 8px; font-weight: bold; font-size: 16px; max-width: 180px; margin: 0 auto;">
                    PT. SENTRA ALAM<br>ANANDANA
                </div>
            </div>
            <h1 class="company-name">PT. Sentra Alam Anandana</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Selamat datang, {{ $user->name }}!
            </div>

            <div class="message">
                Terima kasih telah mendaftar di sistem PT. Sentra Alam Anandana. Untuk melengkapi proses pendaftaran dan mengaktifkan akun Anda, silakan verifikasi alamat email Anda.
            </div>

            <div class="message">
                Klik tombol di bawah ini untuk memverifikasi alamat email dan langsung masuk ke sistem:
            </div>

            <div class="button-container">
                <a href="{{ $url }}" class="verify-button">Verifikasi & Masuk ke Sistem</a>
            </div>

            <div class="warning">
                <strong>Penting:</strong> Link verifikasi ini akan kedaluwarsa dalam {{ $expireMinutes }} menit.
                Jika Anda tidak membuat akun ini, tidak ada tindakan lebih lanjut yang diperlukan. Abaikan email ini.
            </div>

            <div class="info">
                <strong>Apa yang terjadi setelah verifikasi?</strong><br>
                Setelah Anda mengklik tombol verifikasi, email Anda akan diverifikasi dan Anda akan langsung masuk ke sistem tanpa perlu login manual.
            </div>

            <div class="url-section">
                <div class="url-text">
                    Jika tombol di atas tidak berfungsi, salin dan tempel URL berikut ke browser Anda:
                </div>
                <div class="url-link">{{ $url }}</div>
            </div>
        </div>

        <div class="footer">
            <p class="footer-text">
                <strong>Salam hangat,</strong><br>
                Tim IT PT. Sentra Alam Anandana
            </p>
            <div class="company-info">
                Email ini dikirim secara otomatis, mohon tidak membalas email ini.<br>
                Jika Anda memerlukan bantuan, silakan hubungi tim support kami.<br>
                © {{ date('Y') }} PT. Sentra Alam Anandana. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
