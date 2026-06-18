#!/usr/bin/env python3
"""Import nośników agencji Outdoor 3miasto z wypełnionego szablonu Excel + lokalnych zdjęć.

Źródło: /home/dev/Pobrane/reklamap-zdjecia/ (reklamap-import-outdoor.xlsx + foldery billboard_<Lp.>).
Mapuje polskie etykiety na kanoniczne wartości systemu (jak import_optokom.py),
kopiuje i konwertuje zdjęcia (jpg + webp) do storage, zapisuje outdoor3miasto.json.

Po uruchomieniu: php artisan db:seed --class=Outdoor3miastoSeeder
"""

import io
import json
import os
import re
import sys
import time
import urllib.parse
import urllib.request

import openpyxl
from PIL import Image

SRC_DIR = "/home/dev/Pobrane/reklamap-zdjecia"
XLSX = os.path.join(SRC_DIR, "reklamap-import-outdoor.xlsx")
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_JSON = os.path.join(ROOT, "backend/database/seeders/data/outdoor3miasto.json")
STORE_DIR = os.path.join(ROOT, "backend/storage/app/public/advertisements/outdoor3miasto")
REL_PREFIX = "advertisements/outdoor3miasto"

OWNER_EMAIL = "koordynator@outdoor3miasto.com"
PHONE = "602244800"  # potwierdzone mailem 2026-06-18 — pokazywać przy ogłoszeniach

ORIENT = {"pozioma": "landscape", "pionowa": "portrait"}
ROADCLASS = {"autostrada": "highway", "ekspresowa": "expressway", "krajowa": "national",
             "wojewódzka": "regional", "lokalna": "local", "miejska": "urban"}
INTENSITY = {"niski": "low", "średni": "medium", "wysoki": "high"}
VARIANT = {"jednostronny": "standard", "dwustronny (back-to-back)": "two_sided",
           "trójstronny (prismatron)": "three_sided", "scrolling / rolowany": "scrolling"}
PRICE_UNIT = {"za dzień": "day", "za tydzień": "week", "za miesiąc": "month",
              "za rok": "year", "za m²": "sqm", "za kampanię": "campaign"}


def truthy(v) -> bool:
    return str(v).strip().lower() in ("tak", "true", "1", "yes")


def map_traffic_type(v: str) -> list:
    v = (v or "").lower()
    out = []
    if "piesz" in v:
        out.append("pedestrian")
    if "samochod" in v:
        out.append("vehicular")
    return out or ["vehicular"]


def map_traffic_dir(v: str) -> list:
    v = (v or "").lower()
    if "wjazd" in v and "wyjazd" in v:
        return ["both"]
    if "wjazd" in v:
        return ["entry"]
    if "wyjazd" in v:
        return ["exit"]
    return []


GEO_CACHE = os.path.join(SRC_DIR, ".geocache.json")
geo = json.load(open(GEO_CACHE)) if os.path.exists(GEO_CACHE) else {}


def geocode(address: str, city: str):
    # współrzędne wprost z adresu (WGS84 ... lat, lng)
    m = re.search(r'(\d{2}\.\d{4,})\s*,\s*(\d{2}\.\d{4,})', address)
    if m:
        return float(m.group(1)), float(m.group(2))
    key = f"{address}|{city}".lower()
    if key in geo:
        return geo[key]["lat"], geo[key]["lng"]
    addr_clean = re.sub(r'\(.*?\)', '', address).strip()
    q = urllib.parse.quote(f"{addr_clean}, {city}, Polska")
    url = f"https://nominatim.openstreetmap.org/search?format=json&limit=1&q={q}"
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "reklamap-import/1.0 (kontakt@reklamap.pl)"})
        with urllib.request.urlopen(req, timeout=15) as r:
            j = json.load(r)
        time.sleep(1.1)
        if j:
            geo[key] = {"lat": float(j[0]["lat"]), "lng": float(j[0]["lon"])}
            return geo[key]["lat"], geo[key]["lng"]
        # fallback: samo miasto
        q2 = urllib.parse.quote(f"{city}, Polska")
        req2 = urllib.request.Request(f"https://nominatim.openstreetmap.org/search?format=json&limit=1&q={q2}",
                                      headers={"User-Agent": "reklamap-import/1.0"})
        with urllib.request.urlopen(req2, timeout=15) as r:
            j2 = json.load(r)
        time.sleep(1.1)
        if j2:
            return float(j2[0]["lat"]), float(j2[0]["lon"])
    except Exception as e:
        print(f"    geo błąd ({city}): {e}")
    return None, None


def find_photo(lp: int):
    folder = os.path.join(SRC_DIR, f"billboard_{lp}")
    if not os.path.isdir(folder):
        return None
    for f in os.listdir(folder):
        if f.lower().endswith((".jpg", ".jpeg", ".png", ".webp")) and not f.startswith("_"):
            return os.path.join(folder, f)
    return None


