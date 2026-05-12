# Pola ogłoszeń per typ powierzchni

Pełna referencja pól dla każdego typu powierzchni reklamowej w ReklaMap.
Źródła prawdy:
- **Formularz**: `frontend/src/views/AddAdPage.vue` (krok 1–4 — wymagane, krok 4 — opcjonalne)
- **Porównywarka**: `frontend/src/utils/comparisonFields.ts` (`typeFieldsConfig`)
- **Walidacja API**: `backend/app/Http/Controllers/AdvertisementController.php` (`store` / `update`)

Status pól oznaczany:
- ✅ **wymagane** — formularz nie przepuści dalej
- ⚪ **opcjonalne** — można pominąć
- 🚫 **ukryte** — w UI nie pojawia się dla tego typu
- 🤖 **automatyczne** — wyliczane lub uzupełniane bez udziału użytkownika
- 📊 **porównywarka** — jeśli pole figuruje w `typeFieldsConfig` dla danego typu; `(req)` = `required: true` w porównywarce (zawsze widoczna kolumna)

---

## Pola wspólne dla WSZYSTKICH typów

Pochodzą z kroku 1, 2, 3, 6 formularza — niezależnie od typu nośnika.

| Pole (API) | Etykieta UI | Status | Porównywarka | Uwagi |
|---|---|---|---|---|
| `title` | Tytuł ogłoszenia | ✅ | — | min. 3 znaki, walidacja `ProfanityRule` |
| `type` | Typ powierzchni | ✅ | ✅ (req) | wybór decyduje o widoczności pozostałych pól |
| `description` | Opis | ✅ | — | walidacja `ProfanityRule` |
| `price` | Cena | ✅ | ✅ (req) | 0–999 999, liczba |
| `price_unit` | Cena za okres | ✅ | — | `day / week / month / year / sqm / campaign` |
| `location` | Adres / lokalizacja | ✅ | ✅ (req) | adres uliczny; geokodowanie automatyczne |
| `city` | Miasto | ✅ | — | string |
| `latitude` | Szerokość geo | 🤖 | — | wyliczane z adresu |
| `longitude` | Długość geo | 🤖 | — | wyliczane z adresu |
| `region` | Województwo | 🤖 | — | wyznaczane z adresu po stronie backendu |
| `orientation` | Orientacja | ✅* | — | `pionowa / pozioma / kwadrat`; *dla `totem` opcjonalne |
| `contact_preference` | Preferowany kontakt | ✅ | — | `email / phone / both` |
| `phone` | Telefon | ✅** | — | **wymagane gdy `contact_preference` ∈ `phone, both`, dokładnie 9 cyfr |
| `offer_type` | Rodzaj oferty | ✅ | ✅ (req) | `owner / agency` |
| `status` | Status dostępności | ✅ | ✅ (req) | `available / reserved / soon_available` |
| `available_from` | Data dostępności | ✅*** | ✅ | ***wymagane gdy `status = soon_available` |
| `images` | Zdjęcia | ⚪ | — | max 5; w UI zachęta, ale brak walidacji wymagania |
| `price_negotiable` | Cena do negocjacji | ⚪ | ✅ | bool |
| `has_vat_invoice` | Faktura VAT | ⚪ | ✅ (req) | bool |
| `estimated_daily_views` | Szacunkowy OTS / dzień | ⚪ | zależnie od typu | tylko dla outdoor display |
| `campaign_duration` | Czas kampanii (dni) | ✅**** | zależnie od typu | ****wymagane gdy `price_unit = campaign` |
| `slug` | URL slug | 🤖 | — | generowany z `title` |
| `is_active` | Aktywne | 🤖 | — | `true` po imporcie |
| `is_verified` | Zweryfikowane | 🤖 | — | flaga admina |

---

## Billboard

