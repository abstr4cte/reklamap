<script setup lang="ts">
import { ref, computed } from 'vue'
import { useSeo } from '../composables/useSeo'

interface FaqItem {
  id: number
  question: string
  answer: string
  category: string
}

const selectedCategory = ref('wszystkie')
const openItems = ref<number[]>([])

const categories = [
  { id: 'wszystkie', name: 'Wszystkie' },
  { id: 'podstawy', name: 'Podstawy' },
  { id: 'ogloszenia', name: 'Ogłoszenia' },
  { id: 'platnosci', name: 'Płatności' },
  { id: 'techniczne', name: 'Techniczne' }
]

const faqItems: FaqItem[] = [
  {
    id: 1,
    category: 'podstawy',
    question: 'Czym jest ReklaMap?',
    answer: 'ReklaMap to platforma internetowa łącząca właścicieli i zarządców powierzchni reklamowych z osobami fizycznymi i firmami poszukującymi miejsc na reklamę. Oferujemy kompleksową bazę billboardów, citylightów, ekranów LED, banerów, ścian, totemów, transportu, mobile i innych nośników reklamowych w całej Polsce.'
  },
  {
    id: 2,
    category: 'podstawy',
    question: 'Czy korzystanie z platformy jest bezpłatne?',
    answer: 'Tak, przeglądanie ogłoszeń i wyszukiwanie powierzchni reklamowych jest całkowicie bezpłatne. Dodawanie ogłoszeń również nie wiąże się z żadnymi kosztami. ReklaMap nie pobiera żadnych prowizji - jest to serwis bezpłatny dla wszystkich użytkowników.'
  },
  {
    id: 3,
    category: 'podstawy',
    question: 'Jak rozpocząć korzystanie z platformy?',
    answer: 'Wystarczy wejść na stronę ReklaMap i rozpocząć przeglądanie dostępnych powierzchni reklamowych. Możesz używać zaawansowanych filtrów, aby zawęzić wyniki do swoich potrzeb (typ nośnika, lokalizacja, cena, wymiary, natężenie ruchu, itp.). Jeśli chcesz dodać własne ogłoszenie, kliknij przycisk "Dodaj ogłoszenie" w górnym menu.'
  },
  {
    id: 4,
    category: 'ogloszenia',
    question: 'Jak dodać ogłoszenie?',
    answer: 'Kliknij przycisk "Dodaj ogłoszenie" w górnym menu. Wypełnij formularz w 4 krokach: 1) Typ nośnika i lokalizacja, 2) Wymiary i parametry techniczne, 3) Cena i jednostka ceny, 4) Zdjęcia i opis. Podaj wymagane dane kontaktowe (imię, nazwisko, email, telefon). Po zatwierdzeniu ogłoszenie pojawi się na stronie i będzie aktywne przez 90 dni.'
  },
  {
    id: 5,
    category: 'ogloszenia',
    question: 'Jakie typy nośników mogę dodać?',
    answer: 'Możesz dodawać ogłoszenia dla następujących typów nośników: Billboard, Citylight, Ekran LED, Baner, Ściana, Totem, Transport (autobus, tramwaj, metro, przystanek), Mobile (przyczepka, samochód, rower, inne) oraz Inne. Każdy typ ma swoje specyficzne parametry techniczne.'
  },
  {
    id: 6,
    category: 'ogloszenia',
    question: 'Ile ogłoszeń mogę dodać?',
    answer: 'Nie ma limitu liczby ogłoszeń, które możesz dodać. Możesz publikować dowolną liczbę ofert powierzchni reklamowych, o ile każda z nich zawiera prawdziwe i aktualne informacje. Każde ogłoszenie jest niezależne i ma swój własny kod PIN do zarządzania.'
  },
  {
    id: 7,
    category: 'ogloszenia',
    question: 'Jak edytować lub usunąć ogłoszenie?',
    answer: 'Wejdź do sekcji "Zarządzaj" w górnym menu. Wpisz kod PIN, który otrzymałeś przy dodawaniu ogłoszenia. Po weryfikacji zobaczysz listę swoich ogłoszeń i będziesz mógł je edytować (zmienić dane, zdjęcia, cenę) lub usunąć. Możesz również przedłużyć ogłoszenie na kolejne 90 dni.'
  },
  {
    id: 8,
    category: 'ogloszenia',
    question: 'Jak długo ogłoszenie jest aktywne?',
    answer: 'Ogłoszenie pozostaje aktywne przez 90 dni od daty publikacji. Po tym czasie ogłoszenie wygasa automatycznie. Przed wygaśnięciem możesz je przedłużyć bezpłatnie na kolejne 90 dni. Otrzymasz powiadomienie e-mail z przypomnieniem o zbliżającym się terminie wygaśnięcia.'
  },
  {
    id: 9,
    category: 'ogloszenia',
    question: 'Jakie zdjęcia powinienem dodać?',
    answer: 'Dodaj czytelne, wysokiej jakości zdjęcia pokazujące powierzchnię reklamową z różnych perspektyw. Warto pokazać: lokalizację i otoczenie, widoczność z różnych kierunków, stan techniczny nośnika, wymiary (jeśli to możliwe), oświetlenie (dla nośników nocnych). Możesz dodać maksymalnie 10 zdjęć. Zdjęcia powinny być w formacie JPG lub PNG.'
  },
  {
    id: 10,
    category: 'ogloszenia',
    question: 'Jakie parametry techniczne powinienem podać?',
    answer: 'W zależności od typu nośnika, podaj: wymiary (szerokość x wysokość), oświetlenie (dla billboardów, citylightów, LED), orientację (pionowa/pozioma), natężenie ruchu (niskie/średnie/wysokie), kierunek ruchu (wjazd/wyjazd/oba), typ ruchu (pieszy/samochodowy), rozdzielczość i jasność (dla ekranów LED), oraz inne szczegóły techniczne. Te informacje wpływają na cenę i atrakcyjność ogłoszenia.'
  },
  {
    id: 11,
    category: 'ogloszenia',
    question: 'Jak ustalić cenę?',
    answer: 'Cena zależy od wielu czynników: typu nośnika, lokalizacji, wymiarów, natężenia ruchu, oświetlenia, czasu trwania kampanii i popytu na rynku. Możesz podać cenę w różnych jednostkach: dzień, tydzień, miesiąc, rok, kampania (dla transportu) lub m² (dla większych powierzchni). Warto porównać ceny podobnych nośników w okolicy, aby ustalić konkurencyjną ofertę.'
  },
  {
    id: 12,
    category: 'platnosci',
    question: 'Jak działa system płatności?',
    answer: 'ReklaMap nie pośredniczy w płatnościach. Po znalezieniu odpowiedniej powierzchni, potencjalny wynajmujący kontaktuje się bezpośrednio z Tobą (za pośrednictwem emaila lub telefonu podanego w ogłoszeniu). Uzgadniacie szczegóły współpracy, warunki wynajmu i formę płatności (przelew, gotówka, itp.). Możesz negocjować warunki i oferować rabaty dla dłuższych kampanii.'
  },
  {
    id: 13,
    category: 'platnosci',
    question: 'Czy mogę negocjować cenę?',
    answer: 'Tak, ceny podane w ogłoszeniach są często punktem wyjścia do negocjacji. Szczególnie przy dłuższych kampaniach (3-12 miesięcy) lub wynajmie większej liczby powierzchni, właściciele często oferują rabaty. Skontaktuj się bezpośrednio z wynajmującym i omów możliwości negocjacji.'
  },
  {
    id: 14,
    category: 'techniczne',
    question: 'Jak działa wyszukiwarka?',
    answer: 'Nasza zaawansowana wyszukiwarka pozwala filtrować ogłoszenia według: typu nośnika, lokalizacji (województwo, miasto), wymiarów, ceny, jednostki ceny, orientacji, natężenia ruchu, kierunku ruchu, typu ruchu, oświetlenia, wariantów (dla typów z wariantami), i wielu innych parametrów. Możesz również wyszukiwać po słowach kluczowych w opisie ogłoszenia.'
  },
  {
    id: 15,
    category: 'techniczne',
    question: 'Co to jest mapa interaktywna?',
    answer: 'Mapa interaktywna pokazuje rozmieszczenie wszystkich dostępnych powierzchni reklamowych w Polsce. Możesz kliknąć na znacznik, aby zobaczyć szczegóły danej lokalizacji, typ nośnika, cenę i zdjęcia. To świetne narzędzie do planowania kampanii regionalnych i wizualizacji dostępnych opcji na mapie.'
  },
  {
    id: 16,
    category: 'techniczne',
    question: 'Jak korzystać z porównania ogłoszeń?',
    answer: 'Kliknij ikonę porównania (dwa kwadraty) na karcie ogłoszenia, aby dodać je do porównania. Możesz dodać maksymalnie 5 ogłoszeń. Następnie kliknij ikonę porównania w górnym menu, aby zobaczyć szczegółowe zestawienie wybranych powierzchni obok siebie. Porównanie pokazuje wszystkie parametry techniczne, cenę, zdjęcia i opis.'
  },
  {
    id: 17,
    category: 'techniczne',
    question: 'Jak działa lista ulubionych?',
    answer: 'Kliknij ikonę serca na karcie ogłoszenia, aby dodać je do ulubionych. Twoje ulubione ogłoszenia są zapisywane lokalnie w przeglądarce (w cookies). Możesz do nich wrócić w każdej chwili, klikając ikonę serca w górnym menu. Ulubione ogłoszenia są dostępne tylko na tym urządzeniu i w tej przeglądarce.'
  },
  {
    id: 18,
    category: 'podstawy',
    question: 'Czy potrzebuję konta, aby korzystać z platformy?',
    answer: 'Nie, nie musisz zakładać konta. Możesz przeglądać ogłoszenia, używać filtrów, mapy i porównania bez rejestracji. Aby dodać ogłoszenie, wystarczy podać imię, nazwisko, email i numer telefonu - nie jest wymagana pełna rejestracja. Otrzymasz kod PIN do zarządzania swoimi ogłoszeniami.'
  },
  {
    id: 19,
    category: 'techniczne',
    question: 'Jak mogę zobaczyć statystyki moich ogłoszeń?',
    answer: 'Wejdź do sekcji "Zarządzaj" i podaj kod PIN. Dla każdego ogłoszenia zobaczysz statystyki: liczbę wyświetleń, kliknięcia na telefon, kliknięcia na email, oraz wykres zaangażowania z ostatnich 30 dni. Te informacje pomagają ocenić efektywność ogłoszenia i dostosować parametry.'
  },
  {
    id: 20,
    category: 'techniczne',
    question: 'Czy mogę eksportować ogłoszenie do PDF?',
    answer: 'Tak, na stronie szczegółów ogłoszenia (AdDetailPage) znajduje się przycisk "Pobierz PDF". Kliknij go, aby pobrać profesjonalny dokument PDF zawierający wszystkie informacje o powierzchni reklamowej, zdjęcia, mapę i szczegóły techniczne. PDF można wydrukować lub wysłać potencjalnym wynajmującym.'
  },
  {
    id: 21,
    category: 'techniczne',
    question: 'Czy mogę zobaczyć ulicę na mapie (Street View)?',
    answer: 'Tak, na stronie szczegółów ogłoszenia znajduje się przycisk "Pokaż Street View". Kliknij go, aby zobaczyć widok ulicy z poziomu gruntu. To pozwala na lepszą ocenę lokalizacji, otoczenia i widoczności nośnika reklamowego.'
  },
  {
    id: 22,
    category: 'ogloszenia',
    question: 'Co zrobić, jeśli znalazłem błędne lub nieaktualne ogłoszenie?',
    answer: 'Jeśli napotkasz ogłoszenie z nieprawidłowymi informacjami, skontaktuj się z nami przez formularz kontaktowy (dostępny w stopce strony) lub wyślij email na kontakt@reklamap.pl. Zweryfikujemy zgłoszenie i podejmiemy odpowiednie działania, w tym usunięcie ogłoszenia, jeśli jest to konieczne.'
  },
  {
    id: 23,
    category: 'podstawy',
    question: 'Czy mogę się skontaktować z właścicielem powierzchni?',
    answer: 'Tak, każde ogłoszenie zawiera dane kontaktowe właściciela: imię, nazwisko, email i numer telefonu. Możesz się z nim skontaktować bezpośrednio, aby omówić szczegóły wynajmu, cenę, dostępność i warunki współpracy. Właściciel będzie zainteresowany Twoją ofertą.'
  }
]

