<script setup lang="ts">
import { ref, computed } from 'vue'

interface BlogPost {
  id: number
  title: string
  excerpt: string
  content: string
  category: string
  image: string
  date: string
  readTime: string
  author: string
}

const selectedCategory = ref('wszystkie')

const categories = [
  { id: 'wszystkie', name: 'Wszystkie' },
  { id: 'poradniki', name: 'Poradniki' },
  { id: 'trendy', name: 'Trendy' },
  { id: 'case-study', name: 'Case Study' },
  { id: 'nowosci', name: 'Nowości' }
]

const blogPosts: BlogPost[] = [
  {
    id: 1,
    title: 'Jak wybrać idealne miejsce na billboard reklamowy',
    excerpt: 'Poznaj kluczowe czynniki, które decydują o skuteczności reklamy outdoorowej. Lokalizacja to podstawa sukcesu każdej kampanii.',
    content: '',
    category: 'poradniki',
    image: 'https://images.pexels.com/photos/417273/pexels-photo-417273.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '15 listopada 2025',
    readTime: '5 min',
    author: 'Anna Kowalska'
  },
  {
    id: 2,
    title: 'Trendy w reklamie zewnętrznej na 2025 rok',
    excerpt: 'Digital out-of-home, programmatic buying i personalizacja - sprawdź, co będzie kształtować branżę outdoor w najbliższych miesiącach.',
    content: '',
    category: 'trendy',
    image: 'https://images.pexels.com/photos/3761504/pexels-photo-3761504.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '10 listopada 2025',
    readTime: '7 min',
    author: 'Michał Nowak'
  },
  {
    id: 3,
    title: 'ROI kampanii outdoor - jak mierzyć efektywność',
    excerpt: 'Kompleksowy przewodnik po metodach pomiaru skuteczności kampanii reklamowych w przestrzeni miejskiej i na drogach.',
    content: '',
    category: 'poradniki',
    image: 'https://images.pexels.com/photos/590016/pexels-photo-590016.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '5 listopada 2025',
    readTime: '8 min',
    author: 'Katarzyna Wiśniewska'
  },
  {
    id: 4,
    title: 'Kampania marki XYZ - 300% wzrost rozpoznawalności',
    excerpt: 'Sprawdź, jak marka XYZ wykorzystała strategicznie rozmieszczone billboardy do zwiększenia świadomości marki w kluczowych regionach.',
    content: '',
    category: 'case-study',
    image: 'https://images.pexels.com/photos/3683056/pexels-photo-3683056.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '1 listopada 2025',
    readTime: '6 min',
    author: 'Piotr Zieliński'
  },
  {
    id: 5,
    title: 'Ekrany LED vs tradycyjne billboardy - co wybrać',
    excerpt: 'Porównanie kosztów, zasięgu i skuteczności nowoczesnych ekranów LED z klasycznymi nośnikami reklamowymi.',
    content: '',
    category: 'poradniki',
    image: 'https://images.pexels.com/photos/936137/pexels-photo-936137.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '28 października 2025',
    readTime: '6 min',
    author: 'Anna Kowalska'
  },
  {
    id: 6,
    title: 'Nowa funkcja - porównywanie powierzchni reklamowych',
    excerpt: 'Wprowadziliśmy nowe narzędzie umożliwiające zestawienie do 5 ogłoszeń obok siebie. Zobacz, jak z niego korzystać.',
    content: '',
    category: 'nowosci',
    image: 'https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '25 października 2025',
    readTime: '3 min',
    author: 'Michał Nowak'
  },
  {
    id: 7,
    title: 'Sezonowość w reklamie outdoor - kompletny przewodnik',
    excerpt: 'Dowiedz się, kiedy najlepiej zaplanować kampanię reklamową, aby zmaksymalizować jej skuteczność i zoptymalizować koszty.',
    content: '',
    category: 'poradniki',
    image: 'https://images.pexels.com/photos/3184360/pexels-photo-3184360.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '20 października 2025',
    readTime: '9 min',
    author: 'Katarzyna Wiśniewska'
  },
  {
    id: 8,
    title: 'Przyszłość reklamy zewnętrznej w erze cyfrowej',
    excerpt: 'Jak technologie AR, IoT i AI zmieniają tradycyjną reklamę outdoor? Analiza najnowszych innowacji w branży.',
    content: '',
    category: 'trendy',
    image: 'https://images.pexels.com/photos/3861969/pexels-photo-3861969.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '15 października 2025',
    readTime: '10 min',
    author: 'Piotr Zieliński'
  },
  {
    id: 9,
    title: 'Startup osiągnął 10 000 leadów dzięki citylightom',
    excerpt: 'Historia sukcesu technologicznego startupu, który dzięki przemyślanej kampanii citylightowej zdobył tysiące potencjalnych klientów.',
    content: '',
    category: 'case-study',
    image: 'https://images.pexels.com/photos/3184291/pexels-photo-3184291.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '10 października 2025',
    readTime: '7 min',
    author: 'Anna Kowalska'
  }
]

