<template>
    <PageContainer>
        <div class="monitoring">
            <header class="monitoring__header">
                <div>
                    <h1 class="monitoring__title">OABs monitoradas</h1>
                    <p class="monitoring__description">Gerencie as inscrições usadas na busca de publicações do DJEN.
                    </p>
                </div>
                <div class="monitoring__header-actions">
                    <AppButton type="button" variant="ghost" @click="back">Voltar às publicações</AppButton>
                    <AppButton v-if="canManage" type="button" @click="openCreate">Nova OAB</AppButton>
                </div>
            </header>

            <div v-if="errorMessage" class="monitoring__error" role="alert">{{ errorMessage }}</div>
            <div v-if="successMessage" class="monitoring__success" role="status">{{ successMessage }}</div>

            <AppTable :columns="columns" :rows="store.registrations"
                :empty-text="store.fetching ? 'Carregando inscrições...' : 'Nenhuma inscrição OAB monitorada.'">
                <template #cell-bar_number="{ row }">{{ row.bar_number }}/{{ row.state }}</template>
                <template #cell-active="{ value }">
                    <span class="monitoring__status"
                        :class="value ? 'monitoring__status--active' : 'monitoring__status--inactive'">
                        {{ value ? 'Ativa' : 'Inativa' }}
                    </span>
                </template>
                <template #cell-last_synced_at="{ value }">{{ formatDateTime(value) }}</template>
                <template #cell-publications_count="{ value }">{{ value ?? 0 }}</template>
                <template #cell-actions="{ row }">
                    <div class="monitoring__actions">
                        <AppButton v-if="canSync && row.active" type="button" size="sm" variant="outline"
                            :loading="store.syncingId === row.id" @click="sync(row)">Sincronizar</AppButton>
                        <AppButton v-if="canManage" type="button" size="sm" variant="ghost" @click="openEdit(row)">
                            Editar</AppButton>
                    </div>
                </template>
            </AppTable>

            <AppDialog :open="dialogOpen" :title="editing ? 'Editar OAB monitorada' : 'Nova OAB monitorada'" size="sm"
                :close-on-backdrop="!saving" :close-on-escape="!saving" @close="closeDialog">
                <form class="monitoring__form" @submit.prevent="save">
                    <AppInput v-model="form.lawyer_name" id="monitor-lawyer" label="Nome do advogado" required
                        :error="errors.lawyer_name" />
                    <div class="monitoring__form-row">
                        <AppInput v-model="form.bar_number" id="monitor-number" label="Número da OAB" required
                            :error="errors.bar_number" />
                        <AppSelect v-model="form.state" id="monitor-state" label="UF" required :options="stateOptions"
                            :error="errors.state" />
                    </div>
                    <AppDate v-model="form.monitoring_started_on" id="monitor-start" label="Monitorar desde"
                        :error="errors.monitoring_started_on" />
                    <AppSwitch v-model="form.active" id="monitor-active" label="Monitoramento ativo" />

                    <div class="monitoring__form-actions">
                        <AppButton type="button" variant="ghost" :disabled="saving" @click="closeDialog">Cancelar
                        </AppButton>
                        <AppButton type="submit" :loading="saving">Salvar</AppButton>
                    </div>
                </form>
            </AppDialog>
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppDate, AppInput, AppSelect, AppSwitch } from '@/components/forms'
import { AppButton, AppDialog, AppTable } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useMonitoredBarRegistrationsStore } from '@/stores/monitored-bar-registrations.js'

const router = useRouter()
const authStore = useAuthStore()
const store = useMonitoredBarRegistrationsStore()
const dialogOpen = ref(false)
const editing = ref(null)
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const emptyForm = () => ({ lawyer_name: '', bar_number: '', state: null, active: true, monitoring_started_on: '' })
const form = reactive(emptyForm())
const errors = reactive({ lawyer_name: '', bar_number: '', state: '', monitoring_started_on: '' })

