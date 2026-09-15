<template>
    <PageContainer>
        <div class="settings-page">
            <header>
                <h1>Configurações</h1>
                <p>Cadastros e acessos de {{ auth.organization?.name || 'seu escritório' }}.</p>
            </header>
            <section v-for="group in groups" :key="group" :aria-label="group">
                <h2>{{ group }}</h2>
                <div class="settings-page__cards">
                    <RouterLink
                        v-for="entry in visible.filter((item) => item.group === group)"
                        :key="entry.name"
                        :to="{ name: entry.name }"
                        class="settings-page__card"
                    >
                        <strong>{{ entry.title }}</strong
                        ><span>{{ entry.description }}</span
                        ><span class="settings-page__open">Acessar →</span>
                    </RouterLink>
                </div>
            </section>
            <p v-if="!visible.length" role="status">
                Você não possui acesso às configurações deste escritório.
            </p>
        </div>
    </PageContainer>
</template>
<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import { useAuthStore } from '@/stores/auth.js'
import { settingsEntries, permits } from '@/config/settings.js'
const auth = useAuthStore()
const visible = computed(() => settingsEntries.filter((entry) => permits(auth, entry)))
const groups = computed(() => [...new Set(visible.value.map((entry) => entry.group))])
</script>
<style scoped>
.settings-page {
    display: grid;
    gap: var(--space-6);
}
h1,
h2,
strong {
    color: var(--color-brand);
}
p,
.settings-page__card span {
    color: var(--color-text-muted);
    line-height: 1.6;
}
.settings-page__cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 18rem), 1fr));
    gap: var(--space-4);
}
.settings-page__card {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    padding: var(--space-5);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    text-decoration: none;
    background: var(--color-surface);
}
.settings-page__card:hover {
    background: var(--color-surface-secondary-soft);
}
.settings-page__card:focus-visible {
    outline: 2px solid var(--color-brand);
    outline-offset: 3px;
}
.settings-page__card .settings-page__open {
    color: var(--color-brand);
    font-weight: 600;
    margin-top: auto;
}
</style>
