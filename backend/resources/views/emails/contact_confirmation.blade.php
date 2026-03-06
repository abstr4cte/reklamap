<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potwierdzenie zapytania — ReklaMap</title>
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
            margin: 0 0 16px 0;
        }

        .ad-info {
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
            border: 1px solid #667eea30;
            padding: 20px;
            border-radius: 10px;
            margin: 24px 0;
        }

        .ad-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .ad-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 12px 0;
        }

        .message-copy {
            background-color: #f9fafb;
            border-left: 4px solid #d1d5db;
            padding: 20px;
            margin: 24px 0;
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
            color: #374151;
            line-height: 1.6;
            margin: 0;
            white-space: pre-wrap;
            font-size: 15px;
        }

        .info-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
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
            <div class="success-badge">✓ Zapytanie wysłane</div>
            <h2 class="greeting">Twoje zapytanie dotarło!</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Właściciel ogłoszenia otrzymał Twoją wiadomość i powinien odpowiedzieć bezpośrednio na Twój adres
                e-mail.
                Poniżej znajdziesz kopię wysłanej wiadomości dla własnej dokumentacji.
            </p>

            <!-- Ad Info -->
            <div class="ad-info">
                <div class="ad-label">Ogłoszenie, którego dotyczy zapytanie</div>
                <p class="ad-title">{{ $adTitle }}</p>
                <a href="{{ $adUrl }}" style="color: #667eea; font-size: 14px; text-decoration: none;">
                    → Przejdź do ogłoszenia
                </a>
            </div>

            <!-- Message copy -->
            <div class="message-copy">
                <div class="message-label">Kopia Twojej wiadomości:</div>
                <p class="message-text">{{ $message }}</p>
            </div>

            <!-- Info -->
            <div class="info-box">
                <p style="margin: 0; color: #1e40af; font-size: 14px; line-height: 1.6;">
                    <strong>💡 Co dalej?</strong><br>
                    Właściciel odpowie bezpośrednio na Twój adres e-mail. Sprawdź folder SPAM, jeśli odpowiedź nie
                    pojawi się w ciągu 24–48 godzin.
                </p>
            </div>

            <div class="divider"></div>

            <div style="text-align: center;">
                <a href="{{ $adUrl }}" class="button">Przejdź do ogłoszenia</a>
            </div>
        </div>

        <!-- Footer -->
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