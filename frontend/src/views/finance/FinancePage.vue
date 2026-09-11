<template>
    <PageContainer>
        <div class="finance-page">
            <header class="finance-page__header">
                <div><span class="finance-page__eyebrow">Gestão financeira</span><h1>Financeiro</h1><p>Acompanhe cobranças, vencimentos e pagamentos do escritório.</p></div>
                <AppButton v-if="canManage && !showInvoiceForm" variant="navigation" icon="wallet"
                    @click="showInvoiceForm = true">Nova cobrança</AppButton>
            </header>

            <div v-if="error" class="finance-page__alert" role="alert">{{ error }}</div>

            <section class="finance-page__summary" aria-label="Resumo financeiro">
                <AppCard class="finance-summary-card finance-summary-card--receivable">
                    <div class="finance-summary-card__icon" aria-hidden="true">
                        <WalletCards :size="22" :stroke-width="1.8" />
                    </div>
                    <div class="finance-summary-card__content">
                        <span class="finance-summary-card__label">A receber</span>
                        <strong class="finance-summary-card__value">{{ money(financeStore.summary.receivable_cents) }}</strong>
                        <small class="finance-summary-card__detail">Saldo total em aberto</small>
                    </div>
                </AppCard>

                <AppCard class="finance-summary-card finance-summary-card--overdue">
                    <div class="finance-summary-card__icon" aria-hidden="true">
                        <TriangleAlert :size="22" :stroke-width="1.8" />
                    </div>
                    <div class="finance-summary-card__content">
                        <span class="finance-summary-card__label">Vencido</span>
                        <strong class="finance-summary-card__value">{{ money(financeStore.summary.overdue_cents) }}</strong>
                        <small class="finance-summary-card__detail">
                            {{ overdueDescription }}
                        </small>
                    </div>
                </AppCard>

                <AppCard class="finance-summary-card finance-summary-card--received">
                    <div class="finance-summary-card__icon" aria-hidden="true">
                        <CircleDollarSign :size="22" :stroke-width="1.8" />
                    </div>
                    <div class="finance-summary-card__content">
                        <span class="finance-summary-card__label">Recebido no mês</span>
                        <strong class="finance-summary-card__value">{{ money(financeStore.summary.received_this_month_cents) }}</strong>
                        <small class="finance-summary-card__detail">Pagamentos confirmados</small>
                    </div>
                </AppCard>
            </section>

            <AppCard v-if="showInvoiceForm">
                <form class="finance-page__form" @submit.prevent="submitInvoice">
                    <div class="finance-page__form-heading"><h2>Nova cobrança</h2><p>Registre um recebível para o cliente.</p></div>
                    <div class="finance-page__grid">
                        <label>Cliente *<select v-model="invoiceForm.client_id" required><option value="" disabled>Selecione</option><option v-for="client in clientsStore.clients" :key="client.id" :value="client.id">{{ client.name }}</option></select></label>
                        <label>Pasta<select v-model="invoiceForm.folder_id"><option value="">Sem pasta</option><option v-for="folder in foldersStore.folders" :key="folder.id" :value="folder.id">{{ folder.name }}</option></select></label>
                        <label class="finance-page__wide">Contrato de honorários<select v-model="invoiceForm.fee_agreement_id" :disabled="!invoiceForm.folder_id || loadingAgreements"><option value="">{{ agreementPlaceholder }}</option><option v-for="agreement in availableAgreements" :key="agreement.id" :value="agreement.id">{{ agreementOptionLabel(agreement) }}</option></select><small v-if="selectedAgreement" class="finance-page__field-help">{{ agreementTerms(selectedAgreement) }}</small></label>
                        <label>Vencimento *<input v-model="invoiceForm.due_on" type="date" required></label>
                        <AppCurrency v-model="invoiceForm.subtotal" id="invoice-subtotal" label="Valor" :min="0.01"
                            :allow-empty="false" shift-decimal required />
                        <AppCurrency v-model="invoiceForm.discount" id="invoice-discount" label="Desconto" :min="0" :allow-empty="false" shift-decimal />
                        <label>Parcelas<input v-model.number="invoiceForm.installment_count" type="number" min="1" max="120" required></label>
                        <label v-if="invoiceForm.installment_count > 1">Intervalo<select v-model.number="invoiceForm.installment_interval_months"><option :value="1">Mensal</option><option :value="2">A cada 2 meses</option><option :value="3">A cada 3 meses</option><option :value="6">A cada 6 meses</option><option :value="12">Anual</option></select></label>
                        <label class="finance-page__wide">Observações<textarea v-model.trim="invoiceForm.notes" rows="3" maxlength="10000" /></label>
                    </div>
                    <div class="finance-page__actions"><AppButton type="button" variant="ghost" :disabled="submitting" @click="showInvoiceForm = false">Cancelar</AppButton><AppButton type="submit" :loading="submitting">Salvar cobrança</AppButton></div>
                </form>
            </AppCard>

            <AppCard>
                <section class="finance-page__invoices">
                    <div class="finance-page__section-heading"><div><h2>Contas a receber</h2><p>Cobranças atuais e histórico de recebimentos.</p></div><span v-if="hasActiveInvoiceFilters" class="finance-page__filter-count">{{ financeStore.invoices.length }} resultado(s)</span></div>
                    <form class="finance-page__filters" aria-label="Filtros de contas a receber" @submit.prevent="applyInvoiceFilters">
                        <label>Cliente<select v-model="invoiceFilters.client_id"><option value="">Todos os clientes</option><option v-for="client in clientsStore.clients" :key="client.id" :value="client.id">{{ client.name }}</option></select></label>
                        <label>Transação<input v-model.trim="invoiceFilters.transaction" type="search" placeholder="Cobrança ou transação"></label>
                        <label>Vencimento<select v-model="invoiceFilters.period_type"><option value="all">Qualquer período</option><option value="month">Por mês</option><option value="range">Período personalizado</option></select></label>
                        <label v-if="invoiceFilters.period_type === 'month'">Mês<input v-model="invoiceFilters.month" type="month" required></label>
                        <template v-if="invoiceFilters.period_type === 'range'">
                            <label>De<input v-model="invoiceFilters.due_from" type="date" required></label>
                            <label>Até<input v-model="invoiceFilters.due_to" type="date" :min="invoiceFilters.due_from" required></label>
                        </template>
                        <div class="finance-page__filter-actions">
                            <AppButton v-if="hasInvoiceFilterInput" type="button" variant="ghost" :disabled="financeStore.loading" @click="clearInvoiceFilters">Limpar</AppButton>
                            <AppButton type="submit" variant="outline" :loading="financeStore.loading">Filtrar</AppButton>
                        </div>
                    </form>
                    <p v-if="financeStore.loading" class="finance-page__empty">Carregando cobranças...</p>
                    <p v-else-if="!financeStore.invoices.length" class="finance-page__empty">{{ hasActiveInvoiceFilters ? 'Nenhuma cobrança corresponde aos filtros.' : 'Nenhuma cobrança registrada.' }}</p>
                    <div v-else class="finance-page__table-wrap">
                        <table>
                            <thead><tr><th>Cobrança</th><th>Parcela</th><th>Cliente</th><th>Vencimento</th><th>Situação</th><th>Saldo</th><th><span class="sr-only">Ações</span></th></tr></thead>
                            <tbody><tr v-for="invoice in financeStore.invoices" :key="invoice.id">
                                <td><strong>{{ invoice.charge_identifier ?? invoice.number }}</strong><small>#{{ invoice.number }} · {{ invoice.folder?.name ?? 'Sem pasta' }}</small></td>
                                <td><strong>{{ invoice.installment_number ?? 1 }}/{{ invoice.installment_count ?? 1 }}</strong></td>
                                <td>{{ invoice.client?.name ?? '—' }}</td><td>{{ date(invoice.due_on) }}</td>
                                <td><span class="finance-page__status" :class="`finance-page__status--${displayStatus(invoice)}`">{{ statusLabel(invoice) }}</span></td>
                                <td><strong>{{ money(invoice.balance_cents) }}</strong><small>de {{ money(invoice.total_cents) }}</small></td>
                                <td><div class="finance-page__row-actions"><AppButton size="sm" variant="ghost" @click="openDetails(invoice)">Detalhes</AppButton><template v-if="canManage"><AppButton v-if="['draft', 'open', 'partial'].includes(invoice.status)" size="sm" variant="ghost" @click="openInvoiceEdit(invoice)">Editar</AppButton><AppButton v-if="isLastInstallment(invoice) && Number(invoice.installment_count) > 1" size="sm" variant="outline" @click="openInstallment(invoice)">Nova parcela</AppButton><AppButton v-if="Number(invoice.balance_cents) > 0 && invoice.status !== 'cancelled'" size="sm" variant="outline" @click="openPayment(invoice)">Registrar pagamento</AppButton><AppButton v-if="['draft', 'open'].includes(invoice.status) && !invoice.payments?.length" size="sm" variant="ghost" @click="openCancellation(invoice)">Cancelar</AppButton><AppButton v-if="!invoice.payments?.length" size="sm" variant="ghost" @click="pendingDelete = invoice">Excluir</AppButton></template></div></td>
                            </tr></tbody>
                        </table>
                    </div>
                </section>
            </AppCard>

            <AppDialog :open="Boolean(detailInvoiceId)" title="Detalhes da cobrança" @close="closeDetails">
                <div v-if="financeStore.loadingDetails" class="finance-page__empty">Carregando detalhes...</div>
                <div v-else-if="financeStore.invoiceDetails" class="finance-details">
                    <header class="finance-details__header"><div><span>{{ financeStore.invoiceDetails.charge_identifier }}</span><strong>Parcela {{ financeStore.invoiceDetails.installment_number }}/{{ financeStore.invoiceDetails.installment_count }}</strong></div><span class="finance-page__status" :class="`finance-page__status--${displayStatus(financeStore.invoiceDetails)}`">{{ statusLabel(financeStore.invoiceDetails) }}</span></header>
                    <dl class="finance-details__summary"><div><dt>Cliente</dt><dd>{{ financeStore.invoiceDetails.client?.name ?? '—' }}</dd></div><div><dt>Pasta</dt><dd>{{ financeStore.invoiceDetails.folder?.name ?? 'Sem pasta' }}</dd></div><div><dt>Vencimento</dt><dd>{{ date(financeStore.invoiceDetails.due_on) }}</dd></div><div><dt>Saldo</dt><dd>{{ money(financeStore.invoiceDetails.balance_cents) }}</dd></div></dl>
                    <section v-if="financeStore.invoiceDetails.fee_agreement"><h3>Contrato de honorários</h3><p>{{ agreementOptionLabel(financeStore.invoiceDetails.fee_agreement) }}</p><small>{{ agreementTerms(financeStore.invoiceDetails.fee_agreement) }}</small></section>
                    <section v-if="financeStore.invoiceDetails.status === 'cancelled'" class="finance-details__cancellation"><h3>Cancelamento</h3><p>{{ financeStore.invoiceDetails.cancellation_reason }}</p><small>{{ dateTime(financeStore.invoiceDetails.cancelled_at) }}</small></section>
                    <section><h3>Composição</h3><div v-if="!financeStore.invoiceDetails.time_entries?.length && !financeStore.invoiceDetails.expenses?.length" class="finance-details__empty">Cobrança criada sem lançamentos vinculados.</div><div v-for="entry in financeStore.invoiceDetails.time_entries" :key="`time-${entry.id}`" class="finance-details__row"><div><strong>{{ entry.description }}</strong><small>{{ date(entry.worked_on) }} · {{ entry.user?.name ?? '—' }} · {{ formatDuration(entry.duration_minutes) }}</small></div><span>{{ money(timeEntryAmount(entry)) }}</span></div><div v-for="expense in financeStore.invoiceDetails.expenses" :key="`expense-${expense.id}`" class="finance-details__row"><div><strong>{{ expense.description }}</strong><small>{{ date(expense.incurred_on) }} · {{ expense.user?.name ?? '—' }}</small></div><span>{{ money(expense.amount_cents) }}</span></div></section>
                    <section><h3>Pagamentos</h3><div v-if="!financeStore.invoiceDetails.payments?.length" class="finance-details__empty">Nenhum pagamento registrado.</div><div v-for="payment in financeStore.invoiceDetails.payments" :key="payment.id" class="finance-details__row" :class="{ 'finance-details__row--cancelled': payment.cancelled_at }"><div><strong>{{ paymentMethodLabel(payment.method) }} <small v-if="payment.cancelled_at" class="finance-details__cancelled-tag">Cancelado</small></strong><small>{{ dateTime(payment.paid_at) }} · {{ payment.recorded_by?.name ?? '—' }}</small><small v-if="payment.cancelled_at">{{ payment.cancellation_reason }} · {{ payment.cancelled_by?.name ?? '—' }}</small></div><div class="finance-details__payment-value"><span>{{ money(payment.amount_cents) }}</span><AppButton v-if="canManage && !payment.cancelled_at" type="button" size="sm" variant="ghost" @click="openPaymentCancellation(payment)">Cancelar pagamento</AppButton></div></div></section>
                    <div class="finance-details__totals"><span>Subtotal <strong>{{ money(financeStore.invoiceDetails.subtotal_cents) }}</strong></span><span>Desconto <strong>{{ money(financeStore.invoiceDetails.discount_cents) }}</strong></span><span>Total <strong>{{ money(financeStore.invoiceDetails.total_cents) }}</strong></span></div>
                </div>
            </AppDialog>

            <AppDialog :open="Boolean(invoiceToEdit)" title="Editar cobrança" size="md" @close="invoiceToEdit = null">
                <form class="finance-edit" @submit.prevent="submitInvoiceEdit">
                    <div class="finance-edit__intro"><div><span>{{ invoiceToEdit?.charge_identifier }}</span><h3>Parcela {{ invoiceToEdit?.installment_number }}/{{ invoiceToEdit?.installment_count }}</h3></div><span class="finance-page__status" :class="`finance-page__status--${displayStatus(invoiceToEdit || {})}`">{{ statusLabel(invoiceToEdit || {}) }}</span></div>
                    <div class="finance-edit__fields">
                        <AppSelect v-model="invoiceEditForm.client_id" id="edit-invoice-client" label="Cliente" :options="clientOptions" required />
                        <AppSelect v-model="invoiceEditForm.folder_id" id="edit-invoice-folder" label="Pasta" :options="folderOptions" placeholder="Sem pasta" />
                        <AppInput v-model="invoiceEditForm.due_on" id="edit-invoice-due-on" label="Vencimento" type="date" required />
                        <AppCurrency v-model="invoiceEditForm.subtotal" id="edit-invoice-subtotal" label="Valor" :min="0.01" :allow-empty="false" shift-decimal required />
                        <AppCurrency v-model="invoiceEditForm.discount" id="edit-invoice-discount" label="Desconto" :min="0" :allow-empty="false" shift-decimal />
                        <div class="finance-edit__balance"><span>Valor já pago</span><strong>{{ money(invoiceToEdit?.paid_cents) }}</strong></div>
                    </div>
                    <AppTextarea v-model="invoiceEditForm.notes" id="edit-invoice-notes" label="Observações" :rows="3" :maxlength="10000" placeholder="Informações adicionais sobre a cobrança" />
                    <div class="finance-edit__actions"><AppButton type="button" variant="ghost" :disabled="submitting" @click="invoiceToEdit = null">Cancelar</AppButton><AppButton type="submit" variant="navigation" :loading="submitting">Salvar alterações</AppButton></div>
                </form>
            </AppDialog>

            <AppDialog :open="Boolean(cancellationInvoice)" title="Cancelar cobrança" size="sm" @close="cancellationInvoice = null">
                <form class="finance-cancel" @submit.prevent="submitCancellation">
                    <div class="finance-cancel__intro">
                        <span class="finance-cancel__icon" aria-hidden="true"><TriangleAlert :size="24" :stroke-width="1.8" /></span>
                        <div><h3>Esta ação exige atenção</h3><p>A cobrança deixará de compor o saldo a receber, mas permanecerá no histórico.</p></div>
                    </div>
                    <dl class="finance-cancel__summary">
                        <div><dt>Cobrança</dt><dd>{{ cancellationInvoice?.charge_identifier }}</dd></div>
                        <div><dt>Cliente</dt><dd>{{ cancellationInvoice?.client?.name ?? '—' }}</dd></div>
                        <div><dt>Parcela</dt><dd>{{ cancellationInvoice?.installment_number }}/{{ cancellationInvoice?.installment_count }}</dd></div>
                        <div><dt>Saldo cancelado</dt><dd>{{ money(cancellationInvoice?.balance_cents) }}</dd></div>
                    </dl>
                    <div v-if="cancellationInvoice?.time_entries?.length || cancellationInvoice?.expenses?.length" class="finance-cancel__impact"><strong>Lançamentos serão liberados</strong><span>Horas e despesas vinculadas voltarão a ficar disponíveis para faturamento.</span></div>
                    <AppTextarea v-model="cancellationForm.reason" id="cancellation-reason"
                        class="finance-cancel__reason" label="Motivo do cancelamento"
                        :hint="`${cancellationForm.reason.length}/1000 caracteres`" :rows="4" :maxlength="1000"
                        required autofocus placeholder="Descreva por que esta cobrança está sendo cancelada" />
                    <div class="finance-cancel__actions"><AppButton type="button" variant="ghost" :disabled="submitting" @click="cancellationInvoice = null">Manter cobrança</AppButton><AppButton type="submit" variant="danger" :loading="submitting" :disabled="!cancellationForm.reason.trim()">Confirmar cancelamento</AppButton></div>
                </form>
            </AppDialog>

            <AppDialog :open="Boolean(installmentInvoice)" title="Adicionar parcela" @close="installmentInvoice = null">
                <form class="finance-page__payment" @submit.prevent="submitInstallment">
                    <p>Cobrança: <strong>{{ installmentInvoice?.charge_identifier }}</strong></p>
                    <AppCurrency v-model="installmentForm.subtotal" id="installment-subtotal" label="Valor" :min="0.01" :allow-empty="false" shift-decimal required />
                    <AppCurrency v-model="installmentForm.discount" id="installment-discount" label="Desconto" :min="0" :allow-empty="false" shift-decimal />
                    <label>Vencimento<input v-model="installmentForm.due_on" type="date" required></label>
                    <label>Observações<textarea v-model.trim="installmentForm.notes" rows="3" maxlength="10000" /></label>
                    <div class="finance-page__actions"><AppButton type="button" variant="ghost" @click="installmentInvoice = null">Cancelar</AppButton><AppButton type="submit" :loading="submitting">Adicionar parcela</AppButton></div>
                </form>
            </AppDialog>

            <AppDialog :open="Boolean(paymentInvoice)" title="Registrar pagamento" size="md" @close="paymentInvoice = null">
                <form class="finance-payment" @submit.prevent="submitPayment">
                    <div class="finance-payment__intro">
                        <span class="finance-payment__icon" aria-hidden="true"><CircleDollarSign :size="24" /></span>
                        <div><h3>Confirmar recebimento</h3><p>Informe os dados do pagamento para atualizar o saldo da cobrança.</p></div>
                    </div>
                    <dl class="finance-payment__summary">
                        <div><dt>Cobrança</dt><dd>{{ paymentInvoice?.charge_identifier }}</dd></div>
                        <div><dt>Cliente</dt><dd>{{ paymentInvoice?.client?.name ?? '—' }}</dd></div>
                        <div><dt>Vencimento</dt><dd>{{ date(paymentInvoice?.due_on) }}</dd></div>
                        <div><dt>Saldo atual</dt><dd>{{ money(paymentInvoice?.balance_cents) }}</dd></div>
                    </dl>
                    <AppCurrency v-model="paymentForm.amount" id="payment-amount" label="Valor" :min="0.01"
                        :max="Number(paymentInvoice?.balance_cents || 0) / 100" :allow-empty="false" shift-decimal required autofocus />
                    <div class="finance-payment__fields">
                        <AppInput v-model="paymentForm.paid_at" id="payment-date" label="Data e hora" type="datetime-local" required />
                        <AppSelect v-model="paymentForm.method" id="payment-method" label="Forma de pagamento" :options="paymentMethodOptions" required />
                    </div>
                    <div class="finance-payment__remaining">
                        <span>Saldo após o pagamento</span><strong>{{ money(paymentRemainingCents) }}</strong>
                    </div>
                    <div class="finance-payment__actions"><AppButton type="button" variant="ghost" :disabled="submitting" @click="paymentInvoice = null">Cancelar</AppButton><AppButton type="submit" variant="navigation" :loading="submitting" :disabled="!paymentForm.amount">Confirmar pagamento</AppButton></div>
                </form>
            </AppDialog>

            <AppDialog :open="Boolean(paymentToCancel)" title="Cancelar pagamento" size="sm" @close="paymentToCancel = null">
                <form class="finance-cancel" @submit.prevent="submitPaymentCancellation">
                    <div class="finance-cancel__intro">
                        <span class="finance-cancel__icon" aria-hidden="true"><TriangleAlert :size="24" /></span>
                        <div><h3>Estornar recebimento</h3><p>O valor retornará ao saldo da cobrança e o pagamento permanecerá no histórico.</p></div>
                    </div>
                    <dl class="finance-cancel__summary">
                        <div><dt>Forma</dt><dd>{{ paymentMethodLabel(paymentToCancel?.method) }}</dd></div>
                        <div><dt>Data</dt><dd>{{ dateTime(paymentToCancel?.paid_at) }}</dd></div>
                        <div><dt>Registrado por</dt><dd>{{ paymentToCancel?.recorded_by?.name ?? '—' }}</dd></div>
                        <div><dt>Valor</dt><dd>{{ money(paymentToCancel?.amount_cents) }}</dd></div>
                    </dl>
                    <AppTextarea v-model="paymentCancellationForm.reason" id="payment-cancellation-reason" label="Motivo do cancelamento" :hint="`${paymentCancellationForm.reason.length}/1000 caracteres`" :rows="4" :maxlength="1000" required autofocus placeholder="Descreva por que este pagamento está sendo cancelado" />
                    <div class="finance-cancel__actions"><AppButton type="button" variant="ghost" :disabled="submitting" @click="paymentToCancel = null">Manter pagamento</AppButton><AppButton type="submit" variant="danger" :loading="submitting" :disabled="!paymentCancellationForm.reason.trim()">Confirmar cancelamento</AppButton></div>
                </form>
            </AppDialog>
            <AppConfirmDialog :open="Boolean(pendingDelete)" title="Excluir cobrança" message="Esta cobrança será removida permanentemente." confirm-label="Excluir" :loading="submitting" @cancel="pendingDelete = null" @confirm="confirmDelete" />
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { CircleDollarSign, TriangleAlert, WalletCards } from '@lucide/vue'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppCurrency, AppInput, AppSelect, AppTextarea } from '@/components/forms'
import { AppButton, AppCard, AppConfirmDialog, AppDialog } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'
import { useFinanceStore } from '@/stores/finance.js'
import { useFoldersStore } from '@/stores/folders.js'
import { listFeeAgreements } from '@/api/folder-financial.js'

