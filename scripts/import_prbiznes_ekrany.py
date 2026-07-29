#!/usr/bin/env python3
"""Import ekranów LCD (Galeria Warmińska, AURA) i telebimów PR Biznes (Olsztyn).

Dane pobrane bezpośrednio ze stron prbiz.pl (nie z maila/arkusza jak billboardy):
- LCD: cenniki są obrazkami (gw.jpg / ga.jpg), odczytane wizualnie i przepisane ręcznie
  do CENNIK_GW / CENNIK_AURA niżej.
- Telebimy: cennik jest realną tabelą HTML (<table class="telebimy-tabela">) — identyczna
  dla wszystkich 3 lokalizacji. Tylko lokalizacja nr 1 ma opublikowany rozmiar ekranu
  (30 m², 1280x768 px) — loc 2/3 bez rozmiaru, zostaje null (nie zgadujemy).

Każda "galeria"/telebim to JEDNO ogłoszenie reprezentujące sieć ekranów w danej lokalizacji
(cena = najtańszy wariant, pełny cennik w opisie) — spójne z istniejącym wzorcem pojedynczych
ekranów LED w bazie (np. "Telebim Częstochowa"), ale tu ekran/sieć zamiast pojedynczego banera.

Dopisuje rekordy do WSPÓLNEGO backend/database/seeders/data/prbiznes.json (ten sam
PrBiznesSeeder, ten sam owner_email — jeden sync).

Po uruchomieniu: php artisan db:seed --class=PrBiznesSeeder
"""

import json
import os
import time
import urllib.parse
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_JSON = os.path.join(ROOT, "backend/database/seeders/data/prbiznes.json")
STORE_DIR = os.path.join(ROOT, "backend/storage/app/public/advertisements/prbiznes")
REL_PREFIX = "advertisements/prbiznes"

OWNER_EMAIL = "biuro@prbiznes.pl"
PHONE = "502941504"


def geocode(query: str):
    q = urllib.parse.quote(query)
    url = f"https://nominatim.openstreetmap.org/search?format=json&limit=1&q={q}"
    req = urllib.request.Request(url, headers={"User-Agent": "reklamap-import/1.0 (kontakt@reklamap.pl)"})
    with urllib.request.urlopen(req, timeout=15) as r:
        j = json.load(r)
    time.sleep(1.1)
    if j:
        return float(j[0]["lat"]), float(j[0]["lon"])
    return None, None


def fetch_and_save(url: str, base: str, crop=None) -> str:
    from PIL import Image
    import io as _io
    req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urllib.request.urlopen(req, timeout=20) as r:
        data = r.read()
    img = Image.open(_io.BytesIO(data)).convert("RGB")
    if crop:
        img = img.crop(crop)
    os.makedirs(STORE_DIR, exist_ok=True)
    img.save(os.path.join(STORE_DIR, f"{base}.jpg"), "JPEG", quality=85)
    img.save(os.path.join(STORE_DIR, f"{base}.webp"), "WEBP", quality=85)
    return f"{REL_PREFIX}/{base}.jpg"


# Cenniki LCD przepisane z obrazków gw.jpg / ga.jpg (PAKIET STANDARD, 10 sekund — najtańszy wariant
# jako cena bazowa; pełna tabela w opisie).
CENNIK_GW = """PAKIET STANDARD (10 sek.): 1 mies. — 3 ekrany poziome Pasaż 790 zł / 5 ekranów pionowych Food Court 990 zł / wszystkie 8 ekranów 1490 zł; 2 mies. — 1190/1690/2590 zł; 3 mies. — 1690/2590/3590 zł; 6 mies. — 2990/3990/5990 zł; 12 mies. — 5690/6990/9990 zł.
PAKIET OPTIMUM (15 sek.): 1 mies. — 990/1390/1990 zł; 12 mies. — 7490/8990/14990 zł.
PAKIET VIP (20 sek.): 1 mies. — 1390/1690/2390 zł; 12 mies. — 8900/9900/16900 zł.
PAKIET PREMIUM (30 sek.): 1 mies. — 1690/1990/2990 zł; 12 mies. — 13900/15990/24990 zł.
Ceny netto + 23% VAT."""

CENNIK_AURA = """PAKIET STANDARD (10 sek.): 1 mies. — 2 ekrany Poziom 2 Pasaż 590 zł / 3 ekrany Poziom 1 Food Court 690 zł / wszystkie 5 ekranów 990 zł; 12 mies. — 3990/4490/6990 zł.
PAKIET OPTIMUM (15 sek.): 1 mies. — 790/890/1390 zł; 12 mies. — 5490/5990/8990 zł.
PAKIET VIP (20 sek.): 1 mies. — 990/1190/1690 zł; 12 mies. — 7490/7990/9990 zł.
PAKIET PREMIUM (30 sek.): 1 mies. — 1390/1590/1990 zł; 12 mies. — 9990/10990/15990 zł.
Ceny netto + 23% VAT."""

