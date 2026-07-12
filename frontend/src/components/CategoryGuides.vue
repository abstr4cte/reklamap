<script setup lang="ts">
import { computed } from 'vue'
import { getCategoryGuides } from '../data/categoryGuides'

// Sekcja „Przewodniki” na stronach kategorii — linki do powiązanych artykułów blogowych.
// Zamyka silos kategoria→blog (link-equity z rankujących kategorii do rankującego bloga).
// router-link renderuje <a href> → crawlowalne przez Googlebota w prerenderze.
const props = defineProps<{
  typeSlug?: string
}>()

const guides = computed(() => getCategoryGuides(props.typeSlug))
</script>

<template>
  <section v-if="guides.length > 0" class="category-guides" aria-label="Przewodniki i poradniki">
    <h2 class="guides-title">Przewodniki i poradniki</h2>
    <ul class="guides-list">
      <li v-for="g in guides" :key="`${g.category}-${g.slug}`">
        <router-link :to="`/blog/${g.category}/${g.slug}`" class="guide-pill">
          {{ g.label }}
        </router-link>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.category-guides {
  background: var(--card-bg, linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%));
  border-radius: 16px;
  padding: 2rem;
  margin-top: 1.5rem;
  border: 1px solid var(--border-color, rgba(102, 126, 234, 0.1));
  box-shadow: var(--card-shadow, 0 4px 12px rgba(0, 0, 0, 0.05));
}

.guides-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-main, #1f2937);
  margin: 0 0 1rem 0;
}

.guides-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.guide-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background: var(--bg-secondary, white);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 999px;
  text-decoration: none;
  color: var(--text-main, #374151);
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.guide-pill:hover {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: transparent;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
}

@media (max-width: 768px) {
  .category-guides {
    padding: 1.5rem;
  }

  .guides-title {
    font-size: 1.05rem;
  }

  .guide-pill {
    font-size: 0.85rem;
    padding: 0.4rem 0.85rem;
  }
}
</style>
