#!/usr/bin/env python3
"""Pobiera realne zdjęcia nośników Optokom z optokom.pl i podpina je do optokom.json.

Powód: pierwotny import wstawił wspólny `optokom-placeholder.jpg` (thin/duplicate → Google
„crawled, not indexed", patrz SEO_TECH_AUDIT 2026-06-15). Realne, unikalne zdjęcia to
najsilniejszy lewar na odblokowanie indeksacji leaf-stron.

Pipeline (spójny z import_optokom.py → optokom.json → OptokomSeeder):
1. crawl /lokalizacje → kategorie-miasta → pozycje (slug zawiera ref, np. be05)
2. z podstrony pozycji czyta galerię: /images/galerie/{id}/full/{ref}-galeria-N.webp
3. pobiera zdjęcia, konwertuje webp→jpg (Pillow), zapisuje do storage/app/public/advertisements/optokom/
4. aktualizuje optokom.json: image_url = pierwsze zdjęcie, images = wszystkie (ścieżki lokalne)

Po uruchomieniu: php artisan db:seed --class=OptokomSeeder

Użycie:
    python3 scripts/fetch_optokom_images.py                 # całość
    python3 scripts/fetch_optokom_images.py --max-cities 1  # test na 1 mieście
"""

import argparse
import io
import json
import os
import re
import sys
import time
import urllib.request

from PIL import Image

BASE = "https://optokom.pl"
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OPTOKOM_JSON = os.path.join(ROOT, "backend/database/seeders/data/optokom.json")
STORE_DIR = os.path.join(ROOT, "backend/storage/app/public/advertisements/optokom")
REL_PREFIX = "advertisements/optokom"
UA = "Mozilla/5.0 (compatible; ReklaMapImport/1.0; +https://reklamap.pl)"

GALLERY_RE = re.compile(r'/images/galerie/\d+/full/([a-z]{2}\d{2,3})-galeria-\d+\.webp', re.I)
CITY_RE = re.compile(r'href="(/lokalizacje/[a-z0-9-]+)"', re.I)
ITEM_RE_TMPL = r'href="({city}/[^"]+)"'


def fetch(url: str) -> str:
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=30) as r:
        return r.read().decode("utf-8", errors="replace")


def fetch_bytes(url: str) -> bytes:
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=30) as r:
        return r.read()


def city_slugs(home: str) -> list[str]:
    slugs = []
    for m in CITY_RE.finditer(home):
        s = m.group(1)
        if s.count("/") == 2 and s not in slugs:   # /lokalizacje/{miasto}, bez głębszych
            slugs.append(s)
    return slugs


def item_urls(city_html: str, city_path: str) -> list[str]:
    rx = re.compile(ITEM_RE_TMPL.format(city=re.escape(city_path)), re.I)
    seen, out = set(), []
    for m in rx.finditer(city_html):
        u = m.group(1)
        if u != city_path and u not in seen:
            seen.add(u)
            out.append(u)
    return out


def gallery_for_item(item_html: str) -> tuple[str | None, list[str]]:
    """Zwraca (ref, [pełne URL-e zdjęć]) na podstawie nazw plików galerii."""
    ref = None
    urls = []
    for m in re.finditer(r'/images/galerie/\d+/full/[a-z]{2}\d{2,3}-galeria-\d+\.webp', item_html, re.I):
        u = BASE + m.group(0)
        if u not in urls:
            urls.append(u)
        if ref is None:
            gm = GALLERY_RE.search(m.group(0))
            if gm:
                ref = gm.group(1).upper()
    return ref, urls


