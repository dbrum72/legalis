<template>
    <details class="playground-code">
        <summary class="playground-code__summary">
            Código
        </summary>

        <pre class="playground-code__pre"><code>{{ code }}</code></pre>
    </details>
</template>

<script setup>
defineProps({
    code: {
        type: String,
        required: true,
    },
})
</script>

<style scoped>
.playground-code {
    margin-top: var(--space-4);
}

.playground-code__summary {
    width: fit-content;

    color: var(--color-accent);
    cursor: pointer;

    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
}

.playground-code__pre {
    margin: var(--space-3) 0 0;
    padding: var(--space-4);

    overflow-x: auto;

    background-color: var(--color-surface-muted);

    border: var(--border-width) solid var(--color-border);
    border-radius: var(--radius-md);

    color: var(--color-text);

    font-family: var(--font-family-mono, monospace);
    font-size: var(--font-size-sm);
    line-height: var(--line-height-relaxed);
    white-space: pre;
}
</style>