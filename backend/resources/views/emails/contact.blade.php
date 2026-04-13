<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowa wiadomość z formularza kontaktowego — ReklaMap</title>
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
        }
        .logo-image {
            height: 50px;
            width: auto;
            object-fit: contain;
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #111827;
            margin: 0;
            letter-spacing: -0.5px;
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
        .info-section {
            background-color: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin: 24px 0;
        }
        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin: 14px 0 4px 0;
        }
        .info-label:first-child {
            margin-top: 0;
        }
        .info-value {
            color: #1f2937;
            font-size: 15px;
            word-break: break-word;
            margin: 0;
        }
        .info-value a {
            color: #667eea;
            text-decoration: none;
        }
        .message-box {
            background-color: #f9fafb;
            border-left: 4px solid #d1d5db;
            padding: 20px;
            border-radius: 8px;
            margin: 24px 0;
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
            color: #374151;
            line-height: 1.6;
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 15px;
        }
        .divider {
            height: 1px;
            background-color: #e5e7eb;
            margin: 30px 0;
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
        .footer-link {
            color: #667eea;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        @media only screen and (max-width: 600px) {
            .content { padding: 30px 20px; }
            .header { padding: 30px 20px; }
            .greeting { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <div class="header-content">
                <img src="{{ asset('logo-text.png') }}" alt="ReklaMap" class="logo-image" />
                
            </div>
            <p class="tagline">Portal powierzchni reklamowych</p>
        </div>

        <div class="content">
            <h2 class="greeting">Nowa wiadomość z formularza kontaktowego</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Otrzymałeś nową wiadomość ze strony ReklaMap.
            </p>

            <div class="info-section">
                <div class="info-label">Temat</div>
                <p class="info-value">{{ $subject }}</p>

                <div class="info-label">Imię i nazwisko</div>
                <p class="info-value">{{ $name }}</p>

                <div class="info-label">Email</div>
                <p class="info-value"><a href="mailto:{{ $email }}">{{ $email }}</a></p>

                @if($phone)
                <div class="info-label">Telefon</div>
                <p class="info-value">{{ $phone }}</p>
                @endif
            </div>

            <div class="message-box">
                <div class="message-label">Treść wiadomości</div>
                <p class="message-text">{{ $message }}</p>
            </div>

            <div class="divider"></div>

            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                <strong>Aby odpowiedzieć</strong> — użyj przycisku "Odpowiedz" w swoim kliencie poczty. Odpowiedź trafi bezpośrednio do nadawcy.
            </p>
        </div>

        <div class="footer">
            <p class="footer-text">
                Ta wiadomość została wysłana automatycznie z formularza kontaktowego ReklaMap.
            </p>
            <div>
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