def save_image(data: bytes, ref: str, idx: int) -> str | None:
    """Zapisuje jpg + webp, zwraca relatywną ścieżkę do jpg (lub None przy błędzie)."""
    try:
        img = Image.open(io.BytesIO(data)).convert("RGB")
        if img.width > 1600:
            img = img.resize((1600, round(img.height * 1600 / img.width)))
        base = f"{ref.lower()}-{idx}"
        img.save(os.path.join(STORE_DIR, f"{base}.jpg"), "JPEG", quality=85)
        img.save(os.path.join(STORE_DIR, f"{base}.webp"), "WEBP", quality=85)
        return f"{REL_PREFIX}/{base}.jpg"
    except Exception as e:
        print(f"    błąd zapisu {ref}#{idx}: {e}")
        return None


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--max-cities", type=int, default=0, help="ile miast (0 = wszystkie)")
    ap.add_argument("--delay", type=float, default=0.2)
    args = ap.parse_args()

    os.makedirs(STORE_DIR, exist_ok=True)

    print("Crawl /lokalizacje …")
    home = fetch(f"{BASE}/lokalizacje")
    cities = city_slugs(home)
    if args.max_cities:
        cities = cities[:args.max_cities]
    print(f"Miast: {len(cities)}")

    # ref → [pełne URL-e zdjęć]
    ref_photos: dict[str, list[str]] = {}
    for ci, city in enumerate(cities, 1):
        try:
            chtml = fetch(BASE + city)
        except Exception as e:
            print(f"  [{city}] błąd: {e}")
            continue
        items = item_urls(chtml, city)
        print(f"  [{ci}/{len(cities)}] {city} → {len(items)} pozycji")
        for it in items:
            time.sleep(args.delay)
            try:
                ihtml = fetch(BASE + it)
            except Exception as e:
                print(f"      {it} błąd: {e}")
                continue
            ref, urls = gallery_for_item(ihtml)
            if ref and urls:
                ref_photos.setdefault(ref, [])
                for u in urls:
                    if u not in ref_photos[ref]:
                        ref_photos[ref].append(u)

    # fallback: dla ref-ów z optokom.json bez galerii spróbuj /images/design/{ref}.webp
    all_refs = {(r.get("ref") or "").upper() for r in json.load(open(OPTOKOM_JSON, encoding="utf-8"))}
    missing = [r for r in all_refs if r and r not in ref_photos]
    print(f"Galerie: {len(ref_photos)} nośników. Sprawdzam fallback (design/) dla {len(missing)} bez galerii…")
    for ref in missing:
        time.sleep(args.delay)
        url = f"{BASE}/images/design/{ref.lower()}.webp"
        try:
            req = urllib.request.Request(url, headers={"User-Agent": UA}, method="HEAD")
            with urllib.request.urlopen(req, timeout=15) as r:
                ok = r.status == 200 and "image" in (r.headers.get("Content-Type") or "")
            if ok:
                ref_photos[ref] = [url]
        except Exception:
            pass

    print(f"\nZnaleziono zdjęcia dla {len(ref_photos)} nośników. Pobieram i konwertuję…")

    # pobranie + konwersja
    ref_local: dict[str, list[str]] = {}
    for ref, urls in ref_photos.items():
        local = []
        for idx, u in enumerate(urls, 1):
            time.sleep(args.delay)
            try:
                rel = save_image(fetch_bytes(u), ref, idx)
                if rel:
                    local.append(rel)
            except Exception as e:
                print(f"    pobranie {u} błąd: {e}")
        if local:
            ref_local[ref] = local

    # aktualizacja optokom.json
    records = json.load(open(OPTOKOM_JSON, encoding="utf-8"))
    updated = 0
    for rec in records:
        ref = (rec.get("ref") or "").upper()
        if ref in ref_local:
            rec["image_url"] = ref_local[ref][0]
            rec["images"] = ref_local[ref]
            rec["has_image"] = True
            updated += 1
    json.dump(records, open(OPTOKOM_JSON, "w", encoding="utf-8"), ensure_ascii=False, indent=2)

    no_photo = len(records) - updated
    print(f"\nGotowe.")
    print(f"  Nośników z realnym zdjęciem: {updated}/{len(records)}")
    print(f"  Bez zdjęcia (zostaje placeholder): {no_photo}")
    print(f"  Zdjęcia w: {STORE_DIR}")
    print(f"\nTeraz przeseeduj: php artisan db:seed --class=OptokomSeeder")
    return 0


if __name__ == "__main__":
    sys.exit(main())
