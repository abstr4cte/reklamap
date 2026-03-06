<script setup lang="ts">
import { useSeo } from '../composables/useSeo'

useSeo({
  title: 'Polityka Prywatności | ReklaMap',
  description: 'Zasady przetwarzania i ochrony danych osobowych na platformie ReklaMap.',
  keywords: 'prywatność, RODO, dane osobowe, polityka prywatności, ReklaMap',
  noindex: true // Typically we don't need privacy policy to compete in search results, but it should be indexable if desired. Actually, 'noindex' is safer if you want to focus SEO on main pages, but usually it's fine to leave it. Let's keep it indexable but with low priority in sitemap.
})
</script>

<template>
  <div class="privacy-policy-page">
    <!-- Hero Section -->
    <div class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">Polityka Prywatności</h1>
        <p class="hero-subtitle">Dowiedz się, jak chronimy Twoje dane osobowe i dbamy o Twoją prywatność</p>
      </div>
    </div>

    <div class="container">
      <div class="content">
        <section>
          <h2>1. Informacje ogólne</h2>
          <p>Niniejsza Polityka Prywatności określa zasady przetwarzania i ochrony danych osobowych przekazanych przez Użytkowników w związku z korzystaniem przez nich z platformy ReklaMap (dalej: "Serwis"). Serwis jest platformą internetową umożliwiającą publikację i przeglądanie ogłoszeń dotyczących powierzchni reklamowych, łącząc właścicieli i zarządców powierzchni z osobami fizycznymi i firmami poszukującymi miejsc na reklamę.</p>
        </section>

        <section>
          <h2>2. Administrator Danych Osobowych</h2>
          <p>Administratorem danych osobowych zawartych w serwisie jest ReklaMap. W sprawach związanych z ochroną danych osobowych można kontaktować się pod adresem e-mail: kontakt@reklamap.pl</p>
        </section>

        <section>
          <h2>3. Zakres zbieranych danych osobowych</h2>
          <p>W związku z korzystaniem z Serwisu zbieramy następujące dane osobowe:</p>
          <ul>
            <li><strong>Dane kontaktowe:</strong> imię, nazwisko (lub nazwa firmy), adres e-mail, numer telefonu</li>
            <li><strong>Dane lokalizacyjne:</strong> adres IP, informacje o lokalizacji (na podstawie adresu IP)</li>
            <li><strong>Dane dotyczące ogłoszeń:</strong> informacje zawarte w publikowanych ogłoszeniach (lokalizacja, typ nośnika, wymiary, cena, zdjęcia)</li>
            <li><strong>Dane techniczne:</strong> informacje o urządzeniu, przeglądarce, systemie operacyjnym, czas i data dostępu</li>
            <li><strong>Dane analityczne:</strong> informacje o interakcjach z Serwisem (kliknięcia, wyświetlenia, wyszukiwania)</li>
          </ul>
        </section>

        <section>
          <h2>4. Cele przetwarzania danych</h2>
          <p>Dane osobowe przetwarzane są w następujących celach:</p>
          <ul>
            <li><strong>Świadczenie usług:</strong> umożliwienie dodawania, edycji, usuwania i przeglądania ogłoszeń</li>
            <li><strong>Komunikacja:</strong> kontakt z Użytkownikami w sprawie ogłoszeń, odpowiedzi na zapytania, wysyłanie powiadomień</li>
            <li><strong>Weryfikacja:</strong> sprawdzenie autentyczności danych kontaktowych i zapobieganie nadużyciom</li>
            <li><strong>Statystyka i analiza:</strong> analiza korzystania z Serwisu, poprawa funkcjonalności i doświadczenia użytkownika</li>
            <li><strong>Obowiązki prawne:</strong> realizacja obowiązków wynikających z przepisów prawa, w tym podatkowymi i rachunkowymi</li>
            <li><strong>Bezpieczeństwo:</strong> ochrona przed nieautoryzowanym dostępem, oszustwami i innymi zagrożeniami</li>
          </ul>
        </section>

        <section>
          <h2>5. Podstawa prawna przetwarzania</h2>
          <p>Przetwarzanie danych osobowych odbywa się na podstawie:</p>
          <ul>
            <li>Zgody Użytkownika (art. 6 ust. 1 lit. a RODO) - dla danych zbieranych za pośrednictwem formularzy</li>
            <li>Wykonania umowy (art. 6 ust. 1 lit. b RODO) - dla danych niezbędnych do świadczenia usług</li>
            <li>Obowiązku prawnego (art. 6 ust. 1 lit. c RODO) - dla danych wymaganych przepisami prawa</li>
            <li>Uzasadnionego interesu (art. 6 ust. 1 lit. f RODO) - dla danych analitycznych i bezpieczeństwa</li>
          </ul>
        </section>

        <section>
          <h2>6. Udostępnianie danych osobowych</h2>
          <p>Dane osobowe Użytkownika (imię, nazwisko, email, telefon) są widoczne dla innych Użytkowników w celu umożliwienia kontaktu w sprawie ogłoszeń. Dane nie są udostępniane stronom trzecim, z wyjątkiem:</p>
          <ul>
            <li>Dostawców usług technicznych (hosting, bazy danych) - na podstawie umów przetwarzania danych</li>
            <li>Organów publicznych - na podstawie obowiązku prawnego</li>
            <li>Firm zajmujących się bezpieczeństwem - w celu ochrony przed oszustwami</li>
          </ul>
        </section>

        <section>
          <h2>7. Pliki Cookies i technologie śledzące</h2>
          <p>Serwis używa plików cookies i podobnych technologii śledzących:</p>
          <ul>
            <li><strong>Cookies niezbędne:</strong> umożliwiają funkcjonowanie Serwisu (sesja, bezpieczeństwo)</li>
            <li><strong>Cookies analityczne:</strong> zbierają informacje o korzystaniu z Serwisu (Google Analytics)</li>
            <li><strong>Cookies funkcjonalne:</strong> zapamiętują preferencje użytkownika (język, filtry wyszukiwania)</li>
            <li><strong>Cookies marketingowe:</strong> śledzą aktywność użytkownika w celu personalizacji treści</li>
          </ul>
          <p>Użytkownik może zarządzać cookies w ustawieniach przeglądarki. Odmowa akceptacji cookies może ograniczyć funkcjonalność Serwisu.</p>
        </section>

        <section>
          <h2>8. Okres przechowywania danych</h2>
          <p>Dane osobowe przechowywane są przez okres:</p>
          <ul>
            <li><strong>Dane kontaktowe:</strong> przez czas korzystania z Serwisu i 3 lata po usunięciu konta</li>
            <li><strong>Dane ogłoszeń:</strong> przez 90 dni od publikacji (okres aktywności ogłoszenia)</li>
            <li><strong>Dane analityczne:</strong> przez 26 miesięcy</li>
            <li><strong>Dane techniczne:</strong> przez 30 dni</li>
            <li><strong>Dane wymagane prawem:</strong> zgodnie z obowiązującymi przepisami (np. 5 lat dla danych podatkowych)</li>
          </ul>
        </section>

        <section>
          <h2>9. Prawa Użytkownika</h2>
          <p>Użytkownikowi przysługuje prawo do:</p>
          <ul>
            <li><strong>Dostępu do danych:</strong> prawo do uzyskania kopii swoich danych osobowych</li>
            <li><strong>Sprostowania:</strong> prawo do poprawy nieprawidłowych danych</li>
            <li><strong>Usunięcia:</strong> prawo do usunięcia danych (prawo do bycia zapomnianym)</li>
            <li><strong>Ograniczenia przetwarzania:</strong> prawo do ograniczenia przetwarzania danych</li>
            <li><strong>Przenoszenia danych:</strong> prawo do otrzymania danych w strukturyzowanym formacie</li>
            <li><strong>Sprzeciwu:</strong> prawo do wniesienia sprzeciwu wobec przetwarzania danych</li>
            <li><strong>Wycofania zgody:</strong> prawo do wycofania wcześniej udzielonej zgody</li>
          </ul>
          <p>Aby skorzystać z powyższych praw, Użytkownik powinien skontaktować się z Administratorem na adres: kontakt@reklamap.pl</p>
        </section>

        <section>
          <h2>10. Bezpieczeństwo danych</h2>
          <p>Administrator podejmuje odpowiednie środki techniczne i organizacyjne w celu ochrony danych osobowych przed nieautoryzowanym dostępem, zmianą, ujawnieniem lub zniszczeniem. Dane są przechowywane na zabezpieczonych serwerach z szyfrowaniem SSL/TLS.</p>
        </section>

        <section>
          <h2>11. Kontakt i skargi</h2>
          <p>W sprawach związanych z ochroną danych osobowych prosimy o kontakt pod adresem e-mail: kontakt@reklamap.pl</p>
          <p>Użytkownik ma prawo do złożenia skargi do Prezesa Urzędu Ochrony Danych Osobowych, jeśli uważa, że przetwarzanie jego danych narusza przepisy RODO.</p>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.privacy-policy-page {
  min-height: 80vh;
  background: #f9fafb;
}

