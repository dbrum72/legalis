<template>
    <section class="folder-financial">
        <header class="folder-financial__header">
            <div>
                <h2>Financeiro</h2>
                <p>Honorários, horas e despesas vinculados a esta pasta.</p>
            </div>
            <div class="folder-financial__actions">
                <AppButton v-if="canManageFinance" size="sm" variant="outline" @click="toggleForm('agreement')">Novo contrato</AppButton>
                <AppButton v-if="canCreateTime" size="sm" variant="outline" @click="toggleForm('time')">Apontar horas</AppButton>
                <AppButton v-if="canCreateExpense" size="sm" @click="toggleForm('expense')">Nova despesa</AppButton>
            </div>
        </header>

        <div v-if="error" class="folder-financial__alert" role="alert">{{ error }}</div>
        <p v-if="financialStore.loading" class="folder-financial__loading">Carregando informações financeiras...</p>

        <div v-else class="folder-financial__summary">
            <article v-if="canViewTime" class="folder-financial__metric">
                <span>Horas faturáveis em aberto</span>
                <strong>{{ formatDuration(financialStore.billableMinutes) }}</strong>
            </article>
            <article v-if="canViewFinance" class="folder-financial__metric folder-financial__metric--brand">
                <span>Honorários por horas</span>
                <strong>{{ formatMoney(financialStore.billableTimeCents) }}</strong>
            </article>
            <article v-if="canViewExpenses" class="folder-financial__metric">
                <span>Despesas reembolsáveis</span>
                <strong>{{ formatMoney(financialStore.reimbursableExpenseCents) }}</strong>
            </article>
        </div>

        <form v-if="activeForm === 'agreement'" class="folder-financial__form" @submit.prevent="submitAgreement">
            <h3>Novo contrato de honorários</h3>
            <div class="folder-financial__grid">
                <label>Cliente<select v-model="agreementForm.client_id" required><option value="" disabled>Selecione</option><option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }}</option></select></label>
                <label>Modalidade<select v-model="agreementForm.type"><option value="hourly">Por hora</option><option value="fixed">Valor fixo</option><option value="contingency">Êxito</option><option value="hybrid">Híbrido</option></select></label>
                <label v-if="['hourly', 'hybrid'].includes(agreementForm.type)">Valor por hora (R$)<input v-model.number="agreementForm.hourly_rate" type="number" min="0" step="0.01"></label>
                <label v-if="['fixed', 'hybrid'].includes(agreementForm.type)">Valor fixo (R$)<input v-model.number="agreementForm.fixed_fee" type="number" min="0" step="0.01"></label>
                <label v-if="['contingency', 'hybrid'].includes(agreementForm.type)">Êxito (%)<input v-model.number="agreementForm.contingency_percentage" type="number" min="0.01" max="100" step="0.01"></label>
            </div>
            <div class="folder-financial__form-actions"><AppButton type="button" variant="ghost" @click="activeForm = ''">Cancelar</AppButton><AppButton type="submit" :loading="submitting">Salvar contrato</AppButton></div>
        </form>

        <form v-if="activeForm === 'time'" class="folder-financial__form" @submit.prevent="submitTime">
            <h3>Novo apontamento</h3>
            <div class="folder-financial__grid">
                <label>Data<input v-model="timeForm.worked_on" type="date" required></label>
                <label>Duração (minutos)<input v-model.number="timeForm.duration_minutes" type="number" min="1" max="1440" required></label>
                <label class="folder-financial__wide">Descrição<input v-model.trim="timeForm.description" maxlength="500" required></label>
                <label class="folder-financial__check"><input v-model="timeForm.billable" type="checkbox"> Faturável</label>
            </div>
            <div class="folder-financial__form-actions"><AppButton type="button" variant="ghost" @click="activeForm = ''">Cancelar</AppButton><AppButton type="submit" :loading="submitting">Salvar apontamento</AppButton></div>
        </form>

        <form v-if="activeForm === 'expense'" class="folder-financial__form" @submit.prevent="submitExpense">
            <h3>Nova despesa</h3>
            <div class="folder-financial__grid">
                <label>Data<input v-model="expenseForm.incurred_on" type="date" required></label>
                <label>Valor (R$)<input v-model.number="expenseForm.amount" type="number" min="0.01" step="0.01" required></label>
                <label class="folder-financial__wide">Descrição<input v-model.trim="expenseForm.description" maxlength="500" required></label>
                <label class="folder-financial__check"><input v-model="expenseForm.reimbursable" type="checkbox"> Reembolsável</label>
            </div>
            <div class="folder-financial__form-actions"><AppButton type="button" variant="ghost" @click="activeForm = ''">Cancelar</AppButton><AppButton type="submit" :loading="submitting">Salvar despesa</AppButton></div>
        </form>

        <section v-if="canViewFinance" class="folder-financial__block">
            <h3>Contratos de honorários</h3>
            <div v-if="!financialStore.agreements.length" class="folder-financial__empty">Nenhum contrato cadastrado.</div>
            <article v-for="agreement in financialStore.agreements" :key="agreement.id" class="folder-financial__row">
                <div><strong>{{ agreement.client?.name ?? 'Sem cliente' }}</strong><span>{{ agreementLabel(agreement) }}</span></div>
                <span class="folder-financial__badge">{{ agreement.status === 'active' ? 'Ativo' : 'Rascunho' }}</span>
                <AppButton v-if="canManageFinance" size="sm" variant="ghost" @click="askDelete('agreement', agreement)">Excluir</AppButton>
            </article>
        </section>

        <section v-if="canViewTime" class="folder-financial__block">
            <h3>Apontamentos de tempo</h3>
            <div v-if="!financialStore.timeEntries.length" class="folder-financial__empty">Nenhum apontamento registrado.</div>
            <article v-for="entry in financialStore.timeEntries" :key="entry.id" class="folder-financial__row">
                <div><strong>{{ entry.description }}</strong><span>{{ formatDate(entry.worked_on) }} · {{ entry.user?.name ?? '—' }}</span></div>
                <strong>{{ formatDuration(entry.duration_minutes) }}</strong>
                <AppButton v-if="canDeleteTime && entry.status === 'open'" size="sm" variant="ghost" @click="askDelete('time', entry)">Excluir</AppButton>
            </article>
        </section>

        <section v-if="canViewExpenses" class="folder-financial__block">
            <h3>Despesas</h3>
            <div v-if="!financialStore.expenses.length" class="folder-financial__empty">Nenhuma despesa registrada.</div>
            <article v-for="expense in financialStore.expenses" :key="expense.id" class="folder-financial__row">
                <div><strong>{{ expense.description }}</strong><span>{{ formatDate(expense.incurred_on) }} · {{ expense.user?.name ?? '—' }}</span></div>
                <strong>{{ formatMoney(expense.amount_cents) }}</strong>
                <AppButton v-if="canDeleteExpense && expense.status === 'open'" size="sm" variant="ghost" @click="askDelete('expense', expense)">Excluir</AppButton>
            </article>
        </section>

        <AppConfirmDialog :open="Boolean(pendingDelete)" title="Excluir registro" message="Esta ação não poderá ser desfeita." confirm-label="Excluir" :loading="deleting" @confirm="confirmDelete" @cancel="pendingDelete = null" />
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { AppButton, AppConfirmDialog } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useFolderFinancialStore } from '@/stores/folder-financial.js'

