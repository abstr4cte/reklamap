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
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .header-logo {
            height: 150px;
            width: auto;
            flex-shrink: 0;
        }

        .header-content {
            flex: 1;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #111827;
            margin-top: 0;
        }

        .price {
            font-size: 20px;
            color: #4f46e5;
            font-weight: bold;
        }

        .main-image-wrapper {
            width: 100%;
            height: 350px;
            background-color: #f3f4f6;
            margin-bottom: 2rem;
            text-align: center;
            border-radius: 8px;
        }

        .main-image {
            max-width: 100%;
            max-height: 350px;
            height: auto;
            width: auto;
            margin: 0 auto;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
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
        <img src="{{ public_path('logo-text.png') }}" alt="ReklaMap" class="header-logo" />
        <div class="header-content">
            <div class="title">{{ $advertisement->title }}</div>
            <div class="price">
            {{ number_format($advertisement->price, 2, ',', ' ') }} PLN
            <span style="font-size: 14px; color: #6b7280; font-weight: normal;">
                /
                @php
                    $priceUnitLabels = [
                        'day' => 'dzień',
                        'week' => 'tydzień',
                        'month' => 'miesiąc',
                        'year' => 'rok',
                        'campaign' => 'kampania',
                        'sqm' => 'm²'
                    ];
                @endphp
                {{ $priceUnitLabels[$advertisement->price_unit] ?? $advertisement->price_unit }}
            </span>
            @if($advertisement->price_negotiable)
                <span
                    style="font-size: 14px; color: #4f46e5; border: 1px solid #4f46e5; padding: 2px 6px; border-radius: 4px; margin-left: 10px; font-weight: normal;">Do
                    negocjacji</span>
            @endif
            </div>
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
        <table style="width: 100%; margin-bottom: 2rem; border-collapse: collapse;">
            <tr>
                <td style="width: 100%; height: 350px; background-color: #f3f4f6; text-align: center; vertical-align: middle; border-radius: 8px;">
                    <img src="{{ $base64Image }}" style="max-width: 100%; max-height: 350px; display: inline-block;">
                </td>
            </tr>
        </table>
    @endif

    <div class="section-title">Szczegóły</div>
    @php
        $showDimensions = in_array($advertisement->type, ['billboard', 'citylight', 'banner', 'wall', 'totem', 'led_screen']) && $advertisement->width && $advertisement->height && $advertisement->width > 0 && $advertisement->height > 0;
        $showTrafficIntensity = in_array($advertisement->type, ['billboard', 'banner', 'wall', 'totem']) && $advertisement->traffic_intensity;
        $showTrafficDirection = in_array($advertisement->type, ['billboard', 'banner', 'wall', 'totem']) && $advertisement->traffic_direction && count($advertisement->traffic_direction ?? []) > 0;
        $showRoadClass = $advertisement->type === 'billboard' && $advertisement->road_class;
        $showLighting = in_array($advertisement->type, ['citylight', 'led_screen', 'totem']) && $advertisement->has_backlight;
        $showLightingTypeBanner = in_array($advertisement->type, ['banner', 'wall']) && !empty($advertisement->lighting_type_banner);
        $showEnvironment = in_array($advertisement->type, ['citylight', 'led_screen', 'totem', 'banner', 'mobile', 'other']) && $advertisement->environment;
        $showTrafficType = in_array($advertisement->type, ['billboard', 'banner', 'wall', 'totem']) && $advertisement->traffic_type && count($advertisement->traffic_type ?? []) > 0;
        $showPrint = in_array($advertisement->type, ['billboard', 'banner', 'citylight']) && $advertisement->price_includes_print;
        $showMounting = in_array($advertisement->type, ['billboard', 'banner', 'wall', 'citylight', 'totem']) && $advertisement->price_includes_mounting;
        $showGraphicDesign = in_array($advertisement->type, ['billboard', 'banner', 'wall', 'citylight', 'totem']) && $advertisement->graphic_design_help;
        $showVariant = in_array($advertisement->type, ['billboard', 'citylight', 'led_screen', 'totem', 'transport', 'mobile']) && $advertisement->variant && trim($advertisement->variant) !== '';
        $showResolution = $advertisement->type === 'led_screen' && !empty($advertisement->resolution);
        $showPixelPitch = $advertisement->type === 'led_screen' && !empty($advertisement->pixel_pitch);
        $showBrightness = $advertisement->type === 'led_screen' && !empty($advertisement->brightness);
        $showCampaignDuration = $advertisement->price_unit === 'campaign' && !empty($advertisement->campaign_duration);
        $showTransportScope = $advertisement->type === 'transport' && !empty($advertisement->transport_scope);
        $showVehicleCount = $advertisement->type === 'transport' && !empty($advertisement->vehicle_count);
        $showMobileExposureMode = $advertisement->type === 'mobile' && !empty($advertisement->mobile_exposure_mode);
        $showOperatingHours = $advertisement->type === 'mobile' && !empty($advertisement->operating_hours);
        $showRouteArea = $advertisement->type === 'mobile' && !empty($advertisement->route_area);
        // Nowe pola dla rozszerzonych opcji
        $showLightingType = $advertisement->type === 'billboard' && !empty($advertisement->lighting_type);
        $showDailyPassengers = $advertisement->type === 'transport' && !empty($advertisement->daily_passengers);
        $showOperatingZone = $advertisement->type === 'mobile' && !empty($advertisement->operating_zone);
        $showAmbientLightControl = $advertisement->type === 'led_screen' && !empty($advertisement->ambient_light_control);
        $showAvailableFrom = !empty($advertisement->available_from);
        $showLocationTier = $advertisement->type === 'billboard';

        $details = [];

        // 1. Location (always)
        $parts = array_map('trim', explode(',', $advertisement->location));
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
            $streetWithNumber = $parts[0] ?? $advertisement->location;
        }

        $regionName = $advertisement->region ? ' (' . ucfirst($advertisement->region) . ')' : '';

        $details[] = [
            'label' => 'Lokalizacja',
            'value' => $streetWithNumber . ', ' . $advertisement->city . $regionName
        ];

        // 2. Type (always)
        $typeLabels = [
            'billboard' => 'Billboard',
            'citylight' => 'Citylight',
            'led_screen' => 'Ekran LED',
            'banner' => 'Banner',
            'wall' => 'Ściana reklamowa',
            'totem' => 'Totem reklamowy',
            'transport' => 'Reklama w transporcie',
            'mobile' => 'Reklama mobilna',
            'other' => 'Inne'
        ];
        $details[] = [
            'label' => 'Typ powierzchni',
            'value' => $typeLabels[$advertisement->type] ?? $advertisement->type
        ];

        // 3. Dimensions and Orientation
        if ($showDimensions) {
            $dimValue = ($advertisement->type === 'led_screen')
                ? number_format($advertisement->width * 1000, 0) . 'mm × ' . number_format($advertisement->height * 1000, 0) . 'mm'
                : (float)$advertisement->width . 'm × ' . (float)$advertisement->height . 'm';
            $details[] = ['label' => 'Wymiary', 'value' => $dimValue];
            $details[] = ['label' => 'Orientacja', 'value' => $advertisement->orientation === 'horizontal' ? 'Poziom' : 'Pion'];
        }

        // 4. Variant
        if ($showVariant) {
            $variantLabels = [
                // Billboard
                'standard' => 'Jednostronny',
                'two_sided' => 'Dwustronny (back-to-back)',
                'three_sided' => 'Trójstronny (prismatron)',
                'scrolling' => 'Scrolling / Rolowany',
                // Citylight
                'single_sided' => 'Jednostronny',
                'double_sided' => 'Dwustronny',
                'digital' => 'Cyfrowy (DOOH)',
                'interactive' => 'Interaktywny',
                // Totem
                'multi_sided' => 'Wielostronny / Kolumna',
                'pylon' => 'Pylon / Przydrożny',
                // Transport
                'bus' => 'Autobus',
                'tram' => 'Tramwaj',
                'train' => 'Pociąg / SKM / Kolej',
                'metro' => 'Metro',
                'stop' => 'Przystanek',
                // Mobile
                'trailer' => 'Przyczepka',
                'car' => 'Samochód',
                'bike' => 'Rower',
                'other' => 'Inna'
            ];
            $details[] = ['label' => 'Wariant', 'value' => $variantLabels[$advertisement->variant] ?? $advertisement->variant];
        }

        // 5. Traffic Intensity and Road Class
        if ($showTrafficIntensity) {
            $intensity = $advertisement->traffic_intensity === 'low' ? 'Niskie' : ($advertisement->traffic_intensity === 'medium' ? 'Średnie' : 'Wysokie');
            $details[] = ['label' => 'Natężenie ruchu', 'value' => $intensity];
        }

        if ($advertisement->estimated_daily_views) {
            $details[] = ['label' => 'Dzienny zasięg (OTS)', 'value' => number_format($advertisement->estimated_daily_views, 0, ',', ' ') . ' osób'];
        }
        if ($showRoadClass) {
            $roadClassLabels = [
                'highway' => 'Autostrada (A)',
                'expressway' => 'Droga ekspresowa (S)',
                'national' => 'Droga krajowa (DK)',
                'regional' => 'Droga wojewódzka',
                'local' => 'Droga lokalna',
                'urban' => 'Droga miejska'
            ];
            $details[] = ['label' => 'Klasa drogi', 'value' => $roadClassLabels[$advertisement->road_class] ?? $advertisement->road_class];
        }

        if ($showLocationTier) {
            $isPremium = ($advertisement->traffic_intensity === 'high' && in_array($advertisement->road_class, ['highway', 'expressway', 'national']));
            $details[] = ['label' => 'Klasa lokalizacji', 'value' => $isPremium ? 'PREMIUM' : 'STANDARD'];
        }

        // 6. Traffic Direction
        if ($showTrafficDirection) {
            $directions = $advertisement->traffic_direction ?? [];
            if (in_array('entry', $directions) && in_array('exit', $directions)) {
                $dirValue = 'Oba kierunki';
            } else {
                $directionLabels = ['entry' => 'Wjazd do miasta', 'exit' => 'Wyjazd z miasta'];
                $formatted = [];
                foreach ($directions as $dir) {
                    $formatted[] = $directionLabels[$dir] ?? $dir;
                }
                $dirValue = implode(', ', $formatted);
            }
            $details[] = ['label' => 'Kierunek ruchu', 'value' => $dirValue];
        }

        // 7. Traffic Type
        if ($showTrafficType) {
            $trafficTypes = $advertisement->traffic_type ?? [];
            $tLabels = ['pedestrian' => 'Pieszy', 'vehicular' => 'Samochodowy'];
            $formatted = [];
            foreach ($trafficTypes as $t) {
                $formatted[] = $tLabels[$t] ?? $t;
            }
            $details[] = ['label' => 'Rodzaj ruchu', 'value' => implode(', ', $formatted)];
        }

        // 8. Environment
        if ($showEnvironment) {
            $envLabels = ['indoor' => 'Wewnątrz', 'outdoor' => 'Na zewnątrz', 'event' => 'Event / Wydarzenie'];
            $details[] = ['label' => 'Środowisko', 'value' => $envLabels[$advertisement->environment] ?? $advertisement->environment];
        }

        // 9. LED Technical specs
        if ($showResolution)
            $details[] = ['label' => 'Rozdzielczość', 'value' => $advertisement->resolution];
        if ($showPixelPitch)
            $details[] = ['label' => 'Pixel Pitch', 'value' => $advertisement->pixel_pitch . ' mm'];
        if ($showBrightness)
            $details[] = ['label' => 'Jasność', 'value' => $advertisement->brightness . ' nits'];
        if ($showAmbientLightControl)
            $details[] = ['label' => 'Dostosowanie do otoczenia', 'value' => 'Tak'];

        // 10. Campaign Duration
        if ($showCampaignDuration)
            $details[] = ['label' => 'Czas trwania kampanii', 'value' => $advertisement->campaign_duration . ' dni'];

        // 11. Billboard Lighting Type
        if ($showLightingType) {
            $lLabels = ['led' => 'LED', 'fluorescent' => 'Fluorescencyjne', 'natural' => 'Naturalne', 'none' => 'Brak'];
            $details[] = ['label' => 'Typ oświetlenia', 'value' => $lLabels[$advertisement->lighting_type] ?? $advertisement->lighting_type];
        }

        // 11b. Banner/Wall Lighting Type
        if ($showLightingTypeBanner) {
            $ltbLabels = ['none' => 'Brak podświetlenia', 'backlight' => 'Podświetlenie z tyłu', 'frontlight' => 'Podświetlenie z przodu'];
            $details[] = ['label' => 'Typ oświetlenia', 'value' => $ltbLabels[$advertisement->lighting_type_banner] ?? $advertisement->lighting_type_banner];
        }

        // 12. Transport Scope, Vehicle Count and Daily Passengers
        if ($showTransportScope) {
            $sLabels = ['internal' => 'Wewnętrzna', 'external' => 'Zewnętrzna', 'full_vehicle' => 'Całopojazdowa'];
            $details[] = ['label' => 'Zakres', 'value' => $sLabels[$advertisement->transport_scope] ?? $advertisement->transport_scope];
        }
        if ($showVehicleCount)
            $details[] = ['label' => 'Liczba pojazdów', 'value' => $advertisement->vehicle_count];
        if ($showDailyPassengers)
            $details[] = ['label' => 'Liczba pasażerów dziennie', 'value' => $advertisement->daily_passengers];

        // 13. Mobile details
        if ($showMobileExposureMode) {
            $mLabels = ['moving' => 'Jeżdżąca', 'stationary' => 'Stojąca', 'mixed' => 'Mieszana'];
            $details[] = ['label' => 'Tryb ekspozycji', 'value' => $mLabels[$advertisement->mobile_exposure_mode] ?? $advertisement->mobile_exposure_mode];
        }
        if ($showOperatingHours)
            $details[] = ['label' => 'Godziny działania', 'value' => $advertisement->operating_hours];
        if ($showRouteArea)
            $details[] = ['label' => 'Trasa / Obszar', 'value' => $advertisement->route_area];
        if ($showOperatingZone) {
            $zLabels = ['center' => 'Centrum', 'periphery' => 'Peryferia', 'agglomeration' => 'Cała aglomeracja'];
            $details[] = ['label' => 'Strefa operacyjna', 'value' => $zLabels[$advertisement->operating_zone] ?? $advertisement->operating_zone];
        }

        // 14. Features
        if ($showLighting)
            $details[] = ['label' => 'Podświetlenie', 'value' => 'Tak'];
        if ($showPrint)
            $details[] = ['label' => 'Druk w cenie', 'value' => 'Tak'];
        if ($showMounting)
            $details[] = ['label' => 'Montaż w cenie', 'value' => 'Tak'];
        if ($showGraphicDesign)
            $details[] = ['label' => 'Pomoc graficzna', 'value' => 'Dostępna'];

        // 14. Offer type and VAT
        $offerTypeLabel = 'Nieznany';
        if ($advertisement->offer_type === 'owner')
            $offerTypeLabel = 'Właściciel (bezpośrednio)';
        elseif ($advertisement->offer_type === 'agency')
            $offerTypeLabel = 'Agencja reklamowa';
        elseif ($advertisement->offer_type === 'sublease')
            $offerTypeLabel = 'Podnajmujący';

        $details[] = ['label' => 'Rodzaj oferty', 'value' => $offerTypeLabel];

        if ($advertisement->has_vat_invoice)
            $details[] = ['label' => 'Faktura VAT', 'value' => 'Tak'];

        if ($showAvailableFrom) {
            $details[] = ['label' => 'Dostępne od', 'value' => date('d.m.Y', strtotime($advertisement->available_from))];
        } else {
            $details[] = ['label' => 'Dostępność', 'value' => 'Od zaraz'];
        }

    @endphp

    <div class="grid">
        @foreach(array_chunk($details, 2) as $row)
            <div class="row">
                @foreach($row as $item)
                    <div class="col">
                        <div class="label">{{ $item['label'] }}</div>
                        <div class="value">{{ $item['value'] }}</div>
                        @if(isset($item['subtext']))
                            <div class="value" style="font-size: 12px; color: #6b7280;">{{ $item['subtext'] }}</div>
                        @endif
                    </div>
                @endforeach
                @if(count($row) < 2)
                    <div class="col"></div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="section-title">Opis</div>
    <div class="description">
        {{ str_replace(['[IMAGES]', '[/IMAGES]'], '', preg_replace('/\[IMAGES\].*?\[\/IMAGES\]/s', '', $advertisement->description)) }}
    </div>

    <div class="section-title">Lokalizacja</div>

    @php
        $mapScreenshotBase64 = null;
        if ($advertisement->map_screenshot_path) {
            $mapScreenshotPath = storage_path('app/public/' . $advertisement->map_screenshot_path);
            if (file_exists($mapScreenshotPath)) {
                $mapScreenshotData = file_get_contents($mapScreenshotPath);
                $mapScreenshotBase64 = 'data:image/png;base64,' . base64_encode($mapScreenshotData);
            }
        }
    @endphp

    @if($mapScreenshotBase64)
        <table style="width: 100%; margin-bottom: 1rem; border-collapse: collapse;">
            <tr>
                <td style="width: 100%; text-align: center; background-color: #f3f4f6; border-radius: 8px;">
                    <img src="{{ $mapScreenshotBase64 }}" style="max-width: 100%; max-height: 300px; display: inline-block;">
                </td>
            </tr>
        </table>
    @endif

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