CENNIK_TELEBIM = """Cena za spot 10 sek. wg częstotliwości emisji: ~co 10 min (min. 6x/godz.) 799 zł, ~co 7,5 min (8x/godz.) 999 zł, ~co 6 min (10x/godz.) 1399 zł, ~co 5 min (12x/godz.) 1499 zł, ~co 2,5 min (24x/godz.) 1999 zł.
Spot 15 sek.: 999–2599 zł. Spot 20 sek.: 1399–2999 zł. Spot 30 sek.: 1999–3999 zł (wg tej samej skali częstotliwości).
Rabaty przy kampaniach 3/6/12 miesięcy. Koszt zaprojektowania spotu: 299 zł netto. Ceny netto + 23% VAT."""

RECORDS_SPEC = [
    {
        "kind": "led_screen",
        "title": "Ekrany LED Galeria Warmińska, Olsztyn",
        "location": "Galeria Warmińska, Olsztyn",
        "geocode_query": "Galeria Warmińska, Olsztyn, Polska",
        "price": 1490.0,
        "width": 0.69, "height": 1.22,  # 55" reprezentatywny ekran pionowy (Food Court, 5 z 8)
        "orientation": "vertical",
        "description": (
            "Sieć 8 ekranów LED (55\" Full HD) w Galerii Warmińskiej w Olsztynie: "
            "3 ekrany poziome w pasażu, 5 ekranów pionowych w strefie Food Court. "
            "Emisja reklam rotacyjnie, materiały: animacje (mp4/avi/mkv) lub grafika statyczna. "
            "Ok. 3 240 wyświetleń dziennie / 90 000 miesięcznie (dane właściciela). "
            "Aktywacja 24-48h od potwierdzenia płatności.\n\n" + CENNIK_GW
        ),
        "photo_url": "https://prbiz.pl/wp-content/uploads/2019/03/KP2_9772.jpg",
        "estimated_daily_views": 3240,
    },
    {
        "kind": "led_screen",
        "title": "Ekrany LED Galeria AURA, Olsztyn",
        "location": "Galeria AURA, Olsztyn",
        "geocode_query": "Aura Centrum, Olsztyn, Polska",
        "price": 990.0,
        "width": 1.22, "height": 0.69,
        "orientation": "horizontal",
        "description": (
            "Sieć 5 ekranów LED (55\" Full HD) w Galerii AURA w Olsztynie: "
            "3 ekrany w strefie Food Court (poziom P1), 2 ekrany na poziomie P2 przy kawiarni i kinie Helios. "
            "Emisja rotacyjna co 5-10 minut, materiały wideo lub statyczne. "
            "Aktywacja 24-48h od potwierdzenia płatności.\n\n" + CENNIK_AURA
        ),
        "photo_url": "https://prbiz.pl/wp-content/uploads/2020/06/Ekrany-reklamowe-w-Aura-Centrum-Olsztyna-Strefa-Food-Court-RTV-Euro-AGD.jpg",
        "estimated_daily_views": None,
    },
    {
        "kind": "telebim",
        "title": "Telebim Olsztyn – Centrum (Ratusz)",
        "location": "Skrzyżowanie Piłsudskiego / 1 Maja / Pieniężnego, Olsztyn (centrum przy Ratuszu)",
        "geocode_query": "Stary Ratusz, Olsztyn, Polska",
        "price": 799.0,
        "width": 7.07, "height": 4.24,  # 30 m² @ 1280x768 (jedyna lokalizacja z podanym rozmiarem)
        "orientation": "horizontal",
        "description": (
            "Telebim LED w samym centrum Olsztyna, przy głównym skrzyżowaniu naprzeciwko Ratusza, "
            "obok Galerii AURA i Domu Handlowego Dukat — 100 m od Starówki. Rozmiar ekranu: 30 m², "
            "rozdzielczość 1280x768 px. Emisja codziennie 07:00-22:00, ok. 150 tys. kontaktów/dobę. "
            "Cena bazowa za spot 10-sekundowy przy najniższej częstotliwości emisji — szczegóły niżej.\n\n"
            + CENNIK_TELEBIM
        ),
        "photo_url": "https://prbiz.pl/wp-content/uploads/2020/04/IMG_20200226_111002.jpg",
        "estimated_daily_views": 150000,
    },
    {
        "kind": "telebim",
        "title": "Telebim Olsztyn – Sikorskiego/Tuwima",
        "location": "ul. Sikorskiego / Tuwima, Olsztyn (naprzeciwko Galerii Warmińskiej)",
        "geocode_query": "Sikorskiego, Olsztyn, Polska",
        "price": 799.0,
        "width": None, "height": None,  # rozmiar ekranu nieopublikowany — nie zgadujemy
        "orientation": "horizontal",
        "description": (
            "Telebim LED przy ul. Sikorskiego/Tuwima w Olsztynie, naprzeciwko Galerii Warmińskiej. "
            "Cena bazowa za spot 10-sekundowy przy najniższej częstotliwości emisji — szczegóły niżej. "
            "Rozmiar ekranu do potwierdzenia z agencją.\n\n" + CENNIK_TELEBIM
        ),
        "photo_url": "https://prbiz.pl/wp-content/uploads/2020/12/tuwima2.png",
        "photo_crop": (0, 0, 330, 200),
        "estimated_daily_views": None,
    },
    {
        "kind": "telebim",
        "title": "Telebim Olsztyn – Pstrowskiego/Synów Pułku",
        "location": "ul. Pstrowskiego / Synów Pułku, Olsztyn",
        "geocode_query": "Pstrowskiego, Olsztyn, Polska",
        "price": 799.0,
        "width": None, "height": None,
        "orientation": "horizontal",
        "description": (
            "Telebim LED przy ul. Pstrowskiego/Synów Pułku w Olsztynie. "
            "Cena bazowa za spot 10-sekundowy przy najniższej częstotliwości emisji — szczegóły niżej. "
            "Rozmiar ekranu do potwierdzenia z agencją.\n\n" + CENNIK_TELEBIM
        ),
        "photo_url": "https://prbiz.pl/wp-content/uploads/2020/12/pstrowskiego.png",
        "estimated_daily_views": None,
    },
]