const props = defineProps({ folderId: { type: [Number, String], required: true }, clients: { type: Array, default: () => [] } })
const authStore = useAuthStore()
const financialStore = useFolderFinancialStore()
const activeForm = ref('')
const error = ref('')
const submitting = ref(false)
const deleting = ref(false)
const pendingDelete = ref(null)
const today = () => new Date().toLocaleDateString('en-CA')
const agreementForm = reactive({ client_id: '', type: 'hourly', hourly_rate: null, fixed_fee: null, contingency_percentage: null })
const timeForm = reactive({ worked_on: today(), duration_minutes: 60, description: '', billable: true })
const expenseForm = reactive({ incurred_on: today(), amount: null, description: '', reimbursable: true })
const allowed = (permission) => authStore.hasPermission(permission)
const canViewFinance = computed(() => allowed('finance.view'))
const canManageFinance = computed(() => allowed('finance.manage'))
const canViewTime = computed(() => allowed('time-entries.view'))
const canCreateTime = computed(() => allowed('time-entries.create'))
const canDeleteTime = computed(() => allowed('time-entries.delete'))
const canViewExpenses = computed(() => allowed('expenses.view'))
const canCreateExpense = computed(() => allowed('expenses.create'))
const canDeleteExpense = computed(() => allowed('expenses.delete'))
const formatMoney = (cents) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(cents || 0) / 100)
const formatDate = (value) => value ? new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(value)) : '—'
const formatDuration = (minutes) => `${Math.floor(Number(minutes || 0) / 60)}h ${Number(minutes || 0) % 60}min`
const agreementLabel = (item) => ({ hourly: `${formatMoney(item.hourly_rate_cents)}/hora`, fixed: formatMoney(item.fixed_fee_cents), contingency: `${item.contingency_percentage}% de êxito`, hybrid: 'Modelo híbrido' })[item.type] ?? item.type
function toggleForm(name) { activeForm.value = activeForm.value === name ? '' : name; error.value = '' }
async function submit(action) { submitting.value = true; error.value = ''; try { await action(); activeForm.value = '' } catch (exception) { error.value = exception?.response?.data?.message ?? Object.values(exception?.response?.data?.errors ?? {})[0]?.[0] ?? 'Não foi possível salvar o registro.' } finally { submitting.value = false } }
function submitAgreement() { return submit(() => financialStore.createAgreement(props.folderId, { client_id: Number(agreementForm.client_id), type: agreementForm.type, status: 'active', hourly_rate_cents: agreementForm.hourly_rate == null ? null : Math.round(agreementForm.hourly_rate * 100), fixed_fee_cents: agreementForm.fixed_fee == null ? null : Math.round(agreementForm.fixed_fee * 100), contingency_percentage: agreementForm.contingency_percentage })) }
function submitTime() { return submit(() => financialStore.createTime(props.folderId, { ...timeForm })) }
function submitExpense() { return submit(() => financialStore.createExpense(props.folderId, { incurred_on: expenseForm.incurred_on, description: expenseForm.description, amount_cents: Math.round(expenseForm.amount * 100), reimbursable: expenseForm.reimbursable })) }
function askDelete(type, item) { pendingDelete.value = { type, item } }
async function confirmDelete() { deleting.value = true; error.value = ''; try { const { type, item } = pendingDelete.value; await ({ agreement: financialStore.removeAgreement, time: financialStore.removeTime, expense: financialStore.removeExpense })[type](props.folderId, item.id); pendingDelete.value = null } catch (exception) { error.value = exception?.response?.data?.message ?? 'Não foi possível excluir o registro.' } finally { deleting.value = false } }
onMounted(() => financialStore.fetchAll(props.folderId, { finance: canViewFinance.value, time: canViewTime.value, expenses: canViewExpenses.value }).catch(() => { error.value = 'Não foi possível carregar as informações financeiras.' }))
</script>

