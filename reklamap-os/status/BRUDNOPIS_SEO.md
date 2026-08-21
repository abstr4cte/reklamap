# Brudnopis SEO — ReklaMap

- **Silos docelowy:** poradniki — **NOWY** `posts/20260821213000_reklama-na-przystankach.md` (opublikowany jako draft 2026-08-21)
- **Hasło główne:** `reklama na przystankach` + warstwa miejska (`reklama na przystankach {miasto}`)
- **Angle:** *Reklamy na przystanku nie wynajmiesz od miasta — gabloty należą do operatora, który wybudował wiaty w zamian za prawo do reklamy. Pierwsze zestawienie stawek za zajęcie pasa drogowego dla wiat z 9 dzienników urzędowych.*
- **Trigger:** GSC 90 dni (2026-05-20 .. 2026-08-18): **192 wyśw / 7 fraz / poz. 36,8 / 0 klik**. Gdańsk 68, Poznań 64, Łódź 28, Kraków 11, Wrocław 11, Bydgoszcz 6, Katowice 4.

> ⚠️ **Zapisane po fakcie (2026-08-21).** Artykuł powstał z pominięciem Stratega i brudnopisu — materiał zebrano przy okazji rozbudowy `citylight-reklama` i `telebim-ekran-led-reklama`. Koszt tego skrótu: kanibalizacja (24% zdań duplikatem citylightu) wykryta dopiero przez Korektora, po napisaniu całego tekstu. Przy kolejnych tematach wracamy do kolejności Strateg → Pisarz → Korektor.

---

## 🚩 FLAGI dla Pisarza i Korektora

1. **NAJGROŹNIEJSZA KLASA BŁĘDU W TYM KLASTRZE: uchwała nieuchylona ≠ uchwała aktualna.** Dwie uchwały cytowane w pierwszej wersji okazały się zastąpione — Kraków XXX/789/19 (uchylona przez XXXVI/730/25 z 17.09.2025) i załącznik Łodzi LX/1804/22 (zastąpiony przez LXVIII/2022/22 od 1.01.2023). **Przy KAŻDEJ stawce z uchwały sprawdź, czy załącznik nie został zastąpiony.** Wojewódzkie dzienniki mają API: `GET /api/legalact?year=&journal=&position=` zwraca metryczkę z listą aktów zmieniających.
2. **NIE UŻYWAJ tekstów ujednoliconych z podstron urzędów.** Warszawski tekst ujednolicony z `ochota.um.warszawa.pl` kończy się na zmianie z 2019 r., a jego stawki reklamowe są nieaktualne od 31.12.2022. Korektor trzykrotnie zgłosił na jego podstawie fałszywy „błąd". **Zawsze PDF z dziennika urzędowego.**
3. **Warunek „podmiot sam wybudował wiatę" NIE jest powszechny.** Występuje w uchwale tylko w **Gdańsku, Katowicach i Krakowie**. W Warszawie, Gdyni, Łodzi, Poznaniu, Wrocławiu i Lublinie kryterium jest czysto techniczne (nośnik ma być częścią wiaty). Nie uogólniać.
4. **Kanibalizacja z `citylight-reklama`** — citylight i gablota przystankowa to fizycznie ten sam nośnik. Podział ról ustalony 2026-08-21: `citylight-reklama` = **format i cennik**, `reklama-na-przystankach` = **warstwa miejsko-prawna i kanał zakupu**. Nie powielać tabel.
5. Akamai blokuje `edziennik.mazowieckie.pl` przy zwykłym curlu — potrzebny pełny zestaw nagłówków przeglądarki (User-Agent + Accept + Accept-Language + Sec-Fetch-*).

---

## Twarde dane — stawki za zajęcie pasa drogowego (zł/m²/dobę)

