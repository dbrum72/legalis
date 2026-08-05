<template>
  <section class="playground-section">

    <header class="playground-section__header">

      <h2>
        {{ title }}
      </h2>

      <p v-if="description">
        {{ description }}
      </p>

    </header>

    <PlaygroundCard>
      <slot />
    </PlaygroundCard>

  </section>
</template>

<script setup>
import PlaygroundCard from './PlaygroundCard.vue'

defineProps({
  title: String,
  description: String,
})
</script>

<style scoped>
.playground-section {

  display: grid;

  gap: var(--space-5);

  margin-bottom: var(--space-8);

}

.playground-section__header h2 {

  margin-bottom: var(--space-2);

}

.playground-section__header p {

  color: var(--color-text-muted);

}
</style>