def main() -> int:
    existing = json.load(open(OUT_JSON, encoding="utf-8")) if os.path.isfile(OUT_JSON) else []
    used_refs = {r.get("ref") for r in existing}

    new_records = []
    for i, spec in enumerate(RECORDS_SPEC, start=1):
        ref = f"EKR{i:02d}"
        if ref in used_refs:
            print(f"  {ref}: już w danych, pomijam ponowne dodanie")
            continue

        lat, lng = geocode(spec["geocode_query"])
        if lat is None:
            print(f"  [!] {spec['title']}: geokodowanie nie powiodło się, pomijam")
            continue

        base = f"prbiznes-ekran-{i:02d}"
        img_rel = fetch_and_save(spec["photo_url"], base, spec.get("photo_crop"))

        rec = {
            "ref": ref,
            "title": spec["title"],
            "type": "led_screen",
            "location": spec["location"],
            "city": "Olsztyn",
            "latitude": lat,
            "longitude": lng,
            "description": spec["description"],
            "price": spec["price"],
            "price_unit": "month",
            "width": spec["width"],
            "height": spec["height"],
            "orientation": spec["orientation"],
            "variant": "standard",
            "road_class": None,
            "traffic_intensity": "high" if spec["kind"] == "telebim" else "medium",
            "traffic_direction": [],
            "traffic_type": ["pedestrian"] if spec["kind"] == "led_screen" else ["vehicular", "pedestrian"],
            "has_backlight": True,
            "price_includes_print": False,
            "price_includes_mounting": False,
            "graphic_design_help": False,
            "estimated_daily_views": spec["estimated_daily_views"],
            "price_negotiable": False,
            "has_vat_invoice": True,
            "campaign_duration": None,
            "owner_email": OWNER_EMAIL,
            "phone": PHONE,
            "contact_preference": "both",
            "offer_type": "agency",
            "image_url": img_rel,
            "images": [img_rel] if img_rel else [],
            "has_image": bool(img_rel),
            "status": "active",
            "is_active": True,
            "available_from": None,
        }
        new_records.append(rec)
        print(f"  {ref}: {spec['title']} — {spec['price']:.0f} zł/mc — geo {lat},{lng} — foto OK")

    all_records = existing + new_records
    json.dump(all_records, open(OUT_JSON, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
    print(f"\nGotowe → {OUT_JSON}")
    print(f"  Nowych: {len(new_records)} | razem w pliku: {len(all_records)}")
    print(f"\nTeraz: php artisan db:seed --class=PrBiznesSeeder")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
