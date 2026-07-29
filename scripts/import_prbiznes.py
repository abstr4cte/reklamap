#!/usr/bin/env python3
"""Import billboardów agencji PR Biznes (Olsztyn) z arkusza ODS + zdjęć/kart PDF.

Źródło: /home/dev/Pobrane/prbiznes/ (BB Olsztyn.ods + zdjęcia PNG + karty lokalizacji PDF).
Arkusz ma pełne dane (adres, format, symbol, WSPÓŁRZĘDNE WPROST w arkuszu — bez geokodowania),
ale zdjęcia są w dwóch formach: część jako gotowe PNG, część tylko jako karta PDF
("Karta lokalizacji nośnika" — zdjęcie + mapka na jednej stronie). Dla tych drugich
wycinamy region ze zdjęciem z wyrenderowanej strony PDF (layout kart jest spójny).

3 lokalizacje (OLS 006, OLS 007, OLS 306a) miały zdjęcie/kartę, ale BRAK wiersza w arkuszu
(brak ceny). Dopytane mailem 2026-07-29: OLS 007 i OLS 306a sprzedane w umowie długoterminowej
(pomijamy na stałe), OLS 006 potwierdzone z ceną — dopisane ręcznie do MANUAL_EXTRA niżej
(dane z karty lokalizacji: 2.00x5.00 m, adres/opis z PNG, GPS geokodowany przez Nominatim,
bo w karcie nie ma współrzędnych wprost jak w arkuszu).

Po uruchomieniu: php artisan db:seed --class=PrBiznesSeeder
"""

import io
import json
import os
import re
import sys
import time
import urllib.parse
import urllib.request

from odf.opendocument import load
from odf.table import Table, TableRow, TableCell
from odf.text import P
from PIL import Image

SRC_DIR = "/home/dev/Pobrane/prbiznes"
ODS = os.path.join(SRC_DIR, "BB Olsztyn.ods")
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_JSON = os.path.join(ROOT, "backend/database/seeders/data/prbiznes.json")
STORE_DIR = os.path.join(ROOT, "backend/storage/app/public/advertisements/prbiznes")
REL_PREFIX = "advertisements/prbiznes"

OWNER_EMAIL = "biuro@prbiznes.pl"
PHONE = "502941504"

# symbol z arkusza -> zdjęcie: ("png", nazwa_pliku) lub ("pdf", nazwa_pliku, crop_box)
# crop_box wycięty z renderu 100dpi (861x1218) — region "Widok na nośnik", pomija mapę i stopkę.
PDF_CROP = (18, 245, 843, 615)
PHOTO_SOURCE = {
    "OLS 001": ("png", "Bałtycka Wilgii OLS 001.png"),
    "OLS 002": ("png", "Bałtycka Likusy OLS 002.png"),
    "OLS 003": ("png", "Bałtycka OLS 003.png"),
    "OLS 005": ("png", "Sielska wjazd do Olsztyna OLS 005.png"),
    "OLS 008": ("png", "Wilczyńskiego OLS 008.png"),
    "OLS 009": ("png", "Leonharda OLS 009.png"),
    "OLS 010": ("png", "Witosa Kubusia Puchatka OLS 010.png"),
    "OLS 011": ("png", "Pstrowskiego Dworcowa OLS 011.png"),
    "OLS 012": ("png", "Dworcowa Kętrzyńskiego Ozgraf OLS 012.png"),
    "OLS 013": ("png", "Limanowskiego Żeromskiego OLS 013.png"),
    "OLS 0311": ("pdf", "BB Olsztyn Wilczyńskiego-Sikorskiego.pdf"),
    "OLS 306": ("pdf", "BB Niepodległosci 66.pdf"),
    "OLS 66": ("pdf", "BB Warszawska 88 kierunek Kortowo.pdf"),
    "OLS WF 55": ("pdf", "BB BIG Centrum Piłsudskiego AS.pdf"),
    "OLS WF 02": ("pdf", "BB BIG Centrum ratusz pl. Jana Pawła II.pdf"),
    "OLS 05": ("pdf", "BB Piłsudskiego-Dąbrowszczaków 1.pdf"),
    "OLS WF 79": ("pdf", "BB BIG Limanowskiego-Zientary Malewskiej.pdf"),
    "OLS 302": ("pdf", "BB Bałtycka (Gutkowo) 18m2.pdf"),
    "OLS WF 202": ("pdf", "BB BIG 1 Maja 13 - Centrum.pdf"),
}

