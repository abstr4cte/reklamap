<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()
</script>

<template>
  <div class="not-found-page">
    <div class="container">
      <div class="content">
        <div class="error-code">404</div>
        <h1>Ups! Strona nie została znaleziona</h1>
        <p>Wygląda na to, że strona, której szukasz, nie istnieje lub została przeniesiona.</p>
        
        <div class="actions">
          <button @click="router.push('/')" class="btn btn-primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Wróć na stronę główną
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.not-found-page {
  min-height: 80vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  padding: 2rem;
}

.container {
  max-width: 600px;
  width: 100%;
  text-align: center;
}

.content {
  background: white;
  padding: 4rem 2rem;
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

h1 {
  font-size: 2rem;
  color: #1f2937;
  margin-bottom: 1rem;
  font-weight: 800;
}

p {
  color: #6b7280;
  font-size: 1.1rem;
  margin-bottom: 2.5rem;
  line-height: 1.6;
}

.actions {
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
  font-size: 1.1rem;
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

@media (max-width: 640px) {
  .error-code {
    font-size: 6rem;
  }

  h1 {
    font-size: 1.5rem;
  }

  .content {
    padding: 3rem 1.5rem;
  }
}
</style>
