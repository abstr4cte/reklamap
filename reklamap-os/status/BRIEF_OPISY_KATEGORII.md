# Brief: opisy kategorii i lokalizacji powierzchni reklamowych

> Zadanie jednorazowe dla **Agenta Pisarza**. Sekcje „Wytyczne techniczne SEO" i „Czego brief NIE załatwia" pochodzą od **Agenta Architekta SEO**.
> To **nie jest** artykuł blogowy — pomiń wymagania bloga (3 poziomy CTA, FAQ, 1500 słów, frontmatter posta, INDEX.md, seeder). Obowiązuje wyłącznie to, co poniżej.

> **Realizacja w transzach (instrukcja dla uruchamiającego, nie dla Pisarza):** zakres A (10) + B (12) + C (~32) to za dużo na jedną odpowiedź — jakość ostatnich wpisów spadnie. Odpalaj Pisarza osobno na **A**, potem na **B**, potem na **C** (trzy wywołania), a po każdej transzy puść **Korektora**. Jeden brief, trzy przebiegi.

---

## Cel

Przepisać teksty stron list ofert tak, by każda strona miała **unikalny opis**, nasycony właściwą frazą, który realnie pomaga reklamodawcy zdecydować, czy dany typ nośnika / dane miasto pasuje do jego kampanii. Dotyczy trzech rodzajów stron:

| Strona | URL | Źródło tekstu |
|---|---|---|
| Kategoria (typ) | `/powierzchnie-reklamowe/<typ>` | `categoryDescriptions['<typ>']` |
| Miasto | `/powierzchnie-reklamowe/<miasto>` | `cityDescriptions['<miasto>']` |
| Typ × miasto | `/powierzchnie-reklamowe/<typ>/<miasto>` | `typeCityDescriptions['<typ>-<miasto>']` |

