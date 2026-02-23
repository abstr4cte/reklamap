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
            object-fit: contain;
            object-position: center;
            margin-bottom: 5px;
            border-radius: 4px;
            background-color: #f9fafb;
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
                            $basePrice = $ad->price;
                            $originalUnit = $ad->price_unit ?? 'month';
                            $isConverted = false;
                            $price = $basePrice;
                            $unitLabel = '';

                            // Jeśli displayUnit to 'original', użyj oryginalnej jednostki
                            if ($displayUnit === 'original' || $displayUnit === $originalUnit) {
                                $price = $basePrice;
                                $isConverted = false;
                            } else {
                                $isConverted = true;
                                
                                // Przelicz na dzień
                                $priceInDay = $basePrice;
                                switch ($originalUnit) {
                                    case 'day': $priceInDay = $basePrice; break;
                                    case 'week': $priceInDay = $basePrice / 7; break;
                                    case 'month': $priceInDay = $basePrice / 30; break;
                                    case 'year': $priceInDay = $basePrice / 365; break;
                                    case 'campaign':
                                        $campaignDays = $ad->campaign_duration ?? 30;
                                        $priceInDay = $basePrice / $campaignDays;
                                        break;
                                }
                                
                                // Przelicz na wybraną jednostkę
                                switch ($displayUnit) {
                                    case 'day': $price = $priceInDay; break;
                                    case 'week': $price = $priceInDay * 7; break;
                                    case 'month': $price = $priceInDay * 30; break;
                                    case 'year': $price = $priceInDay * 365; break;
                                }
                            }
                            
                            // Etykieta jednostki
                            $targetUnit = $isConverted ? $displayUnit : $originalUnit;
                            switch ($targetUnit) {
                                case 'day': $unitLabel = 'dzień'; break;
                                case 'week': $unitLabel = 'tydzień'; break;
                                case 'month': $unitLabel = 'miesiąc'; break;
                                case 'year': $unitLabel = 'rok'; break;
                                case 'campaign': $unitLabel = 'kampania'; break;
                                default: $unitLabel = 'miesiąc';
                            }
                        @endphp
                        <span class="price">{{ number_format(round($price), 0, ',', ' ') }} PLN</span>
                        <br>
                        <span style="font-size: 9px;">
                            / {{ $unitLabel }}
                            @if($isConverted)
                                <br>(szacunkowo)
                            @endif
                        </span>
                    </td>
                @endforeach
            </tr>
            @php
                // Pokazuj "Cena za m²" tylko jeśli jest na liście widocznych pól
                $showPricePerSqm = in_array('price_per_sqm', $visibleFields);
            @endphp
            @if($showPricePerSqm)
            <tr>
                <th>Cena za m²</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $area = $ad->width * $ad->height;
                            $pricePerSqm = $area > 0 ? $ad->price / $area : 0;
                        @endphp
                        {{ number_format(round($pricePerSqm), 0, ',', ' ') }} PLN/m²
                        <br>
                        <span style="font-size: 9px;">(szacunkowo)</span>
                    </td>
                @endforeach
            </tr>
            @endif
            <tr>
                <th>Typ powierzchni</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $typeLabels = [
                                'billboard' => 'Billboardy',
                                'citylight' => 'Citylighty',
                                'led_screen' => 'Ekrany LED',
                                'banner' => 'Banery',
                                'wall' => 'Ściany reklamowe',
                                'totem' => 'Totemy reklamowe',
                                'transport' => 'Reklama w transporcie',
                                'mobile' => 'Reklama mobilna',
                                'other' => 'Inne'
                            ];
                            $typeLabel = $typeLabels[$ad->type] ?? $ad->type;
                        @endphp
                        {{ $typeLabel }}
                    </td>
                @endforeach
            </tr>
            @php
                // Pokazuj "Wymiary" tylko jeśli jest na liście widocznych pól
                $showDimensions = in_array('dimensions', $visibleFields);
            @endphp
            @if($showDimensions)
            <tr>
                <th>Wymiary</th>
                @foreach($advertisements as $ad)
                    <td>
                        @if($ad->type === 'led_screen')
                            {{ number_format($ad->width * 1000, 0) }}mm x {{ number_format($ad->height * 1000, 0) }}mm
                        @else
                            {{ $ad->width }}m x {{ $ad->height }}m
                        @endif
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Powierzchnia" tylko jeśli jest na liście widocznych pól
                $showSurfaceArea = in_array('surface_area', $visibleFields);
            @endphp
            @if($showSurfaceArea)
            <tr>
                <th>Powierzchnia</th>
                @foreach($advertisements as $ad)
                    <td class="highlight">{{ number_format($ad->width * $ad->height, 2) }} m²</td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Orientacja" tylko jeśli jest na liście widocznych pól
                $showOrientation = in_array('orientation', $visibleFields);
            @endphp
            @if($showOrientation)
            <tr>
                <th>Orientacja</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->orientation === 'horizontal' ? 'Poziom' : 'Pion' }}</td>
                @endforeach
            </tr>
            @endif
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
            @php
                // Pokazuj "Natężenie ruchu" tylko jeśli jest na liście widocznych pól
                $showTrafficIntensity = in_array('traffic_intensity', $visibleFields);
            @endphp
            @if($showTrafficIntensity)
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
            @endif
            @php
                // Pokazuj "Oświetlenie" tylko jeśli jest na liście widocznych pól
                $showLighting = in_array('has_backlight', $visibleFields);
            @endphp
            @if($showLighting)
            <tr>
                <th>Oświetlenie</th>
                @foreach($advertisements as $ad)
                    <td><span class="{{ $ad->has_backlight ? 'yes' : 'no' }}">{{ $ad->has_backlight ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Druk w cenie" tylko jeśli jest na liście widocznych pól
                $showPriceIncludesPrint = in_array('price_includes_print', $visibleFields);
            @endphp
            @if($showPriceIncludesPrint)
            <tr>
                <th>Druk w cenie</th>
                @foreach($advertisements as $ad)
                    <td><span
                            class="{{ $ad->price_includes_print ? 'yes' : 'no' }}">{{ $ad->price_includes_print ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Pomoc graficzna" tylko jeśli jest na liście widocznych pól
                $showGraphicDesignHelp = in_array('graphic_design_help', $visibleFields);
            @endphp
            @if($showGraphicDesignHelp)
            <tr>
                <th>Pomoc graficzna</th>
                @foreach($advertisements as $ad)
                    <td><span
                            class="{{ $ad->graphic_design_help ? 'yes' : 'no' }}">{{ $ad->graphic_design_help ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Typ oświetlenia" tylko jeśli jest na liście widocznych pól
                $showLightingType = in_array('lighting_type', $visibleFields);
            @endphp
            @if($showLightingType)
            <tr>
                <th>Typ oświetlenia</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $lightingLabels = ['led' => 'LED', 'fluorescent' => 'Fluorescencyjne', 'natural' => 'Naturalne', 'none' => 'Brak'];
                        @endphp
                        {{ $lightingLabels[$ad->lighting_type] ?? $ad->lighting_type }}
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Liczba pasażerów dziennie" tylko jeśli jest na liście widocznych pól
                $showDailyPassengers = in_array('daily_passengers', $visibleFields);
            @endphp
            @if($showDailyPassengers)
            <tr>
                <th>Liczba pasażerów dziennie</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->daily_passengers ?? '-' }}</td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Strefa operacyjna" tylko jeśli jest na liście widocznych pól
                $showOperatingZone = in_array('operating_zone', $visibleFields);
            @endphp
            @if($showOperatingZone)
            <tr>
                <th>Strefa operacyjna</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $zoneLabels = ['center' => 'Centrum', 'periphery' => 'Peryferia', 'agglomeration' => 'Cała aglomeracja'];
                        @endphp
                        {{ $zoneLabels[$ad->operating_zone] ?? $ad->operating_zone }}
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Dostosowanie do otoczenia" tylko jeśli jest na liście widocznych pól
                $showAmbientLightControl = in_array('ambient_light_control', $visibleFields);
            @endphp
            @if($showAmbientLightControl)
            <tr>
                <th>Dostosowanie do otoczenia</th>
                @foreach($advertisements as $ad)
                    <td><span class="{{ $ad->ambient_light_control ? 'yes' : 'no' }}">{{ $ad->ambient_light_control ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                // Pokazuj "Typ oświetlenia (Banner/Wall)" tylko jeśli jest na liście widocznych pól
                $showLightingTypeBanner = in_array('lighting_type_banner', $visibleFields);
            @endphp
            @if($showLightingTypeBanner)
            <tr>
                <th>Typ oświetlenia</th>
                @foreach($advertisements as $ad)
                    @php
                        $ltbLabels = ['none' => 'Brak podświetlenia', 'backlight' => 'Podświetlenie z tyłu', 'frontlight' => 'Podświetlenie z przodu'];
                        $ltbValue = $ltbLabels[$ad->lighting_type_banner] ?? $ad->lighting_type_banner;
                    @endphp
                    <td>{{ $ltbValue }}</td>
                @endforeach
            </tr>
            @endif
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