Najbardziej wymagający typ — pełna konfiguracja outdoor.

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `variant` | Wariant | ✅ (req) | jeden z: `standard, two_sided, three_sided, scrolling` |
| `road_class` | Klasa drogi | ✅ | jeden z: `highway, expressway, national, regional, local, urban` |
| `traffic_intensity` | Natężenie ruchu | ✅ (req) | jeden z: `low, medium, high` |
| `width` | Szerokość [m] | ✅ (req, jako część `dimensions`) | > 0, ≤ 500 |
| `height` | Wysokość [m] | ✅ (req) | > 0, ≤ 100 |

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `lighting_type` | Typ podświetlenia | ✅ | `led / fluorescent / natural / none` |
| `traffic_direction` | Kierunek ruchu | ✅ | array — `entry, exit, both` |
| `traffic_type` | Rodzaj ruchu | ✅ (req w porównywarce) | array — `pedestrian, vehicular, both` |
| `price_includes_print` | Druk w cenie | ✅ | bool |
| `price_includes_mounting` | Montaż w cenie | ✅ | bool |
| `graphic_design_help` | Pomoc graficzna | ✅ | bool |
| `estimated_daily_views` | Zasięg dzienny (OTS) | ✅ (req) | int |

### Pola wyliczane (porównywarka)
- `price_per_sqm` — cena / (width × height)
- `surface_area` — width × height
- `dimensions` — sformatowane „X m × Y m"
- `location_tier` — `PREMIUM` jeśli `traffic_intensity = high` i `road_class ∈ {highway, expressway, national}`, inaczej `STANDARD`

---

## Citylight

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `variant` | Wariant | ✅ (req) | jeden z: `single_sided, double_sided, scrolling, digital` |

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `width` | Szerokość [m] | ✅ | UI ma input, ale nie wymaga |
| `height` | Wysokość [m] | ✅ | jw. |
| `environment` | Środowisko | ✅ | `indoor / outdoor` (galeria / ulica) |
| `has_backlight` | Podświetlenie | ✅ | bool — wyświetlane gdy UI pokaże `showLightingOption` |
| `estimated_daily_views` | Zasięg dzienny (OTS) | ✅ | int |
| `price_includes_print` | Druk w cenie | ✅ | bool (formularz tego nie pokazuje — patrz uwaga niżej) |
| `price_includes_mounting` | Montaż w cenie | ✅ | jw. |
| `graphic_design_help` | Pomoc graficzna | ✅ | jw. |

> **Uwaga — rozjazd UI vs porównywarka**: porównywarka pokazuje `price_includes_print/mounting/graphic_design_help` dla citylight, ale `AddAdPage.vue` ich nie eksponuje (`showPrintOption/MountingOption/GraphicDesignOption` zawiera tylko `billboard, banner, wall, totem`). Czyli te pola na citylight pozostaną zawsze `false`, jeśli ogłoszenie powstało przez UI.

---

## Ekran LED (`led_screen`)

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `variant` | Wariant | ✅ (req) | `standard / interactive` |
| `traffic_intensity` | Natężenie ruchu | — | wymagane przez UI, w porównywarce brak kolumny |
| `width` | Szerokość | ✅ | UI wpisuje w mm (max 500 000), w bazie zapisuje w metrach |
| `height` | Wysokość | ✅ | jw. (max 100 000 mm) |

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `resolution` | Rozdzielczość | ✅ | np. `1920×1080` |
| `pixel_pitch` | Pixel Pitch (mm) | ✅ | 0.1–100 |
| `brightness` | Jasność (nits) | ✅ | 1000–15000 |
| `ambient_light_control` | Dostosowanie do jasności | ✅ | bool |
| `environment` | Środowisko | ✅ | `indoor / outdoor / event` |
| `has_backlight` | Podświetlenie | ✅ | bool (rozjazd jak w citylight — UI nie pokazuje) |
| `campaign_duration` | Czas kampanii (dni) | ✅ | int — używane gdy `price_unit = campaign` |
| `estimated_daily_views` | Zasięg dzienny (OTS) | ✅ | int |

### Pola wyliczane
- `price_per_sqm` (req) — cena / powierzchnia (z metrów)
- `surface_area` — width × height (metry)
- `dimensions` — sformatowane jako `XXXmm × YYYmm` (LED specjalna konwersja)

---

