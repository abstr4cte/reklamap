<script setup lang="ts">
import { useSeo } from '../composables/useSeo'
import { appUrl } from '../utils/url'

const CONTACT_EMAIL = 'kontakt@reklamap.pl'

// Temat i szkielet maila wypełnione z góry — im mniej agencja musi wymyślić, tym większa
// szansa, że wyśle cokolwiek. Cała propozycja wartości opiera się na tym, że po ich stronie
// nie ma pracy: wysyłają to, co już mają, w formacie, w jakim to mają.
const mailtoHref = [
  `mailto:${CONTACT_EMAIL}`,
  '?subject=' + encodeURIComponent('Nasze nośniki na ReklaMap'),
  '&body=' + encodeURIComponent(
    'Dzień dobry,\n\n' +
    'chcemy wystawić nasze nośniki na ReklaMap. W załączniku przesyłamy to, czym dysponujemy.\n\n' +
    'Nazwa firmy:\n' +
    'Osoba kontaktowa:\n' +
    'Telefon:\n' +
    'E-mail do ogłoszeń:\n' +
    'Liczba nośników (orientacyjnie):\n' +
    'Miasta / region:\n\n' +
    'Pozdrawiamy,\n'
  ),
].join('')

const faq = [
  {
    q: 'W jakim formacie musimy przesłać dane?',
    a: 'W żadnym konkretnym. Excel, arkusz Google, PDF z cennikiem, plik Word, wydruk, link do Waszej strony, a nawet zdjęcie tabeli — bierzemy to, co macie pod ręką. Uporządkowanie danych jest po naszej stronie.',
  },
  {
    q: 'Ile to kosztuje?',
    a: 'Nic. Wystawienie nośników jest bezpłatne i nie pobieramy prowizji od wynajmu. Reklamodawca kontaktuje się z Wami bezpośrednio — nie pośredniczymy w transakcji ani nie wchodzimy między Was a klienta.',
  },
  {
    q: 'Ile pracy jest po naszej stronie?',
    a: 'Jeden e-mail. Import, opisy, przypisanie do miast, współrzędne na mapie i zdjęcia robimy my. Przed publikacją odsyłamy podsumowanie do akceptacji, żebyście widzieli, co pójdzie na stronę.',
  },
  {
    q: 'Co, jeśli nośnik jest akurat zajęty albo zmienia się cena?',
    a: 'Po imporcie dostajecie dostęp do panelu, w którym możecie ukryć zajęty nośnik, przywrócić go po zwolnieniu i zmienić cenę. Nie trzeba do nas pisać za każdym razem. Dostęp otwiera się linkiem wysyłanym na Wasz adres — bez haseł do zapamiętywania.',
  },
  {
    q: 'Kto widzi nasze dane kontaktowe?',
    a: 'Przy każdym ogłoszeniu podajemy ten adres e-mail i telefon, który wskażecie. Zapytania trafiają prosto do Was.',
  },
  {
    q: 'Czy możemy się wycofać?',
    a: 'Tak, w każdej chwili. Wystarczy jedna wiadomość — zdejmujemy wszystkie nośniki.',
  },
]

useSeo({
  title: 'Dla agencji i operatorów OOH | ReklaMap',
  description: 'Wystawimy Wasze nośniki za Was — wyślijcie cennik w dowolnym formacie, resztę robimy my. Bezpłatnie, bez prowizji, kontakt od reklamodawcy prosto do Was.',
  ogType: 'website',
  canonical: `${appUrl}/dla-agencji`,
  keywords: 'nośniki reklamowe dla agencji, portal OOH, wystaw nośniki, operator reklamy zewnętrznej',
  structuredData: {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faq.map(item => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: { '@type': 'Answer', text: item.a },
    })),
  },
})
</script>

