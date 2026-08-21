# Indeks postów blogowych — ReklaMap

Lista wszystkich postów w systemie. Artykuły o statusie 🛠️ SZKIC wymagają weryfikacji danych (Perplexity) i nowej redakcji przez Agenta Pisarza oraz Korektora.

> ## ✅ AUDYT FAKTOGRAFICZNY ZAKOŃCZONY — 2026-08-20
>
> ⚠️ **Sprostowanie 2026-08-21:** deklaracja „36/36" z 20.08 była przedwczesna. Trzy artykuły miały status ZRECENZOWANY **bez naniesionych poprawek** — `citylight-reklama` (fałszywa tabela operatorów), `ile-kosztuje-reklama-outdoor` (10 błędów, w tym zawyżenie ceny reklamy całopojazdowej ~10×) i `pozwolenie-na-tablice-reklamowa`. Poprawione 21.08. Wniosek na przyszłość: **status w INDEX-ie nadawaj dopiero po commicie z poprawkami**, nie po samym przeczytaniu raportu.
>
> **Wszystkie 36 artykułów przeszło weryfikację faktów.** 28 tekstów powstało przed wprowadzeniem reguł anty-konfabulacyjnych (12.07.2026) i tylko 5 z nich było kiedykolwiek sprawdzonych — 20.08 przeaudytowano pozostałe 23, WebSearchem przeciwko źródłom pierwotnym (BIP, ISAP/ELI, orzecznictwo, oficjalne cenniki operatorów).
>
> **Trafienie: konfabulacje w KAŻDYM sprawdzonym artykule.** Najczęstsze klasy błędów: nieaktualne lub wprost błędne jednostki redakcyjne ustaw (w jednym przypadku przywołany przepis stanowił *dosłowne zaprzeczenie* tezy), cenniki operatorów zaniżone o połowę lub przypisane do złego formatu, ceny pakietów podane jako ceny jednostkowe, fałszywe superlatywy („jedyne miasto w Polsce…"), zmyśleni operatorzy i akronimy, atrybucja do źródeł nie zawierających cytowanych liczb.
>
> **Wszystkie poprawki są na produkcji** (`php artisan blog:update-content <slug>` — aktualizacja w miejscu, bez ruszania statusu i daty publikacji). Zasada przyjęta przy naprawach: poprawiamy tylko to, co potwierdzone URL-em do dokumentu źródłowego; wartości niepotwierdzonej się nie zgaduje — usuwa się konkret albo pisze ogólnie.
>
> ⚠️ **Pułapka samopotwierdzenia:** nasze konfabulacje są już w indeksie Google i przy weryfikacji potrafią wrócić jako „źródło" (potwierdzone dla zmyślonych stref warszawskiej uchwały i dla cennika AMS). Przy każdej weryfikacji wykluczaj `reklamap.pl`.

> **⚠️ Stan na 2026-07-25 (audyt): 33 artykuły w repo, 30 na prodzie, a 12 z tych 30 jest NIEWIDOCZNYCH dla Google.**
> **(a) Trzy oznaczone 🔴 NIEOPUBLIKOWANY** — gotowe i zrecenzowane, ale nie ma ich w bazie prod; wymagają publikacji w panelu **oraz deployu frontu** (bez deployu URL jest w sitemapie, ale serwowany jako `noindex` — patrz `SEO_TECH_AUDIT.md` 2026-07-25, poz. 2).
> **(b) Dwanaście artykułów jest zamrożonych w werdykcie GSC „Błąd serwera (5xx)" z 2026-05-15** i ma **0 wyświetleń przez 3 miesiące**, mimo że na żywo zwracają 200: `reklama-w-transporcie-publicznym`, `telebim-ekran-led-reklama`, `totem-reklamowy`, `reklama-na-samochodzie`, `reklama-outdoor-warszawa`, `reklama-outdoor-wroclaw`, `baner-reklamowy-cena`, `tablica-reklamowa`, `reklama-zewnetrzna`, `jak-wybrac-powierzchnie-reklamowa` + kategorie `/blog/poradniki` i `/blog/prawo-i-regulacje`. Stoi nad nimi **40,7% całego popytu w GSC**.
> **Zanim ktokolwiek uzna artykuł za „słaby" i zleci przepisanie — sprawdź, czy nie jest na tej liście.** Odmrożenie to akcja w UI GSC, nie praca redakcyjna. Szczegóły i kolejność działań: `status/STRATEGY_LOG.md` → sekcja „🚦 BLOKER PRZED CAŁĄ KOLEJKĄ".

| Data utworzenia | Slug | Kategoria | Status | Plik |
|:---|:---|:---|:---|:---|
| **Kategoria: /blog/poradniki** | | | | |
| 2026-04-14 06:00 | jak-wybrac-powierzchnie-reklamowa | poradniki | ✅ ZRECENZOWANY | [Link](posts/20260414060000_jak-wybrac-powierzchnie-reklamowa.md) |
| 2026-04-14 06:01 | ile-kosztuje-reklama-outdoor | poradniki | ✅ ZRECENZOWANY 2026-08-21 (10 błędów: „ceny obejmują druk i montaż" wbrew obu cytowanym cennikom MPK; full cover autobus 13 900–36 000 → realnie 1 500–2 200 zł/mc; 8 000–40 000 zł/mc przypisane billboard-x.pl, które tej liczby nie zawiera; usunięto niepotwierdzoną tabelę warszawską i „jedyny format z CPM") | [Link](posts/20260414060100_ile-kosztuje-reklama-outdoor.md) |
| 2026-08-18 10:42 | dzierzawa-gruntu-pod-reklame | poradniki (PODAŻ) | ✅ ZRECENZOWANY (zero wolumenu Google, treść pod cold calling + AI-search, patrz brudnopis) | [Link](posts/20260818104212_dzierzawa-gruntu-pod-reklame.md) |
| 2026-06-09 18:16 | ekran-led-cena | poradniki | ✅ ZRECENZOWANY | [Link](posts/20260609181608_ekran-led-cena.md) |
| 2026-06-22 11:30 | jak-zarobic-na-wynajmie-powierzchni-reklamowej | poradniki (PODAŻ) | ✅ ZRECENZOWANY | [Link](posts/20260622113049_jak-zarobic-na-wynajmie-powierzchni-reklamowej.md) |
| 2026-06-22 11:56 | czy-oplaca-sie-wynajmowac-powierzchnie-reklamowa | poradniki (PODAŻ) | ✅ ZRECENZOWANY | [Link](posts/20260622115615_czy-oplaca-sie-wynajmowac-powierzchnie-reklamowa.md) |
| 2026-06-22 12:39 | reklama-na-ogrodzeniu | poradniki (PODAŻ) | ✅ ZRECENZOWANY + zweryfikowany faktograficznie 2026-08-20 · opublikowany | [Link](posts/20260622123930_reklama-na-ogrodzeniu.md) |
| 2026-06-22 12:51 | reklama-na-elewacji-wspolnoty | poradniki (PODAŻ) | ✅ ZRECENZOWANY + zweryfikowany faktograficznie 2026-08-20 · opublikowany | [Link](posts/20260622125118_reklama-na-elewacji-wspolnoty.md) |
| 2026-04-14 06:02 | reklama-na-samochodzie | poradniki | ✅ ZRECENZOWANY (rozbudowa: reklama mobilna/przyczepki) | [Link](posts/20260414060200_reklama-na-samochodzie.md) |
| 2026-04-14 06:03 | reklama-w-transporcie-publicznym | poradniki | ✅ ZRECENZOWANY | [Link](posts/20260414060300_reklama-w-transporcie-publicznym.md) |
| **Kategoria: /blog/lokalizacje** | | | | |
| 2026-04-14 06:04 | reklama-outdoor-warszawa | lokalizacje | ✅ ZRECENZOWANY | [Link](posts/20260414060400_reklama-outdoor-warszawa.md) |
| 2026-04-14 06:05 | reklama-outdoor-krakow | lokalizacje | ✅ ZRECENZOWANY 2026-07-13 (korekta stref 4→3 I/II/III; daty XXXVI/908/20 26.02.2020 / publ. 9.03.2020 poz.1984 potwierdzone oficjalnie; złagodzone cross-city superlatywy; nota o limicie 18 m² — ✅ LIVE NA PROD, zweryfikowane curl-em 2026-07-25) | [Link](posts/20260414060500_reklama-outdoor-krakow.md) |
| 2026-04-14 06:06 | reklama-outdoor-wroclaw | lokalizacje | ✅ ZRECENZOWANY | [Link](posts/20260414060600_reklama-outdoor-wroclaw.md) |
| **Kategoria: /blog/poradniki (cd.)** | | | | |
| 2026-04-14 06:10 | tablica-reklamowa | poradniki | ✅ ZRECENZOWANY | [Link](posts/20260414061000_tablica-reklamowa.md) |
| **Kategoria: /blog/poradniki (cd.)** | | | | |
| 2026-05-12 23:00 | citylight-reklama | poradniki | ✅ ZRECENZOWANY 2026-08-21 (tabela operatorów wg raportu OOHlife 31.12.2024: AMS 12 634 CL, Ströer 4 586, Bauer/Clear Channel 4 018, Warexpo 1 512; usunięto Mini Media — ma ZERO citylightów; „77 digital CL" opatrzone datą VIII 2023; usunięto niepotwierdzony zasięg 14,5–26%) + ROZBUDOWA 2026-08-21: nowa sekcja „Citylight w polskich miastach" (Gdańsk/Kraków/Łódź — operatorzy wiat, uchwały krajobrazowe, stawki za pas drogowy z 3 dzienników urzędowych; pytanie prejudycjalne NSA II OSK 1153/25 do TSUE z 2.06.2026). cenniki zastąpione OFICJALNYMI cennikami AMS (od 1.07.2026) i Bauer Media Outdoor (od 19.08.2026) + MPK Lublin; komplet 10 miast z Olsztynem. 14 577 → 27 035 zn. | [Link](posts/20260414060700_citylight-reklama.md) |
| 2026-04-14 06:08 | reklama-zewnetrzna | poradniki | ✅ ZRECENZOWANY | [Link](posts/20260414060800_reklama-zewnetrzna.md) |
| **Kategoria: /blog/trendy** | | | | |
| 2026-04-14 06:09 | murale-reklamowe | trendy | ✅ ZRECENZOWANY | [Link](posts/20260414060900_murale-reklamowe.md) |
| 2026-04-14 06:11 | telebim-ekran-led-reklama | trendy | ✅ ZRECENZOWANY | [Link](posts/20260414061100_telebim-ekran-led-reklama.md) |
| 2026-04-14 06:12 | totem-reklamowy | trendy | ✅ ZRECENZOWANY | [Link](posts/20260414061200_totem-reklamowy.md) |
| **Kategoria: /blog/poradniki (cd.)** | | | | |
| 2026-04-14 06:13 | baner-reklamowy-cena | poradniki | ✅ ZRECENZOWANY | [Link](posts/20260414061300_baner-reklamowy-cena.md) |
| **Kategoria: /blog/lokalizacje (cd.)** | | | | |
| 2026-04-14 06:14 | reklama-outdoor-gdansk | lokalizacje | ✅ ZRECENZOWANY 2026-07-13 (fałsz „brak uchwały" → egzekwowana XLVIII/1465/18; 2026-07-13: struktura stref 8 obszarów SZ/S0/SR/S1/S2/SI/S3/SP zamiast zmyślonych 6 A-F, usunięto błędne progi/odległości i sprzeczny cennik 36 m² — ✅ LIVE NA PROD, zweryfikowane curl-em 2026-07-25) | [Link](posts/20260414061400_reklama-outdoor-gdansk.md) |
| 2026-04-19 16:23 | reklama-outdoor-poznan | lokalizacje | ✅ ZRECENZOWANY 2026-07-13 (rozbudowa LED/DOOH + fakty potwierdzone oficjalnie: uchwała LXXXVIII/1671/VIII/2023, WSA II SA/Po 547/23, TK P 20/19, skarżący Jeronimo Martins/Biedronka; dodano niuans tymczasowości zwolnienia, usunięto stawki opłaty — Poznań jej nie pobiera — ✅ LIVE NA PROD, zweryfikowane curl-em 2026-07-25) | [Link](posts/20260419162331_reklama-outdoor-poznan.md) |
| 2026-05-12 22:30 | billboard-reklama | poradniki | ✅ ZRECENZOWANY (refresh) | [Link](posts/20260419163813_billboard-reklama.md) |
| 2026-05-05 10:55 | reklama-outdoor-lodz | lokalizacje | ✅ ZRECENZOWANY 2026-07-13 („najsurowsza obowiązuje" → nieegzekwowana/zawieszona; 2026-07-13: numer XXXVII/966/16 (poz.5588) potwierdzony, złagodzono niepotwierdzony zakres wyroku 2024 i usunięto sprzeczność „zawieszona vs wyrok" — ✅ LIVE NA PROD, zweryfikowane curl-em 2026-07-25) | [Link](posts/20260505105504_reklama-outdoor-lodz.md) |
| 2026-05-05 11:36 | oplata-reklamowa | prawo-i-regulacje | ✅ ZRECENZOWANY — 🔧 **POPRAWKA 2026-08-18** (błędne stawki maks. 3,89/0,36 → poprawne 2,50/0,20, przeliczone wszystkie przykłady; sprawdzić re-seed na prod jeśli już live) | [Link](posts/20260505113610_oplata-reklamowa.md) |
| 2026-08-18 11:01 | podatek-od-wynajmu-powierzchni-reklamowej | prawo-i-regulacje (PODAŻ) | ✅ ZRECENZOWANY (YMYL, zero wolumenu Google, patrz brudnopis) | [Link](posts/20260818110152_podatek-od-wynajmu-powierzchni-reklamowej.md) |
| 2026-08-18 11:15 | budowa-wlasnego-billboardu-koszt | poradniki (PODAŻ) | ✅ ZRECENZOWANY (zero wolumenu Google, 3 konflikty prawne z researchu poprawnie ominięte) | [Link](posts/20260818111500_budowa-wlasnego-billboardu-koszt.md) |
| 2026-05-05 11:47 | reklama-outdoor-katowice | lokalizacje | ✅ ZRECENZOWANY | [Link](posts/20260505114741_reklama-outdoor-katowice.md) |
| 2026-05-25 22:01 | reklama-outdoor-olsztyn | lokalizacje | ✅ ZRECENZOWANY | [Link](posts/20260525220136_reklama-outdoor-olsztyn.md) |
| 2026-05-25 22:44 | reklama-outdoor-bydgoszcz | lokalizacje | ✅ ZRECENZOWANY | [Link](posts/20260525224446_reklama-outdoor-bydgoszcz.md) |
| 2026-06-22 11:11 | reklama-outdoor-lublin | lokalizacje | ✅ ZRECENZOWANY | [Link](posts/20260622111121_reklama-outdoor-lublin.md) |
| 2026-06-22 12:28 | reklama-outdoor-szczecin | lokalizacje | ✅ ZRECENZOWANY + zweryfikowany faktograficznie 2026-08-20 · opublikowany | [Link](posts/20260622122850_reklama-outdoor-szczecin.md) |
| 2026-05-25 23:22 | dooh-reklama-programatyczna | trendy | ✅ ZRECENZOWANY | [Link](posts/20260525232247_dooh-reklama-programatyczna.md) |
| **Kategoria: /blog/prawo-i-regulacje** | | | | |
| 2026-04-19 16:23 | uchwala-krajobrazowa-reklama | prawo-i-regulacje | ✅ ZRECENZOWANY 2026-07-13 (korekta huba: Łódź „aktywna"→zawieszona, Poznań fałsz „brak zakazów”→MA uchwałę, Gdańsk egzekwuje, usunięto niepotwierdzone 90%/3 mln + kalkulator Kraków — ✅ LIVE NA PROD, zweryfikowane curl-em 2026-07-25) | [Link](posts/20260419162332_uchwala-krajobrazowa-reklama.md) |
| 2026-06-22 12:07 | pozwolenie-na-tablice-reklamowa | prawo-i-regulacje | ✅ ZRECENZOWANY 2026-08-21 (art. 30 → art. 29 jako katalog robót zgłoszeniowych; dopisano kontrprzepis art. 29 ust. 3 pkt 3 lit. c + orzecznictwo NSA; opłata skarbowa doprecyzowana do 155 zł; uzupełniono drogi wojewódzkie/gminne) | [Link](posts/20260622120711_pozwolenie-na-tablice-reklamowa.md) |
| 2026-06-22 12:20 | reklama-bez-pozwolenia-kary | prawo-i-regulacje | ✅ ZRECENZOWANY | [Link](posts/20260622122024_reklama-bez-pozwolenia-kary.md) |
| 2026-08-21 21:30 | reklama-na-przystankach | poradniki | ✅ ZRECENZOWANY 2026-08-21 (Korektor: 3 stawki z uchylonych aktów — Kraków XXX/789/19 i Łódź LX/1804/22 zastąpione; teza o warunku inwestycyjnym zawężona do 3 z 10 miast; rozdzielenie ról z citylight-reklama. ODRZUCONO zarzut o Warszawie — Korektor cytował nieaktualny tekst ujednolicony, poz. 20 Dz.Urz. 2022/13948 potwierdza 3,25–6,75 zł) (materiał z 9 dzienników urzędowych + cenniki AMS/Bauer/MPK Lublin; trigger GSC: 192 wyśw / 7 fraz / poz. 36,8 / 0 klik) | [Link](posts/20260821213000_reklama-na-przystankach.md) |