const filteredFaq = ref(faqItems)

const filterByCategory = (category: string) => {
  selectedCategory.value = category
  if (category === 'wszystkie') {
    filteredFaq.value = faqItems
  } else {
    filteredFaq.value = faqItems.filter(item => item.category === category)
  }
}

const toggleItem = (id: number) => {
  const index = openItems.value.indexOf(id)
  if (index > -1) {
    openItems.value.splice(index, 1)
  } else {
    openItems.value.push(id)
  }
}

const isOpen = (id: number) => {
  return openItems.value.includes(id)
}

// FAQ Schema for SEO
const faqSchema = computed(() => ({
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  'mainEntity': faqItems.map(item => ({
    '@type': 'Question',
    'name': item.question,
    'acceptedAnswer': {
      '@type': 'Answer',
      'text': item.answer
    }
  }))
}))

// SEO Meta Tags
useSeo({
  title: 'FAQ - Często zadawane pytania | ReklaMap',
  description: 'Odpowiedzi na najczęstsze pytania dotyczące korzystania z platformy ReklaMap. Dowiedz się jak dodawać ogłoszenia, zarządzać powierzchniami reklamowymi i więcej.',
  keywords: 'faq, pytania, pomoc, instrukcje, jak dodać ogłoszenie, powierzchnie reklamowe',
  ogType: 'website',
  canonical: 'https://reklamap.pl/faq',
  structuredData: faqSchema.value
})
</script>

