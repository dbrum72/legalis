<template>
    <PageContainer>
        <div class="reconciliation">
            <RouterLink :to="{ name: 'finance' }" class="reconciliation__back"
                >← Financeiro</RouterLink
            >
            <header>
                <h1>Conciliação e fechamento</h1>
                <p>
                    {{ auth.organization?.name || 'Escritório atual' }} · Confira recebimentos e
                    pagamentos antes de encerrar o mês.
                </p>
            </header>
            <p>
                Compare cada registro com seu extrato ou comprovante. A conferência registra
                diferenças de valor ou data; correções no pagamento são feitas no Financeiro, com
                estorno e novo registro quando necessário.
            </p>
            <form class="reconciliation__filters" @submit.prevent="applyMonth">
                <AppInput
                    id="reconciliation-month"
                    v-model="monthInput"
                    type="month"
                    label="Mês do pagamento"
                    required
                    :disabled="busy"
                />
                <AppSelect
                    id="reconciliation-status"
                    v-model="statusInput"
                    label="Situação da conferência"
                    :options="statuses"
                    :disabled="busy"
                />
                <AppButton variant="filter" type="submit" :loading="loading" :disabled="busy"
                    >Consultar mês</AppButton
                >
            </form>
            <p v-if="success" role="status">{{ success }}</p>
            <div v-if="error" role="alert">
                <p>{{ error }}</p>
                <AppButton variant="filter" :disabled="busy || loading" @click="load"
                    >Tentar novamente</AppButton
                >
            </div>
            <p v-if="loading" role="status">Carregando conferência...</p>
            <template v-else-if="report && !error">
                <section class="reconciliation__period" aria-label="Fechamento do mês">
                    <div>
                        <h2>{{ displayMonth(report.month) }} · {{ periodStatus[report.state] }}</h2>
                        <p v-if="report.state === 'review_required'" role="alert">
                            Os registros mudaram após o fechamento. Reabra o mês, confira as
                            alterações e faça um novo fechamento.
                        </p>
                        <p v-else-if="report.state === 'closed'">
                            Conferência encerrada em {{ timestamp(report.closing.closed_at) }}.
                            Alterações posteriores serão sinalizadas para revisão.
                        </p>
                        <p v-else>
                            Feche meses já encerrados depois de conferir todos os pagamentos ativos
                            e resolver as divergências.
                        </p>
                    </div>
                    <AppButton
                        v-if="canManage && report.state === 'open'"
                        variant="modal"
                        :disabled="!report.can_close || busy"
                        @click="openClosing('close')"
                        >Revisar fechamento</AppButton
                    >
                    <AppButton
                        v-if="canManage && report.state !== 'open'"
                        variant="modal"
                        :disabled="busy"
                        @click="openClosing('reopen')"
                        >Reabrir mês</AppButton
                    >
                    <small
                        >O fechamento registra a conferência interna do mês. Os pagamentos continuam
                        disponíveis para correção, com histórico e sinalização de mudanças.</small
                    >
                </section>
                <dl class="reconciliation__totals">
                    <div>
                        <dt>Recebimentos do mês</dt>
                        <dd>{{ money(report.summary.incoming_cents) }}</dd>
                    </div>
                    <div>
                        <dt>Pagamentos do mês</dt>
                        <dd>{{ money(report.summary.outgoing_cents) }}</dd>
                    </div>
                    <div>
                        <dt>Movimentação líquida</dt>
                        <dd>{{ money(report.summary.net_cents) }}</dd>
                    </div>
                </dl>
                <p v-if="report.state === 'review_required'">
                    No fechamento: recebimentos {{ money(report.closing.totals.incoming_cents) }},
                    pagamentos {{ money(report.closing.totals.outgoing_cents) }} e movimentação
                    líquida {{ money(report.closing.totals.net_cents) }}.
                </p>
                <section aria-labelledby="reconciliation-items-title">
                    <h2 id="reconciliation-items-title">Conferência dos registros</h2>
                    <p>
                        {{ report.summary.pending }} pendente(s) ·
                        {{ report.summary.divergent }} divergente(s) ·
                        {{ report.summary.reconciled }} conciliado(s) ·
                        {{ report.summary.cancelled }} cancelado(s). Totais consideram o mês inteiro
                        e excluem cancelados.
                    </p>
                    <p v-if="!report.rows.length" class="reconciliation__empty">
                        Nenhum registro encontrado para esta consulta.
                    </p>
                    <article v-for="row in report.rows" :key="row.key" class="reconciliation__row">
                        <div>
                            <strong
                                >{{ sourceLabel(row.key) }} ·
                                {{ row.party || 'Sem identificação' }}</strong
                            >
                            <p>{{ row.description }}</p>
                            <p>
                                {{ date(row.source.paid_at) }} ·
                                {{ money(row.source.amount_cents) }} · {{ labels[row.status] }}
                            </p>
                            <p v-if="row.check">
                                Extrato: {{ date(row.check.observed_on) }} ·
                                {{ money(row.check.observed_amount_cents) }} ·
                                {{ row.check.reference }}
                            </p>
                            <p v-if="row.status === 'divergent'">
                                Diferença de valor: {{ money(row.difference_cents) }}. Confira
                                também a data.
                            </p>
                            <small v-if="row.check">{{ row.check.note }}</small>
                        </div>
                        <AppButton
                            v-if="canManage && row.status !== 'cancelled'"
                            variant="modal"
                            size="sm"
                            :disabled="busy"
                            :aria-label="`Conferir ${sourceLabel(row.key)}`"
                            @click="openCheck(row)"
                            >{{ row.check ? 'Revisar conferência' : 'Conferir' }}</AppButton
                        >
                    </article>
                    <nav class="reconciliation__pagination" aria-label="Paginação dos pagamentos">
                        <AppButton
                            variant="filter"
                            :disabled="report.page <= 1 || busy"
                            @click="changePage(-1)"
                            >Anterior</AppButton
                        >
                        <span
                            >Página {{ report.page }} de
                            {{ Math.max(1, Math.ceil(report.total / 25)) }} ·
                            {{ report.total }} registro(s)</span
                        >
                        <AppButton
                            variant="filter"
                            :disabled="report.page * 25 >= report.total || busy"
                            @click="changePage(1)"
                            >Próxima</AppButton
                        >
                    </nav>
                </section>
                <details>
                    <summary>Histórico do mês ({{ report.audit_total }} eventos)</summary>
                    <p>
                        Registros de pagamentos, estornos, conferências e fechamentos. A auditoria
                        passa a registrar operações a partir da implantação desta etapa.
                    </p>
                    <p v-if="!report.audit.length">Nenhum evento registrado neste período.</p>
                    <article
                        v-for="event in report.audit"
                        :key="event.id"
                        class="reconciliation__event"
                    >
                        <strong
                            >{{ actions[event.action] || event.action }} ·
                            {{ sourceLabel(event.source_key) }}</strong
                        >
                        <p>
                            {{ timestamp(event.created_at) }} · {{ event.actor_name || 'Sistema' }}
                        </p>
                        <p v-if="event.note">{{ event.note }}</p>
                        <p v-if="event.before">Antes: {{ auditValues(event.before) }}</p>
                        <p v-if="event.after">Depois: {{ auditValues(event.after) }}</p>
                    </article>
                    <nav class="reconciliation__pagination" aria-label="Paginação do histórico">
                        <AppButton
                            variant="filter"
                            :disabled="report.audit_page <= 1 || busy"
                            @click="changePage(-1, true)"
                            >Eventos anteriores</AppButton
                        >
                        <span
                            >Página {{ report.audit_page }} de
                            {{ Math.max(1, Math.ceil(report.audit_total / 25)) }}</span
                        >
                        <AppButton
                            variant="filter"
                            :disabled="report.audit_page * 25 >= report.audit_total || busy"
                            @click="changePage(1, true)"
                            >Mais eventos</AppButton
                        >
                    </nav>
                </details>
            </template>

            <AppDialog
                :open="Boolean(selected)"
                title="Conferir pagamento"
                :busy="busy"
                :error="dialogError"
                @close="selected = null"
            >
                <form
                    id="reconciliation-check-form"
                    class="reconciliation__form"
                    @submit.prevent="saveCheck"
                >
                    <p>
                        {{ selected?.party }} · Registrado: {{ date(selected?.source.paid_at) }} ·
                        {{ money(selected?.source.amount_cents) }}
                    </p>
                    <AppCurrency
                        id="reconciliation-amount"
                        v-model="form.amount"
                        label="Valor no extrato"
                        required
                        :disabled="busy"
                    />
                    <AppDate
                        id="reconciliation-date"
                        v-model="form.observed_on"
                        label="Data no extrato"
                        required
                        :disabled="busy"
                    />
                    <AppInput
                        id="reconciliation-reference"
                        v-model="form.reference"
                        label="Referência do extrato ou comprovante"
                        required
                        :maxlength="180"
                        :disabled="busy"
                    />
                    <AppTextarea
                        id="reconciliation-note"
                        v-model="form.note"
                        label="Observação da conferência ou motivo do ajuste"
                        required
                        :maxlength="2000"
                        :disabled="busy"
                    />
                    <p>
                        Diferenças de valor ou data ficam registradas como divergência e impedem o
                        fechamento. O valor original do pagamento será preservado.
                    </p>
                    <p v-if="report?.state !== 'open'">
                        Esta revisão poderá sinalizar alteração no mês já fechado.
                    </p>
                </form>
                <template #footer
                    ><AppButton variant="ghost" :disabled="busy" @click="selected = null"
                        >Cancelar</AppButton
                    ><AppButton
                        variant="action"
                        type="submit"
                        form="reconciliation-check-form"
                        :loading="busy"
                        >Salvar conferência</AppButton
                    ></template
                >
            </AppDialog>
            <AppDialog
                :open="Boolean(closingAction)"
                :title="
                    closingAction === 'close'
                        ? 'Confirmar fechamento mensal'
                        : 'Reabrir mês para revisão'
                "
                :busy="busy"
                :error="dialogError"
                @close="closingAction = ''"
            >
                <form
                    id="reconciliation-closing-form"
                    class="reconciliation__form"
                    @submit.prevent="saveClosing"
                >
                    <p>
                        {{ displayMonth(report?.month) }} · Recebimentos
                        {{ money(report?.summary.incoming_cents) }} · Pagamentos
                        {{ money(report?.summary.outgoing_cents) }}.
                    </p>
                    <p>
                        {{
                            closingAction === 'close'
                                ? 'Confirmo a conferência dos registros deste mês. Os totais serão preservados para identificar alterações posteriores.'
                                : 'O fechamento anterior será preservado no histórico. Informe o motivo da nova conferência.'
                        }}
                    </p>
                    <AppTextarea
                        id="reconciliation-closing-note"
                        v-model="closingNote"
                        label="Motivo ou observação"
                        required
                        :maxlength="2000"
                        :disabled="busy"
                    />
                </form>
                <template #footer
                    ><AppButton variant="ghost" :disabled="busy" @click="closingAction = ''"
                        >Cancelar</AppButton
                    ><AppButton
                        variant="action"
                        type="submit"
                        form="reconciliation-closing-form"
                        :loading="busy"
                        >{{
                            closingAction === 'close'
                                ? 'Confirmar fechamento'
                                : 'Confirmar reabertura'
                        }}</AppButton
                    ></template
                >
            </AppDialog>
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppDate, AppCurrency, AppInput, AppSelect, AppTextarea } from '@/components/forms'
import { AppButton, AppDialog } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import {
    getReconciliation,
    saveReconciliation,
    closeFinancialMonth,
    reopenFinancialMonth,
} from '@/api/financial-reconciliation.js'

