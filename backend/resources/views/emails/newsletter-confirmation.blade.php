<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zapisano do newslettera — ReklaMap</title>
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
        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 20px 0;
        }
        .check-list {
            background: #f8faff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .check-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 15px;
            color: #374151;
            line-height: 1.5;
        }
        .check-item:last-child {
            margin-bottom: 0;
        }
        .check-icon {
            color: #10b981;
            font-size: 18px;
            flex-shrink: 0;
            margin-top: 1px;
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
            text-align: center;
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
            <img src="{{ asset('logo-text.png') }}" alt="ReklaMap" class="logo-image" />
            <p class="tagline">Portal powierzchni reklamowych</p>
        </div>

        <div class="content">
            <h2 class="greeting">Witamy w newsletterze ReklaMap!</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Twój adres e-mail został zapisany. Od teraz będziesz pierwszą osobą, która dowie się o nowościach na platformie.
            </p>

            <div class="check-list">
                <div class="check-item">
                    <span class="check-icon">✓</span>
                    <span>Nowe ogłoszenia powierzchni reklamowych z całej Polski</span>
                </div>
                <div class="check-item">
                    <span class="check-icon">✓</span>
                    <span>Artykuły i porady dotyczące reklamy zewnętrznej</span>
                </div>
                <div class="check-item">
                    <span class="check-icon">✓</span>
                    <span>Aktualności i nowe funkcje platformy</span>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="{{ config('app.frontend_url') }}/powierzchnie-reklamowe" class="button">Przeglądaj ogłoszenia</a>
            </div>

            <div class="divider"></div>

            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                Dziękujemy, że jesteś z nami!
            </p>
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
            <p style="color: #9ca3af; font-size: 12px; margin: 15px 0 0 0;">
                Nie chcesz już otrzymywać newslettera?
                <a href="{{ config('app.url') }}/api/newsletter/unsubscribe/{{ $unsubscribeToken }}" style="color: #9ca3af;">Wypisz się tutaj</a>.
            </p>
            <p style="color: #9ca3af; font-size: 12px; margin: 10px 0 0 0;">
                © {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </div>
</body>
</html>
