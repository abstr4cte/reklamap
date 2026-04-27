<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Nowe ogłoszenie — ReklaMap</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; }
        .header p { color: rgba(255,255,255,0.9); font-size: 14px; margin: 6px 0 0; }
        .content { padding: 32px 30px; }
        .badge { display: inline-block; background: #dbeafe; color: #1e40af; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 18px; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #6b7280; }
        .value { font-size: 15px; color: #1f2937; margin-top: 2px; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff !important; text-decoration: none !important; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 15px; margin-top: 20px; }
        .divider { height: 1px; background: #e5e7eb; margin: 24px 0; }
        .footer { background: #f9fafb; padding: 24px 30px; text-align: center; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ url('logo-text.png') }}" alt="ReklaMap" style="height:60px;width:auto;" />
            <p>Powiadomienie administracyjne</p>
        </div>

        <div class="content">
            <div class="badge">Nowe ogłoszenie</div>
            <h2 style="margin:0 0 20px;font-size:20px;">{{ $ad->title }}</h2>

            <div class="field">
                <div class="label">Typ</div>
                <div class="value">{{ $ad->type }}</div>
            </div>
            <div class="field">
                <div class="label">Lokalizacja</div>
                <div class="value">{{ $ad->city }}{{ $ad->region ? ', ' . $ad->region : '' }}</div>
            </div>
            <div class="field">
                <div class="label">Cena</div>
                @php
                    $priceUnitLabels = ['day' => 'dzień', 'week' => 'tydzień', 'month' => 'miesiąc', 'year' => 'rok', 'campaign' => 'kampanię', 'sqm' => 'm²'];
                @endphp
                <div class="value">{{ $ad->price }} zł / {{ $priceUnitLabels[$ad->price_unit] ?? $ad->price_unit }}</div>
            </div>
            <div class="field">
                <div class="label">Właściciel</div>
                <div class="value">{{ $ad->owner_email }}{{ $ad->phone ? ' · ' . $ad->phone : '' }}</div>
            </div>
            <div class="field">
                <div class="label">Data dodania</div>
                <div class="value">{{ $ad->created_at->timezone('Europe/Warsaw')->format('d.m.Y H:i') }}</div>
            </div>

            <div class="divider"></div>

            <div style="text-align:center;">
                <a href="{{ env('FRONTEND_URL', 'https://reklamap.pl') }}{{ $ad->full_url }}" class="button">Zobacz ogłoszenie</a>
            </div>
        </div>

        <div class="footer">
            © {{ date('Y') }} ReklaMap — powiadomienie administracyjne
        </div>
    </div>
</body>
</html>
