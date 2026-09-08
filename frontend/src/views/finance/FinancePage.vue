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
                    <div class="finance-page__section-heading"><div><h2>Contas a receber</h2><p>Cobranças atuais e histórico de recebimentos.</p></div></div>
                    <p v-if="financeStore.loading" class="finance-page__empty">Carregando cobranças...</p>
                    <p v-else-if="!financeStore.invoices.length" class="finance-page__empty">Nenhuma cobrança registrada.</p>
                    <div v-else class="finance-page__table-wrap">
                        <table>
                            <thead><tr><th>Cobrança</th><th>Parcela</th><th>Cliente</th><th>Vencimento</th><th>Situação</th><th>Saldo</th><th><span class="sr-only">Ações</span></th></tr></thead>
                            <tbody><tr v-for="invoice in financeStore.invoices" :key="invoice.id">
                                <td><strong>{{ invoice.charge_identifier ?? invoice.number }}</strong><small>#{{ invoice.number }} · {{ invoice.folder?.name ?? 'Sem pasta' }}</small></td>
                                <td><strong>{{ invoice.installment_number ?? 1 }}/{{ invoice.installment_count ?? 1 }}</strong></td>
                                <td>{{ invoice.client?.name ?? '—' }}</td><td>{{ date(invoice.due_on) }}</td>
                                <td><span class="finance-page__status" :class="`finance-page__status--${displayStatus(invoice)}`">{{ statusLabel(invoice) }}</span></td>
                                <td><strong>{{ money(invoice.balance_cents) }}</strong><small>de {{ money(invoice.total_cents) }}</small></td>
                                <td><div v-if="canManage" class="finance-page__row-actions"><AppButton v-if="isLastInstallment(invoice) && Number(invoice.installment_count) > 1" size="sm" variant="outline" @click="openInstallment(invoice)">Nova parcela</AppButton><AppButton v-if="Number(invoice.balance_cents) > 0 && invoice.status !== 'cancelled'" size="sm" variant="outline" @click="openPayment(invoice)">Registrar pagamento</AppButton><AppButton v-if="!invoice.payments?.length" size="sm" variant="ghost" @click="pendingDelete = invoice">Excluir</AppButton></div></td>
                            </tr></tbody>
                        </table>
                    </div>
                </section>
            </AppCard>

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

            <AppDialog :open="Boolean(paymentInvoice)" title="Registrar pagamento" @close="paymentInvoice = null">
                <form class="finance-page__payment" @submit.prevent="submitPayment">
                    <p>Saldo: <strong>{{ money(paymentInvoice?.balance_cents) }}</strong></p>
                    <AppCurrency v-model="paymentForm.amount" id="payment-amount" label="Valor" :min="0.01"
                        :max="Number(paymentInvoice?.balance_cents || 0) / 100" shift-decimal required />
                    <label>Data<input v-model="paymentForm.paid_at" type="datetime-local" required></label>
                    <label>Forma<select v-model="paymentForm.method"><option value="pix">Pix</option><option value="bank_transfer">Transferência</option><option value="cash">Dinheiro</option><option value="credit_card">Cartão de crédito</option><option value="boleto">Boleto</option><option value="other">Outra</option></select></label>
                    <div class="finance-page__actions"><AppButton type="button" variant="ghost" @click="paymentInvoice = null">Cancelar</AppButton><AppButton type="submit" :loading="submitting">Confirmar pagamento</AppButton></div>
                </form>
            </AppDialog>
            <AppConfirmDialog :open="Boolean(pendingDelete)" title="Excluir cobrança" message="Esta cobrança será removida permanentemente." confirm-label="Excluir" :loading="submitting" @cancel="pendingDelete = null" @confirm="confirmDelete" />
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { CircleDollarSign, TriangleAlert, WalletCards } from '@lucide/vue'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppCurrency } from '@/components/forms'
import { AppButton, AppCard, AppConfirmDialog, AppDialog } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'
import { useFinanceStore } from '@/stores/finance.js'
import { useFoldersStore } from '@/stores/folders.js'

