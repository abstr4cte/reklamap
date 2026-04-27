<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { api } from '../services/api'
import { getRecaptchaToken, isRecaptchaAvailable } from '../services/recaptchaService'
import { useSeo } from '../composables/useSeo'
import { appUrl } from '../utils/url'
import WebPImage from '../components/WebPImage.vue'

const categoryMeta: Record<string, { title: string; description: string; keywords: string; heading: string; intro: string }> = {
  'wszystkie': {
    title: 'Blog o reklamie zewnętrznej | ReklaMap',
    description: 'Artykuły, poradniki i aktualności ze świata reklamy zewnętrznej OOH. Dowiedz się jak skutecznie reklamować się na billboardach, banerach i ekranach LED.',
    keywords: 'blog reklama zewnętrzna, OOH, billboardy, poradniki reklamowe',
    heading: 'Blog ReklaMap',
    intro: 'Porady, trendy i inspiracje ze świata reklamy outdoor'
  },
  'poradniki': {
    title: 'Poradniki – Jak skutecznie reklamować się outdoorowowo | ReklaMap Blog',
    description: 'Praktyczne poradniki dla reklamodawców i właścicieli nośników. Jak wybrać billboard, ustalić cenę, napisać brief i mierzyć efektywność kampanii OOH.',
    keywords: 'poradniki reklama zewnętrzna, jak wybrać billboard, kampania OOH, reklama outdoor jak zacząć',
    heading: 'Poradniki reklamowe',
    intro: 'Praktyczne wskazówki dla reklamodawców i właścicieli nośników — od wyboru lokalizacji po mierzenie efektywności kampanii OOH.'
  },
  'trendy': {
    title: 'Trendy w reklamie zewnętrznej – OOH i DOOH | ReklaMap Blog',
    description: 'Najnowsze trendy w reklamie zewnętrznej: DOOH, programmatic OOH, AI w reklamie outdoorowej. Śledź zmiany na rynku reklamy OOH w Polsce i na świecie.',
    keywords: 'trendy reklama zewnętrzna, DOOH, programmatic OOH, cyfrowe billboardy, reklama przyszłości',
    heading: 'Trendy w reklamie OOH',
    intro: 'Śledzmy razem przyszłość reklamy zewnętrznej — od cyfrowych ekranów LED po programmatic buying i AI w planowaniu kampanii outdoorowych.'
  },
  'case-study': {
    title: 'Case Study – Skuteczne kampanie reklamowe OOH | ReklaMap Blog',
    description: 'Analizy i case study skutecznych kampanii reklamowych outdoor. Dowiedz się co działało, jakie wyniki osiągnęły marki i jakie wnioski wyciągnąć dla własnej kampanii.',
    keywords: 'case study reklama OOH, kampania billboardowa wyniki, przykłady reklamy zewnętrznej',
    heading: 'Case study kampanii OOH',
    intro: 'Konkretne przykłady i analizy kampanii outdoorowych — co zadziałało, jakie wyniki osiągnięto i co możesz zastosować we własnej reklamie.'
  },
  'rynek-ooh': {
    title: 'Rynek reklamy OOH w Polsce – Dane i Analizy | ReklaMap Blog',
    description: 'Analizy rynku reklamy zewnętrznej OOH w Polsce. Dane o wydatkach, wzrostach, udziałach formatów i prognozy dla branży outdoor na kolejne lata.',
    keywords: 'rynek OOH Polska, reklama zewnętrzna dane, wydatki na billboardy, branża outdoor statystyki',
    heading: 'Rynek reklamy OOH',
    intro: 'Dane, statystyki i analizy polskiego rynku reklamy zewnętrznej — wydatki, udziały formatów, wzrosty i prognozy dla branży OOH.'
  },
  'prawo-i-regulacje': {
    title: 'Prawo i regulacje w reklamie zewnętrznej | ReklaMap Blog',
    description: 'Przepisy dotyczące reklamy outdoor w Polsce. Ustawa krajobrazowa, pozwolenia na billboard, strefy ochrony krajobrazu i zmiany prawne w reklamie zewnętrznej.',
    keywords: 'ustawa krajobrazowa billboard, pozwolenie na reklamę zewnętrzną, przepisy reklama outdoor, regulacje OOH',
    heading: 'Prawo i regulacje OOH',
    intro: 'Przepisy, pozwolenia i zmiany prawne dotyczące reklamy zewnętrznej w Polsce — wszystko co musisz wiedzieć zanim postawisz nośnik.'
  },
  'lokalizacje': {
    title: 'Najlepsze lokalizacje pod reklamę zewnętrzną w Polsce | ReklaMap Blog',
    description: 'Przewodniki po lokalizacjach reklamowych w polskich miastach. Gdzie warto postawić billboard, citylight lub ekran LED w Warszawie, Krakowie, Wrocławiu i innych miastach.',
    keywords: 'najlepsze lokalizacje billboard, gdzie postawić reklamę, lokalizacje OOH Warszawa Kraków',
    heading: 'Lokalizacje reklamowe w Polsce',
    intro: 'Przewodniki po rynkach reklamowych polskich miast — gdzie warto zainwestować w billboard, citylight lub ekran LED, żeby maksymalizować zasięg kampanii.'
  }
}