| Miasto | Gablota w wiacie | Zwykła reklama | Uchwała |
|---|---:|---:|---|
| Warszawa | **0,01** | 3,25–6,75 | XXXI/666/2004 w brzmieniu LXXIV/2468/2022 z 15.12.2022, Dz.Urz.Woj.Maz. 2022 poz. 13948, zał. 3 poz. 34 (wiata) i poz. 20 (zwykła) |
| Gdańsk | 0,06–0,03 | 3,10–2,80 (oświetlone) · 2,60–2,30 (pozostałe) | LV/1389/22 z 27.10.2022, § 4 poz. 16 |
| Gdynia | 0,05 | 4,00–2,50 | XIII/429/19 z 23.10.2019, § 4 ust. 2; tekst jednolity obwieszczenie XII/O/24 z 18.12.2024 |
| Katowice | 0,08–0,04 | 4,00–2,00 | **XX/476/20 z 28.05.2020** (uchyliła XLVIII/896/17), § 4 ust. 2 pkt 2 |
| Kraków | 0,13–0,10 | 4,50–4,00 | **XXXVI/730/25 z 17.09.2025** (§ 8 uchyla XXX/789/19), poz. 3 i poz. 8 |
| Łódź | 0,15 | 6,00–4,00 | LX/1804/22 w brzmieniu **LXVIII/2022/22 z 16.11.2022**, Dz.Urz.Woj.Łódz. 2022 poz. 7025, poz. 7 i poz. 12 |
| Poznań | 0,40 | 5,00–1,50 | XCI/1749/VIII/2023 z 24.10.2023, § 6 |
| Wrocław | 0,70 | 7,00–5,00 | LIX/1549/22 z 20.10.2022 ze zm. LXI/1582/22, zał. 3 |
| Lublin | 1,90 | 5,10–3,80 | wykaz ZDiTM „Opłaty jednolite 2025", poz. 5 i 6b |
| Olsztyn | **brak pozycji** | 3,50–2,50 | VII/99/24 z 27.11.2024, Dz.Urz.Woj.W-M 2024 poz. 5624, § 4 ust. 2 |

**Stawka dla ekranów LED / zmiennej treści** (do artykułu o telebimach): Kraków **10,00 zł** ryczałtem (XXXVI/730/25 poz. 5) · Warszawa 10,00 zł (poz. 22c i 23) · Gdańsk 10,00 zł (poz. 20) · **Łódź, Wrocław i Katowice NIE MAJĄ osobnej pozycji** — ekran liczony jak zwykła reklama (sprawdzone w pełnych załącznikach).

## Operatorzy wiat

| Miasto | Operator | Szczegóły |
|---|---|---|
| Warszawa | AMS SERWIS | umowa 3.10.2025, 1 640 wiat, **roczna z opcją przedłużeń półrocznych** (przedłużona VIII 2026), wynagrodzenie wyłącznie z reklamy, wartość szac. 64,21 mln zł netto |
| Poznań | Ströer + AMS | wiatami zarządza Grupa MTP; Ströer ok. 400 powierzchni na 124 wiatach od 1.02.2019; AMS 1 086 gablot na 323 wiatach od VII 2019; umowy 10-letnie |
| Gdańsk | Ströer (2 pakiety) + AMS (1) | umowy 12-letnie z 2023 r. z **GZDiZ** (od 1.01.2026 Gdański Zarząd Dróg), 215 wiat, 35 gotowych do VII 2026 |
| Wrocław | AMS + Bauer Media Outdoor | 430 **wiat** z citylightami; BIP ZDiUM bez daty, wciąż pisze „ClearChannel" — wpis nieodświeżony |
| Kraków | AMS + Business Consulting | wg mpk.krakow.pl |
| Łódź | rozproszone | dane UMŁ z **19.06.2019**: ZDiT 572, UMŁ 187, MPK 184, AMS 141, Clear Channel 135, prywatne 2 (razem 1 221). AMS zrewitalizował potem ok. 250 wiat |
| Lublin | MPK Lublin | gabloty 1,2 × 1,8 m, publiczny cennik |
| Olsztyn | ZDZiT (miasto) | gabloty **1,15 × 1,78 m**; komunikat o zaprzestaniu udostępniania od 1.01.2025 SPRZECZNY z przetargami na wiaty z gablotami (2025 i 2026) — wymaga telefonu, 89 544-32-93 |
| Gdynia | **nierozstrzygnięte** | PPP na 15 lat, 260 wiat + 14 nośników, oferty AMS i Bauer, stan na 16.04.2026 |

## Cenniki operatorów (zweryfikowane co do złotówki)

