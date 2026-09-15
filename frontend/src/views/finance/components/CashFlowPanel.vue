<template>
    <section class="cash-flow" aria-labelledby="cash-flow-title" :aria-busy="loading">
        <header class="cash-flow__heading">
            <div>
                <span class="cash-flow__eyebrow">Relatórios gerenciais</span>
                <h2 id="cash-flow-title">Fluxo de caixa por período</h2>
                <p>Confira os pagamentos realizados e o saldo ainda pendente por vencimento.</p>
            </div>
            <AppButton
                variant="action"
                icon="download"
                :loading="exporting"
                :disabled="loading || !report || Boolean(error)"
                @click="download"
                >Exportar fluxo CSV</AppButton
            >
        </header>

        <form
            class="cash-flow__filters"
            aria-label="Período do fluxo de caixa"
            @submit.prevent="apply"
        >
            <AppSelect
                id="cash-flow-period"
                v-model="filters.mode"
                label="Consultar por"
                :options="periodOptions"
                :disabled="loading"
            />
            <AppInput
                v-if="filters.mode === 'month'"
                id="cash-flow-month"
                v-model="filters.month"
                type="month"
                label="Mês de referência"
                required
                :disabled="loading"
            />
            <template v-else>
                <AppDate
                    id="cash-flow-from"
                    v-model="filters.from"
                    label="De"
                    required
                    :disabled="loading"
                />
                <AppDate
                    id="cash-flow-to"
                    v-model="filters.to"
                    label="Até"
                    required
                    :disabled="loading"
                />
            </template>
            <AppButton
                type="submit"
                variant="filter"
                :loading="loading"
                aria-label="Aplicar período"
                >Aplicar período</AppButton
            >
        </form>
        <p class="cash-flow__note">
            Realizado pela data do pagamento. Pendente pelo vencimento, incluindo atrasos do
            período. A projeção usa os saldos atuais e não reconstitui posições históricas. Limite:
            366 dias.
        </p>
        <p v-if="loading" role="status">Carregando fluxo de caixa...</p>
        <div v-else-if="error" role="alert" class="cash-flow__error">
            <p>{{ error }}</p>
            <AppButton variant="filter" @click="apply">Tentar novamente</AppButton>
        </div>
        <template v-else-if="report">
            <p class="cash-flow__period">
                Período aplicado:
                <strong>{{ date(report.period.from) }} a {{ date(report.period.to) }}</strong>
            </p>
            <div class="cash-flow__totals">
                <AppCard v-for="group in groups" :key="group.key">
                    <h3>{{ group.label }}</h3>
                    <dl>
                        <div>
                            <dt>Entradas</dt>
                            <dd>{{ money(report[group.key].in_cents) }}</dd>
                        </div>
                        <div>
                            <dt>Saídas</dt>
                            <dd>{{ money(report[group.key].out_cents) }}</dd>
                        </div>
                        <div class="cash-flow__net">
                            <dt>Resultado</dt>
                            <dd :class="{ 'cash-flow__negative': report[group.key].net_cents < 0 }">
                                {{ money(report[group.key].net_cents) }}
                            </dd>
                        </div>
                    </dl>
                </AppCard>
            </div>
            <p class="cash-flow__combined">
                Realizado + pendente: <strong>{{ money(report.combined_net_cents) }}</strong
                ><small
                    >Movimentação líquida do período, sem saldo inicial bancário. Recebimentos
                    projetados não são garantidos.</small
                >
            </p>
            <div class="cash-flow__indicators">
                <article>
                    <span>Margem de caixa realizada</span
                    ><strong>{{ percent(report.indicators.cash_margin_percent) }}</strong
                    ><small>(Entradas − saídas) ÷ entradas realizadas.</small>
                </article>
                <article>
                    <span>Inadimplência do período</span
                    ><strong>{{ percent(report.indicators.overdue_percent) }}</strong
                    ><small
                        >Saldo vencido atual ÷ valor das cobranças com vencimento no período.</small
                    >
                </article>
                <article>
                    <span>Concentração no maior cliente</span
                    ><strong>{{ percent(report.indicators.largest_client_percent) }}</strong
                    ><small
                        >{{
                            report.indicators.largest_client?.name || 'Sem recebimentos no período'
                        }}
                        · participação nas entradas realizadas.</small
                    >
                </article>
            </div>
            <div
                class="cash-flow__table-wrap"
                role="region"
                aria-label="Resultados mensais"
                tabindex="0"
            >
                <table>
                    <caption>
                        Resultado por mês de referência
                    </caption>
                    <thead>
                        <tr>
                            <th scope="col">Mês</th>
                            <th scope="col">Entradas realizadas</th>
                            <th scope="col">Saídas realizadas</th>
                            <th scope="col">Resultado realizado</th>
                            <th scope="col">Resultado pendente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="month in report.monthly" :key="month.month">
                            <th scope="row">{{ month.month.split('-').reverse().join('/') }}</th>
                            <td>{{ money(month.realized.in_cents) }}</td>
                            <td>{{ money(month.realized.out_cents) }}</td>
                            <td>{{ money(month.realized.net_cents) }}</td>
                            <td>{{ money(month.projected.net_cents) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <h3>Entradas e saídas do período</h3>
            <p v-if="!report.entries.length" role="status">
                Nenhuma movimentação ou saldo pendente encontrado neste período.
            </p>
            <ol v-else class="cash-flow__entries" aria-label="Lançamentos do fluxo de caixa">
                <li v-for="entry in report.entries" :key="entry.id">
                    <div class="cash-flow__entry-date">
                        <time :datetime="entry.date">{{ date(entry.date) }}</time
                        ><span>{{ entry.stage === 'realized' ? 'Realizado' : 'Pendente' }}</span>
                    </div>
                    <div class="cash-flow__entry-description">
                        <strong>{{ entry.party }}</strong
                        ><span>{{ entry.description }}</span
                        ><small v-if="entry.reference || entry.method"
                            >{{ method(entry.method)
                            }}<template v-if="entry.reference">
                                · {{ entry.reference }}</template
                            ></small
                        >
                    </div>
                    <div class="cash-flow__entry-amount">
                        <span>{{ entry.direction === 'in' ? 'Entrada' : 'Saída' }}</span
                        ><strong :class="{ 'cash-flow__negative': entry.direction === 'out' }">{{
                            money(entry.amount_cents)
                        }}</strong>
                    </div>
                </li>
            </ol>
            <nav class="cash-flow__pagination" aria-label="Paginação do fluxo de caixa">
                <AppButton
                    variant="filter"
                    :disabled="report.pagination.current_page <= 1"
                    @click="load(applied, report.pagination.current_page - 1)"
                    >Anterior</AppButton
                >
                <span
                    >Página {{ report.pagination.current_page }} de
                    {{ report.pagination.last_page }} ·
                    {{ report.pagination.total }} lançamentos</span
                >
                <AppButton
                    variant="filter"
                    :disabled="report.pagination.current_page >= report.pagination.last_page"
                    @click="load(applied, report.pagination.current_page + 1)"
                    >Próxima</AppButton
                >
            </nav>
        </template>
    </section>
</template>

<script setup>
import { onBeforeUnmount, reactive, ref, watch } from 'vue'
import { AppDate, AppInput, AppSelect } from '@/components/forms'
import { AppButton, AppCard } from '@/components/ui'
import { getCashFlow, exportCashFlow } from '@/api/cash-flow.js'
import { useAuthStore } from '@/stores/auth.js'

const props = defineProps({ refreshToken: { type: Object, default: null } })
const auth = useAuthStore()
const today = new Date().toLocaleDateString('en-CA')
const filters = reactive({
    mode: 'month',
    month: today.slice(0, 7),
    from: `${today.slice(0, 7)}-01`,
    to: today,
})
const applied = ref({ month: filters.month })
const report = ref(null)
const error = ref('')
const loading = ref(false)
const exporting = ref(false)
let requestId = 0
const periodOptions = [
    { value: 'month', label: 'Mês' },
    { value: 'range', label: 'Período personalizado' },
]
const groups = [
    { key: 'realized', label: 'Realizado' },
    { key: 'projected', label: 'Pendente / projetado' },
]
const money = (value) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(
        Number(value || 0) / 100,
    )
const percent = (value) =>
    value == null
        ? '—'
        : `${new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 2 }).format(value)}%`
const date = (value) => String(value).slice(0, 10).split('-').reverse().join('/')
const method = (value) =>
    ({
        pix: 'Pix',
        bank_transfer: 'Transferência',
        cash: 'Dinheiro',
        credit_card: 'Cartão de crédito',
        debit_card: 'Cartão de débito',
        boleto: 'Boleto',
        other: 'Outra',
    })[value] ||
    value ||
    ''
const message = (exception) =>
    Object.values(exception.response?.data?.errors || {}).flat()[0] ||
    exception.response?.data?.message ||
    'Não foi possível consultar o fluxo de caixa.'

async function load(params, page = 1) {
    const id = ++requestId
    loading.value = true
    error.value = ''
    try {
        const { data } = await getCashFlow({ ...params, page })
        if (id !== requestId) return
        report.value = data
        applied.value = { ...params }
    } catch (exception) {
        if (id === requestId) error.value = message(exception)
    } finally {
        if (id === requestId) loading.value = false
    }
}

async function apply() {
    if (loading.value) return
    if (
        (filters.mode === 'month' && !filters.month) ||
        (filters.mode === 'range' && (!filters.from || !filters.to))
    ) {
        error.value = 'Preencha o mês ou as duas datas do período.'
        return
    }
    if (filters.mode === 'range' && filters.from > filters.to) {
        error.value = 'A data inicial deve ser anterior ou igual à data final.'
        return
    }
    await load(
        filters.mode === 'month'
            ? { month: filters.month }
            : { from: filters.from, to: filters.to },
    )
}

async function download() {
    if (loading.value || exporting.value || !report.value) return
    exporting.value = true
    const id = requestId
    try {
        const { data } = await exportCashFlow(applied.value)
        if (id !== requestId) return
        const url = URL.createObjectURL(data)
        const link = document.createElement('a')
        link.href = url
        link.download = `fluxo-de-caixa-${report.value.period.from}-${report.value.period.to}.csv`
        document.body.appendChild(link)
        link.click()
        link.remove()
        URL.revokeObjectURL(url)
    } catch (exception) {
        if (id === requestId) error.value = message(exception)
    } finally {
        exporting.value = false
    }
}

watch(
    () => [auth.currentTenant, props.refreshToken],
    () => {
        report.value = null
        void load(applied.value)
    },
    { immediate: true },
)
onBeforeUnmount(() => {
    requestId++
})
</script>

<style scoped>
.cash-flow {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
    padding: var(--space-5);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    background: var(--color-surface);
    min-width: 0;
}
.cash-flow h2,
.cash-flow h3,
.cash-flow p,
.cash-flow dl,
.cash-flow dd {
    margin: 0;
}
.cash-flow__heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: var(--space-4);
}
.cash-flow__heading > div {
    flex: 1 1 18rem;
}
.cash-flow__eyebrow {
    color: var(--color-brand-secondary);
    font-weight: 700;
    text-transform: uppercase;
    font-size: var(--font-size-xs);
}
.cash-flow h2,
.cash-flow h3 {
    color: var(--color-brand);
}
.cash-flow__heading p,
.cash-flow small,
.cash-flow__note {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
    line-height: 1.6;
}
.cash-flow__filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 11rem), 1fr));
    align-items: end;
    gap: var(--space-3);
    padding: var(--space-4);
    background: var(--color-surface-highlight-soft);
    border-radius: var(--radius-md);
}
.cash-flow__filters > * {
    min-width: 0;
}
.cash-flow__error {
    padding: var(--space-4);
    background: var(--color-danger-soft);
    color: var(--color-danger);
    border-radius: var(--radius-md);
}
.cash-flow__totals {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 17rem), 1fr));
    gap: var(--space-4);
}
.cash-flow dl {
    margin-top: var(--space-3);
}
.cash-flow dl > div {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: var(--space-2);
    padding-block: var(--space-2);
}
.cash-flow dd {
    font-weight: 700;
}
.cash-flow__net {
    border-top: 1px solid var(--color-border);
}
.cash-flow__negative {
    color: var(--color-danger);
}
.cash-flow__combined {
    padding: var(--space-4);
    background: var(--color-surface-highlight-soft);
    border-radius: var(--radius-md);
}
.cash-flow__combined small {
    display: block;
}
.cash-flow__indicators {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 14rem), 1fr));
    gap: var(--space-4);
}
.cash-flow__indicators article {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    overflow-wrap: anywhere;
}
.cash-flow__indicators strong {
    font-size: var(--font-size-xl);
    color: var(--color-brand);
}
.cash-flow__table-wrap {
    overflow-x: auto;
}
.cash-flow table {
    width: 100%;
    border-collapse: collapse;
}
.cash-flow caption {
    text-align: left;
    font-weight: 700;
    padding-bottom: var(--space-3);
}
.cash-flow th,
.cash-flow td {
    padding: var(--space-3);
    border-bottom: 1px solid var(--color-border);
    text-align: right;
    font-size: var(--font-size-sm);
}
.cash-flow th:first-child {
    text-align: left;
}
.cash-flow td {
    white-space: nowrap;
}
.cash-flow__entries {
    display: grid;
    gap: var(--space-3);
    padding: 0;
    margin: 0;
    list-style: none;
}
.cash-flow__entries li {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.cash-flow__entries li > div {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    overflow-wrap: anywhere;
    min-width: 0;
}
.cash-flow__entry-date span,
.cash-flow__entry-amount span {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.cash-flow__entry-amount {
    text-align: right;
}
.cash-flow__pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    font-size: var(--font-size-sm);
}
@media (max-width: 600px) {
    .cash-flow {
        padding: var(--space-4);
    }
    .cash-flow__entries li {
        grid-template-columns: minmax(0, 1fr) auto;
    }
    .cash-flow__entry-description {
        grid-row: 2;
        grid-column: 1 / -1;
    }
    .cash-flow__entry-amount {
        grid-column: 2;
        grid-row: 1;
    }
    .cash-flow__pagination {
        flex-wrap: wrap;
    }
    .cash-flow__pagination span {
        order: -1;
        width: 100%;
        text-align: center;
    }
}
</style>