<style scoped>
.folder-financial { display: flex; flex-direction: column; gap: var(--space-6); }
.folder-financial__header { display: flex; justify-content: space-between; gap: var(--space-4); }
.folder-financial h2, .folder-financial h3, .folder-financial p { margin: 0; }
.folder-financial__header p, .folder-financial__row span { color: var(--color-text-muted); }
.folder-financial__actions, .folder-financial__form-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: var(--space-2); }
.folder-financial__summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--space-3); }
.folder-financial__metric { display: flex; padding: var(--space-4); flex-direction: column; gap: var(--space-2); background: var(--color-surface-muted); border: 1px solid var(--color-border); border-radius: var(--radius-lg); }
.folder-financial__metric span { color: var(--color-text-muted); font-size: var(--font-size-sm); }
.folder-financial__metric strong { color: var(--color-brand); font-size: 1.35rem; }
.folder-financial__metric--brand { background: var(--color-surface-secondary-soft); }
.folder-financial__form { display: flex; padding: var(--space-5); flex-direction: column; gap: var(--space-4); background: var(--color-surface-soft); border: 1px solid var(--color-border); border-radius: var(--radius-lg); }
.folder-financial__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-4); }
.folder-financial__grid label { display: flex; flex-direction: column; gap: var(--space-2); color: var(--color-text-soft); font-size: var(--font-size-sm); font-weight: 600; }
.folder-financial__grid input, .folder-financial__grid select { min-height: 2.65rem; padding: 0 var(--space-3); color: var(--color-text); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); font: inherit; }
.folder-financial__grid .folder-financial__check { flex-direction: row; align-items: center; }
.folder-financial__check input { min-height: auto; }
.folder-financial__wide { grid-column: 1 / -1; }
.folder-financial__block { display: flex; flex-direction: column; gap: var(--space-3); }
.folder-financial__row { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; padding: var(--space-3) var(--space-4); align-items: center; gap: var(--space-4); border: 1px solid var(--color-border); border-radius: var(--radius-md); }
.folder-financial__row > div { display: flex; min-width: 0; flex-direction: column; gap: var(--space-1); }
.folder-financial__badge { padding: 0.25rem 0.55rem; color: var(--color-brand-secondary) !important; background: var(--color-surface-secondary-soft); border-radius: 999px; font-size: var(--font-size-xs); }
.folder-financial__empty, .folder-financial__loading { padding: var(--space-5); color: var(--color-text-muted); text-align: center; border: 1px dashed var(--color-border); border-radius: var(--radius-md); }
.folder-financial__alert { padding: var(--space-3); color: var(--color-danger); background: var(--color-danger-soft); border-radius: var(--radius-md); }
@media (max-width: 760px) { .folder-financial__header { flex-direction: column; } .folder-financial__actions { justify-content: flex-start; } .folder-financial__summary, .folder-financial__grid { grid-template-columns: 1fr; } .folder-financial__wide { grid-column: auto; } }
</style>
