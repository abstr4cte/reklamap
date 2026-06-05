<script setup lang="ts">
import { useSeo } from '../composables/useSeo'
import { appUrl } from '../utils/url'

useSeo({
  title: 'Regulamin | ReklaMap',
  description: 'Regulamin platformy ReklaMap – zasady korzystania z serwisu ogłoszeń powierzchni reklamowych. Prawa i obowiązki użytkowników.',
  ogType: 'website',
  canonical: `${appUrl}/regulamin`,
  keywords: 'regulamin ReklaMap, zasady korzystania, warunki użytkowania'
})
</script>

<template>
  <div class="regulamin-page">
    <div class="hero-section">
      <div class="container">
        <h1>Regulamin platformy ReklaMap</h1>
        <p class="hero-subtitle">Zasady korzystania z serwisu ogłoszeń reklamowych</p>
      </div>
    </div>

    <div class="content-section">
      <div class="container">
        <div class="content-wrapper">
          <aside class="sidebar">
            <nav class="toc">
              <h3>Spis treści</h3>
              <ul>
                <li><a href="#postanowienia-ogolne">1. Postanowienia ogólne</a></li>
                <li><a href="#definicje">2. Definicje</a></li>
                <li><a href="#zasady-korzystania">3. Zasady korzystania</a></li>
                <li><a href="#dodawanie-ogloszen">4. Dodawanie ogłoszeń</a></li>
                <li><a href="#odpowiedzialnosc">5. Odpowiedzialność</a></li>
                <li><a href="#zglaszanie-naruszen">6. Zgłaszanie naruszeń</a></li>
                <li><a href="#dane-osobowe">7. Ochrona danych osobowych</a></li>
                <li><a href="#reklamacje">8. Reklamacje</a></li>
                <li><a href="#postanowienia-koncowe">9. Postanowienia końcowe</a></li>
              </ul>
            </nav>
          </aside>

          <main class="main-content">
            <section id="postanowienia-ogolne" class="section">
              <h2>1. Postanowienia ogólne</h2>
              <p>Niniejszy Regulamin określa zasady korzystania z platformy ReklaMap, serwisu internetowego umożliwiającego publikację i przeglądanie ogłoszeń dotyczących powierzchni reklamowych, łączącego właścicieli i zarządców powierzchni z osobami fizycznymi i firmami poszukującymi miejsc na reklamę.</p>
              <p>Korzystanie z serwisu oznacza akceptację postanowień niniejszego Regulaminu.</p>
              <p>Regulamin dostępny jest nieodpłatnie na stronie internetowej serwisu w formie umożliwiającej jego pobranie, utrwalenie i wydrukowanie.</p>
            </section>

            <section id="definicje" class="section">
              <h2>2. Definicje</h2>
              <dl class="definitions">
                <dt>Serwis</dt>
                <dd>Platforma internetowa ReklaMap dostępna pod adresem domenowym, umożliwiająca publikację i przeglądanie ogłoszeń reklamowych.</dd>

                <dt>Użytkownik</dt>
                <dd>Każda osoba fizyczna, prawna lub jednostka organizacyjna nieposiadająca osobowości prawnej, korzystająca z Serwisu.</dd>

                <dt>Ogłoszenie</dt>
                <dd>Treść publikowana przez Użytkownika w Serwisie, zawierająca informacje o dostępnej powierzchni reklamowej.</dd>

                <dt>Administrator Serwisu</dt>
                <dd>ReklaMap — podmiot prowadzący Serwis, odpowiedzialny za jego funkcjonowanie i rozwój.</dd>

                <dt>Regulamin</dt>
                <dd>Niniejszy dokument określający zasady korzystania z Serwisu.</dd>
              </dl>
            </section>

            <section id="zasady-korzystania" class="section">
              <h2>3. Zasady korzystania z Serwisu</h2>
              <ol class="numbered-list">
                <li>Serwis jest dostępny 24 godziny na dobę, 7 dni w tygodniu, z zastrzeżeniem przerw technicznych i konserwacyjnych.</li>
                <li>Korzystanie z podstawowych funkcji Serwisu, w tym przeglądanie ogłoszeń, jest bezpłatne.</li>
                <li>Użytkownik zobowiązuje się do korzystania z Serwisu w sposób zgodny z prawem, dobrymi obyczajami oraz postanowieniami niniejszego Regulaminu.</li>
                <li>Zabronione jest:
                  <ul>
                    <li>Podawanie nieprawdziwych informacji</li>
                    <li>Publikowanie treści niezgodnych z prawem lub dobrymi obyczajami</li>
                    <li>Naruszanie praw własności intelektualnej osób trzecich</li>
                    <li>Podszywanie się pod inne osoby lub podmioty</li>
                    <li>Wykorzystywanie Serwisu w celach niezgodnych z jego przeznaczeniem</li>
                  </ul>
                </li>
              </ol>
            </section>

            <section id="dodawanie-ogloszen" class="section">
              <h2>4. Dodawanie ogłoszeń</h2>
              <ol class="numbered-list">
                <li>Każdy Użytkownik może dodawać ogłoszenia po podaniu adresu email. Podanie numeru telefonu jest opcjonalne.</li>
                <li>Dodając ogłoszenie, Użytkownik oświadcza, że jest właścicielem lub zarządcą oferowanej powierzchni reklamowej albo posiada zgodę uprawnionego podmiotu na jej wystawienie, oraz że jest uprawniony do ustalenia warunków i ceny zawartych w ogłoszeniu. Wystawienie cudzej powierzchni bez takiego tytułu jest zabronione i stanowi naruszenie Regulaminu.</li>
                <li>Ogłoszenie powinno zawierać prawdziwe i aktualne informacje o oferowanej powierzchni reklamowej, w tym:
                  <ul>
                    <li>Typ nośnika (billboard, citylight, ekran LED, baner, ściana, totem, transport, mobile, inne)</li>
                    <li>Dokładną lokalizację (miasto, adres)</li>
                    <li>Wymiary powierzchni (dla typów, które je wymagają)</li>
                    <li>Cenę i jednostkę ceny (dzień, tydzień, miesiąc, rok, kampania, m²)</li>
                    <li>Dostępne zdjęcia wysokiej jakości</li>
                    <li>Opis i szczegóły techniczne (oświetlenie, orientacja, natężenie ruchu, itp.)</li>
                  </ul>
                </li>
                <li>Użytkownik jest zobowiązany do aktualizowania informacji w ogłoszeniu, w szczególności do oznaczania powierzchni jako niedostępnych lub usuwania ogłoszeń, które nie są już aktualne.</li>
                <li>Ogłoszenie może zawierać maksymalnie 5 zdjęć prezentujących powierzchnię reklamową. Zdjęcia powinny być wysokiej jakości i czytelne.</li>
                <li>Ogłoszenie pozostaje aktywne bezterminowo i nie wygasa automatycznie. Użytkownik może je usunąć lub edytować w dowolnym momencie.</li>
                <li>Administrator zastrzega sobie prawo do:
                  <ul>
                    <li>Moderacji treści ogłoszeń</li>
                    <li>Usuwania ogłoszeń niezgodnych z Regulaminem lub zawierających nieprawdziwe informacje</li>
                    <li>Żądania weryfikacji danych kontaktowych</li>
                  </ul>
                </li>
              </ol>
            </section>

            <section id="odpowiedzialnosc" class="section">
              <h2>5. Odpowiedzialność</h2>
              <ol class="numbered-list">
                <li>Administrator nie ponosi odpowiedzialności za treść ogłoszeń publikowanych przez Użytkowników.</li>
                <li>Administrator nie jest stroną transakcji zawieranych między Użytkownikami.</li>
                <li>Użytkownik ponosi pełną odpowiedzialność za treść publikowanych ogłoszeń oraz ich zgodność z przepisami prawa.</li>
                <li>Administrator nie ponosi odpowiedzialności za szkody wynikające z:
                  <ul>
                    <li>Korzystania z Serwisu przez Użytkowników</li>
                    <li>Braku dostępu do Serwisu z przyczyn technicznych</li>
                    <li>Nieautoryzowanego dostępu osób trzecich do danych Użytkowników</li>
                  </ul>
                </li>
                <li>Administrator dołoży wszelkich starań, aby Serwis działał prawidłowo i nieprzerwanie.</li>
              </ol>
            </section>

            <section id="zglaszanie-naruszen" class="section">
              <h2>6. Zgłaszanie naruszeń i nielegalnych treści</h2>
              <ol class="numbered-list">
                <li>Każda osoba lub podmiot — w tym niebędący Użytkownikiem Serwisu — może zgłosić ogłoszenie, które uważa za niezgodne z prawem, naruszające prawa osób trzecich lub postanowienia niniejszego Regulaminu.</li>
                <li>Zgłoszenia można dokonać:
                  <ul>
                    <li>za pomocą przycisku „Zgłoś błąd / naruszenie" dostępnego przy każdym ogłoszeniu,</li>
                    <li>drogą elektroniczną na adres: kontakt@reklamap.pl.</li>
                  </ul>
                </li>
                <li>Zgłoszenie powinno zawierać: wskazanie ogłoszenia (link lub opis pozwalający je zidentyfikować), opis naruszenia oraz — jeśli dotyczy — wskazanie, jakie prawa zgłaszającego zostały naruszone.</li>
                <li>Administrator rozpatruje zgłoszenia bez zbędnej zwłoki. W przypadku stwierdzenia, że ogłoszenie jest niezgodne z prawem lub Regulaminem, Administrator niezwłocznie usuwa je lub blokuje do niego dostęp, a o podjętej decyzji informuje zgłaszającego oraz Użytkownika, który dodał ogłoszenie.</li>
                <li>Powyższy mechanizm stanowi realizację obowiązków Administratora jako dostawcy usług pośrednich w rozumieniu rozporządzenia Parlamentu Europejskiego i Rady (UE) 2022/2065 (akt o usługach cyfrowych, DSA) oraz ustawy o świadczeniu usług drogą elektroniczną.</li>
              </ol>
            </section>

            <section id="dane-osobowe" class="section">
              <h2>7. Ochrona danych osobowych</h2>
              <ol class="numbered-list">
                <li>Administrator przetwarza dane osobowe Użytkowników zgodnie z przepisami RODO (Rozporządzenie (UE) 2016/679).</li>
                <li>Dane osobowe są przetwarzane w celu:
                  <ul>
                    <li>Świadczenia usług Serwisu (dodawanie, edycja, usuwanie ogłoszeń)</li>
                    <li>Komunikacji z Użytkownikami (odpowiedzi na zapytania, powiadomienia o ogłoszeniach)</li>
                    <li>Realizacji obowiązków prawnych</li>
                    <li>Statystyki i analiza korzystania z Serwisu</li>
                  </ul>
                </li>
                <li>Kontakt z Użytkownikiem w sprawie ogłoszenia odbywa się przez formularz wiadomości lub numer telefonu (jeśli podany) — adres email nie jest widoczny dla innych Użytkowników.</li>
                <li>Użytkownik ma prawo do:
                  <ul>
                    <li>Dostępu do swoich danych osobowych</li>
                    <li>Sprostowania danych</li>
                    <li>Usunięcia danych (prawo do bycia zapomnianym)</li>
                    <li>Ograniczenia przetwarzania</li>
                    <li>Przenoszenia danych</li>
                    <li>Wniesienia sprzeciwu wobec przetwarzania</li>
                  </ul>
                </li>
                <li>Aby skorzystać z powyższych praw, Użytkownik powinien skontaktować się z Administratorem na adres: kontakt@reklamap.pl</li>
                <li>Szczegółowe informacje dotyczące przetwarzania danych osobowych znajdują się w Polityce Prywatności.</li>
              </ol>
            </section>

            <section id="reklamacje" class="section">
              <h2>8. Reklamacje</h2>
              <ol class="numbered-list">
                <li>Użytkownik ma prawo do złożenia reklamacji dotyczącej funkcjonowania Serwisu.</li>
                <li>Reklamację można złożyć:
                  <ul>
                    <li>Drogą elektroniczną na adres: kontakt@reklamap.pl</li>
                    <li>Za pomocą formularza kontaktowego dostępnego w Serwisie</li>
                  </ul>
                </li>
                <li>Reklamacja powinna zawierać:
                  <ul>
                    <li>Adres email do odpowiedzi</li>
                    <li>Opis problemu</li>
                    <li>Żądanie Użytkownika</li>
                  </ul>
                </li>
                <li>Administrator rozpatrzy reklamację w terminie 14 dni roboczych od jej otrzymania.</li>
                <li>Odpowiedź na reklamację zostanie wysłana na adres e-mail podany przez Użytkownika.</li>
              </ol>
            </section>

            <section id="postanowienia-koncowe" class="section">
              <h2>9. Postanowienia końcowe</h2>
              <ol class="numbered-list">
                <li>Administrator zastrzega sobie prawo do zmiany Regulaminu z ważnych przyczyn technicznych lub prawnych.</li>
                <li>Informacja o zmianie Regulaminu zostanie opublikowana w Serwisie z 7-dniowym wyprzedzeniem.</li>
                <li>Kontynuowanie korzystania z Serwisu po wejściu w życie zmian oznacza akceptację nowego Regulaminu.</li>
                <li>W sprawach nieuregulowanych w Regulaminie mają zastosowanie przepisy prawa polskiego.</li>
                <li>Spory wynikające z korzystania z Serwisu będą rozstrzygane przez właściwy sąd powszechny.</li>
                <li>Regulamin wchodzi w życie z dniem publikacji w Serwisie.</li>
              </ol>
            </section>

            <div class="help-box">
              <h2>Masz pytania?</h2>
              <p>Jeśli masz pytania dotyczące Regulaminu, skontaktuj się z nami:</p>
              <div class="help-actions">
                <router-link to="/kontakt" class="help-btn primary">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Formularz kontaktowy
                </router-link>
                <a href="mailto:kontakt@reklamap.pl" class="help-btn secondary">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  kontakt@reklamap.pl
                </a>
              </div>
            </div>

          </main>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.regulamin-page {
  min-height: 100vh;
  background: #f9fafb;
}

