<template>
    <div class="recurrences">
        <p v-if="alerts?.due_soon" role="status" class="recurrences__notice">
            {{ alerts.due_soon }} conta(s) com saldo vencem até {{ date(alerts.through) }}
            <span v-if="alerts.due_today"> — {{ alerts.due_today }} hoje.</span>
            <AppButton variant="filter" size="sm" @click="$emit('due-soon')"
                >Consultar vencimentos</AppButton
            >
        </p>
        <details>
            <summary>
                Contas recorrentes <span v-if="items.length">({{ items.length }})</span>
            </summary>
            <div class="recurrences__body">
                <p>
                    Programe compromissos mensais, trimestrais, semestrais ou anuais. As contas são
                    geradas com até 30 dias de antecedência; pagamentos continuam sendo registrados
                    em cada conta.
                </p>
                <AppButton v-if="canManage" variant="modal" @click="openCreate"
                    >Nova recorrência</AppButton
                >
                <p v-if="success" role="status">{{ success }}</p>
                <p v-if="error" role="alert">{{ error }}</p>
                <p v-if="loading" role="status">Carregando recorrências...</p>
                <AppButton v-else-if="loadError" variant="filter" @click="load"
                    >Tentar novamente</AppButton
                >
                <template v-else>
                    <AppSelect
                        id="recurrence-filter"
                        v-model="filter"
                        label="Situação da recorrência"
                        :options="statuses"
                    />
                    <p v-if="!visible.length">Nenhuma recorrência nesta situação.</p>
                    <article v-for="item in visible" :key="item.id" class="recurrences__item">
                        <div>
                            <strong
                                >{{ item.template.supplier }} ·
                                {{ money(item.template.amount_cents) }}</strong
                            >
                            <p>
                                {{ item.template.description }} ·
                                {{ frequency(item.interval_months) }}
                            </p>
                            <small
                                >{{
                                    !item.next_due_on
                                        ? 'Concluída'
                                        : item.active
                                          ? 'Ativa'
                                          : 'Pausada'
                                }}
                                ·
                                {{
                                    item.next_due_on
                                        ? `Próximo vencimento a gerar: ${date(item.next_due_on)}`
                                        : 'Todas as ocorrências foram geradas.'
                                }}
                                <span v-if="item.ends_on">
                                    · Término: {{ date(item.ends_on) }}</span
                                >
                            </small>
                            <p v-if="item.last_error" role="alert">{{ item.last_error }}</p>
                        </div>
                        <div class="recurrences__actions">
                            <AppButton
                                v-if="item.next_due_on"
                                variant="modal"
                                size="sm"
                                :disabled="busy"
                                @click="openPreview(item)"
                                >Ver próximas</AppButton
                            >
                            <template v-if="canManage && item.next_due_on">
                                <AppButton
                                    variant="modal"
                                    size="sm"
                                    :disabled="busy"
                                    @click="openEdit(item)"
                                    >Editar futuras</AppButton
                                >
                                <AppButton
                                    variant="action"
                                    size="sm"
                                    :disabled="busy"
                                    @click="toggle(item)"
                                    >{{ item.active ? 'Pausar' : 'Retomar' }}</AppButton
                                >
                            </template>
                        </div>
                    </article>
                </template>
                <small
                    >Pausar impede novas gerações e mantém as contas existentes. Retomar inclui os
                    vencimentos pendentes durante a pausa. Para mudar fornecedor, classificações ou
                    calendário, pause a série e crie outra.</small
                >
            </div>
        </details>

        <AppDialog
            :open="creating"
            title="Nova conta recorrente"
            size="lg"
            :busy="busy"
            :error="dialogError"
            @close="creating = false"
        >
            <form id="recurrence-create" class="recurrences__form" @submit.prevent="save">
                <AppInput
                    id="recurrence-supplier"
                    v-model="form.supplier"
                    label="Fornecedor"
                    required
                    :maxlength="180"
                    :disabled="busy"
                />
                <AppInput
                    id="recurrence-description"
                    v-model="form.description"
                    label="Descrição"
                    required
                    :maxlength="500"
                    :disabled="busy"
                />
                <AppCurrency
                    id="recurrence-amount"
                    v-model="form.amount"
                    label="Valor de cada conta"
                    required
                    :disabled="busy"
                />
                <AppDate
                    id="recurrence-start"
                    v-model="form.due_on"
                    label="Primeiro vencimento"
                    required
                    :min="today"
                    :disabled="busy"
                />
                <AppSelect
                    id="recurrence-frequency"
                    v-model="form.interval_months"
                    label="Repetir"
                    :options="frequencies"
                    :disabled="busy"
                />
                <AppDate
                    id="recurrence-end"
                    v-model="form.ends_on"
                    label="Repetir até (opcional)"
                    :min="form.due_on"
                    :disabled="busy"
                />
                <ClassificationFields
                    prefix="recurrence"
                    v-model:category-id="form.category_id"
                    v-model:cost-center-id="form.cost_center_id"
                />
                <p class="recurrences__wide">
                    Sem término, a série permanece ativa até ser pausada. Em meses mais curtos, o
                    vencimento cai no último dia, recuperando o dia original nos meses seguintes.
                </p>
                <p class="recurrences__wide">
                    Prévia dos primeiros vencimentos:
                    {{ initialDates.map(date).join(' · ') || 'Informe as datas para consultar.' }}
                </p>
                <p class="recurrences__wide">
                    Ao salvar, serão criadas as contas com vencimento até
                    {{ date(defaultThrough) }}. As demais serão geradas pela rotina diária.
                </p>
            </form>
            <template #footer>
                <AppButton variant="ghost" :disabled="busy" @click="creating = false"
                    >Cancelar</AppButton
                >
                <AppButton variant="action" type="submit" form="recurrence-create" :loading="busy"
                    >Criar recorrência</AppButton
                >
            </template>
        </AppDialog>

        <AppDialog
            :open="Boolean(editing)"
            title="Editar contas futuras"
            :busy="busy"
            :error="dialogError"
            @close="editing = null"
        >
            <form id="recurrence-edit" class="recurrences__body" @submit.prevent="saveEdit">
                <p>
                    Esta alteração vale apenas para contas ainda não geradas. Contas já existentes,
                    inclusive pagas, mantêm seus valores e descrição.
                </p>
                <AppInput
                    id="recurrence-edit-description"
                    v-model="editForm.description"
                    label="Descrição"
                    required
                    :maxlength="500"
                    :disabled="busy"
                />
                <AppCurrency
                    id="recurrence-edit-amount"
                    v-model="editForm.amount"
                    label="Novo valor de cada conta"
                    required
                    :disabled="busy"
                />
            </form>
            <template #footer
                ><AppButton variant="action" type="submit" form="recurrence-edit" :loading="busy"
                    >Salvar futuras</AppButton
                ></template
            >
        </AppDialog>

        <AppDialog
            :open="Boolean(previewing)"
            title="Próximas contas da recorrência"
            :busy="busy"
            :error="dialogError"
            @close="previewing = null"
        >
            <div class="recurrences__body">
                <p>
                    {{ previewing?.template.supplier }} ·
                    {{ money(previewing?.template.amount_cents) }} por conta
                </p>
                <AppDate
                    id="recurrence-through"
                    v-model="through"
                    label="Gerar até"
                    :min="today"
                    :max="maxThrough"
                    :disabled="busy"
                />
                <AppButton variant="filter" :disabled="busy" @click="preview"
                    >Atualizar prévia</AppButton
                >
                <template v-if="previewThrough === through">
                    <p v-if="!dates.length">Nenhuma ocorrência pendente até a data escolhida.</p>
                    <ul v-else>
                        <li v-for="due in dates" :key="due">{{ date(due) }}</li>
                    </ul>
                    <p v-if="!previewing?.active">
                        A recorrência está pausada. Retome-a antes de gerar.
                    </p>
                </template>
                <p v-else>Atualize a prévia após alterar a data.</p>
            </div>
            <template #footer>
                <AppButton
                    v-if="canManage"
                    variant="action"
                    :loading="busy"
                    :disabled="!previewing?.active || !dates.length || previewThrough !== through"
                    @click="generate"
                    >Gerar {{ dates.length }} conta(s)</AppButton
                >
            </template>
        </AppDialog>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { AppInput, AppCurrency, AppDate, AppSelect } from '@/components/forms'
