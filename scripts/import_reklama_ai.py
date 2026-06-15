#!/usr/bin/env python3
"""Scraper portfolio nośników agencji reklama.ai → CSV gotowy pod import ReklaMap.

Źródło: https://www.reklama.ai/wynajem_billboardow/tablice/... (statyczny HTML, paginacja /1../N).
Zbiera: nr, miasto, adres, wymiary (cm→m), powierzchnię, typ, status, link do zdjęcia.
NIE zbiera cen (na stronie ich nie ma) — kolumna `cena` zostaje pusta do uzupełnienia z cennika agencji.

Użycie:
    python3 scripts/import_reklama_ai.py                # pobiera wszystkie strony, zapisuje CSV
    python3 scripts/import_reklama_ai.py --pages 2      # tylko 2 pierwsze strony (test)
"""

import argparse
import csv
import html as html_lib
import re
import sys
import time
import urllib.request

BASE = "https://www.reklama.ai"
LIST_URL = BASE + "/wynajem_billboardow/tablice/wszystkie_miejscowosci/wolne_i_zajete/dowolny_rozmiar/{page}"
UA = "Mozilla/5.0 (compatible; ReklaMapImport/1.0; +https://reklamap.pl)"
OUT = "reklamap-os/status/reklama_ai_nosniki.csv"

# data-bb-typ → typ nośnika w ReklaMap
TYP_MAP = {
    "zwykly": "billboard",
    "podswietlany": "billboard",   # podświetlony billboard nadal billboard, flaga podświetlenia osobno
    "telebim": "led_screen",
    "led": "led_screen",
}

CARD_RE = re.compile(
    r'<div class="(?P<status>wolna|zajeta)[^"]*pojedynczy-billboard"[^>]*id="(?P<id>[^"]+)"',
    re.IGNORECASE,
)
# miasto bywa poprzedzone ikoną premium (<img ...>) wewnątrz <strong>; bierzemy całą zawartość i czyścimy z tagów
MIASTO_RE = re.compile(
    r'<strong class="miasto">(?P<inner>.*?)</strong>\s*(?P<adres>[^<]*?)\s*<br',
    re.IGNORECASE | re.DOTALL,
)
TAG_RE = re.compile(r'<[^>]+>')
WYMIARY_RE = re.compile(r'wymiary tablicy:\s*(\d+)\s*x\s*(\d+)\s*cm', re.IGNORECASE)
POW_RE = re.compile(r"data-bb-powierzchnia='(\d+)'")
TYP_RE = re.compile(r'data-bb-typ="([^"]+)"')
IMG_RE = re.compile(r'<a href="(/assets/images/billboardy/[^"]+\.jpg)"', re.IGNORECASE)
DETAL_RE = re.compile(r'href="(https://www\.reklama\.ai/wynajem_billboardow/tablica/[^"]+)"')


def fetch(url: str) -> str:
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    with urllib.request.urlopen(req, timeout=30) as resp:
        return resp.read().decode("utf-8", errors="replace")


def detect_last_page(first_html: str) -> int:
    pages = [int(n) for n in re.findall(r'/dowolny_rozmiar/(\d+)"', first_html)]
    return max(pages) if pages else 1


def parse_cards(page_html: str) -> list[dict]:
    cards = []
    # granice kart: od jednego nagłówka karty do następnego
    matches = list(CARD_RE.finditer(page_html))
    for idx, m in enumerate(matches):
        start = m.start()
        end = matches[idx + 1].start() if idx + 1 < len(matches) else len(page_html)
        block = page_html[start:end]

        nr = m.group("id").strip()
        status_raw = m.group("status").lower()

        miasto = adres = ""
        mm = MIASTO_RE.search(block)
        if mm:
            miasto = html_lib.unescape(TAG_RE.sub('', mm.group("inner"))).strip()
            adres = html_lib.unescape(mm.group("adres")).strip()

        szer_m = wys_m = ""
        wm = WYMIARY_RE.search(block)
        if wm:
            szer_m = round(int(wm.group(1)) / 100, 2)
            wys_m = round(int(wm.group(2)) / 100, 2)

        pow_m2 = ""
        pm = POW_RE.search(block)
        if pm:
            pow_m2 = int(pm.group(1))

        typ_raw = ""
        tm = TYP_RE.search(block)
        if tm:
            typ_raw = tm.group(1).lower()
        # ekrany LED nie mają data-bb-typ — rozpoznajemy po numerze (telebim*)
        if "telebim" in nr.lower():
            typ = "led_screen"
        else:
            typ = TYP_MAP.get(typ_raw, "billboard")

        img = ""
        has_photo = False
        im = IMG_RE.search(block)
        if im:
            href = im.group(1)
            if "brak_zdjecia" not in href:
                img = BASE + href
                has_photo = True

        detal = ""
        dm = DETAL_RE.search(block)
        if dm:
            detal = dm.group(1)

        # podświetlenie — sygnał z adresu/opisu ("podświetlana")
        podswietlany = "tak" if "podświetl" in (adres + typ_raw).lower() else ""

        cards.append({
            "nr": nr,
            "typ_nosnika": typ,
            "miasto": miasto,
            "adres": adres,
            "szerokosc_m": szer_m,
            "wysokosc_m": wys_m,
            "powierzchnia_m2": pow_m2,
            "podswietlany": podswietlany,
            "cena": "",            # do uzupełnienia z cennika agencji
            "jednostka_ceny": "",  # do uzupełnienia (np. month / sqm / campaign)
            "status": "zarezerwowany" if status_raw == "zajeta" else "wolny",
            "link_zdjecia": img,
            "ma_zdjecie": "tak" if has_photo else "nie",
            "url_zrodlo": detal,
        })
    return cards


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--pages", type=int, default=0, help="ile stron pobrać (0 = wszystkie)")
    ap.add_argument("--delay", type=float, default=0.8, help="pauza między stronami (s)")
    ap.add_argument("--out", default=OUT)
    args = ap.parse_args()

    print(f"Pobieram stronę 1: {LIST_URL.format(page=1)}")
    first = fetch(LIST_URL.format(page=1))
    last = detect_last_page(first)
    total_pages = args.pages if args.pages else last
    print(f"Wykryto stron: {last}. Pobieram: {total_pages}.")

    all_cards = parse_cards(first)
    for p in range(2, total_pages + 1):
        time.sleep(args.delay)
        print(f"Strona {p}/{total_pages}...")
        all_cards.extend(parse_cards(fetch(LIST_URL.format(page=p))))

    # deduplikacja po nr (na wszelki wypadek)
    seen, unique = set(), []
    for c in all_cards:
        if c["nr"] in seen:
            continue
        seen.add(c["nr"])
        unique.append(c)

    fields = ["nr", "typ_nosnika", "miasto", "adres", "szerokosc_m", "wysokosc_m",
              "powierzchnia_m2", "podswietlany", "cena", "jednostka_ceny",
              "status", "link_zdjecia", "ma_zdjecie", "url_zrodlo"]
    with open(args.out, "w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=fields)
        w.writeheader()
        w.writerows(unique)

    wolne = sum(1 for c in unique if c["status"] == "wolny")
    bez_foto = sum(1 for c in unique if c["ma_zdjecie"] == "nie")
    miasta = len({c["miasto"] for c in unique if c["miasto"]})
    print(f"\nGotowe → {args.out}")
    print(f"Nośników: {len(unique)} | wolne: {wolne} | zarezerwowane: {len(unique)-wolne}")
    print(f"Miejscowości: {miasta} | bez zdjęcia (placeholder z logo): {bez_foto}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
