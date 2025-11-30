<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

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

// Mock data - same as in BlogPage.vue
const blogPosts: BlogPost[] = [
  {
    id: 1,
    title: 'Jak wybrać idealne miejsce na billboard reklamowy',
    excerpt: 'Poznaj kluczowe czynniki, które decydują o skuteczności reklamy outdoorowej. Lokalizacja to podstawa sukcesu każdej kampanii.',
    content: `
      <p>Wybór odpowiedniej lokalizacji dla billboardu reklamowego to jeden z najważniejszych czynników decydujących o sukcesie kampanii outdoorowej. Nawet najlepiej zaprojektowana reklama nie przyniesie oczekiwanych rezultatów, jeśli zostanie umieszczona w miejscu o niskim natężeniu ruchu lub słabej widoczności.</p>
      
      <h3>1. Analiza grupy docelowej</h3>
      <p>Przed wyborem lokalizacji zastanów się, kim są Twoi potencjalni klienci. Czy są to osoby dojeżdżające do pracy w centrum miasta, studenci, czy może rodziny z dziećmi? Lokalizacja powinna być dopasowana do tras, którymi porusza się Twoja grupa docelowa.</p>

      <h3>2. Natężenie ruchu</h3>
      <p>Im więcej osób zobaczy Twoją reklamę, tym lepiej. Szukaj miejsc przy głównych arteriach komunikacyjnych, skrzyżowaniach oraz w pobliżu centrów handlowych. Pamiętaj jednak, że sam ruch to nie wszystko – liczy się też czas kontaktu z reklamą. Miejsca, gdzie tworzą się korki, mogą być bardziej efektywne niż autostrady, gdzie kierowcy przemieszczają się z dużą prędkością.</p>

      <h3>3. Widoczność i otoczenie</h3>
      <p>Upewnij się, że billboard nie jest zasłonięty przez drzewa, budynki czy inne konstrukcje. Zwróć uwagę na otoczenie – czy w pobliżu nie ma zbyt wielu innych reklam, które mogłyby odwrócić uwagę od Twojego przekazu? "Szum reklamowy" to realny problem w dużych miastach.</p>

      <h3>4. Oświetlenie</h3>
      <p>Reklama powinna być widoczna przez całą dobę. Wybieraj nośniki z oświetleniem lub zainwestuj w nowoczesne ekrany LED, które przyciągają wzrok również po zmroku.</p>

      <h3>Podsumowanie</h3>
      <p>Wybór lokalizacji to proces, który wymaga analizy wielu czynników. Warto skorzystać z narzędzi analitycznych oraz wiedzy ekspertów, aby zmaksymalizować zwrot z inwestycji (ROI) w reklamę zewnętrzną.</p>
    `,
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
    content: `
      <p>Rok 2025 przynosi rewolucyjne zmiany w świecie reklamy zewnętrznej. Tradycyjne billboardy ustępują miejsca cyfrowym nośnikom, a dane stają się nową walutą w planowaniu kampanii.</p>

      <h3>Digital Out-of-Home (DOOH)</h3>
      <p>Cyfryzacja przestrzeni miejskiej postępuje w zawrotnym tempie. Ekrany LED oferują nie tylko lepszą jakość obrazu, ale przede wszystkim elastyczność. Możliwość zmiany treści w czasie rzeczywistym pozwala na dopasowanie komunikatu do pory dnia, pogody czy aktualnych wydarzeń.</p>

      <h3>Programmatic Buying</h3>
      <p>Automatyzacja zakupu mediów wkracza do świata outdooru. Dzięki programmatic buying reklamodawcy mogą kupować czas antenowy na ekranach cyfrowych w modelu aukcyjnym, precyzyjnie docierając do wybranych grup odbiorców w określonym czasie.</p>

      <h3>Interaktywność i AR</h3>
      <p>Reklama przestaje być jednostronnym komunikatem. Wykorzystanie rozszerzonej rzeczywistości (AR) i kodów QR pozwala na interakcję z odbiorcą, przenosząc go ze świata offline do online. To doskonały sposób na zwiększenie zaangażowania i mierzenie efektywności kampanii.</p>
    `,
    category: 'trendy',
    image: 'https://images.pexels.com/photos/3761504/pexels-photo-3761504.jpeg?auto=compress&cs=tinysrgb&w=800',
    date: '10 listopada 2025',
    readTime: '7 min',
    author: 'Michał Nowak'
  },
  // ... (I'll add generic content for other IDs if needed, or just handle the missing ones gracefully)
]