import { AppButton, AppDialog } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import ClassificationFields from './ClassificationFields.vue'
import {
    listRecurrences,
    createRecurrence,
    updateRecurrence,
    previewRecurrence,
    generateRecurrence,
    payableAlerts,
} from '@/api/payable-recurrences.js'

const props = defineProps({ refreshToken: [Object, Number] })
const emit = defineEmits(['changed', 'due-soon'])
const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('finance.manage'))
const localDate = (days = 0) => {
    const d = new Date()
    d.setDate(d.getDate() + days)
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}
const today = localDate(),
    defaultThrough = localDate(30),
    maxThrough = localDate(90)
const date = (value) =>
    String(value || '')
        .slice(0, 10)
        .split('-')
        .reverse()
        .join('/')
const money = (value) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(
        Number(value || 0) / 100,
    )
const frequencies = [
    { value: 1, label: 'Mensal' },
    { value: 3, label: 'Trimestral' },
    { value: 6, label: 'Semestral' },
    { value: 12, label: 'Anual' },
]
const frequency = (value) => frequencies.find((item) => item.value === Number(value))?.label
const statuses = [
    { value: 'active', label: 'Ativas' },
    { value: 'paused', label: 'Pausadas' },
    { value: 'all', label: 'Todas (inclui concluídas)' },
]
const filter = ref('active'),
    items = ref([]),
    alerts = ref(null),
    loading = ref(false),
    busy = ref(false),
    error = ref(''),
    success = ref(''),
    loadError = ref(false),
    dialogError = ref('')