## Baner (`banner`)

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `traffic_intensity` | Natężenie ruchu | ✅ | `low / medium / high` |
| `width` | Szerokość [m] | ✅ (req w porównywarce, jako `dimensions`) | > 0, ≤ 500 |
| `height` | Wysokość [m] | ✅ (req) | > 0, ≤ 100 |

> Banner **nie ma** `variant` ani `road_class` jako wymagane.

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `lighting_type_banner` | Oświetlenie | ✅ | `none / backlight / frontlight` |
| `environment` | Środowisko | ✅ | `outdoor / event` |
| `road_class` | Klasa drogi | — (formularz nie pokazuje) | nullable na backendzie |
| `traffic_direction` | Kierunek ruchu | ✅ | array |
| `traffic_type` | Rodzaj ruchu | ✅ (req) | array |
| `price_includes_print` | Druk w cenie | ✅ | bool |
| `price_includes_mounting` | Montaż w cenie | ✅ | bool |
| `graphic_design_help` | Pomoc graficzna | ✅ | bool |
| `estimated_daily_views` | Zasięg dzienny (OTS) | ✅ (req) | int |

### Pola wyliczane
- `price_per_sqm` (req), `surface_area` (req), `dimensions` (req w porównywarce)

---

## Ściana reklamowa (`wall`)

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `traffic_intensity` | Natężenie ruchu | ✅ | `low / medium / high` |
| `width` | Szerokość [m] | ✅ (req jako `dimensions`) | > 0, ≤ 500 |
| `height` | Wysokość [m] | ✅ | > 0, ≤ 100 |

> Wall **nie ma** `variant`, `road_class` jest opcjonalny.

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `lighting_type_banner` | Typ oświetlenia | ✅ | `none / backlight / frontlight` |
| `traffic_direction` | Kierunek ruchu | ✅ | array |
| `traffic_type` | Rodzaj ruchu | ✅ (req) | array |
| `price_includes_print` | Druk w cenie | — (porównywarka tego dla wall NIE pokazuje) | bool — formularz zapisuje, ale porównywarka pomija (rozjazd) |
| `price_includes_mounting` | Montaż w cenie | ✅ | bool |
| `graphic_design_help` | Pomoc graficzna | ✅ | bool |
| `estimated_daily_views` | Zasięg dzienny (OTS) | ✅ (req) | int |

### Pola wyliczane
- `price_per_sqm` (req), `surface_area` (req), `dimensions` (req)

---

## Totem

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `variant` | Wariant | ✅ (req) | `single_sided / double_sided / multi_sided / pylon / digital` |
| `traffic_intensity` | Natężenie ruchu | ✅ | `low / medium / high` |

> Totem **nie wymaga** wymiarów ani orientacji.

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `width` | Szerokość [m] | ✅ | UI pokazuje, ale nie wymaga |
| `height` | Wysokość [m] | ✅ | jw. |
| `orientation` | Orientacja | ✅ | dla totemu w porównywarce `required: false` |
| `environment` | Środowisko | ✅ | `indoor / outdoor / event` |
| `has_backlight` | Podświetlenie | ✅ | bool |
| `traffic_direction` | Kierunek ruchu | ✅ | array |
| `traffic_type` | Rodzaj ruchu | ✅ | array — w porównywarce dla totemu `required: false` |
| `price_includes_mounting` | Montaż w cenie | — (porównywarka nie pokazuje) | bool — UI tak, porównywarka nie (rozjazd) |
| `graphic_design_help` | Pomoc graficzna | — | jw. |
| `estimated_daily_views` | Zasięg dzienny (OTS) | ✅ | int |

---

## Reklama w transporcie (`transport`)

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `variant` | Środek transportu | ✅ (req) | `bus / tram / metro / train / stop` |
| `transport_scope` | Zakres reklamy | ✅ (req) | `internal / external / full_vehicle`; dla `stop` tylko `internal / external` |

