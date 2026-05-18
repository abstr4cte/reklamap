# Sales Log — ReklaMap

Rejestr wyników rozmów cold calling. Agent Marketing czyta ten plik przed generowaniem nowych skryptów.

| Data | Typ rozmówcy | Użyty argument | Wynik | Notatka |
|:---|:---|:---|:---|:---|
| — | — | — | — | Pierwszy wpis pojawi się po pierwszych rozmowach |

---

## Kampania 2026-05 — pozyskanie podaży: agencje OOH

**Cel:** wciągnąć agencje/operatorów OOH na platformę **zanim ruszy kampania skierowana do reklamodawców** — chcemy gęstą podaż, gdy popyt się pojawi.

**Mechanika oferty:** hurtowy import całej oferty operatora z jednego pliku (szablon `reklamap-os/templates/szablon-import-agencje.xlsx`), wykonywany przez zespół ReklaMap. **Darmowy teraz** dla partnerów startowych.

**Rozdział, który MUSI wybrzmieć w każdym kontakcie:** wystawianie ogłoszeń = darmowe na zawsze; tym, co się zmienia, jest tylko „hurtowy import za Was z pliku" (później płatny albo self-serve w panelu). Nie obiecywać konkretnych przyszłych funkcji ani cen; nie grozić („potem nigdy") — deadline miękki.

**Materiały (szablony maili w `reklamap-os/templates/`):** `email-oferta-agencje.html` (pierwszy kontakt) → `email-followup-agencje.html` / `email-wysylka-excela-agencje.html` (follow-up + szablon Excel) → **`email-deadline-import-agencje.html`** (follow-up z miękkim deadline'em „do końca maja robimy priorytetowo" — utworzony 2026-05-13). Plus `prezentacja-agencje.html`/`.pdf`, `qa-agencje.html`/`.pdf` na spotkania. Linki w mailach: kanoniczne `https://reklamap.pl/...` + UTM `utm_source=outreach&utm_medium=email&utm_campaign=agencje-import-maj` (mierzymy w GA4).

**Plan wysyłki follow-up deadline:** ~2026-05-19/20 (≈10 dni przed końcem maja). Targetowanie: agencje z pierwszej fali kontaktu, które po ogłoszeniach widać, że nie wystawiły żadnego nośnika.

**Proces ręcznego importu (concierge) — jak obsługiwać niekompletny plik agencji:**
- Importuj **wszystkie** nośniki z pliku — nie odrzucaj wiersza tylko dlatego, że czegoś brakuje (sens „zrobimy za Was" = wciągnięcie całej oferty).
- Twardo wymagane do **aktywacji** (publiczny nośnik): typ, lokalizacja (min. miasto, najlepiej współrzędne), cena, kontakt (telefon lub e-mail). Brak któregokolwiek → wystaw jako **szkic/nieaktywny** (niewidoczny publicznie).
- Opcjonalne (można aktywować bez nich): wymiary, natężenie/typ ruchu, warianty, opis, dodatkowe zdjęcia. Brak → nośnik idzie na żywo, ale z notką „uzupełnij, żeby był wyżej w wynikach". Zdjęcie — graniczne; bez zdjęcia można aktywować, ale z niższą widocznością.
- Po imporcie → **odpisz agencji z podsumowaniem**: „wgraliśmy X nośników, Y na żywo, Z czeka jako szkic, bo brakuje: [12× cena, 8× e-mail kontaktowy, …] — odeślijcie te dane, aktywujemy". To naturalny follow-up, który znowu angażuje.
- Docelowo (po fazie startowej) to samo robi self-serve bulk import — patrz `docs/PRODUCT_BACKLOG.md` B-4.

**Status / wyniki:** _(uzupełniać — kto dostał follow-up, kto odpowiedział, ile nośników wpłynęło)_

---

## 2026-05-13 — sygnał walidacyjny willingness-to-pay (duża agencja OOH)

Przedstawiciel dużej agencji OOH (ta sama, z którą founder ma poniedziałkowe spotkanie zarządu — patrz pitch ~2026-05-18) w rozmowie powiedział, że „aż głupio, że platforma jest za darmo" i sam zaproponował oddawanie części prowizji.

**Co to znaczy:** pierwszy twardy dowód willingness-to-pay po stronie podaży, *bez naszej inicjatywy* — wartość platformy odbierają wyżej niż cena (zero). Walidacja, że plan premium ma pokrycie w realnym popycie, nie tylko w PowerPoincie.

**Decyzja:** propozycji prowizji NIE przyjmujemy — uzasadnienie w `PRODUCT_BACKLOG.md` (sekcja „ODRZUCONE KIERUNKI MONETYZACJI"). Trzymamy fundament „wystawianie darmowe na zawsze".

**Argumentacja na poniedziałkowy pitch:** 3 ścieżki współpracy zamiast prowizji — Partner Założycielski (darmowy, status + ekspozycja), Concierge (płatna usługa za nasz czas, nie za platformę), Premium visibility (gdy uruchomimy, pierwszeństwo dla założycieli). Skrypt 60-sekundowy w odpowiedzi Agenta Biznesowego z 2026-05-13.

**Materiał argumentacyjny do kolejnych rozmów:** „inna agencja sama zaproponowała prowizję — odmówiliśmy, bo trzymamy się modelu" → buduje wiarygodność dyscypliny.
