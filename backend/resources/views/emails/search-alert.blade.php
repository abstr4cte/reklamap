<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nowa oferta na ReklaMap!</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            border-radius: 16px 16px 0 0;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
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
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .content {
            background-color: #ffffff;
            padding: 40px 30px;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .ad-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            margin: 25px 0;
            background-color: #f8faff;
        }

        .ad-info {
            padding: 20px;
        }

        .ad-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 10px 0;
        }

        .ad-meta {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 15px;
        }

        .ad-price {
            font-size: 20px;
            font-weight: 800;
            color: #4f46e5;
        }

        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-decoration: none !important;
            padding: 14px 30px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 13px;
            color: #9ca3af;
        }

        .unsubscribe-link {
            color: #9ca3af;
            text-decoration: underline;
        }

        .unsubscribe-link:hover {
            color: #6b7280;
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
        <h2 style="margin-top: 0; color: #111827;">Mamy coś dla Ciebie!</h2>
        <p>Witaj,</p>
        <p>Pojawiła się nowa oferta pasująca do Twoich zapisanych powiadomień. Sprawdź, czy to właśnie to, czego
            szukasz!</p>

        <div class="ad-card">
            @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $ad->title }}" style="width: 100%; height: auto; display: block;">
            @endif
            <div class="ad-info">
                <h3 class="ad-title">{{ $ad->title }}</h3>
                <div class="ad-meta">
                    <strong>Lokalizacja:</strong> {{ $ad->city }}{{ $ad->region ? ', ' . $ad->region : '' }}<br>
                    <strong>Typ:</strong> {{ $typeLabel }}
                </div>
                <div class="ad-price">
                    {{ number_format($ad->price, 0, ',', ' ') }} zł / {{ $ad->price_unit }}
                </div>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="{{ $adUrl }}" class="button">Zobacz ofertę</a>
                </div>

            </div>
        </div>

        <p style="font-size: 14px; color: #6b7280;">Dziękujemy, że korzystasz z ReklaMap!</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} ReklaMap. Wszystkie prawa zastrzeżone.</p>
        <p>Nie chcesz już otrzymywać tych powiadomień? <a
                href="{{ route('search-alerts.unsubscribe', ['token' => $unsubscribeToken]) }}"
                class="unsubscribe-link">Wypisz się tutaj</a>.</p>
    </div>
</body>

</html>