const creating = ref(false),
    editing = ref(null),
    previewing = ref(null),
    through = ref(defaultThrough),
    previewThrough = ref(''),
    dates = ref([])
const emptyForm = () => ({
    supplier: '',
    description: '',
    amount: 0,
    due_on: today,
    interval_months: 1,
    ends_on: '',
    category_id: '',
    cost_center_id: '',
})
const form = reactive(emptyForm()),
    editForm = reactive({ description: '', amount: 0 })
const visible = computed(() =>
    items.value.filter(
        (item) =>
            filter.value === 'all' ||
            (item.next_due_on && item.active === (filter.value === 'active')),
    ),
)
const initialDates = computed(() => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(form.due_on)) return []
    const [y, m, day] = form.due_on.split('-').map(Number),
        result = []
    for (let i = 0; i < 4; i++) {
        const d = new Date(y, m - 1 + i * Number(form.interval_months), 1)
        d.setDate(Math.min(day, new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate()))
        const value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
        if (form.ends_on && value > form.ends_on) break
        result.push(value)
    }
    return result
})
let context = 0,
    request = 0
async function load() {
    const id = ++request
    loading.value = true
    loadError.value = false
    error.value = ''
    try {
        const [rules, due] = await Promise.all([listRecurrences(), payableAlerts()])
        if (id !== request) return
        if (!Array.isArray(rules.data)) throw new Error('Invalid recurrence response')
        items.value = rules.data
        alerts.value = due.data
    } catch {
        if (id === request) {
            alerts.value = null
            error.value = 'Não foi possível carregar recorrências e alertas.'
            loadError.value = true
        }
    } finally {
        if (id === request) loading.value = false
    }
}
const message = (e) =>
    Object.values(e.response?.data?.errors || {}).flat()[0] ||
    e.response?.data?.message ||
    'Não foi possível concluir a operação.'