<template>
  <div class="agencies-page">
    <header class="agencies-hero">
      <h1>Wystawimy Wasze nośniki za Was</h1>
      <p class="agencies-hero__lead">
        Prowadzicie agencję reklamy zewnętrznej albo macie własne nośniki?
        Wyślijcie cennik w takiej formie, w jakiej go macie — resztą zajmiemy się my.
        Bezpłatnie, bez prowizji od wynajmu.
      </p>
      <a :href="mailtoHref" class="agencies-cta">Wyślij nam swój cennik</a>
      <p class="agencies-hero__note">
        Albo napiszcie na <a :href="`mailto:${CONTACT_EMAIL}`">{{ CONTACT_EMAIL }}</a>
      </p>
    </header>

    <section class="agencies-section">
      <h2>Jak to działa</h2>
      <ol class="agencies-steps">
        <li>
          <strong>Wysyłacie, co macie.</strong>
          Arkusz, PDF z cennikiem, link do strony — bez przygotowywania czegokolwiek pod nas.
        </li>
        <li>
          <strong>Wrzucamy nośniki na platformę.</strong>
          Opisy, lokalizacje na mapie, zdjęcia i przypisanie do miast robimy my.
          Przed publikacją odsyłamy podsumowanie do akceptacji.
        </li>
        <li>
          <strong>Dostajecie panel i zapytania.</strong>
          Zajęty nośnik ukrywacie w kilka sekund, cenę zmieniacie sami.
          Reklamodawca pisze i dzwoni bezpośrednio do Was.
        </li>
      </ol>
    </section>

    <section class="agencies-section">
      <h2>Format nie ma znaczenia</h2>
      <p class="agencies-lead">
        Najczęstszy powód, dla którego firmy odkładają wystawienie nośników, to przekonanie,
        że trzeba wcześniej uporządkować dane. Nie trzeba. Przyjmujemy:
      </p>
      <ul class="agencies-formats">
        <li>Excel lub arkusz Google</li>
        <li>PDF z cennikiem</li>
        <li>Dokument Word</li>
        <li>Link do Waszej strony z nośnikami</li>
        <li>Zdjęcie albo skan tabeli</li>
        <li>Zwykłą listę w treści maila</li>
      </ul>
      <p class="agencies-lead">
        Brakujące dane — współrzędne, przypisanie do miasta, opisy — uzupełniamy sami.
        Jeśli czegoś nie da się ustalić, dopytujemy jednym mailem zbiorczo, nie przy każdym nośniku.
      </p>
    </section>

    <section class="agencies-section">
      <h2>Co z tego macie</h2>
      <div class="agencies-benefits">
        <div class="agencies-benefit">
          <h3>Ekspozycja bez kosztu</h3>
          <p>Wasze nośniki widzi każdy, kto szuka powierzchni reklamowej w danym mieście. Nie płacicie za wystawienie ani prowizji od wynajmu.</p>
        </div>
        <div class="agencies-benefit">
          <h3>Kontakt bezpośredni</h3>
          <p>Zapytania idą prosto do Was — na Wasz e-mail i telefon. Nie pośredniczymy w rozmowie z klientem.</p>
        </div>
        <div class="agencies-benefit">
          <h3>Panel bez haseł</h3>
          <p>Ukrycie zajętego nośnika, przywrócenie go i zmiana ceny zajmują kilka sekund. Dostęp otwiera link wysyłany na Wasz adres.</p>
        </div>
        <div class="agencies-benefit">
          <h3>Statystyki nośników</h3>
          <p>Widzicie, ile razy każdy nośnik wyświetlono i ile osób kliknęło w kontakt.</p>
        </div>
      </div>
    </section>

    <section class="agencies-section">
      <h2>Najczęściej zadawane pytania</h2>
      <div class="agencies-faq">
        <details v-for="item in faq" :key="item.q" class="agencies-faq__item">
          <summary>{{ item.q }}</summary>
          <p>{{ item.a }}</p>
        </details>
      </div>
    </section>

    <section class="agencies-final">
      <h2>Wyślijcie cennik, resztę zrobimy my</h2>
      <p>Jeden e-mail wystarczy, żeby Wasze nośniki trafiły na platformę.</p>
      <a :href="mailtoHref" class="agencies-cta">Wyślij nam swój cennik</a>
    </section>
  </div>
