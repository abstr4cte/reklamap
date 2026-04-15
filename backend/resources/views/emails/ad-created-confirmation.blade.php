<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Twoje ogłoszenie zostało opublikowane — ReklaMap</title>
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
        .success-badge {
            display: inline-block;
            background-color: #d1fae5;
            color: #065f46;
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
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            padding: 14px 32px;
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
            <div class="success-badge">✓ Ogłoszenie opublikowane</div>
            <h2 class="greeting">Twoje ogłoszenie jest już online!</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Dziękujemy za dodanie ogłoszenia w serwisie ReklaMap. Cieszymy się, że jesteś z nami!
            </p>

            <div style="text-align: center;">
                <a href="{{ env('FRONTEND_URL', 'https://reklamap.pl') }}{{ $ad->full_url }}" class="button">Zobacz swoje ogłoszenie</a>
            </div>

            <div class="divider"></div>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 16px 0;">
                Chcesz zarządzać swoimi ogłoszeniami? W panelu zarządzania możesz je edytować, dezaktywować lub usunąć.
            </p>

            <div style="text-align: center;">
                <a href="{{ env('FRONTEND_URL', 'https://reklamap.pl') }}/zarzadzaj" class="button-secondary">Zarządzaj ogłoszeniami</a>
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
