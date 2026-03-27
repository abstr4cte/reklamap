<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  currentPage: number
  totalPages: number
  totalItems: number
  itemsPerPage: number
  showInfo?: boolean
  scrollToTop?: boolean
  scrollTarget?: string
}>()

const showInfoValue = props.showInfo !== undefined ? props.showInfo : true

const emit = defineEmits<{
  'update:currentPage': [page: number]
}>()

const startItem = computed(() => {
  return (props.currentPage - 1) * props.itemsPerPage + 1
})

const endItem = computed(() => {
  return Math.min(props.currentPage * props.itemsPerPage, props.totalItems)
})

const visiblePages = computed(() => {
  const pages: (number | string)[] = []
  const maxVisible = 7
  
  if (props.totalPages <= maxVisible) {
    // Show all pages if total is less than max
    for (let i = 1; i <= props.totalPages; i++) {
      pages.push(i)
    }
  } else {
    // Always show first page
    pages.push(1)
    
    if (props.currentPage > 3) {
      pages.push('...')
    }
    
    // Show pages around current page
    const start = Math.max(2, props.currentPage - 1)
    const end = Math.min(props.totalPages - 1, props.currentPage + 1)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (props.currentPage < props.totalPages - 2) {
      pages.push('...')
    }
    
    // Always show last page
    pages.push(props.totalPages)
  }
  
  return pages
})

const goToPage = (page: number) => {
  if (page >= 1 && page <= props.totalPages && page !== props.currentPage) {
    emit('update:currentPage', page)
    
    // Scroll do góry jeśli włączone
    if (props.scrollToTop !== false) {
      if (props.scrollTarget) {
        // Scrolluj do określonego elementu
        const element = document.querySelector(props.scrollTarget)
        if (element) {
          element.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      } else {
        // Scrolluj do góry strony
        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        })
      }
    }
  }
}
</script>

<template>
  <div class="pagination-container">
    <div v-if="showInfoValue" class="pagination-info">
      Wyświetlanie {{ startItem }}-{{ endItem }} z {{ totalItems }} ogłoszeń
    </div>
    
    <nav class="pagination" aria-label="Nawigacja stron">
      <button
        @click="goToPage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="pagination-btn prev-btn"
        aria-label="Poprzednia strona"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Poprzednia
      </button>
      
      <div class="pagination-numbers">
        <button
          v-for="(page, index) in visiblePages"
          :key="index"
          @click="typeof page === 'number' ? goToPage(page) : null"
          :class="[
            'pagination-number',
            { active: page === currentPage },
            { ellipsis: page === '...' }
          ]"
          :disabled="page === '...'"
          :aria-label="typeof page === 'number' ? `Strona ${page}` : undefined"
          :aria-current="page === currentPage ? 'page' : undefined"
        >
          {{ page }}
        </button>
      </div>
      
      <button
        @click="goToPage(currentPage + 1)"
        :disabled="currentPage === totalPages"
        class="pagination-btn next-btn"
        aria-label="Następna strona"
      >
        Następna
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </nav>
  </div>
</template>

<style scoped>
.pagination-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  padding: 2rem 0;
}

.pagination-info {
  color: var(--text-muted, #6b7280);
  font-size: 0.95rem;
  font-weight: 500;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  background: var(--card-bg, white);
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  color: var(--text-main, #374151);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  border-color: #667eea;
  color: #667eea;
  background: #f0f4ff;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-numbers {
  display: flex;
  gap: 0.25rem;
}

.pagination-number {
  min-width: 40px;
  height: 40px;
  padding: 0.5rem;
  background: var(--card-bg, white);
  border: 2px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  color: var(--text-main, #374151);
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-number:hover:not(:disabled):not(.active) {
  border-color: #667eea;
  color: #667eea;
  background: #f0f4ff;
}

.pagination-number.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: transparent;
}

.pagination-number.ellipsis {
  border: none;
  background: transparent;
  cursor: default;
  color: #9ca3af;
}

.pagination-number.ellipsis:hover {
  background: transparent;
  border: none;
}

@media (max-width: 640px) {
  .pagination-container {
    padding: 1.5rem 0;
  }
  
  .pagination {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .pagination-btn {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
  }
  
  .pagination-btn span {
    display: none;
  }
  
  .pagination-number {
    min-width: 36px;
    height: 36px;
    font-size: 0.875rem;
  }
  
  .pagination-info {
    font-size: 0.875rem;
    text-align: center;
  }
}
</style>