- **AMS**, cennik Klasyczne OOH od **1.07.2026** (`ams.com.pl/oferta/cennik`): citylight **735–4 655 zł** netto/mc wg przedziału dobowych kontaktów P1–P10; Premium CL **1 110–6 725 zł**; poza obszarem badania Outdoor Track odpowiednio **860** i **1 755 zł**. Indeks aglomeracyjny: Warszawa/Trójmiasto/Wrocław **×1,35**, Kraków/Poznań **×1,2**; Katowice, Łódź, Lublin w badaniu bez mnożnika. Ekspozycja **półmiesięczna** ×0,7. Cena CL obejmuje rotację, nie obejmuje druku. 735 zł to cena RC przed rabatami.
- **Bauer Media Outdoor Poland** (do 1.04.2025 Clear Channel Poland), cennik od **19.08.2026**: citylight **2 010–4 110 zł** wg kategorii VAC A–E (Gemius); Citylight Select 2 510–5 140 zł. Miesiąc = **kampania 28 dni**, 2 tygodnie ×0,7. Zmiana plakatu 74 zł, ekspresowa instalacja 54 zł, dokumentacja zdjęciowa 185 zł.
- **Ströer** — cen nie publikuje (pakiet / tailor-made). Mediakit podaje listę miast per produkt.
- **MPK Lublin** (plik z 22.08.2024): gablota CL 1,2 × 1,8 m, **za jedną stronę**: do 14 dni **150 zł**, 15–21 dni **200 zł**, **22–31 dni 250 zł**. Montaż i demontaż w cenie. Najemca płaci osobno za zajęcie pasa drogowego.
- Druk: papier 135 g 50–90 zł/szt. **przy nakładzie** (detalicznie 160+); folia backlight **ok. 120–200 zł/szt.**

## Uchwały krajobrazowe — status

| Miasto | Status |
|---|---|
| Gdańsk | obowiązuje XLVIII/1465/18, dostosowanie minęło 3.04.2020; definiuje „reklamę typu A" 1,2×1,8 m, **na obiekcie także 0,9×2,26 m** (±5%) |
| Gdynia | obowiązuje LV/1678/23, **strefy A i B**, dostosowanie 12 mies.; „gablota ekspozycyjna" do 3 m², wiaty w RAL 7016 |
| Kraków | obowiązuje XXXVI/908/20 od 1.07.2020; **§ 9 ust. 8 ZAKAZUJE telebimów i ekranów LED/LCD** z 5 wyjątkami; NSA II OSK 1153/25 z 2.06.2026 — pytanie prejudycjalne do TSUE |
| Łódź | obowiązuje XXXVII/966/16, ale przepisy dostosowawcze prawomocnie unieważnione (WSA 9.10.2024); **NIK ocenił egzekwowanie NEGATYWNIE** (LLO.410.18.2.2023) |
| Poznań | obowiązuje LXXXVIII/1671/VIII/2023, § 10 ust. 1–3 i 6 nieważne (NSA II OSK 934/24 z 18.03.2026) |
| Warszawa | **NIE OBOWIĄZUJE** — NSA II OSK 1005/21 z 21.09.2021. „Strefy A/B/C" nie istnieją |
| Wrocław | nie uchwalona; Park Kulturowy „Stare Miasto" § 17 ust. 1 zakazuje ekranów na zewnątrz budynków |
| Katowice | nie uchwalona; prace stanęły po wyroku TK z 12.12.2023 |
| Lublin, Olsztyn | nie uchwalone |

---

## Czego NIE udało się potwierdzić

- Aktualność danych operatorskich na 2026 dla **Wrocławia i Łodzi** (BIP bez daty / dane z 2019). Wymagałoby wniosku o informację publiczną — uznane za nieopłacalne.
- **Liczba ekranów DOOH w Polsce** — raport OOHlife podaje wartość rynku (DOOH 278,39 mln zł w 2025, +16,4%), nie liczbę nośników.
- Adresy ekranów u AMS, Ströera, Bauera, Cityboard, Jet Line — strony blokują automat (HTTP 403/406).
- Utożsamienie działek 1/179–1/185 obr. 52 Nowa Huta (wyjątek w krakowskiej uchwale) z TAURON Areną — logiczne, ale bez potwierdzenia geodezyjnego.
