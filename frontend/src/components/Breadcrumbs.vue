<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'

interface BreadcrumbItem {
  label: string
  path?: string
}

const props = defineProps<{
  items: BreadcrumbItem[]
}>()

const router = useRouter()

const navigateTo = (path?: string) => {
  if (path) {
    // Navigate via breadcrumbs means returning to a general category page,
    // so we should clear the search continuity flags, same as header menu
    try {
      localStorage.removeItem('user_initiated_search')
      localStorage.removeItem('reklamap_last_search')
    } catch (e) { /* ignore */ }
    
    // Reset query params when navigating via breadcrumbs
    router.push({ path, query: {} })
  }
}

// JSON-LD structured data for SEO
const jsonLd = computed(() => {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    'itemListElement': props.items.map((item, index) => ({
      '@type': 'ListItem',
      'position': index + 1,
      'name': item.label,
      ...(item.path && { 'item': `${window.location.origin}${item.path}` })
    }))
  }
})

let scriptElement: HTMLScriptElement | null = null

const updateJsonLd = () => {
  if (typeof window === 'undefined') return
  
  // Remove existing script if present
  if (scriptElement && scriptElement.parentNode) {
    scriptElement.parentNode.removeChild(scriptElement)
  }
  
  // Create new script element
  scriptElement = document.createElement('script')
  scriptElement.type = 'application/ld+json'
  scriptElement.textContent = JSON.stringify(jsonLd.value)
  document.head.appendChild(scriptElement)
}

onMounted(() => {
  updateJsonLd()
})

watch(() => props.items, () => {
  updateJsonLd()
}, { deep: true })

onUnmounted(() => {
  if (scriptElement && scriptElement.parentNode) {
    scriptElement.parentNode.removeChild(scriptElement)
  }
})
</script>

<template>
  <nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol class="breadcrumb-list">
      <li 
        v-for="(item, index) in items" 
        :key="index"
        class="breadcrumb-item"
        :class="{ 'active': index === items.length - 1 }"
      >
        <a 
          v-if="item.path && index < items.length - 1"
          @click.prevent="navigateTo(item.path)"
          :href="item.path"
          class="breadcrumb-link"
        >
          {{ item.label }}
        </a>
        <span v-else class="breadcrumb-current">
          {{ item.label }}
        </span>
        
        <svg 
          v-if="index < items.length - 1"
          class="breadcrumb-separator"
          width="16" 
          height="16" 
          viewBox="0 0 24 24" 
          fill="none"
        >
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </li>
    </ol>
  </nav>
</template>

<style scoped>
.breadcrumbs {
  margin-bottom: 1.5rem;
  padding: 0;
}

.breadcrumb-list {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  list-style: none;
  padding: 0;
  margin: 0;
  gap: 0.5rem;
  background: var(--card-bg, white);
  padding: 0.875rem 1.25rem;
  border-radius: 12px;
  box-shadow: var(--card-shadow, 0 1px 3px rgba(0, 0, 0, 0.1));
  border: 1px solid var(--border-color, #e5e7eb);
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.breadcrumb-link {
  color: var(--text-muted, #6b7280);
  text-decoration: none;
  transition: all 0.2s;
  cursor: pointer;
  font-weight: 500;
  position: relative;
}

.breadcrumb-link:hover {
  color: var(--primary-color, #667eea);
}

.breadcrumb-link::after {
  content: '';
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 2px;
  background: var(--primary-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));
  transition: width 0.3s ease;
}

.breadcrumb-link:hover::after {
  width: 100%;
}

.breadcrumb-current {
  color: var(--text-main, #374151);
  font-weight: 600;
}

.breadcrumb-separator {
  color: var(--text-light, #d1d5db);
  flex-shrink: 0;
}

.breadcrumb-item.active .breadcrumb-current {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

@media (max-width: 768px) {
  .breadcrumbs {
    margin-bottom: 1rem;
  }
  
  .breadcrumb-list {
    padding: 0.75rem 1rem;
    border-radius: 10px;
  }
  
  .breadcrumb-item {
    font-size: 0.8125rem;
  }
  
  .breadcrumb-separator {
    width: 14px;
    height: 14px;
  }
}
</style>