const authStore = useAuthStore(); const clientsStore = useClientsStore(); const foldersStore = useFoldersStore(); const financeStore = useFinanceStore()
const error = ref(''); const submitting = ref(false); const showInvoiceForm = ref(false); const paymentInvoice = ref(null); const installmentInvoice = ref(null); const pendingDelete = ref(null)
const detailInvoiceId = ref(null)
const cancellationInvoice = ref(null); const cancellationForm = reactive({ reason: '' })
const invoiceToEdit = ref(null)
const invoiceEditForm = reactive({ client_id: '', folder_id: '', due_on: '', subtotal: 0, discount: 0, notes: '' })
const paymentToCancel = ref(null); const paymentCancellationForm = reactive({ reason: '' })
const availableAgreements = ref([]); const loadingAgreements = ref(false)
const localDate = () => new Date().toLocaleDateString('en-CA'); const localDateTime = () => `${localDate()}T${new Date().toTimeString().slice(0, 5)}`
const invoiceForm = reactive({ client_id: '', folder_id: '', fee_agreement_id: '', due_on: localDate(), subtotal: 0, discount: 0, installment_count: 1, installment_interval_months: 1, notes: '' })
const invoiceFilters = reactive({ client_id: '', transaction: '', period_type: 'all', month: localDate().slice(0, 7), due_from: '', due_to: '' })
const paymentForm = reactive({ amount: null, paid_at: localDateTime(), method: 'pix' })
const paymentMethodOptions = [
    { value: 'pix', label: 'Pix' },
    { value: 'bank_transfer', label: 'Transferência bancária' },
    { value: 'cash', label: 'Dinheiro' },
    { value: 'credit_card', label: 'Cartão de crédito' },
    { value: 'boleto', label: 'Boleto' },
    { value: 'other', label: 'Outra' },
]
const installmentForm = reactive({ due_on: localDate(), subtotal: 0, discount: 0, notes: '' })
const canManage = computed(() => authStore.hasPermission('finance.manage'))
const clientOptions = computed(() => clientsStore.clients.map((client) => ({ value: client.id, label: client.name })))
const folderOptions = computed(() => [{ value: '', label: 'Sem pasta' }, ...foldersStore.folders.map((folder) => ({ value: folder.id, label: folder.name }))])
const paymentRemainingCents = computed(() => Math.max(0, Number(paymentInvoice.value?.balance_cents || 0) - Math.round(Number(paymentForm.amount || 0) * 100)))
const hasInvoiceFilterInput = computed(() => Boolean(invoiceFilters.client_id || invoiceFilters.transaction || invoiceFilters.period_type !== 'all'))
const hasActiveInvoiceFilters = computed(() => Object.keys(financeStore.activeFilters).length > 0)
const selectedAgreement = computed(() => availableAgreements.value.find((agreement) => Number(agreement.id) === Number(invoiceForm.fee_agreement_id)) ?? null)
const agreementPlaceholder = computed(() => !invoiceForm.folder_id ? 'Selecione uma pasta primeiro' : loadingAgreements.value ? 'Carregando contratos...' : availableAgreements.value.length ? 'Sem contrato vinculado' : 'Nenhum contrato disponível')
const overdueDescription = computed(() => {
    const count = Number(financeStore.summary.overdue_count || 0)
    return `${count} ${count === 1 ? 'cobrança vencida' : 'cobranças vencidas'}`
})
const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(cents || 0) / 100)
const date = (value) => value ? new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(value)) : '—'
const dateTime = (value) => value ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '—'
const formatDuration = (minutes) => `${Math.floor(Number(minutes || 0) / 60)}h ${Number(minutes || 0) % 60}min`
const timeEntryAmount = (entry) => Math.round(Number(entry.duration_minutes || 0) * Number(entry.hourly_rate_cents || 0) / 60)
const paymentMethodLabel = (method) => ({ pix: 'Pix', bank_transfer: 'Transferência', cash: 'Dinheiro', credit_card: 'Cartão de crédito', boleto: 'Boleto', other: 'Outra' })[method] ?? method
const isOverdue = (invoice) => ['open', 'partial'].includes(invoice.status) && String(invoice.due_on).slice(0, 10) < localDate()
const displayStatus = (invoice) => isOverdue(invoice) ? 'overdue' : invoice.status
const statusLabel = (invoice) => ({ draft: 'Rascunho', open: 'Em aberto', partial: 'Parcial', paid: 'Pago', cancelled: 'Cancelado', overdue: 'Vencido' })[displayStatus(invoice)] ?? invoice.status
const agreementTypeLabel = (type) => ({ hourly: 'Por hora', fixed: 'Valor fixo', contingency: 'Êxito', hybrid: 'Híbrido' })[type] ?? type
const agreementOptionLabel = (agreement) => `${agreement.client?.name ?? 'Sem cliente'} · ${agreementTypeLabel(agreement.type)}`
function agreementTerms(agreement) { const terms = []; if (agreement.fixed_fee_cents) terms.push(`Fixo de ${money(agreement.fixed_fee_cents)}`); if (agreement.hourly_rate_cents) terms.push(`${money(agreement.hourly_rate_cents)} por hora`); if (agreement.contingency_percentage) terms.push(`${agreement.contingency_percentage}% de êxito`); return terms.join(' + ') }
function message(exception) { return exception?.response?.data?.message ?? Object.values(exception?.response?.data?.errors ?? {})[0]?.[0] ?? 'Não foi possível concluir a operação.' }
async function submitInvoice() { submitting.value = true; error.value = ''; try { await financeStore.addInvoice({ client_id: Number(invoiceForm.client_id), folder_id: invoiceForm.folder_id ? Number(invoiceForm.folder_id) : null, fee_agreement_id: invoiceForm.fee_agreement_id ? Number(invoiceForm.fee_agreement_id) : null, due_on: invoiceForm.due_on, subtotal_cents: Math.round(invoiceForm.subtotal * 100), discount_cents: Math.round((invoiceForm.discount || 0) * 100), installment_count: invoiceForm.installment_count, installment_interval_months: invoiceForm.installment_interval_months, notes: invoiceForm.notes || null }); showInvoiceForm.value = false } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
async function openDetails(invoice) { detailInvoiceId.value = invoice.id; financeStore.clearInvoiceDetails(); try { await financeStore.fetchInvoice(invoice.id) } catch (exception) { error.value = message(exception); closeDetails() } }
function closeDetails() { detailInvoiceId.value = null; financeStore.clearInvoiceDetails() }
async function openCancellation(invoice) { cancellationForm.reason = ''; cancellationInvoice.value = invoice; try { cancellationInvoice.value = await financeStore.fetchInvoice(invoice.id) } catch (exception) { error.value = message(exception); cancellationInvoice.value = null } }
async function submitCancellation() { submitting.value = true; error.value = ''; try { await financeStore.cancelInvoice(cancellationInvoice.value.id, { reason: cancellationForm.reason }); cancellationInvoice.value = null; if (detailInvoiceId.value) closeDetails() } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
function openInvoiceEdit(invoice) { invoiceToEdit.value = invoice; Object.assign(invoiceEditForm, { client_id: invoice.client_id, folder_id: invoice.folder_id ?? '', due_on: String(invoice.due_on || '').slice(0, 10), subtotal: Number(invoice.subtotal_cents) / 100, discount: Number(invoice.discount_cents) / 100, notes: invoice.notes ?? '' }) }
async function submitInvoiceEdit() { submitting.value = true; error.value = ''; try { await financeStore.updateInvoice(invoiceToEdit.value.id, { client_id: Number(invoiceEditForm.client_id), folder_id: invoiceEditForm.folder_id ? Number(invoiceEditForm.folder_id) : null, due_on: invoiceEditForm.due_on, subtotal_cents: Math.round(Number(invoiceEditForm.subtotal) * 100), discount_cents: Math.round(Number(invoiceEditForm.discount || 0) * 100), notes: invoiceEditForm.notes || null }); invoiceToEdit.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
function invoiceFilterPayload() { return { ...(invoiceFilters.client_id ? { client_id: Number(invoiceFilters.client_id) } : {}), ...(invoiceFilters.transaction ? { transaction: invoiceFilters.transaction } : {}), ...(invoiceFilters.period_type === 'month' ? { month: invoiceFilters.month } : {}), ...(invoiceFilters.period_type === 'range' ? { due_from: invoiceFilters.due_from, due_to: invoiceFilters.due_to } : {}) } }
async function applyInvoiceFilters() { error.value = ''; try { await financeStore.fetchAll(invoiceFilterPayload()) } catch (exception) { error.value = message(exception) } }
async function clearInvoiceFilters() { Object.assign(invoiceFilters, { client_id: '', transaction: '', period_type: 'all', month: localDate().slice(0, 7), due_from: '', due_to: '' }); await applyInvoiceFilters() }
const isLastInstallment = (invoice) => Number(invoice.installment_number ?? 1) === Number(invoice.installment_count ?? 1)
function openInstallment(invoice) { installmentInvoice.value = invoice; installmentForm.due_on = nextMonth(invoice.due_on); installmentForm.subtotal = 0; installmentForm.discount = 0; installmentForm.notes = '' }
function nextMonth(value) { const source = new Date(`${String(value).slice(0, 10)}T12:00:00Z`); const day = source.getUTCDate(); source.setUTCDate(1); source.setUTCMonth(source.getUTCMonth() + 1); const lastDay = new Date(Date.UTC(source.getUTCFullYear(), source.getUTCMonth() + 1, 0)).getUTCDate(); source.setUTCDate(Math.min(day, lastDay)); return source.toISOString().slice(0, 10) }
async function submitInstallment() { submitting.value = true; error.value = ''; try { await financeStore.addInstallment(installmentInvoice.value.id, { due_on: installmentForm.due_on, subtotal_cents: Math.round(installmentForm.subtotal * 100), discount_cents: Math.round((installmentForm.discount || 0) * 100), notes: installmentForm.notes || null }); installmentInvoice.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
function openPayment(invoice) { paymentInvoice.value = invoice; paymentForm.amount = Number(invoice.balance_cents) / 100; paymentForm.paid_at = localDateTime() }
async function submitPayment() { submitting.value = true; error.value = ''; try { await financeStore.addPayment(paymentInvoice.value.id, { paid_at: paymentForm.paid_at, amount_cents: Math.round(paymentForm.amount * 100), method: paymentForm.method }); paymentInvoice.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
function openPaymentCancellation(payment) { paymentCancellationForm.reason = ''; paymentToCancel.value = payment }
async function submitPaymentCancellation() { submitting.value = true; error.value = ''; try { await financeStore.cancelPayment(detailInvoiceId.value, paymentToCancel.value.id, { reason: paymentCancellationForm.reason }); paymentToCancel.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
async function confirmDelete() { submitting.value = true; try { await financeStore.removeInvoice(pendingDelete.value.id); pendingDelete.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
watch(() => invoiceForm.folder_id, async (folderId) => { invoiceForm.fee_agreement_id = ''; availableAgreements.value = []; if (!folderId) return; loadingAgreements.value = true; const requestedFolder = Number(folderId); try { const { data } = await listFeeAgreements(requestedFolder); if (Number(invoiceForm.folder_id) === requestedFolder) availableAgreements.value = data.filter((agreement) => ['draft', 'active'].includes(agreement.status)) } catch (exception) { error.value = message(exception) } finally { if (Number(invoiceForm.folder_id) === requestedFolder) loadingAgreements.value = false } })
watch(() => invoiceForm.fee_agreement_id, () => { const agreement = selectedAgreement.value; if (!agreement) return; invoiceForm.client_id = agreement.client_id; if (Number(agreement.fixed_fee_cents) > 0) invoiceForm.subtotal = Number(agreement.fixed_fee_cents) / 100 })
onMounted(async () => { try { await Promise.all([financeStore.fetchAll(), clientsStore.fetchClients(), foldersStore.fetchFolders()]) } catch (exception) { error.value = message(exception) } })
</script>

<style scoped>
.finance-page { display: flex; flex-direction: column; gap: var(--space-6); }.finance-page__header,.finance-page__section-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: var(--space-4); }.finance-page h1,.finance-page h2,.finance-page p { margin: 0; }.finance-page__header p,.finance-page__section-heading p { margin-top: var(--space-2); color: var(--color-text-muted); }.finance-page__eyebrow { color: var(--color-brand-secondary); font-size: var(--font-size-sm); font-weight: 700; text-transform: uppercase; }.finance-page__summary { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: var(--space-4); }.finance-page__summary :deep(.app-card) { display: flex; flex-direction: column; gap: var(--space-2); }.finance-page__summary span,.finance-page__summary small { color: var(--color-text-muted); }.finance-page__summary strong { color: var(--color-brand); font-size: 1.5rem; }.finance-page__danger { color: var(--color-danger)!important; }.finance-page__form,.finance-page__invoices,.finance-page__payment { display: flex; flex-direction: column; gap: var(--space-5); }.finance-page__grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: var(--space-4); }.finance-page label { display: flex; flex-direction: column; gap: var(--space-2); color: var(--color-text-soft); font-size: var(--font-size-sm); font-weight: 600; }.finance-page input,.finance-page select,.finance-page textarea { padding: .65rem .75rem; color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); font: inherit; }.finance-page__wide { grid-column: 1/-1; }.finance-page__actions,.finance-page__row-actions { display: flex; justify-content: flex-end; gap: var(--space-2); }.finance-page__table-wrap { overflow-x: auto; }.finance-page table { width: 100%; border-collapse: collapse; }.finance-page th,.finance-page td { padding: var(--space-3); text-align: left; border-bottom: 1px solid var(--color-border); }.finance-page th { color: var(--color-text-muted); font-size: var(--font-size-sm); }.finance-page td>strong,.finance-page td>small { display: block; }.finance-page td small { margin-top: var(--space-1); color: var(--color-text-muted); }.finance-page__status { display: inline-flex; padding: .25rem .55rem; background: var(--color-surface-muted); border-radius: 999px; font-size: var(--font-size-xs); font-weight: 700; }.finance-page__status--paid { color: var(--color-brand-secondary); background: var(--color-surface-secondary-soft); }.finance-page__status--overdue { color: var(--color-danger); background: var(--color-danger-soft); }.finance-page__alert { padding: var(--space-3); color: var(--color-danger); background: var(--color-danger-soft); border-radius: var(--radius-md); }.finance-page__empty { padding: var(--space-6); color: var(--color-text-muted); text-align: center; }.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }@media(max-width:760px){.finance-page__summary,.finance-page__grid{grid-template-columns:1fr}.finance-page__wide{grid-column:auto}.finance-page__header{flex-direction:column}}

.finance-page__header {
    padding-bottom: var(--space-2);
}

.finance-page__header h1 {
    margin-top: var(--space-1);
    color: var(--color-brand);
    font-size: clamp(2rem, 4vw, 2.5rem);
    line-height: 1.1;
}

.finance-page__eyebrow {
    letter-spacing: 0.06em;
}

.finance-summary-card {
    position: relative;
    min-height: 9.5rem;
    border-color: color-mix(in srgb, var(--summary-color) 22%, var(--color-border));
    box-shadow: 0 0.4rem 1.25rem rgb(53 37 27 / 0.07);
}

.finance-summary-card::before {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 3px;
    background: var(--summary-color);
    content: '';
}

.finance-summary-card :deep(.card__body) {
    display: grid;
    grid-template-columns: 2.75rem minmax(0, 1fr);
    padding: var(--space-5);
    align-items: flex-start;
    gap: var(--space-4);
}

.finance-summary-card--receivable {
    --summary-color: var(--color-brand-secondary);
    --summary-soft: var(--color-surface-secondary-soft);
}

.finance-summary-card--overdue {
    --summary-color: var(--color-danger);
    --summary-soft: var(--color-danger-soft);
}

.finance-summary-card--received {
    --summary-color: var(--color-success);
    --summary-soft: var(--color-success-soft);
}

.finance-summary-card__icon {
    display: grid;
    width: 2.75rem;
    height: 2.75rem;
    place-items: center;
    color: var(--summary-color);
    background: var(--summary-soft);
    border-radius: var(--radius-md);
}

.finance-summary-card__content {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.finance-summary-card__label {
    color: var(--color-text-soft) !important;
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
}

.finance-summary-card__value {
    margin-top: var(--space-2);
    color: var(--summary-color) !important;
    font-size: clamp(1.45rem, 2.4vw, 1.85rem) !important;
    line-height: 1.15;
    letter-spacing: -0.025em;
}

.finance-summary-card__detail {
    margin-top: var(--space-2);
    color: var(--color-text-muted) !important;
    font-size: var(--font-size-xs);
}

.finance-page__filters {
    display: grid;
    grid-template-columns: minmax(11rem, 1.25fr) minmax(12rem, 1.5fr) minmax(11rem, 1fr);
    padding: var(--space-4);
    gap: var(--space-3);
    align-items: end;
    background: var(--color-surface-muted);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.finance-page__filter-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}

.finance-page__filter-count {
    padding: .35rem .65rem;
    color: var(--color-brand-secondary);
    background: var(--color-surface-secondary-soft);
    border-radius: 999px;
    font-size: var(--font-size-xs);
    font-weight: 700;
    white-space: nowrap;
}

.finance-page__field-help {
    color: var(--color-brand-secondary);
    font-size: var(--font-size-xs);
    font-weight: 500;
}

.finance-details { display: flex; flex-direction: column; gap: var(--space-5); }
.finance-details h3, .finance-details p { margin: 0; }
.finance-details section { display: flex; flex-direction: column; gap: var(--space-2); }
.finance-details__header { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); }
.finance-details__header > div { display: flex; flex-direction: column; gap: var(--space-1); }
.finance-details__header > div > span { color: var(--color-text-muted); font-size: var(--font-size-sm); }
.finance-details__summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0; gap: var(--space-3); }
.finance-details__summary > div { padding: var(--space-3); background: var(--color-surface-muted); border-radius: var(--radius-md); }
.finance-details__summary dt { color: var(--color-text-muted); font-size: var(--font-size-xs); }
.finance-details__summary dd { margin: var(--space-1) 0 0; font-weight: 700; }
.finance-details__row { display: flex; align-items: center; justify-content: space-between; gap: var(--space-3); padding: var(--space-3) 0; border-bottom: 1px solid var(--color-border); }
.finance-details__row > div { display: flex; min-width: 0; flex-direction: column; gap: var(--space-1); }
.finance-details__row small, .finance-details section > small { color: var(--color-text-muted); }
.finance-details__row > span { font-weight: 700; white-space: nowrap; }
.finance-details__row--cancelled { opacity: .72; }
.finance-details__row--cancelled .finance-details__payment-value > span { text-decoration: line-through; }
.finance-details__cancelled-tag { display: inline-flex; margin-left: var(--space-1); padding: .15rem .4rem; color: var(--color-danger); background: var(--color-danger-soft); border-radius: 999px; font-size: var(--font-size-xs); text-decoration: none; }
.finance-details__payment-value { display: flex; align-items: flex-end; flex-direction: column; gap: var(--space-1); font-weight: 700; white-space: nowrap; }
.finance-details__empty { padding: var(--space-3); color: var(--color-text-muted); background: var(--color-surface-muted); border-radius: var(--radius-md); }
.finance-details__cancellation { padding: var(--space-3); color: var(--color-danger); background: var(--color-danger-soft); border-radius: var(--radius-md); }
.finance-details__totals { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: var(--space-4); padding-top: var(--space-3); border-top: 1px solid var(--color-border); }
.finance-details__totals span { display: flex; flex-direction: column; color: var(--color-text-muted); font-size: var(--font-size-xs); }
.finance-details__totals strong { color: var(--color-brand); font-size: var(--font-size-base); }

.finance-cancel { display: flex; flex-direction: column; gap: var(--space-5); }
.finance-cancel__intro { display: grid; grid-template-columns: 3rem minmax(0, 1fr); align-items: start; gap: var(--space-3); }
.finance-cancel__intro h3, .finance-cancel__intro p { margin: 0; }
.finance-cancel__intro h3 { color: var(--color-brand); font-size: var(--font-size-base); }
.finance-cancel__intro p { margin-top: var(--space-1); color: var(--color-text-muted); font-size: var(--font-size-sm); line-height: 1.5; }
.finance-cancel__icon { display: grid; width: 3rem; height: 3rem; place-items: center; color: var(--color-danger); background: var(--color-danger-soft); border: 1px solid color-mix(in srgb, var(--color-danger) 20%, transparent); border-radius: var(--radius-lg); }
.finance-cancel__summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0; overflow: hidden; border: 1px solid var(--color-border); border-radius: var(--radius-md); }
.finance-cancel__summary > div { padding: var(--space-3) var(--space-4); border-right: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); }
.finance-cancel__summary > div:nth-child(2n) { border-right: 0; }
.finance-cancel__summary > div:nth-last-child(-n+2) { border-bottom: 0; }
.finance-cancel__summary dt { color: var(--color-text-muted); font-size: var(--font-size-xs); }
.finance-cancel__summary dd { margin: var(--space-1) 0 0; overflow: hidden; color: var(--color-text); font-size: var(--font-size-sm); font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
.finance-cancel__summary > div:last-child dd { color: var(--color-danger); }
.finance-cancel__impact { display: flex; padding: var(--space-3) var(--space-4); flex-direction: column; gap: var(--space-1); color: var(--color-brand-secondary-active); background: var(--color-surface-secondary-soft); border-left: 3px solid var(--color-brand-secondary); border-radius: var(--radius-md); font-size: var(--font-size-sm); }
.finance-cancel__impact span { color: var(--color-text-muted); line-height: 1.45; }
.finance-cancel__reason :deep(textarea) { min-height: 7rem; resize: vertical; }
.finance-cancel__reason :deep(.app-field__hint) { align-self: flex-end; }
.finance-cancel__actions { display: flex; justify-content: flex-end; gap: var(--space-2); padding-top: var(--space-1); }

.finance-payment { display: flex; flex-direction: column; gap: var(--space-5); }
.finance-payment__intro { display: grid; grid-template-columns: 3rem minmax(0, 1fr); align-items: start; gap: var(--space-3); }
.finance-payment__intro h3, .finance-payment__intro p { margin: 0; }
.finance-payment__intro h3 { color: var(--color-brand); font-size: var(--font-size-base); }
.finance-payment__intro p { margin-top: var(--space-1); color: var(--color-text-muted); font-size: var(--font-size-sm); line-height: 1.5; }
.finance-payment__icon { display: grid; width: 3rem; height: 3rem; place-items: center; color: var(--color-brand-secondary); background: var(--color-surface-secondary-soft); border: 1px solid color-mix(in srgb, var(--color-brand-secondary) 20%, transparent); border-radius: var(--radius-lg); }
.finance-payment__summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); margin: 0; overflow: hidden; border: 1px solid var(--color-border); border-radius: var(--radius-md); }
.finance-payment__summary > div { padding: var(--space-3) var(--space-4); border-right: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border); }
.finance-payment__summary > div:nth-child(2n) { border-right: 0; }
.finance-payment__summary > div:nth-last-child(-n+2) { border-bottom: 0; }
.finance-payment__summary dt { color: var(--color-text-muted); font-size: var(--font-size-xs); }
.finance-payment__summary dd { margin: var(--space-1) 0 0; overflow: hidden; color: var(--color-text); font-size: var(--font-size-sm); font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
.finance-payment__summary > div:last-child dd { color: var(--color-brand-secondary); }
.finance-payment__fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-3); }
.finance-payment__fields > * { min-width: 0; }
.finance-payment__remaining { display: flex; align-items: center; justify-content: space-between; padding: var(--space-3) var(--space-4); gap: var(--space-3); color: var(--color-brand-secondary-active); background: var(--color-surface-secondary-soft); border-left: 3px solid var(--color-brand-secondary); border-radius: var(--radius-md); font-size: var(--font-size-sm); }
.finance-payment__remaining strong { font-size: var(--font-size-base); white-space: nowrap; }
.finance-payment__actions { display: flex; justify-content: flex-end; gap: var(--space-2); padding-top: var(--space-1); }

.finance-edit { display: flex; flex-direction: column; gap: var(--space-5); }
.finance-edit__intro { display: flex; align-items: center; justify-content: space-between; padding: var(--space-3) var(--space-4); gap: var(--space-3); background: var(--color-surface-muted); border-radius: var(--radius-md); }
.finance-edit__intro > div { display: flex; flex-direction: column; gap: var(--space-1); }
.finance-edit__intro span { color: var(--color-text-muted); font-size: var(--font-size-xs); }
.finance-edit__intro h3 { margin: 0; color: var(--color-brand); font-size: var(--font-size-base); }
.finance-edit__fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-4); }
.finance-edit__fields > * { min-width: 0; }
.finance-edit__balance { display: flex; justify-content: space-between; align-items: center; padding: 0 var(--space-3); color: var(--color-text-muted); background: var(--color-surface-secondary-soft); border-radius: var(--radius-md); font-size: var(--font-size-sm); }
.finance-edit__balance strong { color: var(--color-brand-secondary); }
.finance-edit__actions { display: flex; justify-content: flex-end; gap: var(--space-2); }