Dla każdej strony:
- pole **`title`** jest renderowane jako widoczny **`<h1>`** (`CategoryDescription.vue` — przy stronach typ×miasto bywa nadpisane przez `customTitle` z `ListingsPage.vue`, ale na czystych stronach typu i miasta to realny H1, więc fraza musi w nim być);
- pole **`description`** jest renderowane jako jeden `<p>` (przycięty CSS-em do ~3 linijek, rozwijany przyciskiem „Czytaj więcej"), **a jego początek trafia jako `<meta name="description">`** — patrz niżej;
- pole **`benefits`** (lista „Dlaczego warto?") jest pokazywane dopiero po kliknięciu „Czytaj więcej" — nie trafia do meta tagów, ma niższą wagę SEO, ale buduje treść i zaufanie na stronie.

Meta `<title>` powstaje osobno w kodzie (`ListingsPage.vue` → `seoData`) i **nie korzysta** z pola `title` z tego pliku — jest poza zakresem briefu (patrz „Czego brief NIE załatwia").

## Jak `description` staje się `<meta name="description">`

W `ListingsPage.vue` (computed `seoData`) gdy istnieje wpis w `typeCityDescriptions` / `categoryDescriptions` / `cityDescriptions` dla danego URL:

```ts
description = truncateAtWord(descObj.description, 155)
```

`truncateAtWord` (`frontend/src/utils/text.ts`) przycina do ~155 znaków **na granicy słowa** (nie w połowie wyrazu) i dokleja `…`. Konsekwencje dla Pisarza:
- **Pierwsze ~150 znaków `description` muszą bronić się jako samodzielny opis w SERP** — fraza główna + konkret już w pierwszym zdaniu. Zero lanego wstępu, zero „Witamy na…", zero „W dzisiejszych czasach…".
- Pierwsze zdanie celuj w **≤ ~150 znaków** — wtedy snippet kończy się czystą myślą, a nie wielokropkiem w środku zdania.
- Nie zaczynaj `description` od nazwy serwisu ani od pytania retorycznego.

Gdy wpisu brak — kod generuje generyczny szablon („Przeglądaj oferty … Porównuj ceny, lokalizacje…"). To znaczy: **każdy nowy wpis tutaj realnie podmienia słaby generyk na unikalną treść** dla tego URL.

## Gdzie to trafia

Plik: `frontend/src/data/categoryDescriptions.ts`. Trzy obiekty, ta sama struktura wpisu (nie zmieniaj jej):

```ts
interface CategoryDescription {
  title: string        // widoczny H1 strony
  description: string   // JEDEN akapit zwykłego tekstu — bez HTML, bez linków, bez list
  benefits: string[]    // DOKŁADNIE 5 punktów, każdy = 1 zdanie
}
```

Zachowaj istniejące komentarze grupujące (`// Billboardy`, `// ── BILLBOARDY ──`, `// Citylighty` itd.).

## Zakres — co przepisać

**Część A — `categoryDescriptions` (10 wpisów, priorytet 1):**
`''` (wszystkie powierzchnie), `billboardy`, `citylighty`, `ekrany-led`, `banery`, `sciany-reklamowe`, `totemy-reklamowe`, `reklama-w-transporcie`, `reklama-mobilna`, `inne`.

> **Wyjątek dla wpisu `''`:** na gołej stronie `/powierzchnie-reklamowe` `seoData` wpada w gałąź `else` i `<meta name="description">` jest **hardkodowane w kodzie** — `''.description` tam NIE trafia. Edytując wpis `''` zmieniasz tylko widoczny `<h1>` i blok tekstu na stronie, nie meta tagi. W pozostałych 9 wpisach `description` realnie ląduje w SERP — w `''` nie. Pisz wpis `''` pod czytelnika na stronie, nie pod snippet.

**Część B — `cityDescriptions` (12 wpisów, priorytet 2):**
`warszawa`, `krakow`, `wroclaw`, `poznan`, `gdansk`, `lodz`, `katowice`, `szczecin`, `bydgoszcz`, `lublin`, `bialystok`, `gdynia`.
Mają te same wady co część A (lany wstęp typu „X to największy i najbardziej konkurencyjny rynek…", superlatywy, nieźródłowane liczby) — wymagają tego samego przejścia.

**Część C — `typeCityDescriptions` (~32 wpisy: 4 typy × 8 miast — POPRAWKA CHIRURGICZNA, nie pełne przepisanie):**
Te wpisy są w większości dobre (konkretne nazwy tras, ronda, landmarki) — **nie przepisuj ich od zera**, bo łatwo zepsuć to, co działa. Zamiast tego przejdź każdy wpis i **popraw w miejscu** tylko to, co łamie wytyczne techniczne:
- (a) **nieźródłowane liczby → zmiękcz** (np. „14 mln turystów rocznie" → „milionów turystów rocznie"; „200 tys. studentów" → „jednym z największych ośrodków akademickich w kraju"; „200+ tys. gości rocznie" → „setki tysięcy gości targowych"). Standardowe formaty nośników (12 m², 18 m²) zostają.
- (b) **wątpliwe nazwy obiektów/tras → zweryfikuj**; jeśli nie masz pewności, że dany rondo/węzeł/obwodnica istnieje i nazywa się tak — zamień na sformułowanie ogólne („przy głównych trasach wylotowych", „w okolicach centrum handlowego") zamiast usuwać konkret całkiem.
- (c) **placeholdery / resztki copy → usuń.**
- (d) jeśli `description` zaczyna się od lanego wstępu zamiast od frazy + konkretu — popraw pierwsze zdanie wg zasady „1. zdanie ≤ ~150 znaków, fraza `<typ> w <mieście>` na początku".
- Nie dotykaj wpisów, w których nic z powyższego nie występuje.
Jeśli przy okazji zauważysz, że któryś `cityDescriptions` i pasujący `typeCityDescriptions` (np. `warszawa` vs `billboardy-warszawa`) brzmią jak wariacja tego samego akapitu — to jest duplikat: zróżnicuj `typeCityDescriptions` (ma być o TYM typie nośnika w tym mieście, nie o mieście w ogóle).

## Wytyczne techniczne SEO (od Architekta)

1. **Unikalność = brak duplikatów.** Zero powtarzających się akapitów ani benefitów między wpisami — to thin/duplicate content. Każdy typ ma własne argumenty, formaty i grupy docelowe; każde miasto — własną charakterystykę rynku, własne arterie i landmarki. Uwaga na styk warstw: `cityDescriptions['warszawa']` i `typeCityDescriptions['billboardy-warszawa']` nie mogą być wariacją tego samego akapitu.
2. **Fraza główna per wpis** — pojawia się w `title` (H1) i w pierwszym zdaniu `description`.
   - **Mapa fraz dla `categoryDescriptions`:**

     | klucz | fraza główna | poboczne (LSI) |
     |---|---|---|
     | `billboardy` | wynajem billboardów | billboard reklamowy, tablica wielkoformatowa, reklama przy drodze, format 12 m² / 18 m², prismatron |
     | `citylighty` | citylight reklamowy | citylight / city light (obie pisownie mają wolumen), reklama na przystanku, gablota 120×180, OOH w centrum miasta |
     | `ekrany-led` | ekran LED reklamowy | telebim, billboard LED, DOOH, reklama cyfrowa, digital signage |
     | `banery` | baner reklamowy | siatka mesh, baner winylowy, reklama na ogrodzeniu / elewacji |
     | `sciany-reklamowe` | ściana reklamowa | mural reklamowy, reklama na elewacji, wielkoformatowa siatka mesh |
     | `totemy-reklamowe` | totem reklamowy | pylon reklamowy, reklama przy galerii / stacji paliw |
     | `reklama-w-transporcie` | reklama w transporcie | reklama na autobusie / tramwaju, oklejanie pojazdów, transit advertising |
     | `reklama-mobilna` | reklama mobilna | mobile billboard, przyczepa reklamowa, pojazd reklamowy |
     | `inne` | nietypowe powierzchnie reklamowe | reklama niestandardowa, ambient OOH |
     | `''` | powierzchnie reklamowe | wynajem nośników reklamowych, OOH w Polsce, billboardy citylighty ekrany LED |

   - **Frazy dla `cityDescriptions`:** główna = `powierzchnie reklamowe <miasto>` (w odmianie naturalnej: „w Warszawie", „we Wrocławiu", „w Łodzi"). Poboczne: `billboard <miasto>`, `reklama zewnętrzna <miasto>`, `wynajem billboardu <miasto>`, ewentualnie nazwa regionu. Fraza główna w `title` i w 1. zdaniu `description`.
3. **`title` (H1):** krótkie, zawiera frazę, **bez sufiksu „| ReklaMap"** (meta `<title>` generuje kod osobno). Cel: **≤ ~55 znaków**. Przejrzyj istniejące `title` i wytnij resztki copywriterskie — w szczególności `citylighty.title` zawiera śmieciowy fragment **„(Voucher na Klienta)"**, który pojechał na produkcję; ma zniknąć. Szukaj podobnych placeholderów we wszystkich wpisach (część A i B).
4. **Usuń niezweryfikowane twierdzenia — nie przenoś ich.** Konkretne przykłady z obecnej treści, które MAJĄ zniknąć:
   - „przyciąga wzrok **o 60% skuteczniej** niż statyczna tablica" (`ekrany-led`),
   - „**Największa i najbardziej aktualna baza** powierzchni reklamowych w Polsce" / „**największej platformie** agregującej…" (wpis `''`),
   - „**największy i najbardziej konkurencyjny rynek** reklamowy w Polsce" jako asercja platformy (`warszawa`) — opis rynku miasta jest OK, ale bez przechwałek skali serwisu.
   Bez źródła nie ma liczby ani superlatywu skali.
5. **Brak zmyślonych liczb.** Standardowe formaty nośników (12 m², 18 m², 120×180 cm, totem ~3–10 m, full/half wrap pojazdu) są OK. Stawek złotówkowych, „X% skuteczniej", „średnio Y minut kontaktu", liczb mieszkańców/turystów/studentów — **nie**, chyba że masz źródło. Zamiast tego: „zazwyczaj", „w zależności od lokalizacji", „jeden z większych ośrodków akademickich".
6. **Ostrożnie z przechwałkami skali serwisu.** Platforma działa od kwietnia 2026 — żadnego „największa baza", „tysiące nośników", „lider rynku", „od lat". Opisuj co serwis **robi**: agreguje oferty z całej Polski, daje bezpośredni kontakt z wystawcą bez prowizji, porównywarka do 5 ogłoszeń, interaktywna mapa, filtry techniczne (natężenie ruchu, oświetlenie, wymiary). To kwestia zaufania i ryzyka reklamy wprowadzającej w błąd.
7. **`cityDescriptions` — realność lokalizacji.** Jeśli wymieniasz konkretną trasę / rondo / węzeł / obwodnicę / galerię w danym mieście — musi istnieć i nazywać się tak. Lepiej napisać ogólnie („przy trasach wylotowych i głównych arteriach") niż zmyślić „rondo X". Korektor to zweryfikuje, ale nie podrzucaj mu fikcji.
8. **Schema.org już jest** (`ItemList` na liście ofert, `BreadcrumbList` w breadcrumbach, `Product`+`Offer` na stronie ogłoszenia) — nie wstawiaj JSON-LD nigdzie, i tak nie da się tego z tego pliku.
9. **Brak linków/HTML w `description` i `benefits`** — to czyste stringi w `<p>{{ }}` / `<li>{{ }}`. Żadnych `<a>`, żadnych CTA — wartość budujesz treścią. (Wewnętrzne linkowanie ze stron kategorii to osobny task kodowy, patrz niżej.)

## Wytyczne treściowe

**Ton:** jak w `AGENT_PISARZ.md` — ekspercki w OOH, bez korpożargonu, praktyczny, polskie realia. **Zakaz AI-izmów** (pełna lista w `AGENT_PISARZ.md`): „Warto również wspomnieć…", „Nie można zapomnieć o…", „Kolejnym ważnym aspektem…", „W dzisiejszych czasach…", „Podsumowując…". Bez paddingu — każde zdanie wnosi konkret.

**`description` — typ (część A): 90–140 słów**, jeden akapit. Schemat: (1) czym jest ten nośnik + format/typowa lokalizacja + fraza, od razu konkret; (2) co realnie daje kampanii — co go wyróżnia spośród innych typów (zasięg vs. częstotliwość vs. prestiż vs. cena vs. dynamika); (3) dla jakiej branży / celu kampanii jest najlepszy.

**`description` — miasto (część B): 90–140 słów**, jeden akapit. Schemat: (1) „Powierzchnie reklamowe w `<Mieście>`…" + jakim jest rynkiem (wielkość, kto jest odbiorcą — mieszkańcy, dojeżdżający, turyści, studenci, biznes — bez konkretnych liczb bez źródła); (2) gdzie w mieście są nośniki — realne arterie, obwodnice, węzły, dzielnice biznesowe (patrz wytyczna techniczna 7); (3) jakie typy nośników i pod jakie kampanie/branże to miasto się nadaje. Nie powtarzaj akapitu z `categoryDescriptions` ani z `typeCityDescriptions` dla tego miasta.

**`benefits` — dokładnie 5 punktów w KAŻDYM wpisie** (kilka obecnych ma 4 — np. `inne` — wyrównaj do 5). Każdy = jedno zdanie, zaczyna się od korzyści (nie od „Lub/I/Albo", nie od „Możliwość…" w każdym punkcie po kolei). Mają być rozróżnialne między wpisami:
- billboard → zasięg / OTS / kierowcy i pasażerowie;
- citylight → bliskość pieszego / długi czas kontaktu / podświetlenie / centrum miasta;
- LED → dynamiczny przekaz / brak kosztów druku i montażu / elastyczność emisji;
- baner → najniższy koszt wejścia / produkcja na wymiar / odporność na pogodę;
- ściana → największa powierzchnia ekspozycji / efekt WOW / potencjał viralowy w social;
- totem → ekspozycja przy punkcie sprzedaży / galerii / stacji paliw;
- transport → mobilność nośnika / dotarcie w wielu punktach miasta / niski CPT;
- mobilna → wybór trasy i czasu ekspozycji / dotarcie tam, gdzie nie ma stałych nośników;
- dla **miast** → benefity opisują, co daje reklama w TYM mieście (charakter ruchu, typy lokalizacji, profil odbiorcy), nie powtarzają benefitów z typów nośników.

**Wpis `''` (wszystkie powierzchnie)** — opisuje sam serwis i wymienia typy nośników; nie powtarzaj w nim akapitów z kategorii szczegółowych. **Wpis `inne`** — krótkie, uczciwe: nośniki niestandardowe / ambient, których nie obejmują pozostałe kategorie; nie obiecuj asortymentu, którego może nie być.

## Czego brief NIE załatwia (osobne taski kodowe — nie próbuj tego naprawić tekstem)

1. **Słaby meta `<title>` stron kategorii.** `ListingsPage.vue` → computed `seoData` generuje `<title>` z `urlTypeToLabel[typeSlug]` → np. „Billboardy w Polsce | ReklaMap", bez frazy transakcyjnej. Pole `title` z `categoryDescriptions` tu nie trafia. Fix: dodać konfigurowalny `metaTitle` per typ/miasto i użyć w `seoData`.
2. **Strony kategorii nie linkują wewnętrznie.** `CategoryDescription.vue` renderuje tylko tekst. Fix: rozszerzyć interfejs o `relatedLinks: { label, to }[]` i wyrenderować pod `benefits` (2–4 linki: artykuł(y) bloga z tego silosu + topowe strony `typ+miasto`).
3. **Cienki generyk na long-tailu + próg `noindex`.** `cityDescriptions`/`typeCityDescriptions` pokrywają tylko ~12 miast i ~32 kombinacje; pozostałe strony (Płock, Mszczonów, …), które są już w `sitemap.xml`, dostają generyczny szablon z `seoData`. Fix kodowy: bogatszy fallback budowany z danych listingu (liczba ofert, zakres cen, formaty, lokalizacje) **oraz** `noindex` na stronach z liczbą ogłoszeń poniżej progu (np. <3). **Kluczowe:** nawet najlepszy 120-słowny opis NIE uratuje strony miasta z 2 ogłoszeniami przed oceną „thin / doorway page" — przy małej liczbie ofert lepiej `noindex` niż opisywać. Dotyczy to też niektórych z 12 miast w części B, jeśli realnie mają mało ofert. Pisarz: w „liście braków danych" oznacz, które z opisywanych miast wyglądają na ryzykowne (mało nośników w bazie) — to wejdzie do decyzji o progu `noindex`. Pisarz może też zaproponować listę kolejnych miast/kombinacji wartych ręcznego opisu (priorytetowane po popycie).

Pisarz robi pełne `title`/`description`/`benefits` w częściach A i B oraz chirurgiczne poprawki w części C. Punkty z tej sekcji są osobnymi zadaniami kodowymi — nie da się ich załatwić tekstem.

## Format odpowiedzi

1. **Checklista samokontroli:** unikalność (brak wspólnych akapitów/benefitów między wpisami i między warstwami) / fraza w `title` i w 1. zdaniu `description` / 1. zdanie ≤ ~150 znaków / dokładnie 5 benefitów na wpis / brak zmyślonych liczb i superlatywów skali / usunięte placeholdery z `title` / brak AI-izmów / `cityDescriptions` bez zmyślonych obiektów/tras.
2. **Część A — gotowy do wklejenia fragment `categoryDescriptions` (10 wpisów)** z zachowanymi komentarzami.
3. **Część B — gotowy do wklejenia fragment `cityDescriptions` (12 wpisów)** z zachowanymi komentarzami.
4. **Część C — poprawiony fragment `typeCityDescriptions`** (tylko zmienione wpisy, z zachowanymi komentarzami `// ── BILLBOARDY ──` itd.) **+ obok każdego zmienionego wpisu jednolinijkowa notka, co poprawiłeś i dlaczego** (np. „zmiękczona liczba turystów", „zweryfikowano: rondo X nie istnieje → ogólnie"). Wpisy bez zmian — nie wklejaj.
5. **Lista braków danych** — gdzie użyłeś sformułowań szacunkowych zamiast konkretu, i propozycja kolejnych miast/kombinacji do ręcznego opisu (zadanie 3 powyżej).

Nie zapisuj pliku samodzielnie ani nie ruszaj struktury interfejsu — użytkownik wkleja zmiany ręcznie. Po Pisarzu treść przechodzi przez **Agenta Korektora** (usunięcie AI-izmów, weryfikacja faktów i realności lokalizacji).
