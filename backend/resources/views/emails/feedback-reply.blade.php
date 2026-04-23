<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odpowiedź na Twoje zgłoszenie — ReklaMap</title>
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
        .info-badge {
            display: inline-block;
            background-color: #dbeafe;
            color: #1e40af;
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
        .section-label {
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0 0 8px 0;
        }
        .original-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 0 0 24px 0;
        }
        .original-text {
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
            font-size: 14px;
            font-style: italic;
        }
        .reply-box {
            background-color: #eff6ff;
            border-left: 4px solid #667eea;
            border-radius: 4px;
            padding: 16px 20px;
            margin: 0 0 24px 0;
        }
        .reply-text {
            color: #1f2937;
            line-height: 1.6;
            margin: 0;
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
            <div class="info-badge">Odpowiedź od zespołu ReklaMap</div>
            <h2 class="greeting">Odpowiedzieliśmy na Twoje zgłoszenie</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 24px 0;">
                Dziękujemy za kontakt. Poniżej znajdziesz odpowiedź na Twoje zgłoszenie.
            </p>

            <p class="section-label">Twoja wiadomość</p>
            <div class="original-box">
                <p class="original-text">{{ $feedback->message }}</p>
            </div>

            <p class="section-label">Nasza odpowiedź</p>
            <div class="reply-box">
                <p class="reply-text">{{ $reply }}</p>
            </div>

            <div class="divider"></div>

            <p style="color: #4b5563; line-height: 1.6; margin: 0;">
                Jeśli masz dodatkowe pytania, napisz do nas ponownie przez formularz na stronie.
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
            <p style="color: #9ca3af; font-size: 12px; margin: 20px 0 0 0;">
                © {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </div>
</body>
</html>
