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
    <div class="hero-section">
      <div class="container">
        <h1>Wystawimy Wasze nośniki za Was</h1>
        <p class="hero-subtitle">
          Wyślijcie cennik w takiej formie, w jakiej go macie — resztą zajmiemy się my.
          Bezpłatnie, bez prowizji od wynajmu.
        </p>
        <a :href="mailtoHref" class="hero-cta">Wyślij nam swój cennik</a>
      </div>
    </div>

    <div class="content-section">
      <div class="container">
        <section class="block">
          <h2>Jak to działa</h2>
          <ol class="steps">
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

        <section class="block">
          <h2>Format nie ma znaczenia</h2>
          <p class="lead">
            Najczęstszy powód, dla którego firmy odkładają wystawienie nośników, to przekonanie,
            że trzeba wcześniej uporządkować dane. Nie trzeba. Przyjmujemy:
          </p>
          <ul class="formats">
            <li>Excel lub arkusz Google</li>
            <li>PDF z cennikiem</li>
            <li>Dokument Word</li>
            <li>Link do Waszej strony z nośnikami</li>
            <li>Zdjęcie albo skan tabeli</li>
            <li>Zwykłą listę w treści maila</li>
          </ul>
          <p class="lead">
            Brakujące dane — współrzędne, przypisanie do miasta, opisy — uzupełniamy sami.
            Jeśli czegoś nie da się ustalić, dopytujemy jednym mailem zbiorczo, nie przy każdym nośniku.
          </p>
        </section>

        <section class="block">
          <h2>Co z tego macie</h2>
          <div class="benefits">
            <div class="benefit">
              <h3>Ekspozycja bez kosztu</h3>
              <p>Wasze nośniki widzi każdy, kto szuka powierzchni reklamowej w danym mieście. Nie płacicie za wystawienie ani prowizji od wynajmu.</p>
            </div>
            <div class="benefit">
              <h3>Kontakt bezpośredni</h3>
              <p>Zapytania idą prosto do Was — na Wasz e-mail i telefon. Nie pośredniczymy w rozmowie z klientem.</p>
            </div>
            <div class="benefit">
              <h3>Panel bez haseł</h3>
              <p>Ukrycie zajętego nośnika, przywrócenie go i zmiana ceny zajmują kilka sekund. Dostęp otwiera link wysyłany na Wasz adres.</p>
            </div>
            <div class="benefit">
              <h3>Statystyki nośników</h3>
              <p>Widzicie, ile razy każdy nośnik wyświetlono i ile osób kliknęło w kontakt.</p>
            </div>
          </div>
        </section>

        <section class="block">
          <h2>Najczęściej zadawane pytania</h2>
          <div class="faq">
            <details v-for="item in faq" :key="item.q" class="faq-item">
              <summary>{{ item.q }}</summary>
              <p>{{ item.a }}</p>
            </details>
          </div>
        </section>

        <div class="cta-box">
          <h2>Wyślijcie cennik, resztę zrobimy my</h2>
          <p>Jeden e-mail wystarczy, żeby Wasze nośniki trafiły na platformę.</p>
          <a :href="mailtoHref" class="cta-box__btn">Wyślij nam swój cennik</a>
          <p class="cta-box__note">
            Albo napiszcie na <a :href="`mailto:${CONTACT_EMAIL}`">{{ CONTACT_EMAIL }}</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Wzorzec wizualny wspólny dla podstron informacyjnych (FAQ, Kontakt, Regulamin,
   Polityka prywatności, Blog): tło #f9fafb, hero z gradientem na banerze, kontener 900 px. */
.agencies-page {
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
  margin: 0 auto 2rem;
  max-width: 42rem;
  line-height: 1.6;
}

.hero-cta {
  display: inline-block;
  padding: 1rem 2rem;
  border-radius: 8px;
  background: white;
  color: #667eea;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.hero-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.content-section {
  padding: 4rem 0;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 2rem;
}

.block {
  margin-bottom: 3rem;
}

.block h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-main);
  margin: 0 0 1.5rem 0;
}

.lead {
  line-height: 1.7;
  color: var(--text-muted);
  margin-bottom: 1rem;
}

.steps {
  counter-reset: step;
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 1rem;
}

.steps li {
  counter-increment: step;
  position: relative;
  padding: 1.5rem 1.5rem 1.5rem 4rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  line-height: 1.7;
  color: var(--text-muted);
}

.steps li strong {
  color: var(--text-main);
}

.steps li::before {
  content: counter(step);
  position: absolute;
  left: 1.5rem;
  top: 1.5rem;
  width: 1.75rem;
  height: 1.75rem;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--primary-gradient);
  color: white;
  font-weight: 700;
  font-size: 0.9rem;
}

.formats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
  gap: 0.6rem;
  padding-left: 1.25rem;
  margin-bottom: 1.25rem;
  color: var(--text-muted);
}

.formats li {
  line-height: 1.6;
}

.benefits {
  display: grid;
  /* Dokładnie 2 kolumny — kafli jest 4, więc auto-fit dawał 3 + sierotę w drugim rzędzie. */
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.benefit {
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.benefit h3 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text-main);
  margin: 0 0 0.5rem 0;
}

.benefit p {
  line-height: 1.6;
  color: var(--text-muted);
  font-size: 0.95rem;
  margin: 0;
}

.faq {
  display: grid;
  gap: 0.75rem;
}

.faq-item {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  padding: 1.25rem 1.5rem;
  transition: all 0.3s;
}

.faq-item summary {
  cursor: pointer;
  font-weight: 600;
  color: var(--text-main);
  line-height: 1.5;
}

.faq-item p {
  margin: 0.85rem 0 0 0;
  line-height: 1.7;
  color: var(--text-muted);
}

.cta-box {
  background: linear-gradient(135deg, #f0f3ff 0%, #e8eaff 100%);
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  margin-top: 4rem;
}

.cta-box h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-main);
  margin: 0 0 0.75rem 0;
}

.cta-box p {
  color: var(--text-muted);
  margin: 0 0 1.75rem 0;
}

.cta-box__btn {
  display: inline-block;
  padding: 1rem 2rem;
  border-radius: 8px;
  background: var(--primary-gradient);
  color: white;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s;
}

.cta-box__btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.cta-box__note {
  margin: 1.25rem 0 0 0;
  font-size: 0.95rem;
}

.cta-box__note a {
  color: #667eea;
  font-weight: 600;
}

@media (max-width: 768px) {
  .hero-section {
    padding: 3rem 0;
    background-attachment: scroll;
  }

  .hero-section h1 {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1.05rem;
  }

  .content-section {
    padding: 3rem 0;
  }

  .container {
    padding: 0 1.25rem;
  }

  .block h2,
  .cta-box h2 {
    font-size: 1.4rem;
  }

  .benefits {
    grid-template-columns: 1fr;
  }

  .cta-box {
    padding: 2rem 1.25rem;
  }

  .hero-cta,
  .cta-box__btn {
    display: block;
  }
}
</style>
