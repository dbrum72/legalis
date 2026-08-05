<template>
  <div class="playground-card">
    <slot />
  </div>
</template>

<style scoped>
.playground-card {
  padding: var(--space-6);

  background: var(--color-surface);

  border: 1px solid var(--color-border);

  border-radius: var(--radius-lg);

  box-shadow: var(--shadow-xs);
}
</style>