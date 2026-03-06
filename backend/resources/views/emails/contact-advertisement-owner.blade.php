<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowa wiadomość z ReklaMap</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
        }
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
        }
        .logo-image {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 15px;
        }
        .logo {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            letter-spacing: -0.5px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin: 8px 0 0 0;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 20px 0;
        }
        .message-box {
            background-color: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
        }
        .message-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .message-text {
            color: #1f2937;
            line-height: 1.6;
            margin: 0;
            white-space: pre-wrap;
        }
        .sender-info {
            background-color: #eff6ff;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .sender-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .sender-email {
            font-size: 16px;
            font-weight: 600;
            color: #1e40af;
            margin: 0;
        }
        .ad-info {
            background-color: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin: 30px 0;
        }
        .ad-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 10px 0;
        }
        .ad-id {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            text-align: center;
        }
        .button:hover {
            opacity: 0.9;
            color: #ffffff !important;
        }
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            margin: 0 0 15px 0;
        }
        .footer-links {
            margin: 15px 0;
        }
        .footer-link {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 30px 0;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 30px 20px;
            }
            .header {
                padding: 30px 20px;
            }
            .greeting {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-content">
                <img src="{{ asset('logo.png') }}" alt="ReklaMap" class="logo-image" />
                <h1 class="logo">ReklaMap</h1>
            </div>
            <p class="tagline">Portal powierzchni reklamowych</p>
        </div>

        <!-- Content -->
        <div class="content">
            <h2 class="greeting">Witaj! 👋</h2>
            
            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Otrzymałeś nową wiadomość dotyczącą Twojego ogłoszenia na platformie ReklaMap.
            </p>

            <!-- Advertisement Info -->
            <div class="ad-info">
                <p class="ad-title">{{ $advertisementTitle }}</p>
                <p class="ad-id">ID ogłoszenia: #{{ $advertisementId }}</p>
            </div>

            <!-- Sender Info -->
            <div class="sender-info">
                <div class="sender-label">Wiadomość od:</div>
                <p class="sender-email">{{ $senderEmail }}</p>
            </div>

            <!-- Message -->
            <div class="message-box">
                <div class="message-label">Treść wiadomości:</div>
                <p class="message-text">{{ $senderMessage }}</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ $advertisementUrl }}" class="button">Zobacz ogłoszenie</a>
            </div>

            <div class="divider"></div>

            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                <strong>Jak odpowiedzieć?</strong><br>
                Wystarczy, że odpowiesz na tego maila - Twoja odpowiedź trafi bezpośrednio do osoby zainteresowanej ({{ $senderEmail }}).
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Ta wiadomość została wysłana automatycznie przez platformę ReklaMap.<br>
                Nie odpowiadaj na tego maila, jeśli chcesz skontaktować się z nami.
            </p>
            
            <div class="footer-links">
                <a href="{{ config('app.url') }}" class="footer-link">Strona główna</a>
                <a href="{{ config('app.url') }}/kontakt" class="footer-link">Kontakt</a>
                <a href="{{ config('app.url') }}/regulamin" class="footer-link">Regulamin</a>
            </div>

            <p style="color: #9ca3af; font-size: 12px; margin: 20px 0 0 0;">
                © {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </div>
</body>
</html>