const columns = [
    { key: 'lawyer_name', label: 'Advogado' }, { key: 'bar_number', label: 'OAB' },
    { key: 'active', label: 'Situação' }, { key: 'publications_count', label: 'Publicações' },
    { key: 'last_synced_at', label: 'Última sincronização' }, { key: 'actions', label: 'Ações', align: 'end' },
]
const states = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO']
const stateOptions = states.map((state) => ({ value: state, label: state }))
const canManage = computed(() => authStore.hasPermission('publications.manage-monitoring'))
const canSync = computed(() => authStore.hasPermission('publications.sync'))

function formatDateTime(value) {
    if (!value) return 'Nunca'
    const date = new Date(value)
    return Number.isNaN(date.getTime()) ? '—' : new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(date)
}
function clearErrors() { Object.keys(errors).forEach((key) => { errors[key] = '' }) }
function openCreate() { editing.value = null; Object.assign(form, emptyForm()); clearErrors(); dialogOpen.value = true }
function openEdit(row) {
    editing.value = row
    Object.assign(form, { lawyer_name: row.lawyer_name, bar_number: row.bar_number, state: row.state, active: row.active, monitoring_started_on: row.monitoring_started_on?.slice(0, 10) ?? '' })
    clearErrors(); dialogOpen.value = true
}
function closeDialog() { if (!saving.value) { dialogOpen.value = false; editing.value = null } }
function back() { return router.push({ name: 'publications' }) }
function applyErrors(error) {
    const validation = error?.response?.data?.errors
    if (!validation) return false
    Object.keys(errors).forEach((key) => { errors[key] = validation[key]?.[0] ?? '' })
    return true
}
async function save() {
    saving.value = true; errorMessage.value = ''; clearErrors()
    const payload = { ...form, monitoring_started_on: form.monitoring_started_on || null }
    try {
        if (editing.value) await store.update(editing.value.id, payload)
        else await store.create(payload)
        successMessage.value = 'Monitoramento salvo com sucesso.'; closeDialog(); dialogOpen.value = false
    } catch (error) {
        if (!applyErrors(error)) errorMessage.value = 'Não foi possível salvar o monitoramento.'
    } finally { saving.value = false }
}
async function sync(row) {
    errorMessage.value = ''; successMessage.value = ''
    try {
        const result = await store.sync(row.id)
        successMessage.value = result.message ?? 'Sincronização enfileirada com sucesso.'
    } catch (error) { errorMessage.value = error?.response?.data?.message ?? 'Não foi possível iniciar a sincronização.' }
}

onMounted(async () => {
    try { await store.fetchRegistrations() } catch { errorMessage.value = 'Não foi possível carregar as inscrições monitoradas.' }
})
</script>

<style scoped>
.monitoring {
    display: flex;
    flex-direction: column;
    gap: var(--space-6)
}

.monitoring__header,
.monitoring__header-actions,
.monitoring__actions,
.monitoring__form-actions {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-3)
}

.monitoring__title {
    margin: 0;
    color: var(--color-text)
}

.monitoring__description {
    margin: var(--space-2) 0 0;
    color: var(--color-text-muted)
}

.monitoring__actions {
    justify-content: flex-end;
    flex-wrap: wrap
}

.monitoring__status {
    display: inline-flex;
    padding: var(--space-1) var(--space-2);
    border-radius: var(--radius-full);
    font-size: var(--font-size-sm);
    font-weight: 600
}

.monitoring__status--active {
    background: var(--color-success-soft);
    color: var(--color-success)
}

.monitoring__status--inactive {
    background: var(--color-surface-muted);
    color: var(--color-text-muted)
}

.monitoring__error,
.monitoring__success {
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-md)
}

.monitoring__error {
    border: 1px solid var(--color-danger);
    background: var(--color-danger-soft);
    color: var(--color-danger)
}

.monitoring__success {
    border: 1px solid var(--color-success);
    background: var(--color-success-soft);
    color: var(--color-success)
}

.monitoring__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4)
}

.monitoring__form-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--space-3)
}

.monitoring__form-actions {
    justify-content: flex-end;
    margin-top: var(--space-2)
}

@media(max-width:700px) {

    .monitoring__header,
    .monitoring__header-actions {
        flex-direction: column
    }

    .monitoring__form-row {
        grid-template-columns: 1fr
    }
}
</style>