const auth = useAuthStore()
const canManage = computed(() => auth.hasPermission('finance.manage'))
const initial = new Date()
initial.setDate(1)
initial.setMonth(initial.getMonth() - 1)
const monthInput = ref(
    `${initial.getFullYear()}-${String(initial.getMonth() + 1).padStart(2, '0')}`,
)
const month = ref(monthInput.value),
    statusInput = ref(''),
    status = ref(''),
    page = ref(1),
    auditPage = ref(1)
const report = ref(null),
    loading = ref(false),
    busy = ref(false),
    error = ref(''),
    success = ref(''),
    dialogError = ref('')
const selected = ref(null),
    closingAction = ref(''),
    closingNote = ref('')
const form = reactive({ amount: null, observed_on: '', reference: '', note: '' })
const labels = {
    pending: 'Pendente',
    divergent: 'Divergente',
    reconciled: 'Conciliado',
    cancelled: 'Cancelado',
}
const periodStatus = { open: 'Aberto', closed: 'Fechado', review_required: 'Revisão necessária' }
const statuses = [
    { value: '', label: 'Todas' },
    ...Object.entries(labels).map(([value, label]) => ({ value, label })),
]
const actions = {
    'payment.created': 'Pagamento registrado',
    'payment.updated': 'Pagamento alterado',
    'payment.cancelled': 'Pagamento estornado',
    'reconciliation.created': 'Conferência registrada',
    'reconciliation.updated': 'Conferência ajustada',
    'period.closed': 'Período fechado',
    'period.reopened': 'Período reaberto',
}
const date = (value) =>
    String(value || '')
        .slice(0, 10)
        .split('-')
        .reverse()
        .join('/')
