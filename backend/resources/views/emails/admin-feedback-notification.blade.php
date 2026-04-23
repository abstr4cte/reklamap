<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Nowy feedback — ReklaMap</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; background-color: #f3f4f6; color: #1f2937; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; }
        .header p { color: rgba(255,255,255,0.9); font-size: 14px; margin: 6px 0 0; }
        .content { padding: 32px 30px; }
        .badge-bug { display: inline-block; background: #fee2e2; color: #991b1b; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 18px; }
        .badge-suggestion { display: inline-block; background: #d1fae5; color: #065f46; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 18px; }
        .badge-question { display: inline-block; background: #dbeafe; color: #1e40af; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 18px; }
        .field { margin-bottom: 12px; }
        .label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #6b7280; }
        .value { font-size: 15px; color: #1f2937; margin-top: 2px; }
        .message-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; font-size: 14px; line-height: 1.6; color: #374151; margin-top: 4px; }
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
            @php
                $badgeClass = match($feedback->type) {
                    'bug'        => 'badge-bug',
                    'suggestion' => 'badge-suggestion',
                    default      => 'badge-question',
                };
                $typeLabel = match($feedback->type) {
                    'bug'        => 'Błąd',
                    'suggestion' => 'Sugestia',
                    'question'   => 'Pytanie',
                    default      => $feedback->type,
                };
            @endphp

            <div class="{{ $badgeClass }}">{{ $typeLabel }}</div>
            <h2 style="margin:0 0 20px;font-size:20px;">Nowy feedback od użytkownika</h2>

            <div class="field">
                <div class="label">Od</div>
                <div class="value">{{ $feedback->email }}</div>
            </div>

            <div class="field">
                <div class="label">Wiadomość</div>
                <div class="message-box">{{ $feedback->message }}</div>
            </div>

            @if($feedback->url)
            <div class="field">
                <div class="label">Strona</div>
                <div class="value" style="font-size:13px;word-break:break-all;">{{ $feedback->url }}</div>
            </div>
            @endif

            <div class="field">
                <div class="label">Data</div>
                <div class="value">{{ $feedback->created_at->format('d.m.Y H:i') }}</div>
            </div>
        </div>

        <div class="footer">
            © {{ date('Y') }} ReklaMap — powiadomienie administracyjne
        </div>
    </div>
</body>
</html>
