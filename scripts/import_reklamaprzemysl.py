"""Import billboardów agencji PRS — Reklama Przemyśl (reklamaprzemysl.pl).

Trzy źródła, łączone po numerze nośnika PRS:
1. Strona operatora (WordPress + WooCommerce) — publiczne Store API
   `/wp-json/wc/store/v1/products` daje 73 „produkty”-tablice: numer PRS w nazwie,
   opis położenia, wymiary, jedno-/dwustronność, zdjęcia, kategoria
   „billboardy” (wolne) / „billboardy zajęte”.
2. Arkusz `DANE GPS.xlsx` od operatora (2026-09-11) — numer PRS, lokalizacja,
   współrzędne, wymiary w cm, rodzaj (JEDNOSTR./DWUSTR.), dostępność
   („wolna” / „brak”), ceny za 1/3/6/12 mies., koszty druku/klejenia/banera/montażu.
   Zawiera też nazwy najemców i daty umów — NIE publikujemy.
3. Cennik PDF `oferta cenowa 2026 - 5.pdf` — tylko PRS 70 (Galeria 7: 900 zł),
   którego nie ma w arkuszu; reszta cen pokrywa się z arkuszem.

Źródło prawdy o dostępności: ARKUSZ (jest świeższy niż kategorie na stronie;
11 tablic się różni). Bez wiersza w arkuszu → status z kategorii strony.

Współrzędne i cena są w DB NOT NULL — tablice bez nich są POMIJANE i wypisywane
na końcu (do dopytania operatora). Wg stanu 2026-09-11: PRS 56, 63, 64, 99 (brak
w arkuszu) oraz PRS 31 (brak GPS w arkuszu, chyba że strona ma mapę).

Tytuł zawiera numer PRS — inaczej tablice o tych samych wymiarach w tym samym
mieście są dla Google duplikatami (por. Biała Podlaska, GSC 2026-09-08).

Zdjęcia: pobierane ze strony operatora, zapisywane jako jpg+webp
w backend/storage/app/public/advertisements/reklamaprzemysl/ (storage nie jest
w gicie — na prod trzeba je przenieść osobno, jak przy Big Group / PR Biznes).

Uruchomienie:
    python3 scripts/import_reklamaprzemysl.py [--no-images]
    cd backend && php artisan db:seed --class=ReklamaPrzemyslSeeder
"""
from __future__ import annotations

import html
import io
import json
import os
import re
import sys
import urllib.request
from dataclasses import dataclass, field

import openpyxl
from PIL import Image

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
XLSX = os.path.join(ROOT, "reklamap-os/status/reklamaprzemysl-gps.xlsx")
OUT_JSON = os.path.join(ROOT, "backend/database/seeders/data/reklamaprzemysl.json")
STORE_DIR = os.path.join(ROOT, "backend/storage/app/public/advertisements/reklamaprzemysl")
REL_PREFIX = "advertisements/reklamaprzemysl"

STORE_API = "https://reklamaprzemysl.pl/wp-json/wc/store/v1/products?per_page=100&page={page}"
UA = "Mozilla/5.0 (X11; Linux x86_64) ReklaMap-import/1.0 (kontakt@reklamap.pl)"

OWNER_EMAIL = "prs@reklamaprzemysl.pl"
PHONE = "608464887"
REGION = "podkarpackie"
MAX_IMAGES = 5

# Kategorie WooCommerce na stronie operatora. Slug „billboardy” został 2026-09 przemianowany
# na „billboardy-wolne” — trzymamy oba, żeby zmiana nazwy nie wycięła tablic z importu.
FREE_SLUGS = {"billboardy", "billboardy-wolne"}
TAKEN_SLUGS = {"billboardy-zajete"}