</template>

<style scoped>
.agencies-page {
  max-width: 62rem;
  margin: 0 auto;
  padding: 2.5rem 1.25rem 4rem;
  color: var(--text-main, #374151);
}

.agencies-hero {
  text-align: center;
  padding-bottom: 2.5rem;
  border-bottom: 2px solid var(--border-color, #e5e7eb);
}

.agencies-hero h1 {
  font-size: 2.25rem;
  font-weight: 800;
  margin-bottom: 1rem;
  line-height: 1.2;
}

.agencies-hero__lead {
  max-width: 42rem;
  margin: 0 auto 1.75rem;
  font-size: 1.1rem;
  line-height: 1.6;
  color: var(--text-muted, #6b7280);
}

.agencies-hero__note {
  margin-top: 1rem;
  font-size: 0.95rem;
  color: var(--text-muted, #6b7280);
}

.agencies-cta {
  display: inline-block;
  padding: 0.9rem 2rem;
  border-radius: 12px;
  background: #667eea;
  color: #fff;
  font-weight: 700;
  font-size: 1.05rem;
  text-decoration: none;
  transition: background 0.2s;
}

.agencies-cta:hover {
  background: #5568d3;
}

.agencies-section {
  padding-top: 2.5rem;
}

.agencies-section h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
}

.agencies-lead {
  line-height: 1.65;
  margin-bottom: 1rem;
  color: var(--text-muted, #6b7280);
}

.agencies-steps {
  counter-reset: step;
  list-style: none;
  padding: 0;
  display: grid;
  gap: 1rem;
}

.agencies-steps li {
  counter-increment: step;
  position: relative;
  padding: 1.1rem 1.25rem 1.1rem 3.5rem;
  background: var(--card-bg, #fff);
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  line-height: 1.6;
}

.agencies-steps li::before {
  content: counter(step);
  position: absolute;
  left: 1.1rem;
  top: 1.1rem;
  width: 1.75rem;
  height: 1.75rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: #667eea;
  color: #fff;
  font-weight: 700;
  font-size: 0.9rem;
}

.agencies-formats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
  gap: 0.6rem;
  padding-left: 1.25rem;
  margin-bottom: 1rem;
}

.agencies-formats li {
  line-height: 1.5;
}

.agencies-benefits {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
  gap: 1rem;
}

.agencies-benefit {
  padding: 1.25rem;
  background: var(--card-bg, #fff);
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
}

.agencies-benefit h3 {
  font-size: 1.05rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.agencies-benefit p {
  line-height: 1.55;
  color: var(--text-muted, #6b7280);
  font-size: 0.95rem;
}

.agencies-faq__item {
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  padding: 0.9rem 0;
}

.agencies-faq__item summary {
  cursor: pointer;
  font-weight: 600;
  line-height: 1.5;
}

.agencies-faq__item p {
  margin-top: 0.7rem;
  line-height: 1.6;
  color: var(--text-muted, #6b7280);
}

.agencies-final {
  margin-top: 3rem;
  padding: 2.5rem 1.25rem;
  text-align: center;
  background: var(--card-bg, #fff);
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 16px;
}

.agencies-final h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.6rem;
}

.agencies-final p {
  margin-bottom: 1.5rem;
  color: var(--text-muted, #6b7280);
}

@media (max-width: 768px) {
  .agencies-page {
    padding: 1.5rem 1rem 3rem;
  }

  .agencies-hero h1 {
    font-size: 1.7rem;
  }

  .agencies-hero__lead {
    font-size: 1rem;
  }

  .agencies-section h2,
  .agencies-final h2 {
    font-size: 1.3rem;
  }
}
</style>
