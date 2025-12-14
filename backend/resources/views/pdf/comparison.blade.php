<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1f2937;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #111827;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }

        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
            width: 120px;
        }

        .ad-image {
            width: 100%;
            height: 100px;
            object-fit: cover;
            margin-bottom: 5px;
            border-radius: 4px;
        }

        .ad-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
            display: block;
        }

        .price {
            color: #4f46e5;
            font-weight: bold;
            font-size: 12px;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        .highlight {
            background-color: #f0f9ff;
        }

        .yes {
            color: #10B981;
            font-weight: bold;
        }

        .no {
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Porównanie ogłoszeń</h1>
    </div>

    <table>
        <thead>
            <tr>
                <th></th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $base64Image = null;
                            $imgSrc = $ad->image_url;
                            if (!$imgSrc && !empty($ad->images) && count($ad->images) > 0) {
                                $imgSrc = $ad->images[0];
                            }

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
                            <img src="{{ $base64Image }}" class="ad-image">
                        @endif
                        <span class="ad-title">{{ $ad->title }}</span>
                    </td>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Cena</th>
                @foreach($advertisements as $ad)
                    <td class="highlight">
                        @php
                            $price = $ad->price;
                            $unitLabel = 'miesiąc';

                            switch ($displayUnit) {
                                case 'day':
                                    $price = $price / 30;
                                    $unitLabel = 'dzień';
                                    break;
                                case 'week':
                                    $price = $price / 4;
                                    $unitLabel = 'tydzień';
                                    break;
                                case 'year':
                                    $price = $price * 12;
                                    $unitLabel = 'rok';
                                    break;
                                default: // month
                                    $unitLabel = 'miesiąc';
                            }
                        @endphp
                        <span class="price">{{ number_format($price, 2, ',', ' ') }} PLN</span>
                        <br>
                        <span style="font-size: 9px;">
                            / {{ $unitLabel }}
                        </span>
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Cena za m²</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $area = $ad->width * $ad->height;
                            $pricePerSqm = $area > 0 ? $ad->price / $area : 0;
                        @endphp
                        {{ number_format($pricePerSqm, 2, ',', ' ') }} PLN/m²
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Typ powierzchni</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->type }}</td>
                @endforeach
            </tr>
            <tr>
                <th>Wymiary</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->width }}m x {{ $ad->height }}m</td>
                @endforeach
            </tr>
            <tr>
                <th>Powierzchnia</th>
                @foreach($advertisements as $ad)
                    <td class="highlight">{{ number_format($ad->width * $ad->height, 2) }} m²</td>
                @endforeach
            </tr>
            <tr>
                <th>Orientacja</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->orientation === 'horizontal' ? 'Poziom' : 'Pion' }}</td>
                @endforeach
            </tr>
            <tr>
                <th>Lokalizacja</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $parts = array_map('trim', explode(',', $ad->location));
                            $streetWithNumber = '';
                            if (count($parts) >= 2) {
                                $firstPart = $parts[0];
                                $secondPart = $parts[1];
                                if (preg_match('/^\d+/', $firstPart)) {
                                    $streetWithNumber = $secondPart . ' ' . $firstPart;
                                } else {
                                    $streetWithNumber = $firstPart;
                                }
                            } else {
                                $streetWithNumber = $parts[0] ?? $ad->location;
                            }
                        @endphp
                        {{ $streetWithNumber }}, {{ $ad->city }}
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Natężenie ruchu</th>
                @foreach($advertisements as $ad)
                    <td>
                        @if($ad->traffic_intensity === 'low') Niskie
                        @elseif($ad->traffic_intensity === 'medium') Średnie
                        @else Wysokie
                        @endif
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Oświetlenie</th>
                @foreach($advertisements as $ad)
                    <td><span class="{{ $ad->has_lighting ? 'yes' : 'no' }}">{{ $ad->has_lighting ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Druk w cenie</th>
                @foreach($advertisements as $ad)
                    <td><span
                            class="{{ $ad->price_includes_print ? 'yes' : 'no' }}">{{ $ad->price_includes_print ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Pomoc graficzna</th>
                @foreach($advertisements as $ad)
                    <td><span
                            class="{{ $ad->graphic_design_help ? 'yes' : 'no' }}">{{ $ad->graphic_design_help ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Status</th>
                @foreach($advertisements as $ad)
                    <td>
                        @switch($ad->status)
                            @case('active') Wolne @break
                            @case('reserved') Zarezerwowane @break
                            @case('soon_available') Wkrótce dostępne @break
                            @default Nieznany
                        @endswitch
                    </td>
                @endforeach
            </tr>
            <tr>
                <th>Rodzaj oferty</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->offer_type === 'owner' ? 'Właściciel' : 'Agencja' }}</td>
                @endforeach
            </tr>
            <tr>
                <th>Faktura VAT</th>
                @foreach($advertisements as $ad)
                    <td><span
                            class="{{ $ad->has_vat_invoice ? 'yes' : 'no' }}">{{ $ad->has_vat_invoice ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Wygenerowano z serwisu ReklaMap</p>
        <p>{{ date('d.m.Y H:i') }}</p>
    </div>
</body>

</html>