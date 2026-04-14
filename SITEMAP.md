# Mapa linków ReklaMap

Wszystkie statyczne (niezmienne) URL-e platformy.

---

## Strony główne

| URL | Opis |
|---|---|
| `/` | Strona główna |
| `/powierzchnie-reklamowe` | Lista wszystkich ogłoszeń |
| `/dodaj-powierzchnie-reklamowa` | Formularz dodawania ogłoszenia |
| `/porownaj` | Porównywarka powierzchni (max 5) |

---

## Powierzchnie reklamowe — według typu

| URL | Opis |
|---|---|
| `/powierzchnie-reklamowe/billboardy` | Lista billboardów |
| `/powierzchnie-reklamowe/citylighty` | Lista citylightów |
| `/powierzchnie-reklamowe/ekrany-led` | Lista ekranów LED |
| `/powierzchnie-reklamowe/banery` | Lista banerów |
| `/powierzchnie-reklamowe/sciany-reklamowe` | Lista ścian reklamowych |
| `/powierzchnie-reklamowe/totemy-reklamowe` | Lista totemów reklamowych |
| `/powierzchnie-reklamowe/reklama-w-transporcie` | Lista reklam w transporcie |
| `/powierzchnie-reklamowe/reklama-mobilna` | Lista reklamy mobilnej |
| `/powierzchnie-reklamowe/inne` | Lista pozostałych powierzchni |

---

## Blog — strony statyczne

| URL | Opis |
|---|---|
| `/blog` | Lista wszystkich artykułów |

### Blog — kategorie

| URL | Opis |
|---|---|
| `/blog/poradniki` | Artykuły: Poradniki |
| `/blog/trendy` | Artykuły: Trendy |
| `/blog/case-study` | Artykuły: Case Study |
| `/blog/rynek-ooh` | Artykuły: Rynek OOH |
| `/blog/prawo-i-regulacje` | Artykuły: Prawo i regulacje |
| `/blog/lokalizacje` | Artykuły: Lokalizacje |

---

## Informacyjne

| URL | Opis |
|---|---|
| `/faq` | Najczęściej zadawane pytania |
| `/kontakt` | Strona kontaktowa |
| `/regulamin` | Regulamin platformy |
| `/polityka-prywatnosci` | Polityka prywatności |

---

## Zarządzanie ogłoszeniem

| URL | Opis |
|---|---|
| `/zarzadzaj` | Panel zarządzania (wymaga tokenu z e-mail) |

---

## Przekierowania (301)

| Stary URL | Nowy URL |
|---|---|
| `/ogloszenia` | `/powierzchnie-reklamowe` |
| `/dodaj-ogloszenie` | `/dodaj-powierzchnie-reklamowa` |
| `/ogloszenie/:city/:slug/:id` | `/powierzchnia-reklamowa/inne/:city/:slug-:id` |
