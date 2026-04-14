# Instrukcja tworzenia postów blogowych — ReklaMap

Każdy post tworzony na polecenie użytkownika musi spełniać poniższe wymagania.
Po wygenerowaniu: dodaj slug do `index.md`, zapisz post w `posts/` i dorzuć wpis do seedera.

---

## O platformie

**ReklaMap** to polski marketplace powierzchni reklamowych — "OLX dla reklamy". Właściciele nośników reklamowych wystawiają ogłoszenia, a reklamodawcy je przeglądają i kontaktują się bezpośrednio z wystawcą. Platforma obejmuje zarówno reklamę zewnętrzną (OOH), jak i transport, reklamę mobilną oraz nośniki wewnętrzne (indoor).

### Typy powierzchni dostępnych na platformie
- **Billboardy** — tradycyjne nośniki wielkoformatowe przy drogach
- **Citylighty** — podświetlane gabloty na chodnikach i przystankach
- **Ekrany LED** — cyfrowe ekrany zewnętrzne i wewnętrzne
- **Banery** — elastyczne nośniki na budynkach i ogrodzeniach
- **Ściany reklamowe** — malowane lub oklejane ściany budynków
- **Totemy reklamowe** — wolnostojące słupy i pylony
- **Reklama w transporcie** — autobusy, tramwaje, taksówki
- **Reklama mobilna** — samochody, food trucki, pojazdy specjalne

### Grupy docelowe (dwie strony marketplace)
1. **Właściciele nośników** — firmy outdoorowe, właściciele nieruchomości, zarządcy budynków, przewoźnicy. Chcą dotrzeć do jak największej liczby reklamodawców i wynająć powierzchnię.
2. **Reklamodawcy** — lokalne firmy, agencje reklamowe, marketerzy, startupy. Szukają konkretnej powierzchni w określonej lokalizacji i budżecie.

### Ton i głos marki
- Profesjonalny, ale bez korporacyjnego żargonu
- Ekspercki w tematyce reklamy (OOH, indoor, transport, mobile), ale zrozumiały dla kogoś spoza branży
- Praktyczny — czytelnik ma wyjść z artykułu z konkretną wiedzą lub działaniem
- Polski rynek, polskie realia (przepisy, miasta, przykłady firm)

### CTA na końcu artykułu — przykłady dopasowane do grupy
- Dla reklamodawców: "Znajdź powierzchnię reklamową w swojej okolicy → [reklamap.pl/powierzchnie-reklamowe](/powierzchnie-reklamowe)"
- Dla właścicieli: "Wystaw swój nośnik za darmo → [reklamap.pl/dodaj-powierzchnie-reklamowa](/dodaj-powierzchnie-reklamowa)"
- Neutralne: "Przeglądaj tysiące ogłoszeń → [reklamap.pl](/)"

---

## Wymagania SEO

### Długość i struktura tekstu
- **Minimum 1200 słów**, optymalnie 1500–2500 słów
- Jeden nagłówek **H1** — identyczny z `title` w metadanych
- Minimum **4 nagłówki H2** dzielące tekst na logiczne sekcje
- Opcjonalnie H3 wewnątrz sekcji H2 dla pogłębienia tematu
- Akapity maksymalnie 4–5 zdań — Google i czytelnicy preferują krótkie bloki
- Co najmniej jedna lista punktowana lub numerowana
- Co najmniej jedna tabela (jeśli temat na to pozwala)

### Słowa kluczowe
- **Główne słowo kluczowe** — pojawia się w: tytule (H1), pierwszym akapicie, jednym H2, meta description, URL (slug)
- **Słowa poboczne** (LSI) — rozsiane naturalnie w treści, nie upychane na siłę
- Gęstość głównego słowa kluczowego: **1–2%** (nie więcej)
- Używaj synonimów i form odmienionych — Google rozumie semantykę

### Meta
- `title` — max **60 znaków**, zawiera główne słowo kluczowe blisko początku
- `meta_description` — max **155 znaków**, zawiera CTA lub wartość dla czytelnika, zawiera słowo kluczowe
- `slug` — tylko małe litery, myślniki zamiast spacji, bez polskich znaków, max 60 znaków
- `image_alt` — max **125 znaków**, opisowy tekst obrazka zawierający główne słowo kluczowe, np. *"Billboard przy autostradzie A1 w Łodzi — reklama zewnętrzna"*. Fallback to tytuł posta, ale zawsze podawaj dedykowany alt.
- `image_prompt` — szczegółowy prompt do wygenerowania grafiki głównej (po angielsku, styl: professional photography or flat design illustration, no text on image)

