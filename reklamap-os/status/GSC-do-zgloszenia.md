# Lista URL-i do zgłoszenia w GSC („Poproś o zindeksowanie")

> ⛔ **NIE ZGŁASZAJ, dopóki `reklamap.pl` serwuje challenge anty-DDoS.**
> Zgłoszenie wymusza świeże pobranie. Jeśli Googlebot dostanie stronę
> „One moment, please", potwierdzi zły werdykt na kolejne tygodnie —
> czyli powtórzymy dokładnie to, co zamroziło te URL-e 15.05.2026.
> **Sprawdź przed startem:** otwórz `https://reklamap.pl/blog/poradniki`
> w trybie incognito. Widzisz artykuły → można zgłaszać.

Jak: GSC → pole „Sprawdź dowolny URL" u góry → wklej → „Poproś o zindeksowanie".
Limit ok. 10–12 zgłoszeń dziennie na właściwość. Jeśli zabraknie, resztę jutro.

---

## GRUPA A — 12 URL-i zamrożonych w werdykcie 5xx z 2026-05-15
Wszystkie zwracają dziś 200 dla każdego UA. Google po prostu nie ponawia.
**0 wyświetleń przez 3 miesiące** (grupa kontrolna 6 zdrowych artykułów: 1 532).

1.  https://reklamap.pl/blog/poradniki                                   ← kategoria (hub, zgłoś pierwsze)
2.  https://reklamap.pl/blog/prawo-i-regulacje                           ← kategoria (hub, zgłoś drugie)
3.  https://reklamap.pl/blog/poradniki/reklama-w-transporcie-publicznym  ← treść mocno rozbudowana 20-21.08
4.  https://reklamap.pl/blog/poradniki/reklama-zewnetrzna                ← treść poprawiona 21.08
5.  https://reklamap.pl/blog/lokalizacje/reklama-outdoor-warszawa
6.  https://reklamap.pl/blog/lokalizacje/reklama-outdoor-wroclaw
7.  https://reklamap.pl/blog/poradniki/jak-wybrac-powierzchnie-reklamowa
8.  https://reklamap.pl/blog/poradniki/baner-reklamowy-cena
9.  https://reklamap.pl/blog/poradniki/reklama-na-samochodzie
10. https://reklamap.pl/blog/poradniki/tablica-reklamowa
11. https://reklamap.pl/blog/trendy/telebim-ekran-led-reklama
12. https://reklamap.pl/blog/trendy/totem-reklamowy

## GRUPA B — 2 artykuły, ktore trafily do sitemapy jako `noindex`
Opublikowane i zrecenzowane, ale zgłoszone Google'owi zanim powstał prerender.

13. https://reklamap.pl/blog/prawo-i-regulacje/pozwolenie-na-tablice-reklamowa  ← poprawiony 21.08
14. https://reklamap.pl/blog/prawo-i-regulacje/reklama-bez-pozwolenia-kary

## GRUPA C — zmienione 21.08, warte ponownego pobrania
Nie były zamrożone, ale treść zmieniła się na tyle, że warto wymusić recrawl.

15. https://reklamap.pl/blog/poradniki/citylight-reklama            ← 14 577 → 28 041 zn., nowa sekcja 10 miast
16. https://reklamap.pl/blog/poradniki/ile-kosztuje-reklama-outdoor ← 10 korekt, w tym 3 krytyczne
17. https://reklamap.pl/blog/poradniki/ekran-led-cena               ← usunięta niepotwierdzona tabela cen

---

## Po zgłoszeniu
- Sitemapa: GSC → Mapy witryny → ponów zgłoszenie `https://api.reklamap.pl/sitemap.xml`
- Efekt sprawdzaj po 7–14 dniach w Skuteczności, filtr po stronie.
- Miara sukcesu: którykolwiek z Grupy A wychodzi z zera wyświetleń.
