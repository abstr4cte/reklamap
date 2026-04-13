<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link do zarządzania ogłoszeniami — ReklaMap</title>
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
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-right: 15px;
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
        .expiry-box {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
            font-size: 14px;
            color: #92400e;
            line-height: 1.6;
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
                <img src="{{ asset('logo.png') }}" alt="ReklaMap" class="logo-image" />
                <h1 class="logo">ReklaMap</h1>
            </div>
            <p class="tagline">Portal powierzchni reklamowych</p>
        </div>

        <div class="content">
            <h2 class="greeting">Link do zarządzania ogłoszeniami</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Otrzymujesz tę wiadomość, ponieważ poprosiłeś o dostęp do panelu zarządzania Twoimi ogłoszeniami na platformie ReklaMap.
            </p>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Kliknij poniższy przycisk, aby uzyskać dostęp do wszystkich ogłoszeń powiązanych z Twoim adresem e-mail:
            </p>

            <div style="text-align: center;">
                <a href="{{ env('FRONTEND_URL', 'https://reklamap.pl') }}/zarzadzaj/{{ $token->id }}" class="button">Przejdź do panelu zarządzania</a>
            </div>

            <div class="expiry-box">
                <strong>Uwaga:</strong> Ten link wygaśnie za 30 dni ({{ $token->expires_at->format('d.m.Y H:i') }}). Po tym czasie będziesz musiał wygenerować nowy link.
            </div>

            <div class="divider"></div>

            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                Jeśli nie prosiłeś o ten link, możesz zignorować tę wiadomość. Twoje ogłoszenia pozostają bezpieczne.
            </p>
        </div>

        <div class="footer">
            <p class="footer-text">
                Ta wiadomość została wysłana automatycznie przez platformę ReklaMap.<br>
                Nie odpowiadaj na tego maila — to adres noreply.
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