# Czytelne lokalizacje do tytułów — arkusz operatora używa skrótów roboczych
# („przyst”, „Brow”, „pr.dół”, „po P.P”). Rozwinięcia wg opisów położenia ze strony.
LOCATION_OVERRIDES: dict[int, str] = {
    1: "ul. Sobieskiego, serpentyny (lewa)",
    2: "ul. Sobieskiego, serpentyny (prawa)",
    3: "ul. Sobieskiego, serpentyny (środkowa)",
    5: "ul. Lwowska / Zana, ściana",
    7: "wjazd od Rzeszowa, pole",
    8: "ul. Zana / Mickiewicza (lewa)",
    9: "Hurko, ul. Małachowskiego",
    10: "ul. Zana / Mickiewicza (środkowa)",
    11: "ul. Węgierska, przystanek",
    12: "Nehrybka, rondo",
    14: "wjazd do Medyki (prawa, skrajna)",
    16: "ul. Krakowska",
    17: "ul. Sobieskiego, tablica 7×3,5 m",
    18: "ul. Bohaterów Getta, Pomona",
    19: "ul. Zana / Lwowska (prawa)",
    20: "ogród",
    21: "przejście graniczne",
    22: "al. Solidarności, obwodnica",
    23: "ul. Sportowa, przy E.Leclerc",
    24: "wjazd do Medyki (lewa)",
    25: "Tuje",
    27: "ul. Sanowa, Garbarze",
    28: "ul. 3 Maja, przy Brico Marché",
    29: "ul. Lwowska, Przekopana",
    30: "wjazd do Medyki, dwustronna",
    32: "obwodnica, przy Sanwilu",
    33: "centrum",
    34: "wjazd do Medyki (środkowa)",
    35: "ul. Zana / Mickiewicza (prawa)",
    36: "przy Farm-Bud",
    37: "wjazd do Medyki (prawa)",
    38: "ul. Lwowska, przy serwisie opon",
    39: "ul. Węgierska / Zagłoby",
    40: "Nehrybka, dół",
    41: "Nehrybka, góra",
    42: "ul. Zana / Lwowska (środkowa)",
    43: "ul. 29 Listopada (prawa, dół)",
    44: "rondo Ofiar Wołynia, przy Galerii Sanowa",
    45: "ul. Krakowska (lewa)",
    46: "ul. Krakowska (środkowa)",
    47: "ul. Krakowska (prawa)",
    48: "ul. Zana / Lwowska (lewa)",
    49: "ul. Lwowska, Przekopana, przy torach",
    50: "ul. Węgierska, Browar",
    51: "ul. Bohaterów Getta, przy policji",
    52: "ul. Zana / Lwowska, za PP",
    55: "ul. Krakowska, dwustronna",
    57: "Pełkinie",
    58: "ul. Wincentego Pola",
    59: "ul. Bohaterów Getta, przy Apkon",
    61: "ul. 3 Maja, szkoła (dół)",
    62: "ul. 3 Maja, szkoła (góra)",
    65: "pole 2",
    66: "pole 3",
    67: "pole 4",
    68: "ul. Sobieskiego, tablica 6×3 m",
    70: "Galeria Sanowa, rondo",
    100: "al. Solidarności, przy Rabce (dwustronna)",
    101: "al. Solidarności, przy Rabce (dwustronna, 2)",
}

# Ceny z cennika PDF dla tablic, których nie ma w arkuszu (netto / mies.).
PDF_PRICES: dict[int, dict[str, int]] = {
    70: {"1": 900, "3": 800, "6": 750, "12": 700},  # Galeria 7, 500x250 cm
}
PDF_DIMENSIONS_CM: dict[int, tuple[int, int]] = {70: (500, 250)}

# Współrzędne odczytane z map Google osadzonych na podstronach operatora
# (parametry !3d/!2d w iframe) — dla tablic bez GPS w arkuszu.
MANUAL_COORDS: dict[int, tuple[float, float]] = {
    70: (49.7899834346979, 22.779045076997537),   # Galeria Sanowa, rondo (podstrona PRS 70)
    59: (49.78609343497536, 22.784775076997345),   # Boh. Getta – Apkon (podstrona PRS 59)
}

# Miejscowości, które w arkuszu operator wpisuje pod „Przemyśl”, a są osobnymi
# miejscowościami — wykrywane z tekstu lokalizacji. Kolejność ma znaczenie.
LOCALITY_PATTERNS: list[tuple[str, str]] = [
    (r"jaros[łl]aw", "Jarosław"),
    (r"medyka", "Medyka"),
    (r"[żz]urawica", "Żurawica"),
    (r"fredropol", "Fredropol"),
]
CITY_FIX = {"RZEMYŚL": "Przemyśl", "PRZEMYŚL": "Przemyśl", "JAROSŁAW": "Jarosław"}