const timestamp = (value) => (value ? `${date(value)} ${String(value).slice(11, 19)} UTC` : '')
const displayMonth = (value) =>
    String(value || '')
        .split('-')
        .reverse()
        .join('/')
const money = (value) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(
        Number(value || 0) / 100,
    )
const sourceLabel = (key = '') =>
    key.startsWith('incoming:')
        ? `Recebimento #${key.split(':')[1]}`
        : key.startsWith('outgoing:')
          ? `Pagamento #${key.split(':')[1]}`
          : displayMonth(key)
const auditValues = (data) => {
    if (data.summary)
        return `recebimentos ${money(data.summary.incoming_cents)}, pagamentos ${money(data.summary.outgoing_cents)}`
    if (data.closed_at) return `fechado em ${timestamp(data.closed_at)}`
    const amount = data.observed_amount_cents ?? data.amount_cents
    return [
        amount !== undefined ? money(amount) : '',
        date(data.observed_on || data.paid_at),
        data.reference,
        data.cancelled_at ? 'Estornado' : '',
        data.note,
    ]
        .filter(Boolean)
        .join(' · ')
}
let request = 0,
    context = 0
async function load() {
    const id = ++request
    loading.value = true
    error.value = ''
    try {
        const { data } = await getReconciliation({
            month: month.value,
            status: status.value || undefined,
            page: page.value,
            audit_page: auditPage.value,
        })
        if (id === request) report.value = data
    } catch {
        if (id === request) error.value = 'Não foi possível consultar a conferência do mês.'
    } finally {
        if (id === request) loading.value = false
    }
}
function changePage(delta, audit = false) {
    if (busy.value || loading.value) return
    if (audit) auditPage.value += delta
    else page.value += delta
    return load()
}
function applyMonth() {
    if (busy.value) return
    month.value = monthInput.value
    status.value = statusInput.value
    page.value = 1
    auditPage.value = 1
    success.value = ''
    return load()
}
function openCheck(row) {
    selected.value = row
    dialogError.value = ''
    Object.assign(form, {
        amount: row.check ? row.check.observed_amount_cents / 100 : null,
        observed_on: row.check?.observed_on || '',
        reference: row.check?.reference || '',
        note: '',
    })
}
function openClosing(action) {
    closingAction.value = action
    closingNote.value = ''
    dialogError.value = ''
}
async function mutate(action, successMessage) {
    if (busy.value || !canManage.value) return
    const current = context
    busy.value = true
    dialogError.value = ''
    success.value = ''
    try {
        await action()
        if (current !== context) return
        selected.value = null
        closingAction.value = ''
        success.value = successMessage
        await load()
    } catch (e) {
        if (current === context)
            dialogError.value =
                Object.values(e.response?.data?.errors || {}).flat()[0] ||
                e.response?.data?.message ||
                'Não foi possível salvar. Tente novamente.'
    } finally {
        if (current === context) busy.value = false
    }
}
function saveCheck() {
    return mutate(
        () =>
            saveReconciliation(selected.value.kind, selected.value.id, {
                observed_amount_cents: Math.round(Number(form.amount) * 100),
                observed_on: form.observed_on,
                reference: form.reference,
                note: form.note,
                source_hash: selected.value.source_hash,
            }),
        'Conferência registrada no histórico.',
    )
}
function saveClosing() {
    const closing = closingAction.value === 'close'
    return mutate(
        () =>
            (closing ? closeFinancialMonth : reopenFinancialMonth)({
                month: report.value.month,
                fingerprint: report.value.fingerprint,
                note: closingNote.value,
            }),
        closing ? 'Mês fechado.' : 'Mês reaberto para revisão.',
    )
}
watch(
    () => auth.currentTenant,
    () => {
        context++
        request++
        selected.value = null
        closingAction.value = ''
        report.value = null
        success.value = ''
        busy.value = false
        page.value = 1
        auditPage.value = 1
        void load()
    },
    { immediate: true },
)
onBeforeUnmount(() => {
    context++
    request++
})
</script>

