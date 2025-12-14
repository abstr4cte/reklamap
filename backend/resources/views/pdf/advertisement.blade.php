<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            line-height: 1.5;
        }

        .header {
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 1rem;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #111827;
        }

        .price {
            font-size: 20px;
            color: #4f46e5;
            font-weight: bold;
        }

        .main-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .grid {
            display: table;
            width: 100%;
            margin-bottom: 2rem;
        }

        .row {
            display: table-row;
        }

        .col {
            display: table-cell;
            width: 50%;
            padding: 0.5rem;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #4b5563;
            font-size: 14px;
        }

        .value {
            color: #1f2937;
            font-size: 14px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #111827;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 0.5rem;
        }

        .description {
            font-size: 14px;
            color: #374151;
            margin-bottom: 2rem;
            white-space: pre-line;
        }

        .map-placeholder {
            width: 100%;
            height: 200px;
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            text-align: center;
            padding-top: 80px;
        }

        .footer {
            margin-top: 3rem;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 1rem;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="title">{{ $advertisement->title }}</div>
        <div class="price">
            {{ number_format($advertisement->price, 2, ',', ' ') }} PLN
            <span style="font-size: 14px; color: #6b7280; font-weight: normal;">
                /
                {{ $advertisement->price_unit === 'day' ? 'dzień' : ($advertisement->price_unit === 'week' ? 'tydzień' : ($advertisement->price_unit === 'month' ? 'miesiąc' : 'rok')) }}
            </span>
        </div>
    </div>

    @php
        $imgSrc = $advertisement->image_url;
        if (!$imgSrc && !empty($advertisement->images) && count($advertisement->images) > 0) {
            $imgSrc = $advertisement->images[0];
        }

        $base64Image = null;
        if ($imgSrc) {
            $imageData = null;
            $type = null;

            // Extract filename from URL (works for both full URLs and relative paths)
            $filename = basename(parse_url($imgSrc, PHP_URL_PATH));

            // Images are always stored in storage/app/public/advertisements/
            $path = storage_path('app/public/advertisements/' . $filename);

            // Try to load the image
            if (file_exists($path)) {
                $imageData = file_get_contents($path);
                $type = pathinfo($path, PATHINFO_EXTENSION);
            }

            // Convert to base64 if we have image data
            if ($imageData && $type) {
                $base64Image = 'data:image/' . $type . ';base64,' . base64_encode($imageData);
            }
        }
    @endphp

    @if($base64Image)
        <img src="{{ $base64Image }}" class="main-image">
    @endif

    <div class="section-title">Szczegóły</div>
    <div class="grid">
        <div class="row">
            <div class="col">
                <div class="label">Lokalizacja</div>
                <div class="value">{{ $advertisement->city }}, {{ $advertisement->region }}</div>
                <div class="value" style="font-size: 12px; color: #6b7280;">{{ $advertisement->location }}</div>
            </div>
            <div class="col">
                <div class="label">Wymiary</div>
                <div class="value">{{ $advertisement->width }}m x {{ $advertisement->height }}m</div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="label">Typ</div>
                <div class="value">{{ $advertisement->type }}</div>
            </div>
            <div class="col">
                <div class="label">Natężenie ruchu</div>
                <div class="value">
                    @if($advertisement->traffic_intensity === 'low') Niskie
                    @elseif($advertisement->traffic_intensity === 'medium') Średnie
                    @else Wysokie
                    @endif
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <div class="label">Oświetlenie</div>
                <div class="value">{{ $advertisement->has_lighting ? 'Tak' : 'Nie' }}</div>
            </div>
            <div class="col">
                <div class="label">Faktura VAT</div>
                <div class="value">{{ $advertisement->has_vat_invoice ? 'Tak' : 'Nie' }}</div>
            </div>
        </div>
    </div>

    <div class="section-title">Opis</div>
    <div class="description">
        {{ str_replace(['[IMAGES]', '[/IMAGES]'], '', preg_replace('/\[IMAGES\].*?\[\/IMAGES\]/s', '', $advertisement->description)) }}
    </div>

    <div class="section-title">Lokalizacja</div>
    <div style="margin-bottom: 1rem;">
        <div class="label">Współrzędne GPS</div>
        <div class="value">{{ number_format($advertisement->latitude, 6) }},
            {{ number_format($advertisement->longitude, 6) }}
        </div>
    </div>

    <div class="footer">
        <p>Wygenerowano z serwisu ReklaMap</p>
        <p>{{ date('d.m.Y H:i') }}</p>
    </div>
</body>

</html>