@dataclass
class SiteBoard:
    product_id: int
    prs: int | None
    name: str
    free: bool
    address: str
    width_cm: int | None
    height_cm: int | None
    two_sided: bool | None
    lat: float | None
    lng: float | None
    narrative: str
    images: list[str] = field(default_factory=list)


@dataclass
class SheetRow:
    prs: int
    location: str
    free: bool | None
    two_sided: bool | None
    width_cm: int | None
    height_cm: int | None
    city: str | None
    lat: float | None
    lng: float | None
    prices: dict[str, float | None]
    print_poster: float | None
    glue: float | None
    banner_print: str | None
    banner_mount: float | None
    note: str | None


# ---------------------------------------------------------------- helpers ---

def strip_tags(s: str | None) -> str:
    return html.unescape(re.sub(r"<[^>]+>", "\n", s or ""))


def prs_number(text: str) -> int | None:
    m = re.search(r"PRS\s*[–\-/]?\s*(\d+)", text, re.I)
    return int(m.group(1)) if m else None


def parse_dims_cm(text: str) -> tuple[int | None, int | None]:
    m = re.search(r"(\d{3})\s*[x×]\s*(\d{3})", text)
    return (int(m.group(1)), int(m.group(2))) if m else (None, None)


def parse_coords(text: str | None) -> tuple[float | None, float | None]:
    m = re.search(r"(\d{2}\.\d{3,})\s*,\s*(\d{2}\.\d{3,})", str(text or ""))
    if not m:
        return None, None
    lat, lng = float(m.group(1)), float(m.group(2))
    if not (49.0 < lat < 51.0 and 21.5 < lng < 23.5):  # Podkarpacie — filtr literówek
        return None, None
    return round(lat, 6), round(lng, 6)


def to_float(v: object) -> float | None:
    if v is None or v == "":
        return None
    try:
        return float(str(v).replace(",", ".").strip())
    except ValueError:
        return None


def cm(v: object) -> int | None:
    m = re.search(r"(\d+)", str(v or ""))
    return int(m.group(1)) if m else None


def fetch_json(url: str) -> object:
    req = urllib.request.Request(url, headers={"User-Agent": UA, "Accept": "application/json"})
    with urllib.request.urlopen(req, timeout=30) as r:
        return json.load(r)


# ------------------------------------------------------------- źródła ----

def load_site() -> list[SiteBoard]:
    products: list[dict] = []
    for page in range(1, 6):
        batch = fetch_json(STORE_API.format(page=page))
        if not batch:
            break
        products.extend(batch)
    boards: list[SiteBoard] = []
    for p in products:
        slugs = {c["slug"] for c in p.get("categories", [])}
        if not slugs & FREE_SLUGS | slugs & TAKEN_SLUGS:
            continue
        short = strip_tags(p.get("short_description"))
        name = strip_tags(p["name"]).strip()

        def field_after(label: str) -> str:
            m = re.search(label + r"[^\n]*?\s{2,}([^\n]+)", short, re.I)
            return m.group(1).strip() if m else ""

        w, h = parse_dims_cm(field_after("Wymiary") or short)
        rodzaj = field_after("Rodzaj").lower()
        two_sided = True if "dwustr" in rodzaj else (False if "jednostr" in rodzaj else None)
        lat, lng = parse_coords(field_after("Dane GPS"))
        narrative = short.split("Dane GPS")[-1] if "Dane GPS" in short else short
        narrative = re.sub(r"^[^A-ZŻŹĆŁŚĄĘŃÓ]*", "", re.sub(r"\s+", " ", narrative)).strip()
        boards.append(
            SiteBoard(
                product_id=p["id"],
                prs=prs_number(name),
                name=name,
                free=bool(slugs & FREE_SLUGS),
                address=field_after("Adres"),
                width_cm=w,
                height_cm=h,
                two_sided=two_sided,
                lat=lat,
                lng=lng,
                narrative=narrative,
                images=[img["src"] for img in p.get("images", [])][:MAX_IMAGES],
            )
        )
    return boards