/* Hero Section */
.hero-section {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.3) 0%, rgba(118, 75, 162, 0.3) 100%), url('../assets/banner-section.png');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  padding: 6rem 2rem;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  opacity: 0;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

.hero-title {
  font-size: 3rem;
  font-weight: 800;
  color: white;
  margin: 0 0 1rem 0;
  line-height: 1.2;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.hero-subtitle {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.95);
  margin: 0;
  line-height: 1.6;
  font-weight: 400;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 4rem 2rem;
}

.content {
  background: white;
  padding: 3rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

section {
  margin-bottom: 2.5rem;
}

section:last-child {
  margin-bottom: 0;
}

h2 {
  font-size: 1.5rem;
  color: #111827;
  margin-bottom: 1rem;
  font-weight: 700;
}

p {
  color: #4b5563;
  line-height: 1.7;
  margin-bottom: 1rem;
}

ul {
  list-style-type: disc;
  padding-left: 1.5rem;
  color: #4b5563;
  line-height: 1.7;
}

li {
  margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
  .hero-section {
    padding: 4rem 1.5rem;
  }

  .hero-title {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .container {
    padding: 2rem 1rem;
  }

  .content {
    padding: 1.5rem;
  }

  h2 {
    font-size: 1.25rem;
  }
}
</style>
