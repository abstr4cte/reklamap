<script setup lang="ts">
</script>

<template>
  <div class="privacy-policy-page">
    <div class="container">
      <h1>Polityka Prywatności</h1>
      
      <div class="content">
        <section>
          <h2>1. Informacje ogólne</h2>
          <p>Niniejsza Polityka Prywatności określa zasady przetwarzania i ochrony danych osobowych przekazanych przez Użytkowników w związku z korzystaniem przez nich z serwisu AdSpace.</p>
        </section>

        <section>
          <h2>2. Administrator Danych Osobowych</h2>
          <p>Administratorem danych osobowych zawartych w serwisie jest AdSpace z siedzibą w Warszawie.</p>
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
          <p>W sprawach związanych z ochroną danych osobowych prosimy o kontakt pod adresem e-mail: kontakt@adspace.pl</p>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
.privacy-policy-page {
  padding: 4rem 0;
  min-height: 80vh;
  background: #f9fafb;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 2rem;
}

h1 {
  font-size: 2.5rem;
  color: #1f2937;
  margin-bottom: 3rem;
  text-align: center;
  font-weight: 800;
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
  .privacy-policy-page {
    padding: 2rem 0;
  }

  .container {
    padding: 0 1rem;
  }

  .content {
    padding: 1.5rem;
  }

  h1 {
    font-size: 2rem;
    margin-bottom: 2rem;
  }

  h2 {
    font-size: 1.25rem;
  }
}
</style>
