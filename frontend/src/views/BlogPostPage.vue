<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../services/api'
import { useSeo } from '../composables/useSeo'
import { appUrl } from '../utils/url'
import logoImage from '../assets/logo.webp'

const route = useRoute()
const router = useRouter()

interface BlogPost {
  id: number
  title: string
  slug: string
  excerpt: string
  content: string
  category: string
  image: string | null
  imageAlt: string | null
  date: string
  dateIso: string | null
  dateModifiedIso?: string | null
  readTime: string
  author: string
}

const post = ref<BlogPost | null>(null)
const isLoading = ref(true)
const notFound = ref(false)

const loadBlogPost = async () => {
  try {
    isLoading.value = true
    const slug = route.params.slug as string
    const response = await api.get(`/blog/${slug}`)
    post.value = response
  } catch (error) {
    notFound.value = true
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadBlogPost()
})

// Parsuje sekcję FAQ z HTML contentu i zwraca schema FAQPage lub null
function extractFaqSchema(html: string): object | null {
  if (typeof window === 'undefined') return null
  const doc = new DOMParser().parseFromString(html, 'text/html')

  let faqHeading: Element | null = null
  doc.querySelectorAll('h2').forEach(h => {
    if (h.textContent?.includes('Najczęściej zadawane pytania')) {
      faqHeading = h
    }
  })
  if (!faqHeading) return null

  const entities: object[] = []
  let sibling = (faqHeading as Element).nextElementSibling

  while (sibling && sibling.tagName !== 'H2') {
    if (sibling.tagName === 'P') {
      const strong = sibling.querySelector('strong')
      if (strong) {
        const questionText = strong.textContent?.trim() ?? ''
        const clone = sibling.cloneNode(true) as Element
        clone.querySelector('strong')?.remove()
        clone.querySelectorAll('br').forEach(br => br.replaceWith(' '))
        const answerText = clone.textContent?.trim() ?? ''
        if (questionText && answerText) {
          entities.push({
            '@type': 'Question',
            'name': questionText,
            'acceptedAnswer': { '@type': 'Answer', 'text': answerText }
          })
        }
      }
    }
    sibling = sibling.nextElementSibling
  }

  if (entities.length === 0) return null
  return { '@context': 'https://schema.org', '@type': 'FAQPage', 'mainEntity': entities }
}

// Buduje schema BreadcrumbList dla posta
function buildBreadcrumbSchema(p: BlogPost): object {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    'itemListElement': [
      { '@type': 'ListItem', 'position': 1, 'name': 'Strona główna', 'item': appUrl },
      { '@type': 'ListItem', 'position': 2, 'name': 'Blog', 'item': `${appUrl}/blog` },
      { '@type': 'ListItem', 'position': 3, 'name': p.title, 'item': `${appUrl}/blog/${p.category}/${p.slug}` }
    ]
  }
}

// SEO Meta Tags — computed ref przekazany do useSeo na poziomie setup
import { computed } from 'vue'

const seoOptions = computed(() => {
  const newPost = post.value
  if (!newPost) return { title: 'Blog | ReklaMap', description: '' }

  const url = `${appUrl}/blog/${newPost.category}/${newPost.slug}`

  const blogPostingSchema = {
    '@context': 'https://schema.org',
    '@type': 'BlogPosting',
    'headline': newPost.title,
    'url': url,
    'mainEntityOfPage': { '@type': 'WebPage', '@id': url },
    'articleSection': newPost.category,
    'description': newPost.excerpt,
    'image': newPost.image ? {
      '@type': 'ImageObject',
      'url': newPost.image,
      'description': newPost.imageAlt ?? newPost.title
    } : undefined,
    'author': {
      '@type': 'Person',
      'name': newPost.author
    },
    'datePublished': newPost.dateIso ?? undefined,
    'dateModified': newPost.dateModifiedIso ?? newPost.dateIso ?? undefined,
    'publisher': {
      '@type': 'Organization',
      'name': 'ReklaMap',
      'logo': { '@type': 'ImageObject', 'url': logoImage }
    }
  }

  const schemas: object[] = [blogPostingSchema, buildBreadcrumbSchema(newPost)]
  const faqSchema = extractFaqSchema(newPost.content ?? '')
  if (faqSchema) schemas.push(faqSchema)

  return {
    title: `${newPost.title} | Blog ReklaMap`,
    description: newPost.excerpt,
    keywords: `blog, reklama, outdoor, ${newPost.category}, ${newPost.title}`,
    ogType: 'article',
    ogImage: newPost.image || `${base}/og-image.png`,
    ogUrl: url,
    canonical: url,
    publishedTime: newPost.dateIso ?? undefined,
    modifiedTime: newPost.dateModifiedIso ?? undefined,
    structuredData: schemas
  }
})

useSeo(seoOptions)

const categories = [
  { id: 'poradniki', name: 'Poradniki' },
  { id: 'trendy', name: 'Trendy' },
  // { id: 'case-study', name: 'Case Study' }, // brak artykułów — przywrócić gdy pojawią się treści
  { id: 'rynek-ooh', name: 'Rynek OOH' },
  { id: 'prawo-i-regulacje', name: 'Prawo i regulacje' },
  { id: 'lokalizacje', name: 'Lokalizacje' }
]