<template>
  <div class="faq-page">
    <div class="hero-section">
      <div class="container">
        <h1>Często zadawane pytania</h1>
        <p class="hero-subtitle">Znajdź odpowiedzi na najczęstsze pytania dotyczące korzystania z ReklaMap</p>
      </div>
    </div>

    <div class="content-section">
      <div class="container">
        <div class="categories">
          <button
            v-for="category in categories"
            :key="category.id"
            @click="filterByCategory(category.id)"
            class="category-btn"
            :class="{ active: selectedCategory === category.id }"
          >
            {{ category.name }}
          </button>
        </div>

        <div class="faq-list">
          <div
            v-for="item in filteredFaq"
            :key="item.id"
            class="faq-item"
            :class="{ open: isOpen(item.id) }"
          >
            <button @click="toggleItem(item.id)" class="faq-question">
              <span class="question-text">{{ item.question }}</span>
              <svg
                class="chevron"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
              >
                <path
                  d="M6 9l6 6 6-6"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </button>
            <div class="faq-answer" v-show="isOpen(item.id)">
              <p>{{ item.answer }}</p>
            </div>
          </div>
        </div>

        <div class="help-box">
          <h2>Nie znalazłeś odpowiedzi?</h2>
          <p>Jeśli nie znalazłeś odpowiedzi na swoje pytanie, skontaktuj się z nami:</p>
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
      </div>
    </div>
  </div>