def load_sheet() -> dict[int, SheetRow]:
    ws = openpyxl.load_workbook(XLSX, data_only=True).worksheets[0]
    rows = list(ws.iter_rows(values_only=True))
    hdr = [str(h).strip() if h else "" for h in rows[0]]
    out: dict[int, SheetRow] = {}
    for raw in rows[1:]:
        r = dict(zip(hdr, raw))
        n = prs_number(str(r.get("numer nośnika dostawcy") or ""))
        if n is None or not r.get("lokalizacje tablic"):
            continue
        avail = str(r.get("dostępność") or "").strip().lower()
        rodzaj = str(r.get("rodzaj nośnika billboard/siatka") or "").lower()
        lat, lng = parse_coords(r.get("długość i szerokość geograficzna"))
        out[n] = SheetRow(
            prs=n,
            location=str(r["lokalizacje tablic"]).strip(),
            free=True if avail == "wolna" else (False if avail == "brak" else None),
            two_sided=True if "dwustr" in rodzaj else (False if "jednostr" in rodzaj else None),
            width_cm=cm(r.get("szerokość nośnika")),
            height_cm=cm(r.get("wysokość nośnika")),
            city=CITY_FIX.get(str(r.get("miasto") or "").strip().upper()),
            lat=lat,
            lng=lng,
            prices={
                "1": to_float(r.get("koszt ekspozycji miesięcznej")),
                "3": to_float(r.get("koszt ekspozycji trzy miesięcznej")),
                "6": to_float(r.get("koszt ekspozycji sześcio miesięcznej")),
                "12": to_float(r.get("koszt ekspozycji rocznej")),
            },
            print_poster=to_float(r.get("koszt druku plakatu")),
            glue=to_float(r.get("koszt klejenia")),
            banner_print=str(r["koszt wydruku banera"]).strip() if r.get("koszt wydruku banera") else None,
            banner_mount=to_float(r.get("koszt ontażu banera")),
            note=str(r["informacje dodatkowe"]).strip() if r.get("informacje dodatkowe") else None,
        )
    return out


# ------------------------------------------------------------- scalanie ---

def clean_location(loc: str) -> str:
    loc = re.sub(r"\s+", " ", loc).strip(" -–/.")
    loc = re.sub(r"^(Przemyśl|Przemyślal\.|Jarosław)\s*[-–]?\s*", lambda m: "al. " if "al." in m.group(0) else "", loc, flags=re.I)
    loc = re.sub(r"\b(praw|lew|śr|środ|środk|jedn|dwustr|ul)\b\.?", lambda m: {
        "praw": "prawa", "lew": "lewa", "śr": "środkowa", "środ": "środkowa", "środk": "środkowa",
        "jedn": "jednostronna", "dwustr": "dwustronna", "ul": "ul.",
    }[m.group(1).lower()], loc, flags=re.I)
    return loc.strip(" -–/,")


def locality_for(text: str, sheet_city: str | None) -> str:
    for pat, city in LOCALITY_PATTERNS:
        if re.search(pat, text, re.I):
            return city
    return sheet_city or "Przemyśl"


def download_images(urls: list[str], base: str) -> list[str]:
    os.makedirs(STORE_DIR, exist_ok=True)
    rel: list[str] = []
    for i, url in enumerate(urls, start=1):
        name = f"{base}-{i}"
        jpg = os.path.join(STORE_DIR, f"{name}.jpg")
        if not os.path.exists(jpg):
            try:
                req = urllib.request.Request(url, headers={"User-Agent": UA})
                with urllib.request.urlopen(req, timeout=60) as r:
                    data = r.read()
                img = Image.open(io.BytesIO(data)).convert("RGB")
                if max(img.size) > 1600:
                    img.thumbnail((1600, 1600))
                img.save(jpg, "JPEG", quality=85)
                img.save(os.path.join(STORE_DIR, f"{name}.webp"), "WEBP", quality=85)
            except Exception as exc:  # noqa: BLE001 — pojedyncze zdjęcie nie ma zatrzymać importu
                print(f"    ! zdjęcie {url}: {exc}", file=sys.stderr)
                continue
        rel.append(f"{REL_PREFIX}/{name}.jpg")
    return rel