const router = useRouter()
const route = useRoute()

const selectedCategory = computed(() => (route.params.category as string) || 'wszystkie')

const noindexEmptyCategory = ref(false)

const seoData = computed(() => {
  const cat = selectedCategory.value
  const meta = categoryMeta[cat] || categoryMeta['wszystkie']
  const canonical = cat === 'wszystkie' ? `${appUrl}/blog` : `${appUrl}/blog/${cat}`
  return {
    title: meta.title,
    description: meta.description,
    keywords: meta.keywords,
    ogType: 'website' as const,
    ogImage: `${appUrl}/og-image.png`,
    ogImageWidth: '1200',
    ogImageHeight: '630',
    ogImageAlt: 'ReklaMap Blog – reklama zewnętrzna OOH',
    canonical,
    noindex: noindexEmptyCategory.value
  }
})

useSeo(seoData)

const currentCategoryMeta = computed(() => categoryMeta[selectedCategory.value] || categoryMeta['wszystkie'])

const newsletterEmail = ref('')
const isSubmittingNewsletter = ref(false)
const newsletterSuccess = ref(false)
const newsletterError = ref('')

interface BlogPost {
  id: number
  title: string
  slug: string
  excerpt: string
  content: string
  category: string
  image: string
  imageAlt: string | null
  date: string
  readTime: string
  author: string
}

const blogPosts = ref<BlogPost[]>([])
const isLoading = ref(true)

const categories = [
  { id: 'wszystkie', name: 'Wszystkie', path: '/blog' },
  { id: 'poradniki', name: 'Poradniki', path: '/blog/poradniki' },
  { id: 'trendy', name: 'Trendy', path: '/blog/trendy' },
  // { id: 'case-study', name: 'Case Study', path: '/blog/case-study' }, // brak artykułów — przywrócić gdy pojawią się treści
  // { id: 'rynek-ooh', name: 'Rynek OOH', path: '/blog/rynek-ooh' }, // brak artykułów — przywrócić gdy pojawią się treści
  { id: 'prawo-i-regulacje', name: 'Prawo i regulacje', path: '/blog/prawo-i-regulacje' },
  { id: 'lokalizacje', name: 'Lokalizacje', path: '/blog/lokalizacje' }
]

watch(
  () => route.params.category,
  () => { loadBlogPosts() }
)

const loadBlogPosts = async () => {
  try {
    isLoading.value = true
    const response = await api.get('/blog')
    blogPosts.value = response
  } catch (error) {
    blogPosts.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadBlogPosts()
})

const filteredPosts = computed(() => {
  if (selectedCategory.value === 'wszystkie') {
    return blogPosts.value
  }
  return blogPosts.value.filter(post => post.category === selectedCategory.value)
})

watch([filteredPosts, isLoading], () => {
  noindexEmptyCategory.value = !isLoading.value && filteredPosts.value.length === 0 && selectedCategory.value !== 'wszystkie'
})

