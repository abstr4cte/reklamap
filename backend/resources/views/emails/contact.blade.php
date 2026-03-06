<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nowa wiadomość z formularza kontaktowego</title>
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

        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
        }

        .header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-right: 15px;
        }

        .header h1 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: white;
            margin: 0;
        }

        .content {
            background-color: #fff;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .info-section {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }

        .info-label {
            font-weight: bold;
            color: #667eea;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            word-break: break-word;
        }

        .message-box {
            background-color: #f0f3ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-content">
            <img src="{{ asset('logo.png') }}" alt="ReklaMap" />
            <h1>ReklaMap</h1>
        </div>
    </div>

    <div class="content">
        <h2>Nowa wiadomość z formularza kontaktowego</h2>

        <p>Otrzymałeś nową wiadomość z formularza kontaktowego na stronie ReklaMap.</p>

        <div class="info-section">
            <div class="info-label">Temat:</div>
            <div class="info-value">{{ $subject }}</div>

            <div class="info-label">Imię i nazwisko:</div>
            <div class="info-value">{{ $name }}</div>

            <div class="info-label">Email:</div>
            <div class="info-value"><a href="mailto:{{ $email }}">{{ $email }}</a></div>

            @if($phone)
            <div class="info-label">Telefon:</div>
            <div class="info-value">{{ $phone }}</div>
            @endif
        </div>

        <h3>Wiadomość:</h3>
        <div class="message-box">{{ $message }}</div>

        <p style="color: #666; font-size: 14px;">
            <strong>Aby odpowiedzieć na tę wiadomość,</strong> użyj przycisku "Odpowiedz" w swoim kliencie poczty elektronicznej. Odpowiedź zostanie wysłana bezpośrednio do nadawcy.
        </p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.</p>
        <p>To jest wiadomość automatyczna z formularza kontaktowego.</p>
    </div>
</body>

</html>