def save_photo(path: str, lp: int):
    try:
        img = Image.open(path).convert("RGB")
        if img.width > 1600:
            img = img.resize((1600, round(img.height * 1600 / img.width)))
        base = f"o3m-{lp}"
        os.makedirs(STORE_DIR, exist_ok=True)
        img.save(os.path.join(STORE_DIR, f"{base}.jpg"), "JPEG", quality=85)
        img.save(os.path.join(STORE_DIR, f"{base}.webp"), "WEBP", quality=85)
        return f"{REL_PREFIX}/{base}.jpg"
    except Exception as e:
        print(f"    foto błąd lp={lp}: {e}")
        return None


def filled_rows(ws):
    rows = list(ws.iter_rows(values_only=True))
    hi = next(i for i, r in enumerate(rows) if r and str(r[0]).strip() == "Lp.")
    hdr = [str(h).strip() if h else "" for h in rows[hi]]
    data = [r for r in rows[hi + 1:] if r and r[0] is not None and str(r[1] or "").strip()]
    return hdr, data


def main() -> int:
    wb = openpyxl.load_workbook(XLSX, data_only=True)
    hdr, data = filled_rows(wb["Billboard"])
    col = {h: i for i, h in enumerate(hdr)}

    def g(r, name):
        i = col.get(name)
        return r[i] if i is not None and i < len(r) else None

    records = []
    for r in data:
        lp = int(g(r, "Lp."))
        addr_raw = str(g(r, "Adres / lokalizacja") or "").strip()
        city = str(g(r, "Miasto") or "").strip()
        location = re.sub(r'\s*\(współrzędne[^)]*\)', '', addr_raw).strip()
        lat, lng = geocode(addr_raw, city)
        print(f"  lp {lp}: {city} → {lat},{lng}")

        photo_path = find_photo(lp)
        img_rel = save_photo(photo_path, lp) if photo_path else None

        backlight_raw = str(g(r, "Typ podświetlenia") or "").strip().lower()
        has_backlight = backlight_raw not in ("", "brak", "none")

        rec = {
            "ref": f"O3M{lp:02d}",
            "title": str(g(r, "Tytuł ogłoszenia") or "").strip(),
            "type": "billboard",
            "location": location or city,
            "city": city,
            "latitude": lat,
            "longitude": lng,
            "description": str(g(r, "Opis") or "").strip(),
            "price": float(g(r, "Cena (PLN)") or 0),
            "price_unit": PRICE_UNIT.get(str(g(r, "Cena za okres") or "").strip().lower(), "month"),
            "width": float(g(r, "Szerokość [m]") or 0) or None,
            "height": float(g(r, "Wysokość [m]") or 0) or None,
            "orientation": ORIENT.get(str(g(r, "Orientacja") or "").strip().lower(), "landscape"),
            "variant": VARIANT.get(str(g(r, "Wariant") or "").strip().lower(), "standard"),
            "road_class": ROADCLASS.get(str(g(r, "Klasa drogi") or "").strip().lower(), "urban"),
            "traffic_intensity": INTENSITY.get(str(g(r, "Natężenie ruchu") or "").strip().lower(), "medium"),
            "traffic_direction": map_traffic_dir(str(g(r, "Kierunek ruchu") or "")),
            "traffic_type": map_traffic_type(str(g(r, "Typ ruchu") or "")),
            "has_backlight": has_backlight,
            "price_includes_print": truthy(g(r, "Cena zawiera druk")),
            "price_includes_mounting": truthy(g(r, "Cena zawiera montaż")),
            "graphic_design_help": truthy(g(r, "Pomoc w projekcie graf.")),
            "estimated_daily_views": int(g(r, "Szacunkowe OTS / dzień")) if g(r, "Szacunkowe OTS / dzień") else None,
            "price_negotiable": truthy(g(r, "Cena do negocjacji")),
            "has_vat_invoice": True,
            "campaign_duration": int(g(r, "Długość kampanii [dni]")) if g(r, "Długość kampanii [dni]") else None,
            "owner_email": OWNER_EMAIL,
            "phone": PHONE,
            "contact_preference": "both" if PHONE else "form",
            "offer_type": "agency",
            "image_url": img_rel,
            "images": [img_rel] if img_rel else [],
            "has_image": bool(img_rel),
            "status": "active",
            "is_active": True,
            "available_from": None,
        }
        records.append(rec)

    json.dump(geo, open(GEO_CACHE, "w"), ensure_ascii=False, indent=2)
    json.dump(records, open(OUT_JSON, "w", encoding="utf-8"), ensure_ascii=False, indent=2)

    with_photo = sum(1 for r in records if r["image_url"])
    no_geo = sum(1 for r in records if not r["latitude"])
    print(f"\nGotowe → {OUT_JSON}")
    print(f"  Nośników: {len(records)} | ze zdjęciem: {with_photo} | bez geo: {no_geo}")
    print(f"  Zdjęcia: {STORE_DIR}")
    print(f"\nTeraz: php artisan db:seed --class=Outdoor3miastoSeeder")
    return 0


if __name__ == "__main__":
    sys.exit(main())
