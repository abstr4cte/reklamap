<template>
  <div class="skeleton-card">
    <div class="skeleton skeleton-image"></div>
    <div class="skeleton-content">
      <div class="skeleton skeleton-title"></div>
      <div class="skeleton skeleton-text"></div>
      <div class="skeleton skeleton-text short"></div>
      <div class="skeleton-footer">
        <div class="skeleton skeleton-price"></div>
        <div class="skeleton skeleton-button"></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.skeleton-card {
  background: var(--card-bg);
  border-radius: 12px;
  overflow: hidden;
  box-shadow: var(--card-shadow);
  border: 1px solid var(--border-color);
  display: flex;
  flex-direction: column;
}

.skeleton-image {
  width: 100%;
  height: 200px;
}

.skeleton-content {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.skeleton-title {
  height: 1.5rem;
  width: 80%;
}

.skeleton-text {
  height: 1rem;
  width: 100%;
}

.skeleton-text.short {
  width: 60%;
}

.skeleton-footer {
  margin-top: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.skeleton-price {
  height: 1.25rem;
  width: 30%;
}

.skeleton-button {
  height: 2.5rem;
  width: 40%;
  border-radius: 8px;
}
</style>