@media (max-width: 960px) {
    .finance-page__filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .finance-page__filters {
        grid-template-columns: 1fr;
    }

    .finance-page__filter-actions {
        justify-content: stretch;
    }

    .finance-page__filter-actions > * {
        flex: 1;
    }

    .finance-details__summary {
        grid-template-columns: 1fr;
    }

    .finance-cancel__summary {
        grid-template-columns: 1fr;
    }

    .finance-cancel__summary > div,
    .finance-cancel__summary > div:nth-child(2n),
    .finance-cancel__summary > div:nth-last-child(-n+2) {
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }

    .finance-cancel__summary > div:last-child {
        border-bottom: 0;
    }

    .finance-cancel__actions {
        flex-direction: column-reverse;
    }

    .finance-payment__summary,
    .finance-payment__fields,
    .finance-edit__fields {
        grid-template-columns: 1fr;
    }

    .finance-payment__summary > div,
    .finance-payment__summary > div:nth-child(2n),
    .finance-payment__summary > div:nth-last-child(-n+2) {
        border-right: 0;
        border-bottom: 1px solid var(--color-border);
    }

    .finance-payment__summary > div:last-child {
        border-bottom: 0;
    }

    .finance-payment__actions {
        flex-direction: column-reverse;
    }

    .finance-edit__actions { flex-direction: column-reverse; }
}
</style>
