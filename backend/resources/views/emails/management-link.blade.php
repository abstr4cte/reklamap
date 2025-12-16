<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Link do zarządzania ogłoszeniami</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }

        .header img {
            max-width: 150px;
        }

        .content {
            background-color: #fff;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none !important;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            margin: 20px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }

        .expiry {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1 style="color: white; margin: 0;">ReklaMap</h1>
    </div>

    <div class="content">
        <h2>Link do zarządzania ogłoszeniami</h2>

        <p>Witaj!</p>

        <p>Otrzymujesz ten email, ponieważ poprosiłeś o dostęp do panelu zarządzania Twoimi ogłoszeniami na platformie
            ReklaMap.</p>

        <p>Kliknij poniższy przycisk, aby uzyskać dostęp do wszystkich ogłoszeń powiązanych z Twoim adresem email:</p>

        <div style="text-align: center;">
            <a href="{{ env('FRONTEND_URL', 'https://reklamap.pl') }}/zarzadzaj/{{ $token->id }}" class="button">Przejdź
                do panelu zarządzania</a>
        </div>

        <div class="expiry">
            <strong>Uwaga:</strong> Ten link wygaśnie za 24 godziny ({{ $token->expires_at->format('d.m.Y H:i') }}).
        </div>

        <p>Jeśli nie prosiłeś o ten link, możesz zignorować tę wiadomość.</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.</p>
        <p>To jest wiadomość automatyczna, prosimy na nią nie odpowiadać.</p>
    </div>
</body>

</html>