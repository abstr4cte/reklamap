<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nowy artykuł na blogu ReklaMap</title>
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
        .blog-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            margin: 24px 0;
            background-color: #f8faff;
        }
        .blog-info {
            padding: 24px;
        }
        .blog-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .blog-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px 0;
            line-height: 1.4;
        }
        .blog-excerpt {
            color: #4b5563;
            font-size: 15px;
            line-height: 1.6;
            margin: 0;
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
            <img src="{{ asset('logo-text.png') }}" alt="ReklaMap" class="logo-image" />
                
            
            <p class="tagline">Portal powierzchni reklamowych</p>
        </div>

        <div class="content">
            <h2 class="greeting">Nowy artykuł na blogu!</h2>

            <p style="color: #4b5563; line-height: 1.6; margin: 0 0 20px 0;">
                Mamy dla Ciebie nowy artykuł, który może Cię zainteresować.
            </p>

            <div class="blog-card">
                <div class="blog-info">
                    <div class="blog-label">Nowy artykuł</div>
                    <h3 class="blog-title">{{ $blogPost->title }}</h3>
                    <p class="blog-excerpt">
                        {{ $blogPost->content ? substr(strip_tags($blogPost->content), 0, 180) . '...' : 'Przeczytaj artykuł, aby dowiedzieć się więcej.' }}
                    </p>
                </div>
            </div>

            <div style="text-align: center;">
                <a href="{{ $blogUrl }}" class="button">Czytaj artykuł</a>
            </div>

            <div class="divider"></div>

            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 0;">
                Masz pytania lub sugestie dotyczące naszych treści? <a href="{{ config('app.url') }}/kontakt" style="color: #667eea; text-decoration: none;">Napisz do nas.</a>
            </p>
        </div>

        <div class="footer">
            <p class="footer-text">
                Ta wiadomość została wysłana automatycznie przez platformę ReklaMap.<br>
                Nie odpowiadaj na tego maila — to adres noreply.
            </p>
            <div>
                <a href="{{ config('app.frontend_url') }}" class="footer-link">Strona główna</a>
                <a href="{{ config('app.frontend_url') }}/blog" class="footer-link">Blog</a>
                <a href="{{ config('app.frontend_url') }}/regulamin" class="footer-link">Regulamin</a>
            </div>
            @if(isset($unsubscribeToken))
            <p style="color: #9ca3af; font-size: 12px; margin: 15px 0 0 0;">
                Nie chcesz otrzymywać powiadomień o nowych artykułach?
                <a href="{{ config('app.url') }}/api/newsletter/unsubscribe/{{ $unsubscribeToken }}" style="color: #9ca3af;">Wypisz się tutaj</a>.
            </p>
            @endif
            <p style="color: #9ca3af; font-size: 12px; margin: 10px 0 0 0;">
                © {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.
            </p>
        </div>
    </div>
</body>
</html>
