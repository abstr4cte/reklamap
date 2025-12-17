<script setup lang="ts">
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
          <p>Niniejsza Polityka Prywatności określa zasady przetwarzania i ochrony danych osobowych przekazanych przez Użytkowników w związku z korzystaniem przez nich z serwisu ReklaMap.</p>
        </section>

        <section>
          <h2>2. Administrator Danych Osobowych</h2>
          <p>Administratorem danych osobowych zawartych w serwisie jest ReklaMap z siedzibą w Warszawie.</p>
        </section>

        <section>
          <h2>3. Zakres i cel zbierania danych</h2>
          <p>Dane osobowe przetwarzane są w celu:</p>
          <ul>
            <li>Realizacji usług świadczonych drogą elektroniczną</li>
            <li>Obsługi zapytań i zgłoszeń</li>
            <li>Kontaktu z Użytkownikiem</li>
            <li>Marketingu własnych produktów lub usług</li>
          </ul>
        </section>

        <section>
          <h2>4. Pliki Cookies</h2>
          <p>Serwis używa plików cookies. Są to niewielkie pliki tekstowe wysyłane przez serwer www i przechowywane przez oprogramowanie komputera przeglądarki. Kiedy przeglądarka ponownie połączy się ze stroną, witryna rozpoznaje rodzaj urządzenia, z którego łączy się użytkownik. Parametry pozwalają na odczytanie informacji w nich zawartych jedynie serwerowi, który je utworzył. Cookies ułatwiają więc korzystanie z wcześniej odwiedzonych witryn.</p>
        </section>

        <section>
          <h2>5. Prawa Użytkownika</h2>
          <p>Użytkownikowi przysługuje prawo do:</p>
          <ul>
            <li>Dostępu do treści swoich danych</li>
            <li>Ich sprostowania</li>
            <li>Usunięcia</li>
            <li>Ograniczenia przetwarzania</li>
            <li>Przenoszenia danych</li>
            <li>Wniesienia sprzeciwu</li>
            <li>Cofnięcia zgody w dowolnym momencie</li>
          </ul>
        </section>

        <section>
          <h2>6. Kontakt</h2>
          <p>W sprawach związanych z ochroną danych osobowych prosimy o kontakt pod adresem e-mail: kontakt@reklamap.pl</p>
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  opacity: 0.4;
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