const getCategoryName = (id: string) => {
  return categories.find(c => c.id === id)?.name || id
}

import ToastNotification from '../components/ToastNotification.vue'

const toast = ref<InstanceType<typeof ToastNotification> | null>(null)


const shareToSocial = (platform: 'facebook' | 'twitter' | 'whatsapp' | 'linkedin') => {
  if (!post.value) return
  
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
    <div v-if="isLoading" class="skeleton-page">
      <div class="skeleton-hero skeleton"></div>
      <div class="container skeleton-body">
        <div class="skeleton-line skeleton" style="width: 120px; height: 16px; margin-bottom: 2rem;"></div>
        <div class="skeleton-line skeleton" style="width: 100%; height: 24px; margin-bottom: 1rem;"></div>
        <div class="skeleton-line skeleton" style="width: 80%; height: 24px; margin-bottom: 2rem;"></div>
        <div class="skeleton-line skeleton" style="width: 100%; height: 16px; margin-bottom: 0.75rem;"></div>
        <div class="skeleton-line skeleton" style="width: 100%; height: 16px; margin-bottom: 0.75rem;"></div>
        <div class="skeleton-line skeleton" style="width: 90%; height: 16px; margin-bottom: 0.75rem;"></div>
        <div class="skeleton-line skeleton" style="width: 100%; height: 16px; margin-bottom: 0.75rem;"></div>
        <div class="skeleton-line skeleton" style="width: 70%; height: 16px;"></div>
      </div>
    </div>

    <div v-else-if="notFound" class="not-found-page">
      <div class="not-found-content">
          <div class="error-code">404</div>
          <h1>Artykuł nie został znaleziony</h1>
          <p>Wygląda na to, że artykuł, którego szukasz, nie istnieje lub został przeniesiony.</p>
          <div class="not-found-actions">
            <button @click="router.push('/blog')" class="btn btn-primary">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Wróć do bloga
            </button>
          </div>
      </div>
    </div>

    <div v-else-if="post" class="blog-post-page">
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

.post-content :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 2rem 0;
  font-size: 0.95rem;
  overflow-x: auto;
  display: block;
}

.post-content :deep(thead) {
  background: #f3f4f6;
}

.post-content :deep(th) {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #111827;
  border-bottom: 2px solid #e5e7eb;
  white-space: nowrap;
}

.post-content :deep(td) {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  color: #374151;
}

.post-content :deep(tr:last-child td) {
  border-bottom: none;
}

.post-content :deep(tr:hover td) {
  background: #f9fafb;
}

.post-content :deep(ul), .post-content :deep(ol) {
  margin: 0 0 1.5rem 1.5rem;
  line-height: 1.8;
}

.post-content :deep(li) {
  margin-bottom: 0.4rem;
}

.post-content :deep(strong) {
  color: #111827;
  font-weight: 600;
}

.post-content :deep(a) {
  color: #667eea;
  text-decoration: underline;
}

.post-content :deep(a:hover) {
  color: #4f46e5;
}

.post-content :deep(code) {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 0.2em 0.5em;
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.9em;
  color: #4f46e5;
  white-space: nowrap;
}

.post-content :deep(pre) {
  background: #1f2937;
  border-radius: 8px;
  padding: 1.5rem;
  margin: 2rem 0;
  overflow-x: auto;
}

.post-content :deep(pre code) {
  background: none;
  border: none;
  padding: 0;
  color: #e5e7eb;
  font-size: 0.9rem;
  white-space: pre;
}

.post-content :deep(blockquote) {
  border-left: 4px solid #667eea;
  background: #f5f3ff;
  margin: 2rem 0;
  padding: 1rem 1.5rem;
  border-radius: 0 8px 8px 0;
  color: #374151;
  font-style: italic;
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
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
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

/* Skeleton loading */
.skeleton-page {
  min-height: 100vh;
  background: #f9fafb;
}

.skeleton-hero {
  height: 60vh;
  min-height: 400px;
  border-radius: 0;
}

.skeleton-body {
  max-width: 780px;
  padding-top: 3rem;
}

.skeleton-line {
  border-radius: 6px;
  display: block;
}

.skeleton {
  background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* 404 not found */
.not-found-page {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  padding: 2rem;
}

.not-found-content {
  background: white;
  padding: 4rem 2rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
  text-align: center;
  max-width: 560px;
  width: 100%;
}

.error-code {
  font-size: 8rem;
  font-weight: 900;
  line-height: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 1.5rem;
}

.not-found-content h1 {
  font-size: 2rem;
  color: #1f2937;
  font-weight: 800;
  margin-bottom: 1rem;
}

.not-found-content p {
  color: #6b7280;
  font-size: 1.1rem;
  margin-bottom: 2.5rem;
  line-height: 1.6;
}

.not-found-actions {
  display: flex;
  justify-content: center;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(102, 126, 234, 0.5);
}

.btn-primary:active {
  transform: translateY(0);
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