> Transport **nie ma** wymiarów (formularz je ukrywa), traffic_*, road_class.

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `vehicle_count` | Liczba pojazdów | ✅ | int — dla `variant ≠ stop` |
| `daily_passengers` | Liczba pasażerów / dzień | ✅ | int |
| `route_area` | Obszar trasy | ✅ | string (pokazywane w porównywarce, ale **NIE jest** edytowane przez formularz dla transportu — UI ma to pole tylko dla mobile; tu może być uzupełnione tylko przez panel zarządczy / API) |
| `operating_hours` | Godziny operacyjne | ✅ | jw. — rozjazd UI / porównywarka |
| `campaign_duration` | Czas kampanii (dni) | ✅ | int |

---

## Reklama mobilna (`mobile`)

### Pola wymagane (poza wspólnymi)
| Pole | Etykieta UI | Porównywarka | Walidacja |
|---|---|---|---|
| `variant` | Rodzaj pojazdu | ✅ (req) | `trailer / car / bike / other` |
| `mobile_exposure_mode` | Tryb ekspozycji | ✅ (req) | `moving / stationary / mixed` |

> Mobile **nie ma** wymiarów (ukryte), traffic_*, road_class.

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `environment` | Środowisko | ✅ | `outdoor / event` |
| `operating_hours` | Godziny działania | ✅ | string |
| `route_area` | Trasa / obszar | ✅ | textarea |
| `operating_zone` | Strefa działania | ✅ | `center / periphery / agglomeration` |
| `campaign_duration` | Czas kampanii (dni) | ✅ | int — wymagane gdy `price_unit = campaign` |

---

## Inne (`other`)

### Pola wymagane
Tylko wspólne — żadnych pól specyficznych.

### Pola opcjonalne
| Pole | Etykieta UI | Porównywarka | Uwagi |
|---|---|---|---|
| `environment` | Środowisko | ✅ | `indoor / outdoor / event` |

---

## Pola, które NIE pochodzą z formularza

Generowane automatycznie / ustawiane admin / techniczne:

| Pole | Pochodzenie | Komentarz |
|---|---|---|
| `id` | DB | auto-increment |
| `slug` | backend (`AdvertisementController`) | z `title` |
| `created_at` / `updated_at` | Eloquent | timestamps |
| `is_active` | domyślnie `true` po imporcie | flaga ogólnej aktywności |
| `is_verified` | admin | weryfikacja ręczna |
| `latitude` / `longitude` | geokodowanie | z adresu |
| `region` | reverse-geocoding | województwo z adresu |
| `image_url` | upload | URL pierwszego zdjęcia |
| `map_screenshot_path` | tylko po edycji | screenshot mapy |
| `rental_period` | derived z `price_unit` | `short_term` dla `day/week`, `long_term` w pozostałych |

---

## Tabela zbiorcza — które pola obowiązują dla których typów

Legenda: ✅ — wymagane, ⚪ — opcjonalne, 🚫 — ukryte / nie dotyczy.

