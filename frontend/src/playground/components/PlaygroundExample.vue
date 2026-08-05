<template>
    <article class="playground-example">
        <header class="playground-example__header">
            <h3 class="playground-example__title">
                {{ title }}
            </h3>

            <p v-if="description" class="playground-example__description">
                {{ description }}
            </p>
        </header>

        <div class="playground-example__preview">
            <slot />
        </div>

        <PlaygroundCode v-if="code" :code="code" />
    </article>
</template>

<script setup>
import PlaygroundCode from './PlaygroundCode.vue'

defineProps({
    title: {
        type: String,
        required: true,
    },

    description: {
        type: String,
        default: '',
    },

    code: {
        type: String,
        default: '',
    },
})
</script>

<style scoped>
.playground-example {
    display: grid;
    gap: var(--space-4);
}

.playground-example+.playground-example {
    padding-top: var(--space-6);

    border-top: var(--border-width) solid var(--color-border);
}

.playground-example__header {
    display: grid;
    gap: var(--space-2);
}

.playground-example__title {
    margin: 0;

    color: var(--color-text);

    font-size: var(--font-size-lg);
    line-height: var(--line-height-tight);
}

.playground-example__description {
    margin: 0;

    color: var(--color-text-muted);

    font-size: var(--font-size-sm);
    line-height: var(--line-height-normal);
}

.playground-example__preview {
    display: grid;
    gap: var(--space-5);
}
</style>