async function run(action, onSuccess, manage = true) {
    if (busy.value || (manage && !canManage.value)) return
    const current = context
    busy.value = true
    dialogError.value = ''
    error.value = ''
    success.value = ''
    try {
        const result = await action()
        if (current === context) await onSuccess(result)
    } catch (e) {
        if (current === context) {
            dialogError.value = message(e)
            error.value = message(e)
        }
    } finally {
        if (current === context) busy.value = false
    }
}
function openCreate() {
    Object.assign(form, emptyForm())
    dialogError.value = ''
    creating.value = true
}
function changed(text) {
    success.value = text
    emit('changed')
    return load()
}
function save() {
    return run(
        () =>
            createRecurrence({
                ...form,
                amount_cents: Math.round(Number(form.amount) * 100),
                ends_on: form.ends_on || null,
                category_id: form.category_id || null,
                cost_center_id: form.cost_center_id || null,
            }),
        async ({ data }) => {
            creating.value = false
            await changed(`Recorrência criada. ${data.created} conta(s) gerada(s).`)
        },
    )
}
function toggle(item) {
    return run(
        () => updateRecurrence(item.id, { active: !item.active }),
        () =>
            changed(
                item.active
                    ? 'Recorrência pausada.'
                    : 'Recorrência retomada. Vencimentos pendentes serão incluídos na próxima geração.',
            ),
    )
}
function openEdit(item) {
    editing.value = item
    dialogError.value = ''
    Object.assign(editForm, {
        description: item.template.description,
        amount: item.template.amount_cents / 100,
    })
}
function saveEdit() {
    return run(
        () =>
            updateRecurrence(editing.value.id, {
                description: editForm.description,
                amount_cents: Math.round(Number(editForm.amount) * 100),
            }),
        async () => {
            editing.value = null
            await changed('Dados atualizados para contas ainda não geradas.')
        },
    )
}
function openPreview(item) {
    previewing.value = item
    through.value = defaultThrough
    dates.value = []
    previewThrough.value = ''
    return preview()
}
function preview() {
    const requestedThrough = through.value
    return run(
        () => previewRecurrence(previewing.value.id, requestedThrough),
        ({ data }) => {
            dates.value = data.dates
            previewThrough.value = requestedThrough
        },
        false,
    )
}
function generate() {
    return run(
        () => generateRecurrence(previewing.value.id, through.value),
        async ({ data }) => {
            previewing.value = null
            await changed(`${data.created} conta(s) gerada(s).`)
        },
    )
}
watch(
    () => auth.currentTenant,
    () => {
        context++
        request++
        items.value = []
        alerts.value = null
        busy.value = false
        creating.value = false
        editing.value = null
        previewing.value = null
        success.value = ''
        filter.value = 'active'
        void load()
    },
    { immediate: true },
)
watch(
    () => props.refreshToken,
    () => {
        void load()
    },
)
onBeforeUnmount(() => {
    context++
    request++
})
</script>

<style scoped>
.recurrences {
    display: grid;
    gap: var(--space-3);
}
.recurrences__notice {
    background: var(--color-surface-secondary-soft);
    padding: var(--space-3);
    border-radius: var(--radius-md);
}
summary {
    cursor: pointer;
    color: var(--color-brand);
    font-weight: 600;
    padding-block: var(--space-3);
}
.recurrences__body {
    display: grid;
    gap: var(--space-3);
    padding-block: var(--space-3);
}
.recurrences__body > button {
    justify-self: start;
}
.recurrences__item {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    align-items: center;
    border-bottom: 1px solid var(--color-border);
    padding-block: var(--space-3);
}
.recurrences__item > div:first-child {
    flex: 1 1 18rem;
    overflow-wrap: anywhere;
}
.recurrences__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-2);
}
.recurrences__form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 15rem), 1fr));
    gap: var(--space-4);
}
.recurrences__wide {
    grid-column: 1 / -1;
}
p,
small {
    line-height: 1.6;
    color: var(--color-text-muted);
    margin: 0;
}
[role='alert'] {
    color: var(--color-danger);
}
</style>