const post = computed(() => {
  const id = Number(route.params.id)
  return blogPosts.find(p => p.id === id) || blogPosts[0] // Fallback to first post if not found
})

const categories = [
  { id: 'poradniki', name: 'Poradniki' },
  { id: 'trendy', name: 'Trendy' },
  { id: 'case-study', name: 'Case Study' },
  { id: 'nowosci', name: 'Nowości' }
]

const getCategoryName = (id: string) => {
  return categories.find(c => c.id === id)?.name || id
}
</script>

<template>
  <div class="blog-post-page">
    <div class="hero-section" :style="{ backgroundImage: `linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url(${post.image})` }">
      <div class="container">
        <div class="post-meta-header">
          <span class="category-badge">{{ getCategoryName(post.category) }}</span>
          <span class="post-date">{{ post.date }} • {{ post.readTime }} czytania</span>
        </div>
        <h1>{{ post.title }}</h1>
        <div class="author-info">
          <div class="author-avatar">{{ post.author.charAt(0) }}</div>
          <span class="author-name">{{ post.author }}</span>
        </div>
      </div>
    </div>

    <div class="content-section">
      <div class="container">
        <button @click="router.push('/blog')" class="back-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
            <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          Wróć do bloga
        </button>

        <article class="post-content">
          <p class="lead">{{ post.excerpt }}</p>
          <div v-html="post.content || '<p>Treść artykułu w przygotowaniu...</p>'"></div>
        </article>

        <div class="share-section">
          <h3>Udostępnij ten artykuł</h3>
          <div class="share-buttons">
            <button class="share-btn facebook">Facebook</button>
            <button class="share-btn twitter">Twitter</button>
            <button class="share-btn linkedin">LinkedIn</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.blog-post-page {
  min-height: 100vh;
  background: #f9fafb;
}

.hero-section {
  height: 60vh;
  min-height: 400px;
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: flex-end;
  padding-bottom: 4rem;
  color: white;
}

.container {
  max-width: 900px;
  margin: 0 auto;
  padding: 0 2rem;
  width: 100%;
}

.post-meta-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.category-badge {
  background: #667eea;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-weight: 600;
  font-size: 0.875rem;
}

.post-date {
  opacity: 0.9;
  font-size: 0.9rem;
}

h1 {
  font-size: 3rem;
  font-weight: 800;
  line-height: 1.2;
  margin: 0 0 2rem 0;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.author-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.author-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: white;
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
}

.author-name {
  font-weight: 500;
  font-size: 1.1rem;
}

.content-section {
  padding: 3rem 0;
  background: white;
  margin-top: -2rem;
  border-radius: 20px 20px 0 0;
  position: relative;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: none;
  border: none;
  color: #6b7280;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 2rem;
  padding: 0;
  transition: color 0.2s;
}

.back-btn:hover {
  color: #667eea;
}

.post-content {
  font-size: 1.125rem;
  line-height: 1.8;
  color: #374151;
}

.lead {
  font-size: 1.25rem;
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 2rem;
  border-left: 4px solid #667eea;
  padding-left: 1.5rem;
}

.post-content :deep(h2), .post-content :deep(h3) {
  color: #111827;
  font-weight: 700;
  margin: 2.5rem 0 1rem 0;
}

.post-content :deep(p) {
  margin-bottom: 1.5rem;
}

.share-section {
  margin-top: 4rem;
  padding-top: 2rem;
  border-top: 1px solid #e5e7eb;
}

.share-section h3 {
  margin-bottom: 1.5rem;
  color: #1f2937;
}

.share-buttons {
  display: flex;
  gap: 1rem;
}

.share-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  color: white;
  cursor: pointer;
  transition: transform 0.2s;
}

.share-btn:hover {
  transform: translateY(-2px);
}

.facebook { background: #1877f2; }
.twitter { background: #1da1f2; }
.linkedin { background: #0a66c2; }

@media (max-width: 768px) {
  .hero-section {
    height: 50vh;
  }
  
  h1 {
    font-size: 2rem;
  }
  
  .container {
    padding: 0 1.5rem;
  }
}
</style>
