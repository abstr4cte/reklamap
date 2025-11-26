<script setup lang="ts">
import { ref } from 'vue'

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
    question: 'Czym jest AdSpace?',
    answer: 'AdSpace to platforma łącząca właścicieli powierzchni reklamowych z firmami poszukującymi miejsc na reklamę. Oferujemy kompleksową bazę billboardów, citylightów, ekranów LED i innych nośników reklamowych w całej Polsce.'
  },
  {
    id: 2,
    category: 'podstawy',
    question: 'Czy korzystanie z platformy jest bezpłatne?',
    answer: 'Przeglądanie ogłoszeń i wyszukiwanie powierzchni reklamowych jest całkowicie bezpłatne. Dodawanie ogłoszeń również nie wiąże się z żadnymi kosztami. Pobieramy prowizję jedynie od zawartych transakcji.'
  },
  {
    id: 3,
    category: 'podstawy',
    question: 'Jak rozpocząć korzystanie z platformy?',
    answer: 'Wystarczy wejść na stronę AdSpace i rozpocząć przeglądanie dostępnych powierzchni reklamowych. Możesz używać filtrów, aby zawęzić wyniki do swoich potrzeb. Jeśli chcesz dodać własne ogłoszenie, kliknij przycisk "Dodaj ogłoszenie" i wypełnij formularz.'
  },
  {
    id: 4,
    category: 'ogloszenia',
    question: 'Jak dodać ogłoszenie?',
    answer: 'Kliknij przycisk "Dodaj ogłoszenie" w górnym menu. Wypełnij formularz, podając wszystkie wymagane informacje o powierzchni reklamowej: lokalizację, wymiary, cenę, typ nośnika. Dodaj zdjęcia wysokiej jakości i opis. Po zatwierdzeniu ogłoszenie pojawi się na stronie.'
  },
  {
    id: 5,
    category: 'ogloszenia',
    question: 'Ile ogłoszeń mogę dodać?',
    answer: 'Nie ma limitu liczby ogłoszeń, które możesz dodać. Możesz publikować dowolną liczbę ofert powierzchni reklamowych, o ile każda z nich zawiera prawdziwe i aktualne informacje.'
  },
  {
    id: 6,
    category: 'ogloszenia',
    question: 'Jak edytować lub usunąć ogłoszenie?',
    answer: 'Wejdź do sekcji "Zarządzaj" w górnym menu. Znajdziesz tam wszystkie swoje ogłoszenia. Podaj kod PIN, który otrzymałeś przy dodawaniu ogłoszenia. Po weryfikacji możesz edytować lub usunąć wybrane ogłoszenia.'
  },
  {
    id: 7,
    category: 'ogloszenia',
    question: 'Jak długo ogłoszenie jest aktywne?',
    answer: 'Ogłoszenie pozostaje aktywne przez 90 dni od daty publikacji. Po tym czasie możesz je przedłużyć bezpłatnie. Otrzymasz powiadomienie e-mail z przypomnieniem o zbliżającym się terminie wygaśnięcia.'
  },
  {
    id: 8,
    category: 'ogloszenia',
    question: 'Jakie zdjęcia powinienem dodać?',
    answer: 'Dodaj czytelne, wysokiej jakości zdjęcia pokazujące powierzchnię reklamową z różnych perspektyw. Warto pokazać lokalizację, otoczenie, widoczność z różnych kierunków oraz stan techniczny nośnika. Możesz dodać maksymalnie 10 zdjęć.'
  },
  {
    id: 9,
    category: 'platnosci',
    question: 'Jak ustalane są ceny?',
    answer: 'Ceny ustala właściciel powierzchni reklamowej i zależą od wielu czynników: lokalizacji, ruchu pieszego/samochodowego, rozmiaru, typu nośnika oraz czasu trwania kampanii. Ceny można porównać używając naszego narzędzia porównania.'
  },
  {
    id: 10,
    category: 'platnosci',
    question: 'Jak działa system płatności?',
    answer: 'AdSpace nie pośredniczy w płatnościach. Po znalezieniu odpowiedniej powierzchni kontaktujesz się bezpośrednio z właścicielem i uzgadniacie szczegóły współpracy oraz płatności. Możesz negocjować warunki i formę płatności.'
  },
  {
    id: 11,
    category: 'platnosci',
    question: 'Czy mogę negocjować cenę?',
    answer: 'Tak, ceny podane w ogłoszeniach są często punktem wyjścia do negocjacji. Szczególnie przy dłuższych kampaniach lub wynajmie większej liczby powierzchni, właściciele często oferują rabaty. Skontaktuj się bezpośrednio z właścicielem.'
  },
  {
    id: 12,
    category: 'techniczne',
    question: 'Jak działa wyszukiwarka?',
    answer: 'Nasza zaawansowana wyszukiwarka pozwala filtrować ogłoszenia według: typu powierzchni, lokalizacji (region, miasto), wymiarów, ceny, okresu wynajmu, orientacji, natężenia ruchu i wielu innych parametrów. Możesz również wyszukiwać po słowach kluczowych.'
  },
  {
    id: 13,
    category: 'techniczne',
    question: 'Co to jest mapa interaktywna?',
    answer: 'Mapa interaktywna pokazuje rozmieszczenie wszystkich dostępnych powierzchni reklamowych w Polsce. Możesz kliknąć na znacznik, aby zobaczyć szczegóły danej lokalizacji. To świetne narzędzie do planowania kampanii regionalnych.'
  },
  {
    id: 14,
    category: 'techniczne',
    question: 'Jak korzystać z porównania ogłoszeń?',
    answer: 'Kliknij ikonę porównania na karcie ogłoszenia, aby dodać je do porównania. Możesz dodać maksymalnie 5 ogłoszeń. Następnie kliknij ikonę porównania w górnym menu, aby zobaczyć szczegółowe zestawienie wybranych powierzchni obok siebie.'
  },
  {
    id: 15,
    category: 'techniczne',
    question: 'Jak działa lista ulubionych?',
    answer: 'Kliknij ikonę serca na karcie ogłoszenia, aby dodać je do ulubionych. Twoje ulubione ogłoszenia są zapisywane lokalnie w przeglądarce. Możesz do nich wrócić w każdej chwili, klikając ikonę serca w górnym menu.'
  },
  {
    id: 16,
    category: 'podstawy',
    question: 'Czy potrzebuję konta, aby korzystać z platformy?',
    answer: 'Nie, nie musisz zakładać konta. Możesz przeglądać ogłoszenia, używać filtrów i mapy bez rejestracji. Aby dodać ogłoszenie, wystarczy podać adres e-mail i numer telefonu - nie jest wymagana pełna rejestracja.'
  },
  {
    id: 17,
    category: 'techniczne',
    question: 'Czy mogę otrzymywać powiadomienia o nowych ogłoszeniach?',
    answer: 'Obecnie pracujemy nad funkcją powiadomień e-mail. Wkrótce będziesz mógł zapisać swoje kryteria wyszukiwania i otrzymywać automatyczne powiadomienia o nowych ogłoszeniach pasujących do Twoich potrzeb.'
  },
  {
    id: 18,
    category: 'ogloszenia',
    question: 'Co zrobić, jeśli znalazłem błędne lub nieaktualne ogłoszenie?',
    answer: 'Jeśli napotkasz ogłoszenie z nieprawidłowymi informacjami, skontaktuj się z nami przez formularz kontaktowy. Zweryfikujemy zgłoszenie i podejmiemy odpowiednie działania, w tym usunięcie ogłoszenia, jeśli jest to konieczne.'
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
</script>

<template>
  <div class="faq-page">
    <div class="hero-section">
      <div class="container">
        <h1>Często zadawane pytania</h1>
        <p class="hero-subtitle">Znajdź odpowiedzi na najczęstsze pytania dotyczące korzystania z AdSpace</p>
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
            <a href="mailto:kontakt@adspace.pl" class="help-btn secondary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              kontakt@adspace.pl
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