</template>

<style scoped>
.faq-page {
  min-height: 100vh;
  background: #f9fafb;
}

.hero-section {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.85) 0%, rgba(118, 75, 162, 0.85) 100%), url('../assets/banner-section.png');
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
  max-width: 900px;
  margin: 0 auto;
  padding: 0 2rem;
}

.categories {
  display: flex;
  gap: 1rem;
  margin-bottom: 3rem;
  flex-wrap: wrap;
  justify-content: center;
}

.category-btn {
  padding: 0.75rem 1.5rem;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  font-weight: 600;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.category-btn:hover {
  border-color: #667eea;
  color: #667eea;
}

.category-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: transparent;
  color: white;
}

.faq-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.faq-item {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
}

.faq-item:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.faq-question {
  width: 100%;
  padding: 1.5rem;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  transition: background 0.2s;
}

.faq-question:hover {
  background: #f9fafb;
}

.question-text {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  flex: 1;
}

.chevron {
  flex-shrink: 0;
  color: #667eea;
  transition: transform 0.3s;
}

.faq-item.open .chevron {
  transform: rotate(180deg);
}

.faq-answer {
  padding: 0 1.5rem 1.5rem 1.5rem;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.faq-answer p {
  color: #4b5563;
  line-height: 1.8;
  margin: 0;
}

.help-box {
  background: linear-gradient(135deg, #f0f3ff 0%, #e8eaff 100%);
  border-radius: 12px;
  padding: 3rem;
  text-align: center;
  margin-top: 4rem;
}

.help-box h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.help-box p {
  color: #4b5563;
  font-size: 1.125rem;
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

  .categories {
    gap: 0.5rem;
  }

  .category-btn {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
  }

  .faq-question {
    padding: 1.25rem;
  }

  .question-text {
    font-size: 1rem;
  }

  .help-box {
    padding: 2rem 1.5rem;
  }

  .help-box h2 {
    font-size: 1.5rem;
  }

  .help-actions {
    flex-direction: column;
  }

  .help-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
