<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nowy artykuł na blogu Reklamap</title>
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
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin-right: 15px;
        }

        .header h1 {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: #111827;
            margin: 0;
            font-size: 28px;
        }

        .content {
            background-color: #fff;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .blog-preview {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
        }

        .blog-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 0 0 10px 0;
        }

        .blog-excerpt {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
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

        .unsubscribe {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
        }

        .unsubscribe a {
            color: #667eea;
            text-decoration: none;
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
        <h2>Nowy artykuł na naszym blogu!</h2>

        <p>Witaj!</p>

        <p>Mamy dla Ciebie nowy artykuł na blogu Reklamap, który może Cię zainteresować.</p>

        <div class="blog-preview">
            <div class="blog-title">{{ $blogPost->title }}</div>
            <div class="blog-excerpt">
                {{ $blogPost->content ? substr(strip_tags($blogPost->content), 0, 150) . '...' : 'Przeczytaj artykuł aby dowiedzieć się więcej.' }}
            </div>
        </div>

        <p>Kliknij poniżej, aby przeczytać pełny artykuł:</p>

        <div style="text-align: center;">
            <a href="{{ $blogUrl }}" class="button">Czytaj artykuł</a>
        </div>

        <p>Jeśli masz pytania lub sugestie dotyczące naszych artykułów, daj nam znać!</p>
    </div>

    <div class="footer">
        <p>&copy; {{ date('Y') }} ReklaMap. Wszelkie prawa zastrzeżone.</p>
        <p>To jest wiadomość automatyczna, prosimy na nią nie odpowiadać.</p>
        @if(isset($unsubscribeToken))
            <p class="unsubscribe">Nie chcesz otrzymywać tych wiadomości? <a
                    href="{{ route('newsletter.unsubscribe', ['token' => $unsubscribeToken]) }}">Wypisz się tutaj</a></p>
        @endif
    </div>
</body>


</html>