| Pole | billboard | citylight | led_screen | banner | wall | totem | transport | mobile | other |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `title` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `description` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `location` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `city` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `price` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `price_unit` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `orientation` | ✅ | ✅ | ✅ | ✅ | ✅ | ⚪ | ✅ | ✅ | ✅ |
| `variant` | ✅ | ✅ | ✅ | 🚫 | 🚫 | ✅ | ✅ | ✅ | 🚫 |
| `road_class` | ✅ | 🚫 | 🚫 | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 |
| `traffic_intensity` | ✅ | 🚫 | ✅ | ✅ | ✅ | ✅ | 🚫 | 🚫 | 🚫 |
| `traffic_direction` | ⚪ | 🚫 | 🚫 | ⚪ | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 |
| `traffic_type` | ⚪ | 🚫 | 🚫 | ⚪ | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 |
| `width` | ✅ | ⚪ | ✅ | ✅ | ✅ | ⚪ | 🚫 | 🚫 | 🚫 |
| `height` | ✅ | ⚪ | ✅ | ✅ | ✅ | ⚪ | 🚫 | 🚫 | 🚫 |
| `environment` | 🚫 | ⚪ | ⚪ | ⚪ | 🚫 | ⚪ | 🚫 | ⚪ | ⚪ |
| `lighting_type` | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 |
| `lighting_type_banner` | 🚫 | 🚫 | 🚫 | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 |
| `has_backlight` | 🚫 | ⚪ | 🚫 | 🚫 | 🚫 | ⚪ | 🚫 | 🚫 | 🚫 |
| `resolution` | 🚫 | 🚫 | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 |
| `pixel_pitch` | 🚫 | 🚫 | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 |
| `brightness` | 🚫 | 🚫 | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 |
| `ambient_light_control` | 🚫 | 🚫 | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 |
| `transport_scope` | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | ✅ | 🚫 | 🚫 |
| `mobile_exposure_mode` | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | ✅ | 🚫 |
| `vehicle_count` | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | ⚪ | 🚫 | 🚫 |
| `daily_passengers` | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | ⚪ | 🚫 | 🚫 |
| `operating_hours` | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | ⚪ | 🚫 |
| `route_area` | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | ⚪ | 🚫 |
| `operating_zone` | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | 🚫 | ⚪ | 🚫 |
| `price_includes_print` | ⚪ | 🚫 | 🚫 | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 | 🚫 |
| `price_includes_mounting` | ⚪ | 🚫 | 🚫 | ⚪ | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 |
| `graphic_design_help` | ⚪ | 🚫 | 🚫 | ⚪ | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 |
| `estimated_daily_views` | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | 🚫 | 🚫 | 🚫 |
| `campaign_duration` | ⚪* | ⚪* | ⚪* | ⚪* | ⚪* | ⚪* | ⚪* | ⚪* | ⚪* |
| `price_negotiable` | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ |
| `has_vat_invoice` | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ | ⚪ |
| `phone` | ⚪** | ⚪** | ⚪** | ⚪** | ⚪** | ⚪** | ⚪** | ⚪** | ⚪** |

`*` `campaign_duration` jest wymagane, jeśli `price_unit = campaign`.
`**` `phone` jest wymagane, jeśli `contact_preference ∈ {phone, both}`.

---

## Rozjazdy między formularzem a porównywarką (lista do uporządkowania)

Następujące pola są obsługiwane w `comparisonFields.ts`, ale `AddAdPage.vue` ich nie pokazuje (czyli przez UI zawsze pozostają `null/false`):

- **citylight**: `price_includes_print`, `price_includes_mounting`, `graphic_design_help`
- **led_screen**: `has_backlight`, `campaign_duration` (UI pokazuje tylko gdy `price_unit = campaign`)
- **banner**: `road_class`
- **wall**: (brak — wszystko spójne)
- **totem**: (porównywarka pokazuje `price_includes_mounting`, `graphic_design_help` przez sekcję checkboxów w `AddAdPage.vue`, w porównywarce ich nie ma — spójne, ale warto sprawdzić, czy ten brak w porównywarce jest celowy)
- **transport**: `route_area`, `operating_hours` (porównywarka pokazuje, formularz nie)
- **mobile**: (spójne)

Te pola da się ustawić tylko przez API lub panel zarządczy (link z maila), nie przez kreator dodawania ogłoszenia.

---

## Walidacja API (kluczowe ograniczenia liczbowe)

Z `AdvertisementController::store/update`:

- `price`: numeric, `min: 0`, `max: 999999`
- `width`: numeric, `min: 0`, `max: 500`
- `height`: numeric, `min: 0`, `max: 100`
- `pixel_pitch`: numeric, `between: 0.1, 100`
- `brightness`: integer, `between: 1000, 15000`
- `daily_passengers`: integer, `min: 0`
- `vehicle_count`: integer
- `campaign_duration`: integer

**Warunki wymuszane warunkowo (`$requiresXxx`):**
- `variant`: required dla `billboard, citylight, led_screen, totem, transport, mobile`
- `road_class`: required dla `billboard` (dla pozostałych: `nullable|in:...`)

> Walidacje z formularza (np. `transport_scope` required dla `transport`, `mobile_exposure_mode` required dla `mobile`, wymiary required dla outdoor display) są realizowane **wyłącznie po stronie frontu** — backend ich nie egzekwuje. Import omijający formularz musi tego pilnować samodzielnie.