def build_description(
    *, narrative: str, w: float, h: float, two_sided: bool, sheet: SheetRow | None,
    prices: dict[str, float | None], prs: int,
) -> str:
    parts: list[str] = []
    if narrative:
        parts.append(narrative.rstrip(".") + ".")
    parts.append(
        f"Tablica {'dwustronna' if two_sided else 'jednostronna'} o powierzchni {w:.2f} × {h:.2f} m. "
        f"Kod nośnika: PRS {prs}."
    )
    tiers = [(k, v) for k, v in prices.items() if v]
    if tiers:
        labels = {"1": "1 miesiąc", "3": "3 miesiące", "6": "6 miesięcy", "12": "12 miesięcy"}
        tier_txt = ", ".join(f"{labels[k]}: {v:.0f} zł/mies" for k, v in tiers)
        parts.append(f"Cena netto zależy od okresu umowy — {tier_txt}.")
    if any(k in prices and prices[k] for k in ("6", "12")):
        parts.append(
            "Przy umowie na 6 miesięcy operator jednorazowo drukuje i wykleja plakat w cenie dzierżawy, "
            "przy umowie rocznej — drukuje i montuje baner w cenie."
        )
    if sheet:
        extras: list[str] = []
        if sheet.print_poster:
            extras.append(f"druk plakatu {sheet.print_poster:.0f} zł")
        if sheet.glue:
            extras.append(f"wyklejenie {sheet.glue:.0f} zł")
        if sheet.banner_print:
            m = re.match(r"(\d+)\s*/\s*(\d)\s*str", sheet.banner_print)
            extras.append(
                f"druk banera {m.group(1)} zł ({m.group(2)} {'strona' if m.group(2) == '1' else 'strony'})"
                if m else f"druk banera {sheet.banner_print} zł"
            )
        if sheet.banner_mount:
            extras.append(f"montaż banera {sheet.banner_mount:.0f} zł")
        if extras:
            parts.append("Koszty jednorazowe (netto): " + ", ".join(extras) + ".")
        if sheet.note and "podno" in sheet.note.lower():
            parts.append("Obsługa tablicy wymaga podnośnika — jednorazowo +300 zł netto.")
    parts.append("W cenę dzierżawy wliczone jest zaklejenie tablicy po zakończeniu kampanii. Ceny netto, do kwoty doliczany jest 23% VAT.")
    return " ".join(parts)