const handleNewsletterSubmit = async () => {
  const email = newsletterEmail.value.trim()
  
  if (!email) {
    newsletterError.value = 'Proszę wpisać adres e-mail'
    return
  }

  // Validate email format - RFC 5322 simplified
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email)) {
    newsletterError.value = 'Proszę wpisać prawidłowy adres e-mail (np. nazwa@domena.pl)'
    return
  }

  // Additional validation - check for common mistakes
  if (email.includes('..')) {
    newsletterError.value = 'Adres e-mail zawiera błędy'
    return
  }

  if (email.startsWith('.') || email.endsWith('.')) {
    newsletterError.value = 'Adres e-mail zawiera błędy'
    return
  }

  const [localPart, domain] = email.split('@')
  if (!localPart || localPart.length === 0 || localPart.length > 64) {
    newsletterError.value = 'Część przed @ jest za długa lub pusta'
    return
  }

  if (!domain || domain.length === 0 || domain.length > 255) {
    newsletterError.value = 'Domena jest za długa lub pusta'
    return
  }

  if (!domain.includes('.')) {
    newsletterError.value = 'Domena musi zawierać kropkę (np. domena.pl)'
    return
  }

  isSubmittingNewsletter.value = true
  newsletterError.value = ''

  try {
    // Get reCAPTCHA token
    let recaptchaToken = ''
    if (isRecaptchaAvailable()) {
      recaptchaToken = await getRecaptchaToken('newsletter_subscribe')
    }

    await api.subscribeNewsletter(newsletterEmail.value, recaptchaToken)
    
    newsletterSuccess.value = true
    newsletterEmail.value = ''
    
    setTimeout(() => {
      newsletterSuccess.value = false
    }, 5000)
  } catch (err) {
    newsletterError.value = err instanceof Error ? err.message : 'Błąd podczas zapisywania do newslettera'
  } finally {
    isSubmittingNewsletter.value = false
  }
}
</script>

<template>
  <div class="blog-page">
    <div class="hero-section">
      <div class="container">
        <h1>{{ currentCategoryMeta.heading }}</h1>
        <p class="hero-subtitle">{{ currentCategoryMeta.intro }}</p>
      </div>
    </div>

    <div class="content-section">
      <div class="container">
        <div class="categories">
          <router-link
            v-for="category in categories"
            :key="category.id"
            :to="category.path"
            class="category-btn"
            :class="{ active: selectedCategory === category.id }"
          >
            {{ category.name }}
          </router-link>
        </div>

        <div class="blog-grid">
          <article
            v-for="post in filteredPosts"
            :key="post.id"
            class="blog-card"
            @click="router.push(`/blog/${post.category}/${post.slug}`)"
            style="cursor: pointer"
          >
            <div class="card-image">
              <WebPImage :src="post.image" :alt="post.imageAlt ?? post.title" />
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
          <form class="newsletter-form" @submit.prevent="handleNewsletterSubmit">
            <div class="newsletter-form-container">
              <div v-if="newsletterError" class="newsletter-error">
                {{ newsletterError }}
              </div>
              <div v-if="newsletterSuccess" class="newsletter-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                  <path d="M22 11.08V12a10 10 0 11-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M22 4L12 14.01l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Dziękujemy! Sprawdź swoją skrzynkę e-mail.
              </div>
              <div class="newsletter-input-group">
                <input
                  v-model="newsletterEmail"
                  type="text"
                  placeholder="Twój adres e-mail"
                  class="newsletter-input"
                  :disabled="isSubmittingNewsletter"
                />
                <button type="submit" class="newsletter-btn" :disabled="isSubmittingNewsletter">
                  <span v-if="!isSubmittingNewsletter">
                    Zapisz się
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                      <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </span>
                  <span v-else>
                    <div class="spinner"></div>
                  </span>
                </button>
              </div>
            </div>
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
  text-decoration: none;
  display: inline-block;
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
}

.newsletter-form-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.newsletter-input-group {
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
  background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
  color: white;
  border: 2px solid rgba(255,255,255,0.4);
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.newsletter-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0.2) 100%);
  border-color: rgba(255,255,255,0.6);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

.newsletter-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.newsletter-error {
  padding: 0.75rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 6px;
  color: #fff;
  font-size: 0.875rem;
  font-weight: 500;
}

.newsletter-success {
  padding: 0.75rem 1rem;
  background: rgba(16, 185, 129, 0.2);
  border: 1px solid rgba(16, 185, 129, 0.4);
  border-radius: 6px;
  color: #fff;
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
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

  .newsletter-input-group {
    flex-direction: column;
  }

  .newsletter-input {
    width: 100%;
  }

  .newsletter-btn {
    justify-content: center;
  }
}
</style>
