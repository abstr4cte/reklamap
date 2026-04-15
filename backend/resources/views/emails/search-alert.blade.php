<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowa oferta pasująca do Twojego alertu — ReklaMap</title>
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
        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin: 0 0 20px 0;
        }
        .ad-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            margin: 24px 0;
            background-color: #f8faff;
        }
        .ad-card img {
            width: 100%;
            height: auto;
            display: block;
        }
        .ad-info {
            padding: 24px;
        }
        .ad-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .ad-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px 0;
        }
        .ad-meta {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.8;
            margin-bottom: 16px;
        }
        .ad-price {
            font-size: 22px;
            font-weight: 800;
            color: #4f46e5;
            margin-bottom: 20px;
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
            <img src="{{ url('logo-text.png') }}" alt="ReklaMap" class="logo-image" />
                
            
            <p class="tagline">Portal powierzchni reklamowych</p>
        </div>

        <div class="content">
            <h2 class="greeting">Mamy coś dla Ciebie!</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Pojawiła się nowa oferta pasująca do Twoich zapisanych powiadomień. Sprawdź, czy to właśnie to, czego szukasz!
            </p>

            <div class="ad-card">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $ad->title }}" />
                @endif
                <div class="ad-info">
                    <div class="ad-label">Nowa oferta</div>
                    <h3 class="ad-title">{{ $ad->title }}</h3>
                    <div class="ad-meta">
                        <strong>Lokalizacja:</strong> {{ $ad->city }}{{ $ad->region ? ', ' . $ad->region : '' }}<br>
                        <strong>Typ:</strong> {{ $typeLabel }}
                    </div>
                    <div class="ad-price">
                        @php
                            $priceUnits = [
                                'day'      => 'dzień',
                                'week'     => 'tydzień',
                                'month'    => 'miesiąc',
                                'year'     => 'rok',
                                'sqm'      => 'm²',
                                'campaign' => 'kampanię',
                            ];
                        @endphp
                        {{ number_format($ad->price, 0, ',', ' ') }} zł / {{ $priceUnits[$ad->price_unit] ?? $ad->price_unit }}
                    </div>
                    <div style="text-align: center;">
                        <a href="{{ $adUrl }}" class="button">Zobacz ofertę</a>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                Dziękujemy, że korzystasz z ReklaMap!
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
                Nie chcesz już otrzymywać tych powiadomień?
                <a href="{{ config('app.url') }}/api/search-alerts/unsubscribe/{{ $unsubscribeToken }}" style="color: #9ca3af;">Wypisz się tutaj</a>.
            </p>
            <p style="color: #9ca3af; font-size: 12px; margin: 10px 0 0 0;">
                © {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </div>
</body>
</html>
