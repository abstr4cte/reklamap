<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje ogłoszenie zostało usunięte — ReklaMap</title>
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
        .logo-image {
            height: 75px;
            width: auto;
            object-fit: contain;
        }
        .tagline {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin: 8px 0 0 0;
        }
        .content {
            padding: 40px 30px;
        }
        .alert-badge {
            display: inline-block;
            background-color: #fee2e2;
            color: #991b1b;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 20px 0;
        }
        .reason-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 4px;
            padding: 16px 20px;
            margin: 24px 0;
        }
        .reason-label {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 8px 0;
        }
        .reason-text {
            color: #374151;
            line-height: 1.6;
            margin: 0;
            font-size: 15px;
        }
        .ad-title-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
        }
        .ad-title-label {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 4px 0;
        }
        .ad-title-value {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            padding: 13px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 10px 0;
            text-align: center;
        }
        .button-secondary {
            display: inline-block;
            background: #ffffff;
            color: #667eea !important;
            text-decoration: none !important;
            padding: 13px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 10px 0;
            text-align: center;
            border: 2px solid #667eea;
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
            color: #1a0dab;
            text-decoration: underline;
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
            <img src="{{ url('logo-text.png') }}" alt="ReklaMap" class="logo-image" />
            <p class="tagline">Portal powierzchni reklamowych</p>
        </div>

        <div class="content">
            <div class="alert-badge">Ogłoszenie usunięte</div>
            <h2 class="greeting">Twoje ogłoszenie zostało usunięte</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Informujemy, że jedno z Twoich ogłoszeń na platformie ReklaMap zostało usunięte przez administratora serwisu.
            </p>

            <div class="ad-title-box">
                <p class="ad-title-label">Usunięte ogłoszenie</p>
                <p class="ad-title-value">{{ $ad->title }}</p>
            </div>

            <div class="reason-box">
                <p class="reason-label">Powód usunięcia</p>
                <p class="reason-text">{!! nl2br(e($reason)) !!}</p>
            </div>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Jeśli uważasz, że decyzja była błędna lub masz pytania, skontaktuj się z nami — chętnie wyjaśnimy sytuację.
            </p>

            <div class="divider"></div>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 16px 0;">
                Możesz dodać nowe ogłoszenie w dowolnym momencie, pamiętając o przestrzeganiu regulaminu serwisu.
            </p>

            <div style="text-align: center;">
                <a href="{{ config('app.frontend_url') }}/dodaj-powierzchnie-reklamowa" class="button">Dodaj nowe ogłoszenie</a>
            </div>
        </div>

        <div class="footer">
            <p class="footer-text">
                Ta wiadomość została wysłana automatycznie przez platformę ReklaMap.<br>
                Nie odpowiadaj na tego maila — to adres noreply.
            </p>
            <div>
                <a href="{{ config('app.frontend_url') }}" class="footer-link">Strona główna</a>
                <a href="{{ config('app.frontend_url') }}/kontakt" class="footer-link">Kontakt</a>
                <a href="{{ config('app.frontend_url') }}/regulamin" class="footer-link">Regulamin</a>
            </div>
            <p style="color: #9ca3af; font-size: 12px; margin: 20px 0 0 0;">
                © {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </div>
</body>
</html>
