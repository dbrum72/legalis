<template>
    <PageContainer>
        <div class="classification-page">
            <RouterLink :to="{ name: 'settings' }" class="classification-page__back"
                >← Configurações</RouterLink
            >
            <header>
                <h1>{{ title }}</h1>
                <p>
                    {{ auth.organization?.name || 'Escritório atual' }} · Cadastros compartilhados
                    entre contas a pagar e despesas.
                </p>
            </header>
            <p v-if="canManage">
                Desativar impede novos vínculos e preserva os existentes. Renomear atualiza o nome
                exibido nos registros vinculados.
            </p>
            <p v-else>
                Você pode consultar este cadastro. A manutenção exige permissão de gestão
                financeira.
            </p>
            <form v-if="canManage" class="classification-page__form" @submit.prevent="save">
                <AppInput
                    id="classification-name"
                    v-model="form.name"
                    :label="editing ? 'Novo nome' : 'Nome'"
                    :maxlength="80"
                    required
                    :disabled="busy"
                />
                <AppButton type="submit" variant="action" :loading="busy">{{
                    editing ? 'Salvar nome' : 'Adicionar'
                }}</AppButton>
                <AppButton v-if="editing" variant="filter" :disabled="busy" @click="reset"
                    >Cancelar edição</AppButton
                >
            </form>
            <p v-if="operationError" role="alert" class="classification-page__error">
                {{ operationError }}
            </p>
            <p v-if="success" role="status">{{ success }}</p>
            <div class="classification-page__filters">
                <AppSearch
                    id="classification-search"
                    v-model="search"
                    label="Buscar por nome"
                    clearable
                />
                <AppSelect
                    id="classification-status"
                    v-model="status"
                    label="Situação"
                    :options="statuses"
                />
            </div>
            <p v-if="loading" role="status">Carregando cadastros...</p>
            <div v-else-if="error" role="alert">
                <p>{{ error }}</p>
                <AppButton variant="filter" @click="reload">Tentar novamente</AppButton>
            </div>
            <template v-else>
                <p v-if="!filtered.length" role="status">
                    {{
                        hasItems
                            ? 'Nenhum resultado para os filtros aplicados.'
                            : 'Nenhum registro cadastrado.'
                    }}
                </p>
                <ul v-else class="classification-page__list" :aria-label="title">
                    <li v-for="item in filtered" :key="item.id">
                        <div>
                            <strong>{{ item.name }}</strong
                            ><small>{{ item.active ? 'Ativo' : 'Inativo' }}</small>
                        </div>
                        <div v-if="canManage" class="classification-page__actions">
                            <AppButton
                                variant="filter"
                                size="sm"
                                :disabled="busy"
                                :aria-label="`Renomear ${item.name}`"
                                @click="edit(item)"
                                >Renomear</AppButton
                            >
                            <AppButton
                                variant="filter"
                                size="sm"
                                :disabled="busy"
                                :aria-label="`${item.active ? 'Desativar' : 'Reativar'} ${item.name}`"
                                @click="toggle(item)"
                                >{{ item.active ? 'Desativar' : 'Reativar' }}</AppButton
                            >
                        </div>
                    </li>
                </ul>
            </template>
        </div>
    </PageContainer>
</template>
<script setup>
import { computed, nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppInput, AppSearch, AppSelect } from '@/components/forms'
import { AppButton } from '@/components/ui'
import {
    useFinancialClassifications,
    refreshFinancialClassifications,
} from '@/composables/useFinancialClassifications.js'
import { useAuthStore } from '@/stores/auth.js'
import { createClassification, updateClassification } from '@/api/financial-classifications.js'
const props = defineProps({
    kind: {
        type: String,
        required: true,
        validator: (value) => ['category', 'cost_center'].includes(value),
    },
})
const { items, loading, error, reload } = useFinancialClassifications()
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('finance.manage'))
const title = computed(() =>
    props.kind === 'category' ? 'Categorias financeiras' : 'Centros de custo',
)
const form = reactive({ name: '' })
const editing = ref(null),
    busy = ref(false),
    operationError = ref(''),
    success = ref(''),
    search = ref(''),
    status = ref('active')
const statuses = [
    { value: 'active', label: 'Ativos' },
    { value: 'inactive', label: 'Inativos' },
    { value: 'all', label: 'Todos' },
]
const normalize = (value) =>
    String(value)
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase('pt-BR')
const hasItems = computed(() => items.value.some((item) => item.kind === props.kind))
const filtered = computed(() =>
    items.value.filter(
        (item) =>
            item.kind === props.kind &&
            (status.value === 'all' || item.active === (status.value === 'active')) &&
            normalize(item.name).includes(normalize(search.value.trim())),
    ),
)
let context = 0
function reset() {
    editing.value = null
    form.name = ''
}
async function edit(item) {
    editing.value = item.id
    form.name = item.name
    success.value = ''
    await nextTick()
    document.getElementById('classification-name')?.focus()
}
async function run(action, confirmation) {
    if (busy.value || !canManage.value) return
    const current = context
    busy.value = true
    operationError.value = ''
    success.value = ''
    try {
        await action()
        if (current !== context) return
        reset()
        success.value = confirmation
        refreshFinancialClassifications()
    } catch (exception) {
        if (current !== context) return
        operationError.value =
            Object.values(exception.response?.data?.errors || {}).flat()[0] ||
            exception.response?.data?.message ||
            'Não foi possível salvar o cadastro.'
    } finally {
        if (current === context) busy.value = false
    }
}
function save() {
    return run(
        () =>
            editing.value
                ? updateClassification(editing.value, { name: form.name.trim() })
                : createClassification({ kind: props.kind, name: form.name.trim() }),
        'Cadastro salvo.',
    )
}
function toggle(item) {
    return run(
        () => updateClassification(item.id, { active: !item.active }),
        item.active
            ? 'Cadastro desativado. Os vínculos existentes foram preservados.'
            : 'Cadastro reativado.',
    )
}
watch(
    () => [auth.currentTenant, props.kind],
    () => {
        context++
        reset()
        operationError.value = ''
        success.value = ''
        busy.value = false
        search.value = ''
        status.value = 'active'
    },
)
onBeforeUnmount(() => {
    context++
})
</script>
<style scoped>
.classification-page {
    display: grid;
    gap: var(--space-4);
}
h1,
strong,
.classification-page__back {
    color: var(--color-brand);
}
.classification-page__back {
    justify-self: start;
    font-weight: 600;
}
p,
small {
    color: var(--color-text-muted);
    line-height: 1.6;
    margin: 0;
}
.classification-page__form,
.classification-page__filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));
    gap: var(--space-3);
    align-items: end;
    padding: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.classification-page__list {
    padding: 0;
    margin: 0;
    list-style: none;
}
li {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    align-items: center;
    padding: var(--space-4);
    border-bottom: 1px solid var(--color-border);
}
li > div:first-child {
    flex: 1 1 12rem;
    display: grid;
    gap: var(--space-1);
    overflow-wrap: anywhere;
}
.classification-page__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}
.classification-page__error {
    color: var(--color-danger);
}
</style>
