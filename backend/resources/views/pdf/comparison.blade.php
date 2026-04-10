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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header-logo {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
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

        .img-container {
            position: relative;
            width: 100%;
            height: 160px;
            overflow: hidden;
            background-color: #111827;
            border-radius: 6px;
            margin-bottom: 6px;
        }

        .img-bg {
            position: absolute;
            top: -5px;
            left: -5px;
            width: 110%;
            height: 110%;
            background-size: cover;
            background-position: center;
            opacity: 0.3;
        }

        .img-front-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 160px;
            text-align: center;
            line-height: 160px;
        }

        .img-front {
            vertical-align: middle;
            max-width: 100%;
            max-height: 150px;
            line-height: normal;
        }

        .img-placeholder {
            text-align: center;
            color: #6b7280;
            line-height: 160px;
            font-size: 9px;
        }

        .ad-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
            display: block;
        }

        .price {
            color: #4f46e5;
            font-weight: bold;
            font-size: 12px;
        }

        .col-header {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 9px;
            text-align: center;
            color: #374151;
            padding: 5px 4px;
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
        <img src="{{ public_path('logo.png') }}" alt="ReklaMap" class="header-logo" />
        <h1>Porównanie ogłoszeń</h1>
    </div>

    {{-- Sekcja ze zdjęciami — poza tabelą, nie powtarza się przy łamaniu stron --}}
    @php
        $adImages = [];
        foreach ($advertisements as $ad) {
            $base64Image = null;
            $imgSrc = $ad->image_url;
            if (!$imgSrc && !empty($ad->images) && count($ad->images) > 0) {
                $imgSrc = $ad->images[0];
            }
            if ($imgSrc) {
                $filename = basename(parse_url($imgSrc, PHP_URL_PATH));
                $path = storage_path('app/public/advertisements/' . $filename);
                if (file_exists($path)) {
                    $imageData = file_get_contents($path);
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    if ($imageData && $type) {
                        $base64Image = 'data:image/' . $type . ';base64,' . base64_encode($imageData);
                    }
                }
            }
            $adImages[$ad->id] = $base64Image;
        }
    @endphp

    <table style="margin-bottom: 12px;">
        <tbody>
            <tr>
                <td style="width: 120px; border: none; padding: 0;"></td>
                @foreach($advertisements as $ad)
                    <td style="border: none; padding: 0 4px 0 0; vertical-align: top;">
                        <div class="img-container">
                            @if($adImages[$ad->id])
                                <div class="img-bg" style="background-image: url('{{ $adImages[$ad->id] }}');"></div>
                                <div class="img-front-wrapper">
                                    <img src="{{ $adImages[$ad->id] }}" class="img-front">
                                </div>
                            @else
                                <div class="img-placeholder">Brak zdjęcia</div>
                            @endif
                        </div>
                        <span class="ad-title">{{ $ad->title }}</span>
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th></th>
                @foreach($advertisements as $ad)
                    <td class="col-header">{{ Str::limit($ad->title, 35) }}</td>
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
                $showPriceNegotiable = in_array('price_negotiable', $visibleFields);
            @endphp
            @if($showPriceNegotiable)
            <tr>
                <th>Cena do negocjacji</th>
                @foreach($advertisements as $ad)
                    <td><span class="{{ $ad->price_negotiable ? 'yes' : 'no' }}">{{ $ad->price_negotiable ? 'Tak' : 'Nie' }}</span>
                    </td>
                @endforeach
            </tr>
            @endif
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
                $showVariant = in_array('variant', $visibleFields);
            @endphp
            @if($showVariant)
            <tr>
                <th>Wariant</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $variantLabels = [
                                'billboard' => [
                                    'standard' => 'Jednostronny',
                                    'two_sided' => 'Dwustronny (back-to-back)',
                                    'three_sided' => 'Trójstronny (prismatron)',
                                    'scrolling' => 'Scrolling / Rolowany'
                                ],
                                'citylight' => [
                                    'single_sided' => 'Jednostronny',
                                    'double_sided' => 'Dwustronny',
                                    'scrolling' => 'Scrolling (rotacyjny)',
                                    'digital' => 'Cyfrowy (DOOH)'
                                ],
                                'led_screen' => [
                                    'standard' => 'Standardowy',
                                    'interactive' => 'Interaktywny'
                                ],
                                'totem' => [
                                    'single_sided' => 'Jednostronny',
                                    'double_sided' => 'Dwustronny',
                                    'multi_sided' => 'Wielostronny / Kolumna',
                                    'pylon' => 'Pylon (przy drodze)',
                                    'digital' => 'Cyfrowy (LED)'
                                ],
                                'transport' => [
                                    'bus' => 'Autobus',
                                    'tram' => 'Tramwaj',
                                    'metro' => 'Metro',
                                    'train' => 'Pociąg / SKM / Kolej',
                                    'stop' => 'Przystanek'
                                ],
                                'mobile' => [
                                    'trailer' => 'Przyczepka',
                                    'car' => 'Samochód',
                                    'bike' => 'Rower',
                                    'other' => 'Inna'
                                ]
                            ];
                            $vLabel = $variantLabels[$ad->type][$ad->variant] ?? $ad->variant;
                        @endphp
                        {{ $vLabel ?? '-' }}
                    </td>
                @endforeach
            </tr>
            @endif
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
                            {{ (float)$ad->width }}m x {{ (float)$ad->height }}m
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
                    <td class="highlight">{{ rtrim(rtrim(number_format($ad->width * $ad->height, 2), '0'), '.') }} m²</td>
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
                $showLocationTier = in_array('location_tier', $visibleFields);
            @endphp
            @if($showLocationTier)
            <tr>
                <th>Klasa lokalizacji</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $tier = '-';
                            if ($ad->type === 'billboard') {
                                $tier = ($ad->traffic_intensity === 'high' && in_array($ad->road_class, ['highway', 'expressway', 'national'])) ? 'PREMIUM' : 'STANDARD';
                            }
                        @endphp
                        {{ $tier }}
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                $showRoadClass = in_array('road_class', $visibleFields);
            @endphp
            @if($showRoadClass)
            <tr>
                <th>Klasa drogi</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $roadLabels = [
                                'highway' => 'Autostrada',
                                'expressway' => 'Droga ekspresowa',
                                'national' => 'Droga krajowa',
                                'regional' => 'Droga wojewódzka',
                                'local' => 'Droga lokalna',
                                'urban' => 'Droga miejska'
                            ];
                        @endphp
                        {{ $roadLabels[$ad->road_class] ?? ($ad->road_class ?? '-') }}
                    </td>
                @endforeach
            </tr>
            @endif
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
                $showOTS = in_array('estimated_daily_views', $visibleFields);
            @endphp
            @if($showOTS)
            <tr>
                <th>Zasięg dzienny (OTS)</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->estimated_daily_views ? number_format($ad->estimated_daily_views, 0, ',', ' ') . ' osób' : '-' }}</td>
                @endforeach
            </tr>
            @endif
            @php
                $showTrafficDirection = in_array('traffic_direction', $visibleFields);
            @endphp
            @if($showTrafficDirection)
            <tr>
                <th>Kierunek ruchu</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $directions = $ad->traffic_direction ?? [];
                            if (in_array('entry', $directions) && in_array('exit', $directions)) {
                                $dirValue = 'Oba kierunki';
                            } else {
                                $directionLabels = ['entry' => 'Wjazd do miasta', 'exit' => 'Wyjazd z miasta'];
                                $formatted = [];
                                foreach ($directions as $dir) {
                                    $formatted[] = $directionLabels[$dir] ?? $dir;
                                }
                                $dirValue = !empty($formatted) ? implode(', ', $formatted) : '-';
                            }
                        @endphp
                        {{ $dirValue }}
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                $showTrafficType = in_array('traffic_type', $visibleFields);
            @endphp
            @if($showTrafficType)
            <tr>
                <th>Rodzaj ruchu</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $tTypes = $ad->traffic_type ?? [];
                            $tLabels = ['pedestrian' => 'Pieszy', 'vehicular' => 'Samochodowy'];
                            $formatted = [];
                            foreach ($tTypes as $t) {
                                $formatted[] = $tLabels[$t] ?? $t;
                            }
                            $tValue = !empty($formatted) ? implode(', ', $formatted) : '-';
                        @endphp
                        {{ $tValue }}
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                $showEnvironment = in_array('environment', $visibleFields);
            @endphp
            @if($showEnvironment)
            <tr>
                <th>Środowisko</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $envLabels = ['indoor' => 'Wewnątrz', 'outdoor' => 'Na zewnątrz', 'event' => 'Event / Wydarzenie'];
                        @endphp
                        {{ $envLabels[$ad->environment] ?? ($ad->environment ?? '-') }}
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
                $showMounting = in_array('price_includes_mounting', $visibleFields);
            @endphp
            @if($showMounting)
            <tr>
                <th>Montaż w cenie</th>
                @foreach($advertisements as $ad)
                    <td><span
                            class="{{ $ad->price_includes_mounting ? 'yes' : 'no' }}">{{ $ad->price_includes_mounting ? 'Tak' : 'Nie' }}</span>
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
                $showResolution = in_array('resolution', $visibleFields);
                $showPixelPitch = in_array('pixel_pitch', $visibleFields);
                $showBrightness = in_array('brightness', $visibleFields);
            @endphp
            @if($showResolution)
            <tr>
                <th>Rozdzielczość</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->resolution ?? '-' }}</td>
                @endforeach
            </tr>
            @endif
            @if($showPixelPitch)
            <tr>
                <th>Pixel Pitch</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->pixel_pitch ? $ad->pixel_pitch . ' mm' : '-' }}</td>
                @endforeach
            </tr>
            @endif
            @if($showBrightness)
            <tr>
                <th>Jasność</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->brightness ? $ad->brightness . ' nits' : '-' }}</td>
                @endforeach
            </tr>
            @endif
            @php
                $showTransportScope = in_array('transport_scope', $visibleFields);
                $showVehicleCount = in_array('vehicle_count', $visibleFields);
            @endphp
            @if($showTransportScope)
            <tr>
                <th>Zakres reklamy</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $sLabels = ['internal' => 'Wewnętrzna', 'external' => 'Zewnętrzna', 'full_vehicle' => 'Całopojazdowa'];
                        @endphp
                        {{ $sLabels[$ad->transport_scope] ?? ($ad->transport_scope ?? '-') }}
                    </td>
                @endforeach
            </tr>
            @endif
            @if($showVehicleCount)
            <tr>
                <th>Liczba pojazdów</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->vehicle_count ?? '-' }}</td>
                @endforeach
            </tr>
            @endif
            @php
                $showMobileExposureMode = in_array('mobile_exposure_mode', $visibleFields);
                $showOperatingHours = in_array('operating_hours', $visibleFields);
                $showRouteArea = in_array('route_area', $visibleFields);
            @endphp
            @if($showMobileExposureMode)
            <tr>
                <th>Tryb ekspozycji</th>
                @foreach($advertisements as $ad)
                    <td>
                        @php
                            $mLabels = ['moving' => 'Jeżdżąca', 'stationary' => 'Stojąca', 'mixed' => 'Mieszana'];
                        @endphp
                        {{ $mLabels[$ad->mobile_exposure_mode] ?? ($ad->mobile_exposure_mode ?? '-') }}
                    </td>
                @endforeach
            </tr>
            @endif
            @if($showOperatingHours)
            <tr>
                <th>Godziny działania</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->operating_hours ?? '-' }}</td>
                @endforeach
            </tr>
            @endif
            @if($showRouteArea)
            <tr>
                <th>Trasa / Obszar</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->route_area ?? '-' }}</td>
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
            @php
                $showAvailableFrom = in_array('available_from', $visibleFields);
            @endphp
            @if($showAvailableFrom)
            <tr>
                <th>Dostępne od</th>
                @foreach($advertisements as $ad)
                    <td>
                        @if(!$ad->available_from)
                            Od zaraz
                        @else
                            @php
                                $availableDate = new DateTime($ad->available_from);
                                $today = new DateTime();
                                $today->setTime(0, 0, 0);
                                $availableDate->setTime(0, 0, 0);
                            @endphp
                            {{ $availableDate <= $today ? 'Od zaraz' : date('d.m.Y', strtotime($ad->available_from)) }}
                        @endif
                    </td>
                @endforeach
            </tr>
            @endif
            @php
                $showCampaignDuration = in_array('campaign_duration', $visibleFields);
            @endphp
            @if($showCampaignDuration)
            <tr>
                <th>Czas kampanii</th>
                @foreach($advertisements as $ad)
                    <td>{{ $ad->campaign_duration ? $ad->campaign_duration . ' dni' : '-' }}</td>
                @endforeach
            </tr>
            @endif
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