# Wiersze bez ceny w arkuszu — OLS 007/306a sprzedane długoterminowo (pomijamy na stałe),
# OLS 006 przeniesione do MANUAL_EXTRA po potwierdzeniu ceny mailem.
SKIP_NO_PRICE = {"OLS 007", "OLS 306a"}

# Lokalizacje potwierdzone mailem, spoza arkusza ODS — dane z karty lokalizacji (PNG),
# nie z wiersza arkusza, więc GPS trzeba geokodować (karta nie ma współrzędnych wprost).
MANUAL_EXTRA = [
    {
        "symbol": "OLS 006",
        "adres": "Rondo Olsztyńska / Wadąska, Dywity (wjazd od Dywit do Olsztyna)",
        "geocode_query": "Olsztyńska, Dywity, Polska",
        "width_m": 5.00,
        "height_m": 2.00,
        "material": "baner",
        "cena_mc": 1090.0,
        "montaz": 990.0,  # w mailu: "druk i montaż: 990 zł netto" — jedna łączna pozycja
        "demontaz": 190.0,
        "photo_png": "Dywity wjazd do Olsztyna OLS 006.png",
        "photo_crop": (0, 55, 600, 415),  # region ze zdjęciem na karcie lokalizacji (bez tekstu/mapy)
        "extra_note": "Lokalizacja przy rondzie, w pobliżu stacji MOYA, ALDI, Lidl i cmentarza komunalnego.",
    },
]


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


def save_manual_crop(src_path: str, box, base: str) -> str:
    img = Image.open(src_path).convert("RGB").crop(box)
    os.makedirs(STORE_DIR, exist_ok=True)
    img.save(os.path.join(STORE_DIR, f"{base}.jpg"), "JPEG", quality=85)
    img.save(os.path.join(STORE_DIR, f"{base}.webp"), "WEBP", quality=85)
    return f"{REL_PREFIX}/{base}.jpg"


def read_ods_rows(path):
    doc = load(path)
    table = doc.getElementsByType(Table)[0]
    rows = table.getElementsByType(TableRow)
    out = []
    for row in rows:
        cells = row.getElementsByType(TableCell)
        vals = []
        for c in cells:
            ps = c.getElementsByType(P)
            text = " ".join(str(p) for p in ps)
            repeat = int(c.getAttribute("numbercolumnsrepeated") or 1)
            vals.extend([text] * repeat)
        while vals and not vals[-1].strip():
            vals.pop()
        if vals:
            out.append(vals)
    return out


def parse_pl_number(s: str) -> float:
    s = (s or "").strip().replace("\xa0", " ")
    s = re.sub(r"\s+", "", s)
    s = s.replace(",", ".")
    return float(s) if s else 0.0


def parse_dims_m(fmt: str):
    m = re.search(r"(\d+)\s*cm\s*x\s*(\d+)\s*cm", fmt, re.IGNORECASE)
    if not m:
        return None, None
    return round(int(m.group(1)) / 100, 2), round(int(m.group(2)) / 100, 2)


def parse_coords(s: str):
    m = re.search(r"([\d.]+)\s*N\s*,\s*([\d.]+)\s*E", s, re.IGNORECASE)
    if not m:
        return None, None
    return float(m.group(1)), float(m.group(2))


def save_png(src_path: str, base: str) -> str:
    img = Image.open(src_path).convert("RGB")
    if img.width > 1600:
        img = img.resize((1600, round(img.height * 1600 / img.width)))
    os.makedirs(STORE_DIR, exist_ok=True)
    img.save(os.path.join(STORE_DIR, f"{base}.jpg"), "JPEG", quality=85)
    img.save(os.path.join(STORE_DIR, f"{base}.webp"), "WEBP", quality=85)
    return f"{REL_PREFIX}/{base}.jpg"


