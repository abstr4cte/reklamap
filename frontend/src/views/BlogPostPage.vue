<script setup lang="ts">
import { ref, computed } from 'vue'
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

import ToastNotification from '../components/ToastNotification.vue'

const toast = ref<InstanceType<typeof ToastNotification> | null>(null)


const shareToSocial = (platform: 'facebook' | 'twitter' | 'whatsapp' | 'linkedin') => {
  const url = encodeURIComponent(window.location.href)
  const text = encodeURIComponent(`Zobacz ten artykuł: ${post.value.title}`)
  
  let shareUrl = ''
  switch (platform) {
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`
      break
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${text}`
      break
    case 'whatsapp':
      shareUrl = `https://wa.me/?text=${text}%20${url}`
      break
    case 'linkedin':
      shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${url}`
      break
  }
  
  window.open(shareUrl, '_blank', 'width=600,height=400')
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
          <div class="social-share-grid">
            <button @click="shareToSocial('facebook')" class="social-btn facebook">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
              </svg>
              Facebook
            </button>
            <button @click="shareToSocial('twitter')" class="social-btn twitter">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
              X
            </button>
            <button @click="shareToSocial('whatsapp')" class="social-btn whatsapp">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
              </svg>
              WhatsApp
            </button>
            <button @click="shareToSocial('linkedin')" class="social-btn linkedin">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/>
                <rect x="2" y="9" width="4" height="12"/>
                <circle cx="4" cy="4" r="2"/>
              </svg>
              LinkedIn
            </button>
          </div>
        </div>
      </div>
    </div>
    <ToastNotification ref="toast" />
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

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  background: white;
  color: #374151;
  border: 2px solid #e5e7eb;
  padding: 0.875rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  width: fit-content;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
}

.close-btn {
  background: transparent;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.share-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 2rem;
}

.share-link-group {
  display: flex;
  gap: 0.75rem;
  background: #f9fafb;
  padding: 0.5rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.share-input {
  flex: 1;
  padding: 0.75rem;
  border: none;
  background: transparent;
  color: #374151;
  font-size: 0.9rem;
  font-family: inherit;
  width: 100%;
}

.share-input:focus {
  outline: none;
}

.btn-copy {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  color: #374151;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-copy:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
  transform: translateY(-1px);
}

.btn-copy:active {
  transform: translateY(0);
}

.social-share-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.social-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
  border: none;
  border-radius: 12px;
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.social-btn::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(rgba(255,255,255,0.1), rgba(255,255,255,0));
  opacity: 0;
  transition: opacity 0.3s;
}

.social-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.2);
}

.social-btn:hover::after {
  opacity: 1;
}

.social-btn:active {
  transform: translateY(-1px);
}

.social-btn.facebook { 
  background: linear-gradient(135deg, #1877F2 0%, #0C63D4 100%);
  box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}

.social-btn.twitter { 
  background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.social-btn.whatsapp { 
  background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
  box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3);
}

.social-btn.linkedin { 
  background: linear-gradient(135deg, #0A66C2 0%, #004182 100%);
  box-shadow: 0 4px 12px rgba(10, 102, 194, 0.3);
}

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