const filteredPosts = computed(() => {
  if (selectedCategory.value === 'wszystkie') {
    return blogPosts
  }
  return blogPosts.filter(post => post.category === selectedCategory.value)
})
</script>

<template>
  <div class="blog-page">
    <div class="hero-section">
      <div class="container">
        <h1>Blog AdSpace</h1>
        <p class="hero-subtitle">Porady, trendy i inspiracje ze świata reklamy outdoor</p>
      </div>
    </div>

    <div class="content-section">
      <div class="container">
        <div class="categories">
          <button
            v-for="category in categories"
            :key="category.id"
            @click="selectedCategory = category.id"
            class="category-btn"
            :class="{ active: selectedCategory === category.id }"
          >
            {{ category.name }}
          </button>
        </div>

        <div class="blog-grid">
          <article
            v-for="post in filteredPosts"
            :key="post.id"
            class="blog-card"
          >
            <div class="card-image">
              <img :src="post.image" :alt="post.title" />
              <div class="card-category">{{ categories.find(c => c.id === post.category)?.name }}</div>
            </div>
            <div class="card-content">
              <div class="card-meta">
                <span class="meta-item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  {{ post.date }}
                </span>
                <span class="meta-item">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                  {{ post.readTime }}
                </span>
              </div>
              <h2 class="card-title">{{ post.title }}</h2>
              <p class="card-excerpt">{{ post.excerpt }}</p>
              <div class="card-footer">
                <div class="author">
                  <div class="author-avatar">
                    {{ post.author.charAt(0) }}
                  </div>
                  <span class="author-name">{{ post.author }}</span>
                </div>
                <button class="read-more">
                  Czytaj więcej
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
              </div>
            </div>
          </article>
        </div>

        <div class="newsletter-box">
          <div class="newsletter-content">
            <h2>Bądź na bieżąco</h2>
            <p>Zapisz się do newslettera i otrzymuj najnowsze artykuły, porady i informacje o trendach w reklamie outdoor.</p>
          </div>
          <form class="newsletter-form" @submit.prevent>
            <input
              type="email"
              placeholder="Twój adres e-mail"
              class="newsletter-input"
            />
            <button type="submit" class="newsletter-btn">
              Zapisz się
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.blog-page {
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
  max-width: 1400px;
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

.blog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 2rem;
  margin-bottom: 4rem;
}

.blog-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s;
  display: flex;
  flex-direction: column;
}

.blog-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.card-image {
  position: relative;
  width: 100%;
  height: 240px;
  overflow: hidden;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s;
}

.blog-card:hover .card-image img {
  transform: scale(1.05);
}

.card-category {
  position: absolute;
  top: 1rem;
  left: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
}

.card-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.card-meta {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #9ca3af;
  font-size: 0.875rem;
}

.card-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 1rem 0;
  line-height: 1.4;
}

.card-excerpt {
  color: #6b7280;
  line-height: 1.6;
  margin: 0 0 1.5rem 0;
  flex: 1;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 2px solid #f3f4f6;
}

.author {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.author-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
}

.author-name {
  color: #4b5563;
  font-weight: 500;
  font-size: 0.875rem;
}

.read-more {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: transparent;
  border: none;
  color: #667eea;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.read-more:hover {
  gap: 0.75rem;
}

.newsletter-box {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 3rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 3rem;
  align-items: center;
}

.newsletter-content h2 {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin: 0 0 1rem 0;
}

.newsletter-content p {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
  margin: 0;
}

.newsletter-form {
  display: flex;
  gap: 1rem;
}

.newsletter-input {
  flex: 1;
  padding: 1rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
}

.newsletter-input:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
}

.newsletter-btn {
  padding: 1rem 2rem;
  background: white;
  color: #667eea;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.newsletter-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

@media (max-width: 1024px) {
  .blog-grid {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  }

  .newsletter-box {
    grid-template-columns: 1fr;
    gap: 2rem;
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

  .blog-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .newsletter-box {
    padding: 2rem 1.5rem;
  }

  .newsletter-content h2 {
    font-size: 1.5rem;
  }

  .newsletter-form {
    flex-direction: column;
  }

  .newsletter-btn {
    justify-content: center;
  }
}
</style>