const authStore = useAuthStore(); const clientsStore = useClientsStore(); const foldersStore = useFoldersStore(); const financeStore = useFinanceStore()
const error = ref(''); const submitting = ref(false); const showInvoiceForm = ref(false); const paymentInvoice = ref(null); const installmentInvoice = ref(null); const pendingDelete = ref(null)
const localDate = () => new Date().toLocaleDateString('en-CA'); const localDateTime = () => `${localDate()}T${new Date().toTimeString().slice(0, 5)}`
const invoiceForm = reactive({ client_id: '', folder_id: '', due_on: localDate(), subtotal: 0, discount: 0, installment_count: 1, installment_interval_months: 1, notes: '' })
const paymentForm = reactive({ amount: null, paid_at: localDateTime(), method: 'pix' })
const installmentForm = reactive({ due_on: localDate(), subtotal: 0, discount: 0, notes: '' })
const canManage = computed(() => authStore.hasPermission('finance.manage'))
const overdueDescription = computed(() => {
    const count = Number(financeStore.summary.overdue_count || 0)
    return `${count} ${count === 1 ? 'cobrança vencida' : 'cobranças vencidas'}`
})
const money = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(cents || 0) / 100)
const date = (value) => value ? new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(value)) : '—'
const isOverdue = (invoice) => ['open', 'partial'].includes(invoice.status) && String(invoice.due_on).slice(0, 10) < localDate()
const displayStatus = (invoice) => isOverdue(invoice) ? 'overdue' : invoice.status
const statusLabel = (invoice) => ({ draft: 'Rascunho', open: 'Em aberto', partial: 'Parcial', paid: 'Pago', cancelled: 'Cancelado', overdue: 'Vencido' })[displayStatus(invoice)] ?? invoice.status
function message(exception) { return exception?.response?.data?.message ?? Object.values(exception?.response?.data?.errors ?? {})[0]?.[0] ?? 'Não foi possível concluir a operação.' }
async function submitInvoice() { submitting.value = true; error.value = ''; try { await financeStore.addInvoice({ client_id: Number(invoiceForm.client_id), folder_id: invoiceForm.folder_id ? Number(invoiceForm.folder_id) : null, due_on: invoiceForm.due_on, subtotal_cents: Math.round(invoiceForm.subtotal * 100), discount_cents: Math.round((invoiceForm.discount || 0) * 100), installment_count: invoiceForm.installment_count, installment_interval_months: invoiceForm.installment_interval_months, notes: invoiceForm.notes || null }); showInvoiceForm.value = false } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
const isLastInstallment = (invoice) => Number(invoice.installment_number ?? 1) === Number(invoice.installment_count ?? 1)
function openInstallment(invoice) { installmentInvoice.value = invoice; installmentForm.due_on = nextMonth(invoice.due_on); installmentForm.subtotal = 0; installmentForm.discount = 0; installmentForm.notes = '' }
function nextMonth(value) { const source = new Date(`${String(value).slice(0, 10)}T12:00:00Z`); const day = source.getUTCDate(); source.setUTCDate(1); source.setUTCMonth(source.getUTCMonth() + 1); const lastDay = new Date(Date.UTC(source.getUTCFullYear(), source.getUTCMonth() + 1, 0)).getUTCDate(); source.setUTCDate(Math.min(day, lastDay)); return source.toISOString().slice(0, 10) }
async function submitInstallment() { submitting.value = true; error.value = ''; try { await financeStore.addInstallment(installmentInvoice.value.id, { due_on: installmentForm.due_on, subtotal_cents: Math.round(installmentForm.subtotal * 100), discount_cents: Math.round((installmentForm.discount || 0) * 100), notes: installmentForm.notes || null }); installmentInvoice.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
function openPayment(invoice) { paymentInvoice.value = invoice; paymentForm.amount = Number(invoice.balance_cents) / 100; paymentForm.paid_at = localDateTime() }
async function submitPayment() { submitting.value = true; error.value = ''; try { await financeStore.addPayment(paymentInvoice.value.id, { paid_at: paymentForm.paid_at, amount_cents: Math.round(paymentForm.amount * 100), method: paymentForm.method }); paymentInvoice.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
async function confirmDelete() { submitting.value = true; try { await financeStore.removeInvoice(pendingDelete.value.id); pendingDelete.value = null } catch (exception) { error.value = message(exception) } finally { submitting.value = false } }
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
</style>