.hero-section {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%), url('../assets/banner-section.webp');
  background-size: cover;
  background-repeat: no-repeat;
  background-attachment: fixed;
  padding: 4rem 0;
  color: white;
  text-align: center;
}

.hero-section h1 {
  font-size: 3rem;
  font-weight: 800;
  margin: 0 0 1rem 0;
}

.hero-subtitle {
  font-size: 1.25rem;
  opacity: 0.95;
  margin: 0;
}

.content-section {
  padding: 4rem 0;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

.content-wrapper {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 3rem;
  align-items: start;
}

.sidebar {
  position: sticky;
  top: 2rem;
}

.toc {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.toc h3 {
  margin: 0 0 1rem 0;
  font-size: 1.1rem;
  color: #1f2937;
}

.toc ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.toc li {
  margin-bottom: 0.5rem;
}

.toc a {
  color: #6b7280;
  text-decoration: none;
  font-size: 0.95rem;
  transition: color 0.2s;
  display: block;
  padding: 0.375rem 0;
}

.toc a:hover {
  color: #667eea;
}

.main-content {
  background: white;
  border-radius: 12px;
  padding: 3rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.section {
  margin-bottom: 3rem;
  padding-bottom: 3rem;
  border-bottom: 2px solid #f3f4f6;
}

.section:last-of-type {
  border-bottom: none;
}

.section h2 {
  color: #1f2937;
  font-size: 1.875rem;
  font-weight: 700;
  margin: 0 0 1.5rem 0;
  scroll-margin-top: 2rem;
}

.section p {
  color: #4b5563;
  line-height: 1.8;
  margin-bottom: 1rem;
}

.definitions {
  display: grid;
  gap: 1.5rem;
}

.definitions dt {
  font-weight: 700;
  color: #1f2937;
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}

.definitions dd {
  margin: 0;
  color: #4b5563;
  line-height: 1.8;
  padding-left: 1.5rem;
  border-left: 3px solid #667eea;
}

.numbered-list {
  counter-reset: item;
  list-style: none;
  padding: 0;
  margin: 0;
}

.numbered-list > li {
  counter-increment: item;
  margin-bottom: 1.5rem;
  color: #4b5563;
  line-height: 1.8;
  position: relative;
  padding-left: 2.5rem;
}

.numbered-list > li::before {
  content: counter(item) ".";
  position: absolute;
  left: 0;
  font-weight: 700;
  color: #667eea;
  font-size: 1.1rem;
}

.numbered-list ul {
  margin-top: 0.75rem;
  padding-left: 1.5rem;
}

.numbered-list ul li {
  margin-bottom: 0.5rem;
  list-style: disc;
}

.help-box {
  background: linear-gradient(135deg, #f0f3ff 0%, #e8eaff 100%);
  border-radius: 16px;
  padding: 3rem;
  text-align: center;
  margin-top: 3rem;
}

.help-box h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.help-box p {
  color: #6b7280;
  font-size: 1.1rem;
  margin: 0 0 2rem 0;
}

.help-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

.help-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.help-btn.primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
}

.help-btn.primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.help-btn.secondary {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
}

.help-btn.secondary:hover {
  background: #667eea;
  color: white;
}

.date-info {
  text-align: center;
  color: #9ca3af;
  font-size: 0.875rem;
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 2px solid #f3f4f6;
}

@media (max-width: 1024px) {
  .content-wrapper {
    grid-template-columns: 1fr;
  }

  .sidebar {
    position: static;
  }

  .toc {
    margin-bottom: 2rem;
  }
}

@media (max-width: 640px) {
  .hero-section {
    padding: 3rem 0;
  }

  .hero-section h1 {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .main-content {
    padding: 2rem 1.5rem;
  }

  .section h2 {
    font-size: 1.5rem;
  }

  .numbered-list > li {
    padding-left: 2rem;
  }
}
</style>
