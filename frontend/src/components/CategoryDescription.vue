<script setup lang="ts">
import { ref } from 'vue'
import type { CategoryDescription } from '../data/categoryDescriptions'

const props = defineProps<{
  description: CategoryDescription
  count?: number
}>()

const isExpanded = ref(false)

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}
</script>

<template>
  <div class="category-description">
    <div class="description-content">
      <h1 class="description-title">{{ description.title }}</h1>
      
      <div class="description-stats" v-if="count !== undefined">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2"/>
          <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
        </svg>
        <span>Znaleziono <strong>{{ count }}</strong> {{ count === 1 ? 'ogłoszenie' : count < 5 ? 'ogłoszenia' : 'ogłoszeń' }}</span>
      </div>
      
      <div class="description-text" :class="{ expanded: isExpanded }">
        <p>{{ description.description }}</p>
        
        <div class="benefits" v-if="isExpanded && description.benefits.length > 0">
          <h3>Dlaczego warto?</h3>
          <ul>
            <li v-for="(benefit, index) in description.benefits" :key="index">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              {{ benefit }}
            </li>
          </ul>
        </div>
      </div>
      
      <button 
        v-if="description.description.length > 200" 
        @click="toggleExpanded" 
        class="toggle-btn"
      >
        {{ isExpanded ? 'Zwiń' : 'Czytaj więcej' }}
        <svg 
          width="16" 
          height="16" 
          viewBox="0 0 24 24" 
          fill="none"
          :class="{ rotated: isExpanded }"
        >
          <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<style scoped>
.category-description {
  background: var(--card-bg, linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%));
  border-radius: 16px;
  padding: 2rem;
  margin-bottom: 2rem;
  border: 1px solid var(--border-color, rgba(102, 126, 234, 0.1));
  box-shadow: var(--card-shadow, 0 4px 12px rgba(0, 0, 0, 0.05));
}

.description-content {
  max-width: 100%;
}

.description-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text-main, #1f2937);
  margin: 0 0 1rem 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.2;
}

.description-stats {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
  padding: 0.75rem 1rem;
  background: var(--bg-secondary, white);
  border-radius: 8px;
  border: 1px solid var(--border-color, #e5e7eb);
  width: fit-content;
}

.description-stats svg {
  color: #667eea;
  flex-shrink: 0;
}

.description-stats strong {
  color: #667eea;
  font-weight: 700;
}

.description-text {
  position: relative;
  overflow: hidden;
  max-height: 4.5em;
  transition: max-height 0.3s ease;
}

.description-text.expanded {
  max-height: 1000px;
}

.description-text p {
  font-size: 1.05rem;
  line-height: 1.7;
  color: var(--text-muted, #4b5563);
  margin: 0 0 1.5rem 0;
}

.benefits {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 2px solid var(--border-color, rgba(102, 126, 234, 0.1));
}

.benefits h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
  margin: 0 0 1rem 0;
}

.benefits ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 0.75rem;
}

.benefits li {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  font-size: 0.95rem;
  color: var(--text-muted, #4b5563);
  line-height: 1.6;
}

.benefits li svg {
  color: #10b981;
  flex-shrink: 0;
  margin-top: 0.1rem;
}

.toggle-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 1rem;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.toggle-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.toggle-btn svg {
  transition: transform 0.3s ease;
}

.toggle-btn svg.rotated {
  transform: rotate(180deg);
}

@media (max-width: 768px) {
  .category-description {
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  
  .description-title {
    font-size: 1.5rem;
  }
  
  .description-text p {
    font-size: 0.95rem;
  }
  
  .benefits ul {
    gap: 0.5rem;
  }
  
  .benefits li {
    font-size: 0.9rem;
  }
}
</style>
