<template>
    <AppSelect
        :id="`${prefix}-category`"
        :model-value="categoryId"
        label="Categoria"
        :options="[
            { value: '', label: includeInactive ? 'Todas as categorias' : 'Sem categoria' },
            ...options('category', categoryId, includeInactive),
        ]"
        :disabled="loading || Boolean(error)"
        @update:model-value="$emit('update:categoryId', $event)"
    />
    <AppSelect
        :id="`${prefix}-cost-center`"
        :model-value="costCenterId"
        label="Centro de custo"
        :options="[
            {
                value: '',
                label: includeInactive ? 'Todos os centros de custo' : 'Sem centro de custo',
            },
            ...options('cost_center', costCenterId, includeInactive),
        ]"
        :disabled="loading || Boolean(error)"
        @update:model-value="$emit('update:costCenterId', $event)"
    />
    <div v-if="error" role="alert">
        <p>{{ error }}</p>
        <AppButton variant="filter" @click="reload">Tentar novamente</AppButton>
    </div>
    <small v-else-if="!loading && !items.length"
        >Cadastre categorias e centros de custo em Configurações. A classificação é opcional.</small
    >
    <div v-if="canManage" class="classification-links">
        <a :href="`${base}settings/financial-categories`" target="_blank" rel="opener"
            >Gerenciar categorias (nova aba)</a
        >
        <a :href="`${base}settings/cost-centers`" target="_blank" rel="opener"
            >Gerenciar centros de custo (nova aba)</a
        >
    </div>
</template>
<script setup>
import { AppSelect } from '@/components/forms'
import { AppButton } from '@/components/ui'
import { useFinancialClassifications } from '@/composables/useFinancialClassifications.js'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('finance.manage'))
// Internal same-origin tabs inherit the sessionStorage session, keeping this form intact.
const base = import.meta.env.BASE_URL
defineProps({
    prefix: { type: String, required: true },
    categoryId: [String, Number],
    costCenterId: [String, Number],
    includeInactive: Boolean,
})
defineEmits(['update:categoryId', 'update:costCenterId'])
const { items, loading, error, reload, options } = useFinancialClassifications()
</script>
<style scoped>
.classification-links {
    grid-column: 1 / -1;
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    font-size: var(--font-size-sm);
}
.classification-links a {
    color: var(--color-brand);
}
</style>
