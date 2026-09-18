<script setup lang="ts">
import { computed } from 'vue'

// Lekki, wielokrotnego użytku wariant CTA podażowego (pełna wersja: OwnerCallout.vue,
// tylko strona główna). Dodany po audycie SEO 2026-09-18 (reklamap-os/status/SEO_TECH_AUDIT.md):
// strony z realnym ruchem organicznym (ogłoszenie, kategoria miasta z wynikami) nie miały
// ŻADNEGO elementu zachęcającego oglądającego do wystawienia własnego nośnika.
const props = withDefaults(
  defineProps<{
    city?: string | null
    lead?: string | null
  }>(),
  { city: null, lead: null }
)

const defaultLead = computed(() =>
  props.city
    ? `Masz podobny nośnik w ${props.city}? Wystawienie jest bezpłatne, bez prowizji od wynajmu.`
    : 'Masz podobny nośnik reklamowy? Wystawienie jest bezpłatne, bez prowizji od wynajmu.'
)

const buttonLabel = computed(() =>
  props.city ? `Wystaw swoją powierzchnię w ${props.city}` : 'Wystaw swoją powierzchnię'
)

const targetRoute = computed(() => ({
  path: '/dodaj-powierzchnie-reklamowa',
  query: props.city ? { city: props.city } : {},
}))
</script>

<template>
  <div class="supply-cta-card">
    <p class="supply-cta-card__lead">{{ lead || defaultLead }}</p>
    <router-link class="supply-cta-card__button" :to="targetRoute">
      {{ buttonLabel }}
    </router-link>
  </div>
</template>

<style scoped>
.supply-cta-card {
  margin-top: 2rem;
  padding: 1.5rem 1.75rem;
  background: #f4f5fc;
  border: 1px solid rgba(102, 126, 234, 0.2);
  border-radius: 16px;
  text-align: center;
}

.supply-cta-card__lead {
  margin: 0 0 1rem 0;
  color: var(--text-muted, #6b7280);
  font-size: 0.95rem;
  line-height: 1.5;
  text-wrap: pretty;
}

.supply-cta-card__button {
  display: inline-flex;
  align-items: center;
  padding: 0.75rem 1.75rem;
  border-radius: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  font-weight: 700;
  font-size: 0.95rem;
  text-decoration: none;
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.supply-cta-card__button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 22px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
  .supply-cta-card {
    padding: 1.25rem 1.25rem;
    margin-top: 1.5rem;
  }

  .supply-cta-card__button {
    width: 100%;
    justify-content: center;
  }
}
</style>