<style scoped>
.reconciliation {
    display: grid;
    gap: var(--space-5);
}
.reconciliation__back,
h1,
h2,
strong,
summary {
    color: var(--color-brand);
}
.reconciliation__back {
    justify-self: start;
    font-weight: 600;
}
p,
small {
    margin: 0;
    color: var(--color-text-muted);
    line-height: 1.6;
}
.reconciliation__filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));
    gap: var(--space-3);
    align-items: end;
}
.reconciliation__period {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    align-items: center;
    padding: var(--space-4);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.reconciliation__period > div {
    flex: 1 1 20rem;
}
.reconciliation__period > small {
    flex-basis: 100%;
}
.reconciliation__totals {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));
    gap: var(--space-3);
    margin: 0;
}
.reconciliation__totals > div {
    padding: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
dd {
    margin: var(--space-2) 0 0;
    font-size: 1.5rem;
    font-weight: 600;
}
.reconciliation__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
    border-bottom: 1px solid var(--color-border);
    padding-block: var(--space-4);
}
.reconciliation__row > div {
    flex: 1 1 24rem;
    overflow-wrap: anywhere;
}
.reconciliation__pagination {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-3);
    margin-top: var(--space-4);
}
.reconciliation__form {
    display: grid;
    gap: var(--space-4);
}
.reconciliation__empty,
.reconciliation__event {
    padding-block: var(--space-4);
}
.reconciliation__event {
    border-bottom: 1px solid var(--color-border);
    overflow-wrap: anywhere;
}
summary {
    cursor: pointer;
    font-weight: 600;
    padding-block: var(--space-3);
}
[role='alert'] {
    color: var(--color-danger);
}
</style>