def save_pdf_crop(pdf_path: str, base: str) -> str:
    import subprocess
    import tempfile
    with tempfile.TemporaryDirectory() as tmp:
        prefix = os.path.join(tmp, "page")
        subprocess.run(
            ["pdftoppm", "-png", "-r", "100", "-f", "1", "-l", "1", pdf_path, prefix],
            check=True, capture_output=True,
        )
        rendered = prefix + "-1.png"
        if not os.path.isfile(rendered):
            # niektóre wersje poppler nie dodają numeru strony przy jednostronicowym PDF
            candidates = [f for f in os.listdir(tmp) if f.startswith("page")]
            if not candidates:
                raise FileNotFoundError(f"pdftoppm nie wygenerował pliku dla {pdf_path}")
            rendered = os.path.join(tmp, candidates[0])
        img = Image.open(rendered).convert("RGB").crop(PDF_CROP)
        os.makedirs(STORE_DIR, exist_ok=True)
        img.save(os.path.join(STORE_DIR, f"{base}.jpg"), "JPEG", quality=85)
        img.save(os.path.join(STORE_DIR, f"{base}.webp"), "WEBP", quality=85)
        return f"{REL_PREFIX}/{base}.jpg"


def resolve_photo(symbol: str, base: str):
    src = PHOTO_SOURCE.get(symbol)
    if not src:
        print(f"    [!] brak zdjęcia dla {symbol}")
        return None
    kind, fname = src
    path = os.path.join(SRC_DIR, fname)
    if not os.path.isfile(path):
        print(f"    [!] plik nie istnieje: {fname}")
        return None
    try:
        if kind == "png":
            return save_png(path, base)
        return save_pdf_crop(path, base)
    except Exception as e:
        print(f"    [!] błąd zdjęcia {symbol}: {e}")
        return None


MATERIAL_LABEL = {
    "baner": "baner", "plakat": "plakat", "plakat/baner": "plakat lub baner",
    "baner/siatka": "baner lub siatka wielkoformatowa", "siatka": "siatka wielkoformatowa",
}