### Linki wewnętrzne
- Minimum **2 linki wewnętrzne** — mogą prowadzić do podstron platformy lub innych postów blogowych
- Listę podstron platformy znajdziesz w `blog-agent/SITEMAP.md`
- Listę opublikowanych i roboczych postów blogowych znajdziesz w `blog-agent/INDEX.md` — linkuj do tematycznie powiązanych postów gdy tylko to możliwe
- Anchor text opisowy, nie "kliknij tutaj"

### Akapit wprowadzający (snippet zone)
- Pierwsze **100 słów** to strefa, z której Google pobiera featured snippet — musi zawierać główne słowo kluczowe i bezpośrednio odpowiadać na intencję zapytania
- Nie zaczynaj od wstępu ogólnego — od razu daj wartość

### Sekcja FAQ
- Na końcu każdego artykułu dodaj sekcję `## Najczęściej zadawane pytania` z minimum **3 parami pytanie/odpowiedź**
- Pytania formułuj tak jak użytkownicy wpisują je w Google (np. *"Ile kosztuje wynajem billboardu?"*)
- Odpowiedzi zwięzłe — 2–4 zdania
- To generuje **rich snippets** ("People also ask") w wynikach wyszukiwania

### Freshness signals
- Zawsze wypełnij `published_at` w metadanych posta (format: `YYYY-MM-DD HH:MM:SS`)
- Dla kategorii `rynek-ooh` i `prawo-i-regulacje` dodaj w treści wzmiankę o aktualnym roku — Google premiuje świeże treści w tych tematach

### Treść
- Pisz po **polsku**, profesjonalnie ale przystępnie — odbiorca to przedsiębiorca lub marketer
- Zacznij od **problemu lub pytania** — nie od "W tym artykule omówimy..."
- Zakończ **wezwaniem do działania** (CTA) kierującym na platformę
- Nie używaj słów-wypełniaczy: "warto zaznaczyć", "nie ulega wątpliwości", "jak wszyscy wiemy"
- Fakty i liczby tam gdzie możliwe (rynek OOH, statystyki, przykłady)

---

## Format pliku posta

Każdy plik w `posts/` ma nazwę: `{YYYYMMDDHHMMSS}_{slug}.md`

```markdown
---
title: "Tytuł posta (max 60 znaków)"
slug: "slug-posta"
category: poradniki | trendy | case-study | rynek-ooh | prawo-i-regulacje | lokalizacje
meta_description: "Opis do 155 znaków z głównym słowem kluczowym i CTA."
image_alt: "Opisowy alt obrazka z głównym słowem kluczowym, max 125 znaków."
image_prompt: "Detailed English prompt for AI image generation. Professional, no text."
keywords:
  - główne słowo kluczowe
  - słowo poboczne 1
  - słowo poboczne 2
word_count: ~1500
published_at: "YYYY-MM-DD HH:MM:SS"
status: draft
---

# Tytuł posta

[Pierwsze 100 słów — snippet zone: główne słowo kluczowe + bezpośrednia odpowiedź na intencję zapytania]

## Nagłówek H2 z słowem kluczowym

[Treść...]

## Najczęściej zadawane pytania

**Pytanie 1?**
Odpowiedź w 2–4 zdaniach.

**Pytanie 2?**
Odpowiedź w 2–4 zdaniach.

**Pytanie 3?**
Odpowiedź w 2–4 zdaniach.

---

[CTA]
```

---

## Workflow po wygenerowaniu posta

1. Dodaj wpis do `blog-agent/INDEX.md`
2. Zapisz plik w `blog-agent/posts/{timestamp}_{slug}.md`
3. Dorzuć post do tablicy w `backend/database/seeders/BlogPostsSeeder.php`
4. Poinformuj użytkownika — niech uruchomi `php artisan db:seed --class=BlogPostsSeeder` i opublikuje post w panelu Filament
