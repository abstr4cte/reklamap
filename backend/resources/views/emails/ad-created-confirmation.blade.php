<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dziękujemy za dodanie ogłoszenia!</title>
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
    </style>
</head>

<body>
    <div class="header">
        <div class="header-content">
            <img src="https://reklamap.pl/logo.png" alt="ReklaMap" />
            <h1 style="color: white; margin: 0;">ReklaMap</h1>
        </div>
    </div>

    <div class="content">
        <h2>Twoje ogłoszenie zostało opublikowane!</h2>

        <p>Witaj!</p>

        <p>Dziękujemy za dodanie ogłoszenia w serwisie ReklaMap. Cieszymy się, że jesteś z nami!</p>

        <p>Możesz zobaczyć swoje ogłoszenie, klikając w poniższy przycisk:</p>

        <div style="text-align: center;">
            <a href="{{ env('FRONTEND_URL', 'https://reklamap.pl') }}{{ $ad->full_url }}" class="button">Zobacz swoje ogłoszenie</a>
        </div>

        <p>Chcesz zarządzać swoimi ogłoszeniami? Przejdź do panelu zarządzania, gdzie możesz je edytować lub usunąć.</p>

        <div style="text-align: center;">
            <a href="{{ env('FRONTEND_URL', 'https://reklamap.pl') }}/zarzadzaj" class="button">Zarządzaj ogłoszeniami</a>
        </div>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.</p>
        <p>To jest wiadomość automatyczna, prosimy na nią nie odpowiadać.</p>
    </div>
</body>

</html>