def main() -> int:
    rows = read_ods_rows(ODS)
    # pierwsze 2 wiersze to nagłówek + wiersz z nazwami "Kolumna..." — dane od 3. wiersza
    data_rows = [r for r in rows[2:] if len(r) >= 10 and r[0].strip() == "Olsztyn"]

    records = []
    skipped = []
    for i, r in enumerate(data_rows, start=1):
        city, adres, typ, fmt, symbol, coords, material = r[0:7]
        oswietlenie = r[7] if len(r) > 7 else ""
        cena_mc = r[8] if len(r) > 8 else "0"
        montaz = r[9] if len(r) > 9 else "0"
        demontaz = r[10] if len(r) > 10 else "0"

        symbol = symbol.strip()
        if symbol in SKIP_NO_PRICE:
            skipped.append(symbol)
            continue

        width, height = parse_dims_m(fmt)
        lat, lng = parse_coords(coords)
        price = parse_pl_number(cena_mc)
        montaz_v = parse_pl_number(montaz)
        demontaz_v = parse_pl_number(demontaz)

        if not width or not height or not price or lat is None:
            print(f"  [!] pomijam {symbol} — niekompletne dane (w={width} h={height} cena={price} geo={lat})")
            skipped.append(symbol)
            continue

        adres_clean = re.sub(r"\s*\(.*?\)\s*", " ", adres).strip()
        title = f"Billboard {width:.2f}x{height:.2f} m – {adres_clean}, Olsztyn"
        material_label = MATERIAL_LABEL.get(material.strip().lower(), material.strip() or "baner")

        desc_parts = [
            f"Billboard reklamowy w Olsztynie, lokalizacja: {adres_clean}.",
            f"Powierzchnia ekspozycyjna: {width:.2f}x{height:.2f} m ({typ.replace('BB', '').strip()}).",
            f"Nośnik typu: {material_label}. Kod nośnika: {symbol}.",
            f"Cena najmu: {price:.0f} zł/miesiąc netto + 23% VAT.",
        ]
        if montaz_v or demontaz_v:
            desc_parts.append(
                f"Jednorazowo dodatkowo: montaż {montaz_v:.0f} zł, demontaż {demontaz_v:.0f} zł (netto + VAT)."
            )
        if "dodatkow" in oswietlenie.lower():
            desc_parts.append("Możliwość podświetlenia za dodatkową opłatą.")
        description = " ".join(desc_parts)

        base = f"prbiznes-{i:02d}"
        img_rel = resolve_photo(symbol, base)

        rec = {
            "ref": symbol,
            "title": title,
            "type": "billboard",
            "location": adres_clean,
            "city": "Olsztyn",
            "latitude": lat,
            "longitude": lng,
            "description": description,
            "price": price,
            "price_unit": "month",
            "width": width,
            "height": height,
            "orientation": "horizontal" if width >= height else "vertical",
            "variant": "standard",
            "road_class": "urban",
            "traffic_intensity": "medium",
            "traffic_direction": [],
            "traffic_type": ["vehicular"],
            "has_backlight": "tak" in oswietlenie.lower(),
            "price_includes_print": False,
            "price_includes_mounting": False,
            "graphic_design_help": False,
            "estimated_daily_views": None,
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
        records.append(rec)
        print(f"  {symbol}: {adres_clean} — {price:.0f} zł/mc — {'foto OK' if img_rel else 'BRAK FOTO'}")

    for m in MANUAL_EXTRA:
        lat, lng = geocode(m["geocode_query"])
        if lat is None:
            print(f"  [!] {m['symbol']}: geokodowanie nie powiodło się, pomijam")
            continue

        width, height = m["width_m"], m["height_m"]
        title = f"Billboard {width:.2f}x{height:.2f} m – {m['adres']}"
        material_label = MATERIAL_LABEL.get(m["material"], m["material"])
        desc_parts = [
            f"Billboard reklamowy w Olsztynie/Dywitach, lokalizacja: {m['adres']}.",
            f"Powierzchnia ekspozycyjna: {width:.2f}x{height:.2f} m.",
            f"Nośnik typu: {material_label}. Kod nośnika: {m['symbol']}.",
            f"Cena najmu: {m['cena_mc']:.0f} zł/miesiąc netto + 23% VAT.",
            f"Jednorazowo dodatkowo: druk i montaż {m['montaz']:.0f} zł, demontaż {m['demontaz']:.0f} zł (netto + VAT).",
            m.get("extra_note", ""),
        ]
        description = " ".join(p for p in desc_parts if p)

        base = f"prbiznes-{len(records) + 1:02d}"
        img_rel = save_manual_crop(os.path.join(SRC_DIR, m["photo_png"]), m["photo_crop"], base)

        rec = {
            "ref": m["symbol"],
            "title": title,
            "type": "billboard",
            "location": m["adres"],
            "city": "Olsztyn",
            "latitude": lat,
            "longitude": lng,
            "description": description,
            "price": m["cena_mc"],
            "price_unit": "month",
            "width": width,
            "height": height,
            "orientation": "horizontal" if width >= height else "vertical",
            "variant": "standard",
            "road_class": "urban",
            "traffic_intensity": "medium",
            "traffic_direction": [],
            "traffic_type": ["vehicular"],
            "has_backlight": False,
            "price_includes_print": False,
            "price_includes_mounting": False,
            "graphic_design_help": False,
            "estimated_daily_views": None,
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
        records.append(rec)
        print(f"  {m['symbol']}: {m['adres']} — {m['cena_mc']:.0f} zł/mc — geo {lat},{lng}")

    os.makedirs(os.path.dirname(OUT_JSON), exist_ok=True)
    json.dump(records, open(OUT_JSON, "w", encoding="utf-8"), ensure_ascii=False, indent=2)

    with_photo = sum(1 for r in records if r["image_url"])
    print(f"\nGotowe → {OUT_JSON}")
    print(f"  Nośników: {len(records)} | ze zdjęciem: {with_photo}")
    if skipped:
        print(f"  Pominięte (brak ceny/danych): {', '.join(skipped)}")
    print(f"  Zdjęcia: {STORE_DIR}")
    print(f"\nTeraz: php artisan db:seed --class=PrBiznesSeeder")
    return 0


if __name__ == "__main__":
    sys.exit(main())