def main(with_images: bool = True) -> None:
    site = load_site()
    sheet = load_sheet()
    print(f"Strona: {len(site)} tablic; arkusz: {len(sheet)} wierszy z numerem PRS")

    by_prs: dict[int, list[SiteBoard]] = {}
    for b in site:
        if b.prs is not None:
            by_prs.setdefault(b.prs, []).append(b)

    records: list[dict] = []
    skipped: list[str] = []
    pending: list[str] = []
    used_titles: set[str] = set()

    all_prs = sorted(set(by_prs) | set(sheet))
    for prs in all_prs:
        site_boards = by_prs.get(prs, [])
        row = sheet.get(prs)
        # Na stronie bywa ten sam numer PRS pod dwoma produktami (dwie strony / dwa wpisy).
        # Bierzemy wpis z największą liczbą zdjęć jako reprezentanta; pozostałe tylko dokładają zdjęcia.
        site_boards.sort(key=lambda b: (-len(b.images), b.product_id))
        primary = site_boards[0] if site_boards else None
        images = [u for b in site_boards for u in b.images][:MAX_IMAGES]

        # --- współrzędne ---
        lat, lng = (row.lat, row.lng) if row and row.lat else (None, None)
        if lat is None and primary and primary.lat:
            lat, lng = primary.lat, primary.lng
        if lat is None and prs in MANUAL_COORDS:
            lat, lng = MANUAL_COORDS[prs]
        # --- cena ---
        prices: dict[str, float | None] = dict(row.prices) if row else {}
        if prs in PDF_PRICES:
            prices = {k: float(v) for k, v in PDF_PRICES[prs].items()}
        price = prices.get("1")
        negotiable = False
        if price is None and prices.get("12"):
            # PRS 21: operator podał tylko stawki za dłuższe okresy — publikujemy najniższą, do negocjacji.
            price = prices["12"]
            negotiable = True
            pending.append(f"PRS {prs}: brak stawki miesięcznej — wpisano {price:.0f} zł (stawka roczna), price_negotiable")
        # --- wymiary ---
        w_cm = (row.width_cm if row else None) or (primary.width_cm if primary else None)
        h_cm = (row.height_cm if row else None) or (primary.height_cm if primary else None)
        if prs in PDF_DIMENSIONS_CM and not (w_cm and h_cm):
            w_cm, h_cm = PDF_DIMENSIONS_CM[prs]

        location_src = (row.location if row else "") or (primary.address if primary else "") or (primary.name if primary else "")
        city = locality_for(location_src, row.city if row else None)
        location = LOCATION_OVERRIDES.get(prs) or clean_location(location_src)
        location = re.sub(rf"^{re.escape(city)}\s*[-–,]?\s*", "", location, flags=re.I).strip() or city

        missing = [
            what for what, ok in (("GPS", lat is not None), ("cena", price is not None), ("wymiary", bool(w_cm and h_cm)))
            if not ok
        ]
        if missing:
            skipped.append(f"PRS {prs} ({location or '?'}): brak {', '.join(missing)}")
            continue

        two_sided = (row.two_sided if row and row.two_sided is not None else None)
        if two_sided is None:
            two_sided = bool(primary.two_sided) if primary and primary.two_sided is not None else False
        free = row.free if row and row.free is not None else (primary.free if primary else True)

        w, h = w_cm / 100, h_cm / 100
        title = f"Billboard {w:g}x{h:g} m – {location}, {city} (PRS {prs})"
        if title in used_titles:
            title = title.replace(f"(PRS {prs})", f"(PRS {prs}, {primary.product_id if primary else 'b'})")
        used_titles.add(title)

        img_rel = download_images(images, f"prs-{prs:03d}") if (with_images and images) else []
        narrative = primary.narrative if primary else ""
        description = build_description(
            narrative=narrative, w=w, h=h, two_sided=two_sided, sheet=row, prices=prices, prs=prs,
        )

        records.append(
            {
                "ref": f"PRS {prs}",
                "title": title,
                "type": "billboard",
                "location": location,
                "city": city,
                "region": REGION,
                "latitude": lat,
                "longitude": lng,
                "description": description,
                "price": float(price),
                "price_unit": "month",
                "width": round(w, 2),
                "height": round(h, 2),
                "orientation": "horizontal" if w >= h else "vertical",
                "variant": "two_sided" if two_sided else "standard",
                "road_class": "urban",
                "traffic_intensity": "medium",
                "traffic_direction": [],
                "traffic_type": ["vehicular"],
                "has_backlight": False,
                "price_includes_print": False,
                "price_includes_mounting": False,
                "graphic_design_help": True,
                "price_negotiable": negotiable,
                "has_vat_invoice": True,
                "campaign_duration": None,
                "owner_email": OWNER_EMAIL,
                "phone": PHONE,
                "contact_preference": "both",
                "offer_type": "agency",
                "image_url": img_rel[0] if img_rel else None,
                "images": img_rel,
                "has_image": bool(img_rel),
                "status": "active" if free else "reserved",
                "is_active": True,
                "available_from": None,
            }
        )
        src = "arkusz+strona" if (row and primary) else ("arkusz" if row else "strona")
        print(
            f"  PRS {prs:>3}: {title[:70]:70} {price:>5.0f} zł  {'wolna ' if free else 'zajęta'}  "
            f"foto {len(img_rel)}  [{src}]"
        )

    os.makedirs(os.path.dirname(OUT_JSON), exist_ok=True)
    with open(OUT_JSON, "w", encoding="utf-8") as fh:
        json.dump(records, fh, ensure_ascii=False, indent=2)

    active = sum(1 for r in records if r["status"] == "active")
    print(f"\nZapisano {len(records)} rekordów → {OUT_JSON}  (active {active}, reserved {len(records) - active})")
    if pending:
        print("\nDo potwierdzenia z operatorem:")
        for p in pending:
            print("  -", p)
    if skipped:
        print("\nPOMINIĘTE (brak danych obowiązkowych — dopytać operatora):")
        for s in skipped:
            print("  -", s)


if __name__ == "__main__":
    main(with_images="--no-images" not in sys.argv)
