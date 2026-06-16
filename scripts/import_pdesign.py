#!/usr/bin/env python3
"""Import nośników pdesign (Koszalin/Kołobrzeg/Mścice) z grafik lokalizacji.

Adresy odczytane OCR-em (+ podgląd) z grafik na pdesign.com.pl/lokalizacje/N.jpg.
Geokodowanie street+landmark (Nominatim) + lekki rozrzut dla wielu nośników na jednej ulicy.
Zdjęcia: grafiki lokalizacji → jpg+webp do storage.

Parametry (potwierdzone mailem Paweł Janicki): 12 m² = 5,04×2,38 m; cena od 750 zł/mc, do
negocjacji; wszystkie dostępne (active); kontakt biuro@pdesign.com.pl + tel. 574 018 273.

Wynik: backend/database/seeders/data/pdesign.json (+ obrazy). Potem: PdesignSeeder.
"""
import io, json, os, re, time, urllib.parse, urllib.request
from PIL import Image

SRC = "/tmp/pdesign"
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_JSON = os.path.join(ROOT, "backend/database/seeders/data/pdesign.json")
STORE = os.path.join(ROOT, "backend/storage/app/public/advertisements/pdesign")
REL = "advertisements/pdesign"
GEO_CACHE = os.path.join(SRC, "_geocache.json")

OWNER = "biuro@pdesign.com.pl"
PHONE = "574 018 273"

# ręczne uzupełnienia tam, gdzie OCR nie złapał (odczytane z podglądu)
MANUAL = {33: "Koszalin Aleja Armii Krajowej (nasyp PKP)",
          65: "Kołobrzeg rondo Patana", 66: "Kołobrzeg rondo Patana", 67: "Kołobrzeg rondo Patana"}

def clean(a):
    if not a: return a
    a = re.sub(r'^[FH]\s+', '', a.strip())       # śmieci OCR z logo
    return a

def city_of(a):
    # miasto = pierwsze słowo adresu; "kierunek X" to kierunek, nie miasto → Mścice przed Kołobrzeg
    l = a.lower()
    if 'mścice' in l or 'mscice' in l: return 'Mścice'
    if 'kołobrzeg' in l or 'kolobrzeg' in l: return 'Kołobrzeg'
    return 'Koszalin'

def street_of(a, city):
    s = a
    # usuń miasto z początku (różne wielkości liter)
    s = re.sub(r'^\s*'+re.escape(city)+r'\b', '', s, flags=re.I)
    s = re.sub(r'(?i)^koszalin|^kołobrzeg|^mścice', '', s)
    s = s.replace('-', ' ').strip(' -')
    s = re.sub(r'(?i)\bkierunek\b.*$', '', s).strip()   # "kierunek Kołobrzeg" precz
    s = re.sub(r'\s+', ' ', s).strip()
    return s

geo = json.load(open(GEO_CACHE)) if os.path.exists(GEO_CACHE) else {}
UA = "reklamap-import/1.0 (kontakt@reklamap.pl)"

def _nominatim(params):
    try:
        url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&" + params
        req = urllib.request.Request(url, headers={"User-Agent": UA})
        j = json.load(urllib.request.urlopen(req, timeout=15))
        time.sleep(1.1)
        if j:
            return [float(j[0]['lat']), float(j[0]['lon'])]
    except Exception as e:
        print(f"  geo błąd {params}: {e}")
    return None

def geocode_board(a, city, street):
    """street+landmark; bez prefiksu 'ul.' i bez 'Polska' (to psuło Nominatim)."""
    l = a.lower()
    st = re.sub(r'(?i)^\s*(ul|al|aleja|pl)\.?\s*', '', street).strip()  # bez 'ul.'
    if 'patana' in l: key = f"Rondo Jerzego Patana, {city}"
    elif 'armii krajowej' in l or 'nasyp' in l: key = f"Armii Krajowej, {city}"
    else: key = f"{st}, {city}"

    if key in geo: return geo[key]
    coord = _nominatim("q=" + urllib.parse.quote(key))               # 1) wolne "Ulica, Miasto"
    if not coord and st:                                             # 2) strukturalne
        coord = _nominatim(f"street={urllib.parse.quote(st)}&city={urllib.parse.quote(city)}&country=Poland")
    if not coord:                                                    # 3) fallback: miasto
        coord = _nominatim("q=" + urllib.parse.quote(f"{city}, Polska"))
    geo[key] = coord
    print(f"  geo: {key} → {coord}")
    return coord

def save_img(n):
    src = None
    for ext in ('jpg','JPG','png','jpeg'):
        p = os.path.join(SRC, f"{n}.{ext}")
        if os.path.exists(p): src = p; break
    if not src: return None
    os.makedirs(STORE, exist_ok=True)
    img = Image.open(src).convert("RGB")
    if img.width > 1600: img = img.resize((1600, round(img.height*1600/img.width)))
    img.save(os.path.join(STORE, f"{n}.jpg"), "JPEG", quality=85)
    img.save(os.path.join(STORE, f"{n}.webp"), "WEBP", quality=85)
    return f"{REL}/{n}.jpg"

addr = {int(k): v for k, v in json.load(open(os.path.join(SRC, "_addr.json"))).items()}
records = []
for n in range(1, 81):
    a = MANUAL.get(n) or clean(addr.get(n))
    if not a:
        print(f"  POMIJAM {n}: brak adresu"); continue
    city = city_of(a)
    street = street_of(a, city)
    coord = geocode_board(a, city, street)
    if not coord:
        print(f"  POMIJAM {n}: brak geo"); continue
    # deterministyczny rozrzut ±~70 m, by nośniki z jednej ulicy się nie nakładały
    h = (n * 2654435761) & 0xffffffff
    jlat = ((h % 1000)/1000 - 0.5) * 0.0014
    jlng = ((h//1000 % 1000)/1000 - 0.5) * 0.0014
    lat, lng = round(coord[0]+jlat, 8), round(coord[1]+jlng, 8)

    street_disp = street if street else a
    title = f"Billboard {city} – {street_disp}"
    desc = (f"Billboard 12 m² (5,04×2,38 m) w lokalizacji: {city}, {street_disp}. "
            f"Cena od 750 zł/mc netto (cena wywoławcza, do negocjacji).")
    records.append({
        "title": title[:120], "type": "billboard",
        "location": street_disp or city, "city": city,
        "region": "zachodniopomorskie",
        "latitude": lat, "longitude": lng,
        "description": desc,
        "price": 750, "price_unit": "month",
        "width": 5.04, "height": 2.38,
        "orientation": "landscape", "variant": "standard",
        "road_class": "urban", "traffic_intensity": "medium",
        "has_backlight": False, "price_negotiable": True, "has_vat_invoice": True,
        "price_includes_print": False, "price_includes_mounting": False,
        "owner_email": OWNER, "phone": PHONE, "offer_type": "agency",
        "image_url": save_img(n), "images": [f"{REL}/{n}.jpg"], "has_image": True,
        "status": "active", "is_active": True, "available_from": None,
    })

json.dump(geo, open(GEO_CACHE, "w"), ensure_ascii=False, indent=1)
json.dump(records, open(OUT_JSON, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
from collections import Counter
print(f"\nGotowe → {OUT_JSON}")
print(f"Nośników: {len(records)} | miasta: {dict(Counter(r['city'] for r in records))}")
print(f"Zdjęcia: {STORE}")
