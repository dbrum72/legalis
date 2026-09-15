<template>
    <section class="folder-financial">
        <header class="folder-financial__header">
            <div>
                <h2>Financeiro</h2>
                <p>Honorários, horas e despesas vinculados a esta pasta.</p>
            </div>
            <div class="folder-financial__actions">
                <AppButton
                    v-if="canGenerateBilling"
                    size="sm"
                    variant="navigation"
                    @click="toggleForm('billing')"
                    >Gerar cobrança</AppButton
                >
                <AppButton
                    v-if="canManageFinance"
                    size="sm"
                    variant="outline"
                    @click="toggleForm('agreement')"
                    >Novo contrato</AppButton
                >
                <AppButton
                    v-if="canCreateTime"
                    size="sm"
                    variant="outline"
                    @click="toggleForm('time')"
                    >Apontar horas</AppButton
                >
                <AppButton v-if="canCreateExpense" size="sm" @click="toggleForm('expense')"
                    >Nova despesa</AppButton
                >
            </div>
        </header>

        <div v-if="error" class="folder-financial__alert" role="alert">{{ error }}</div>
        <div v-if="success" class="folder-financial__success" role="status">{{ success }}</div>
        <p v-if="financialStore.loading" class="folder-financial__loading">
            Carregando informações financeiras...
        </p>

        <div v-else class="folder-financial__summary">
            <article v-if="canViewTime" class="folder-financial__metric">
                <span>Horas faturáveis em aberto</span>
                <strong>{{ formatDuration(financialStore.billableMinutes) }}</strong>
            </article>
            <article
                v-if="canViewFinance"
                class="folder-financial__metric folder-financial__metric--brand"
            >
                <span>Honorários por horas</span>
                <strong>{{ formatMoney(financialStore.billableTimeCents) }}</strong>
            </article>
            <article v-if="canViewExpenses" class="folder-financial__metric">
                <span>Despesas reembolsáveis</span>
                <strong>{{ formatMoney(financialStore.reimbursableExpenseCents) }}</strong>
            </article>
        </div>

        <form
            v-if="activeForm === 'billing'"
            class="folder-financial__form"
            @submit.prevent="submitBilling"
        >
            <div>
                <h3>Gerar cobrança dos lançamentos</h3>
                <p>Selecione horas e despesas que serão encerradas como faturadas.</p>
            </div>
            <div class="folder-financial__grid">
                <label
                    >Cliente<select v-model="billingForm.client_id" required>
                        <option value="" disabled>Selecione</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                        </option>
                    </select></label
                >
                <label
                    >Contrato<select v-model="billingForm.fee_agreement_id">
                        <option value="">Sem contrato</option>
                        <option
                            v-for="agreement in activeAgreements"
                            :key="agreement.id"
                            :value="agreement.id"
                        >
                            {{ agreement.client?.name }} · {{ agreementLabel(agreement) }}
                        </option>
                    </select></label
                >
                <label>Vencimento<input v-model="billingForm.due_on" type="date" required /></label>
                <AppCurrency
                    v-model="billingForm.discount"
                    id="billing-discount"
                    label="Desconto"
                    :min="0"
                    :max="billingSubtotal / 100"
                    :allow-empty="false"
                    shift-decimal
                />
            </div>
            <fieldset class="folder-financial__billing-items">
                <legend>Horas faturáveis</legend>
                <label v-for="entry in billableEntries" :key="entry.id"
                    ><input
                        v-model="billingForm.time_entry_ids"
                        type="checkbox"
                        :value="entry.id"
                    /><span
                        ><strong>{{ entry.description }}</strong
                        ><small
                            >{{ formatDate(entry.worked_on) }} ·
                            {{ formatDuration(entry.duration_minutes) }}</small
                        ></span
                    ><b>{{ formatMoney(entryAmount(entry)) }}</b></label
                >
                <p v-if="!billableEntries.length">Nenhuma hora disponível.</p>
            </fieldset>
            <fieldset class="folder-financial__billing-items">
                <legend>Despesas reembolsáveis</legend>
                <label v-for="expense in billableExpenses" :key="expense.id"
                    ><input
                        v-model="billingForm.expense_ids"
                        type="checkbox"
                        :value="expense.id"
                    /><span
                        ><strong>{{ expense.description }}</strong
                        ><small v-if="expense.financial_category || expense.cost_center">{{
                            [expense.financial_category?.name, expense.cost_center?.name]
                                .filter(Boolean)
                                .join(' · ')
                        }}</small
                        ><small>{{ formatDate(expense.incurred_on) }}</small></span
                    ><b>{{ formatMoney(expense.amount_cents) }}</b></label
                >
                <p v-if="!billableExpenses.length">Nenhuma despesa disponível.</p>
            </fieldset>
            <div class="folder-financial__billing-total">
                <span>Total selecionado</span><strong>{{ formatMoney(billingTotal) }}</strong>
            </div>
            <div class="folder-financial__form-actions">
                <AppButton type="button" variant="ghost" @click="closeForm">Cancelar</AppButton
                ><AppButton type="submit" :loading="submitting" :disabled="billingSubtotal < 1"
                    >Gerar cobrança</AppButton
                >
            </div>
        </form>

        <form
            v-if="activeForm === 'agreement'"
            class="folder-financial__form"
            @submit.prevent="submitAgreement"
        >
            <h3>
                {{
                    editingAgreement
                        ? 'Editar contrato de honorários'
                        : 'Novo contrato de honorários'
                }}
            </h3>
            <div class="folder-financial__grid">
                <label
                    >Cliente<select v-model="agreementForm.client_id" required>
                        <option value="" disabled>Selecione</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.name }}
                        </option>
                    </select></label
                >
                <label
                    >Modalidade<select v-model="agreementForm.type">
                        <option value="hourly">Por hora</option>
                        <option value="fixed">Valor fixo</option>
                        <option value="contingency">Êxito</option>
                        <option value="hybrid">Híbrido</option>
                    </select></label
                >
                <AppCurrency
                    v-if="['hourly', 'hybrid'].includes(agreementForm.type)"
                    v-model="agreementForm.hourly_rate"
                    id="agreement-hourly-rate"
                    label="Valor por hora"
                    :min="0"
                    :allow-empty="false"
                    shift-decimal
                />
                <AppCurrency
                    v-if="['fixed', 'hybrid'].includes(agreementForm.type)"
                    v-model="agreementForm.fixed_fee"
                    id="agreement-fixed-fee"
                    label="Valor fixo"
                    :min="0"
                    :allow-empty="false"
                    shift-decimal
                />
                <label v-if="['contingency', 'hybrid'].includes(agreementForm.type)"
                    >Êxito (%)<input
                        v-model.number="agreementForm.contingency_percentage"
                        type="number"
                        min="0.01"
                        max="100"
                        step="0.01"
                /></label>
                <label
                    >Status<select v-model="agreementForm.status">
                        <option value="draft">Rascunho</option>
                        <option value="active">Ativo</option>
                        <option value="closed">Encerrado</option>
                        <option value="cancelled">Cancelado</option>
                    </select></label
                >
                <label
                    >Dia de cobrança<input
                        v-model.number="agreementForm.billing_day"
                        type="number"
                        min="1"
                        max="31"
                        placeholder="Ex.: 10"
                /></label>
                <label
                    >Início da vigência<input v-model="agreementForm.starts_on" type="date"
                /></label>
                <label
                    >Fim da vigência<input
                        v-model="agreementForm.ends_on"
                        type="date"
                        :min="agreementForm.starts_on"
                /></label>
                <label class="folder-financial__wide"
                    >Observações<textarea
                        v-model.trim="agreementForm.notes"
                        rows="3"
                        maxlength="10000"
                    />
                </label>
            </div>
            <div class="folder-financial__form-actions">
                <AppButton type="button" variant="ghost" @click="closeForm">Cancelar</AppButton
                ><AppButton type="submit" :loading="submitting">{{
                    editingAgreement ? 'Salvar alterações' : 'Salvar contrato'
                }}</AppButton>
            </div>
        </form>

        <form
            v-if="activeForm === 'time'"
            class="folder-financial__form"
            @submit.prevent="submitTime"
        >
            <h3>{{ editingTime ? 'Editar apontamento' : 'Novo apontamento' }}</h3>
            <div class="folder-financial__grid">
                <label>Data<input v-model="timeForm.worked_on" type="date" required /></label>
                <label
                    >Duração (minutos)<input
                        v-model.number="timeForm.duration_minutes"
                        type="number"
                        min="1"
                        max="1440"
                        required
                /></label>
                <label class="folder-financial__wide"
                    >Descrição<input v-model.trim="timeForm.description" maxlength="500" required
                /></label>
                <label class="folder-financial__check"
                    ><input v-model="timeForm.billable" type="checkbox" /> Faturável</label
                >
            </div>
            <div class="folder-financial__form-actions">
                <AppButton type="button" variant="ghost" @click="closeForm">Cancelar</AppButton
                ><AppButton type="submit" :loading="submitting">{{
                    editingTime ? 'Salvar alterações' : 'Salvar apontamento'
                }}</AppButton>
            </div>
        </form>

        <form
            v-if="activeForm === 'expense'"
            class="folder-financial__form"
            @submit.prevent="submitExpense"
        >
            <h3>{{ editingExpense ? 'Editar despesa' : 'Nova despesa' }}</h3>
            <div class="folder-financial__grid">
                <label>Data<input v-model="expenseForm.incurred_on" type="date" required /></label>
                <AppCurrency
                    v-model="expenseForm.amount"
                    id="expense-amount"
                    label="Valor"
                    :min="0.01"
                    :allow-empty="false"
                    shift-decimal
                    required
                />
                <label class="folder-financial__wide"
                    >Descrição<input
                        v-model.trim="expenseForm.description"
                        maxlength="500"
                        required
                /></label>
                <ClassificationFields
                    prefix="expense"
                    v-model:category-id="expenseForm.category_id"
                    v-model:cost-center-id="expenseForm.cost_center_id"
                />
                <p class="folder-financial__wide">
                    Reembolsável: pode ser incluída na cobrança ao cliente desta pasta. Não
                    reembolsável: fica fora do faturamento. O pagamento ao fornecedor é registrado
                    em contas a pagar; não registre a mesma saída duas vezes.
                </p>
                <label class="folder-financial__check"
                    ><input v-model="expenseForm.reimbursable" type="checkbox" />
                    Reembolsável</label
                >
            </div>
            <div class="folder-financial__form-actions">
                <AppButton type="button" variant="ghost" @click="closeForm">Cancelar</AppButton
                ><AppButton type="submit" :loading="submitting">{{
                    editingExpense ? 'Salvar alterações' : 'Salvar despesa'
                }}</AppButton>
            </div>
        </form>

        <section v-if="canViewFinance" class="folder-financial__block">
            <h3>Contratos de honorários</h3>
            <div v-if="!financialStore.agreements.length" class="folder-financial__empty">
                Nenhum contrato cadastrado.
            </div>
            <article
                v-for="agreement in financialStore.agreements"
                :key="agreement.id"
                class="folder-financial__row"
            >
                <div>
                    <strong>{{ agreement.client?.name ?? 'Sem cliente' }}</strong
                    ><span>{{ agreementLabel(agreement) }}</span
                    ><small v-if="agreement.starts_on || agreement.ends_on"
                        >Vigência: {{ formatDate(agreement.starts_on) }} a
                        {{ formatDate(agreement.ends_on) }}</small
                    >
                </div>
                <span class="folder-financial__badge">{{
                    agreementStatusLabel(agreement.status)
                }}</span>
                <div v-if="canManageFinance" class="folder-financial__row-actions">
                    <AppButton size="sm" variant="outline" @click="editAgreement(agreement)"
                        >Editar</AppButton
                    ><AppButton size="sm" variant="ghost" @click="askDelete('agreement', agreement)"
                        >Excluir</AppButton
                    >
                </div>
            </article>
        </section>

        <section v-if="canViewTime" class="folder-financial__block">
            <h3>Apontamentos de tempo</h3>
            <div v-if="!financialStore.timeEntries.length" class="folder-financial__empty">
                Nenhum apontamento registrado.
            </div>
            <article
                v-for="entry in financialStore.timeEntries"
                :key="entry.id"
                class="folder-financial__row"
            >
                <div>
                    <strong>{{ entry.description }}</strong
                    ><span>{{ formatDate(entry.worked_on) }} · {{ entry.user?.name ?? '—' }}</span>
                </div>
                <strong>{{ formatDuration(entry.duration_minutes) }}</strong>
                <div v-if="entry.status === 'open'" class="folder-financial__row-actions">
                    <AppButton
                        v-if="canUpdateTime"
                        size="sm"
                        variant="outline"
                        @click="editTime(entry)"
                        >Editar</AppButton
                    ><AppButton
                        v-if="canDeleteTime"
                        size="sm"
                        variant="ghost"
                        @click="askDelete('time', entry)"
                        >Excluir</AppButton
                    >
                </div>
            </article>
        </section>

        <section v-if="canViewExpenses" class="folder-financial__block">
            <h3>Despesas</h3>
            <div v-if="!financialStore.expenses.length" class="folder-financial__empty">
                Nenhuma despesa registrada.
            </div>
            <article
                v-for="expense in financialStore.expenses"
                :key="expense.id"
                class="folder-financial__row"
            >
                <div>
                    <strong>{{ expense.description }}</strong
                    ><small v-if="expense.financial_category || expense.cost_center">{{
                        [expense.financial_category?.name, expense.cost_center?.name]
                            .filter(Boolean)
                            .join(' · ')
                    }}</small
                    ><span
                        >{{ formatDate(expense.incurred_on) }} ·
                        {{ expense.user?.name ?? '—' }}</span
                    >
                </div>
                <strong>{{ formatMoney(expense.amount_cents) }}</strong>
                <div v-if="expense.status === 'open'" class="folder-financial__row-actions">
                    <AppButton
                        v-if="canUpdateExpense"
                        size="sm"
                        variant="outline"
                        @click="editExpense(expense)"
                        >Editar</AppButton
                    ><AppButton
                        v-if="canDeleteExpense"
                        size="sm"
                        variant="ghost"
                        @click="askDelete('expense', expense)"
                        >Excluir</AppButton
                    >
                </div>
            </article>
        </section>

        <AppConfirmDialog
            :open="Boolean(pendingDelete)"
            title="Excluir registro"
            message="Esta ação não poderá ser desfeita."
            confirm-label="Excluir"
            :loading="deleting"
            @confirm="confirmDelete"
            @cancel="pendingDelete = null"
        />
    </section>
</template>

<script setup>
import ClassificationFields from '@/views/finance/components/ClassificationFields.vue'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { AppCurrency } from '@/components/forms'
import { AppButton, AppConfirmDialog } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useFolderFinancialStore } from '@/stores/folder-financial.js'

const props = defineProps({
    folderId: { type: [Number, String], required: true },
    clients: { type: Array, default: () => [] },
})
const authStore = useAuthStore()
const financialStore = useFolderFinancialStore()
const activeForm = ref('')
const error = ref('')
const success = ref('')
const submitting = ref(false)
const deleting = ref(false)
const pendingDelete = ref(null)
const editingAgreement = ref(null)
const editingTime = ref(null)
const editingExpense = ref(null)
const today = () => new Date().toLocaleDateString('en-CA')
const agreementForm = reactive({
    client_id: '',
    type: 'hourly',
    status: 'active',
    hourly_rate: 0,
    fixed_fee: 0,
    contingency_percentage: null,
    billing_day: null,
    starts_on: '',
    ends_on: '',
    notes: '',
})
const timeForm = reactive({
    worked_on: today(),
    duration_minutes: 60,
    description: '',
    billable: true,
})
const expenseForm = reactive({
    incurred_on: today(),
    amount: 0,
    description: '',
    category_id: '',
    cost_center_id: '',
    reimbursable: true,
})
const billingForm = reactive({
    client_id: '',
    fee_agreement_id: '',
    due_on: today(),
    discount: 0,
    time_entry_ids: [],
    expense_ids: [],
})
const allowed = (permission) => authStore.hasPermission(permission)
const canViewFinance = computed(() => allowed('finance.view'))
const canManageFinance = computed(() => allowed('finance.manage'))
const canViewTime = computed(() => allowed('time-entries.view'))
const canCreateTime = computed(() => allowed('time-entries.create'))
const canUpdateTime = computed(() => allowed('time-entries.update'))
const canDeleteTime = computed(() => allowed('time-entries.delete'))
const canViewExpenses = computed(() => allowed('expenses.view'))
const canCreateExpense = computed(() => allowed('expenses.create'))
const canUpdateExpense = computed(() => allowed('expenses.update'))
const canDeleteExpense = computed(() => allowed('expenses.delete'))
const billableEntries = computed(() =>
    financialStore.timeEntries.filter(
        (entry) => entry.billable && entry.status === 'open' && !entry.invoice_id,
    ),
)
const billableExpenses = computed(() =>
    financialStore.expenses.filter(
        (expense) => expense.reimbursable && expense.status === 'open' && !expense.invoice_id,
    ),
)
const activeAgreements = computed(() =>
    financialStore.agreements.filter((agreement) => agreement.status === 'active'),
)
const canGenerateBilling = computed(
    () =>
        canManageFinance.value &&
        (billableEntries.value.length > 0 || billableExpenses.value.length > 0),
)
const entryAmount = (entry) =>
    Math.round((Number(entry.duration_minutes || 0) * Number(entry.hourly_rate_cents || 0)) / 60)
const billingSubtotal = computed(
    () =>
        billableEntries.value
            .filter((entry) => billingForm.time_entry_ids.includes(entry.id))
            .reduce((total, entry) => total + entryAmount(entry), 0) +
        billableExpenses.value
            .filter((expense) => billingForm.expense_ids.includes(expense.id))
            .reduce((total, expense) => total + Number(expense.amount_cents), 0),
)
const billingTotal = computed(() =>
    Math.max(0, billingSubtotal.value - Math.round(Number(billingForm.discount || 0) * 100)),
)
const formatMoney = (cents) =>
    new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(
        Number(cents || 0) / 100,
    )
const formatDate = (value) =>
    value ? new Intl.DateTimeFormat('pt-BR', { timeZone: 'UTC' }).format(new Date(value)) : '—'
const formatDuration = (minutes) =>
    `${Math.floor(Number(minutes || 0) / 60)}h ${Number(minutes || 0) % 60}min`
function agreementLabel(item) {
    const terms = []
    if (item.hourly_rate_cents) terms.push(`${formatMoney(item.hourly_rate_cents)}/hora`)
    if (item.fixed_fee_cents) terms.push(formatMoney(item.fixed_fee_cents))
    if (item.contingency_percentage) terms.push(`${item.contingency_percentage}% de êxito`)
    return terms.join(' + ') || item.type
}
const agreementStatusLabel = (status) =>
    ({ draft: 'Rascunho', active: 'Ativo', closed: 'Encerrado', cancelled: 'Cancelado' })[status] ??
    status
function resetAgreementForm() {
    Object.assign(agreementForm, {
        client_id: '',
        type: 'hourly',
        status: 'active',
        hourly_rate: 0,
        fixed_fee: 0,
        contingency_percentage: null,
        billing_day: null,
        starts_on: '',
        ends_on: '',
        notes: '',
    })
    editingAgreement.value = null
}
function resetTimeForm() {
    Object.assign(timeForm, {
        worked_on: today(),
        duration_minutes: 60,
        description: '',
        billable: true,
    })
    editingTime.value = null
}
function resetExpenseForm() {
    Object.assign(expenseForm, {
        incurred_on: today(),
        amount: 0,
        description: '',
        category_id: '',
        cost_center_id: '',
        reimbursable: true,
    })
    editingExpense.value = null
}
function resetBillingForm() {
    Object.assign(billingForm, {
        client_id: props.clients[0]?.id ?? '',
        fee_agreement_id: '',
        due_on: today(),
        discount: 0,
        time_entry_ids: billableEntries.value.map((entry) => entry.id),
        expense_ids: billableExpenses.value.map((expense) => expense.id),
    })
}
function closeForm() {
    activeForm.value = ''
    editingAgreement.value = null
    editingTime.value = null
    editingExpense.value = null
}
function toggleForm(name) {
    if (activeForm.value === name) {
        closeForm()
        return
    }
    if (name === 'agreement') resetAgreementForm()
    if (name === 'time') resetTimeForm()
    if (name === 'expense') resetExpenseForm()
    if (name === 'billing') resetBillingForm()
    activeForm.value = name
    error.value = ''
    success.value = ''
}
function editAgreement(agreement) {
    editingAgreement.value = agreement
    Object.assign(agreementForm, {
        client_id: agreement.client_id ?? '',
        type: agreement.type,
        status: agreement.status,
        hourly_rate: Number(agreement.hourly_rate_cents || 0) / 100,
        fixed_fee: Number(agreement.fixed_fee_cents || 0) / 100,
        contingency_percentage:
            agreement.contingency_percentage == null
                ? null
                : Number(agreement.contingency_percentage),
        billing_day: agreement.billing_day,
        starts_on: String(agreement.starts_on || '').slice(0, 10),
        ends_on: String(agreement.ends_on || '').slice(0, 10),
        notes: agreement.notes || '',
    })
    activeForm.value = 'agreement'
    error.value = ''
}
function editTime(entry) {
    editingTime.value = entry
    Object.assign(timeForm, {
        worked_on: String(entry.worked_on).slice(0, 10),
        duration_minutes: entry.duration_minutes,
        description: entry.description,
        billable: Boolean(entry.billable),
    })
    activeForm.value = 'time'
    error.value = ''
}
function editExpense(expense) {
    editingExpense.value = expense
    Object.assign(expenseForm, {
        incurred_on: String(expense.incurred_on).slice(0, 10),
        amount: Number(expense.amount_cents) / 100,
        description: expense.description,
        category_id: expense.category_id || '',
        cost_center_id: expense.cost_center_id || '',
        reimbursable: Boolean(expense.reimbursable),
    })
    activeForm.value = 'expense'
    error.value = ''
}
async function submit(action) {
    submitting.value = true
    error.value = ''
    try {
        await action()
        activeForm.value = ''
    } catch (exception) {
        error.value =
            exception?.response?.data?.message ??
            Object.values(exception?.response?.data?.errors ?? {})[0]?.[0] ??
            'Não foi possível salvar o registro.'
    } finally {
        submitting.value = false
    }
}
function submitAgreement() {
    const payload = {
        client_id: Number(agreementForm.client_id),
        type: agreementForm.type,
        status: agreementForm.status,
        hourly_rate_cents: ['hourly', 'hybrid'].includes(agreementForm.type)
            ? Math.round(agreementForm.hourly_rate * 100)
            : null,
        fixed_fee_cents: ['fixed', 'hybrid'].includes(agreementForm.type)
            ? Math.round(agreementForm.fixed_fee * 100)
            : null,
        contingency_percentage: ['contingency', 'hybrid'].includes(agreementForm.type)
            ? agreementForm.contingency_percentage
            : null,
        billing_day: agreementForm.billing_day || null,
        starts_on: agreementForm.starts_on || null,
        ends_on: agreementForm.ends_on || null,
        notes: agreementForm.notes || null,
    }
    return submit(() =>
        editingAgreement.value
            ? financialStore.updateAgreement(props.folderId, editingAgreement.value.id, payload)
            : financialStore.createAgreement(props.folderId, payload),
    )
}
function submitTime() {
    const payload = { ...timeForm }
    return submit(() =>
        editingTime.value
            ? financialStore.updateTime(props.folderId, editingTime.value.id, payload)
            : financialStore.createTime(props.folderId, payload),
    )
}
function submitExpense() {
    const payload = {
        incurred_on: expenseForm.incurred_on,
        description: expenseForm.description,
        amount_cents: Math.round(expenseForm.amount * 100),
        reimbursable: expenseForm.reimbursable,
        category_id: expenseForm.category_id || null,
        cost_center_id: expenseForm.cost_center_id || null,
    }
    return submit(() =>
        editingExpense.value
            ? financialStore.updateExpense(props.folderId, editingExpense.value.id, payload)
            : financialStore.createExpense(props.folderId, payload),
    )
}
async function submitBilling() {
    submitting.value = true
    error.value = ''
    success.value = ''
    try {
        const invoice = await financialStore.createBilling(props.folderId, {
            client_id: Number(billingForm.client_id),
            fee_agreement_id: billingForm.fee_agreement_id
                ? Number(billingForm.fee_agreement_id)
                : null,
            due_on: billingForm.due_on,
            discount_cents: Math.round(Number(billingForm.discount || 0) * 100),
            time_entry_ids: billingForm.time_entry_ids,
            expense_ids: billingForm.expense_ids,
        })
        closeForm()
        success.value = `Cobrança ${invoice.charge_identifier} gerada com sucesso.`
    } catch (exception) {
        error.value =
            exception?.response?.data?.message ??
            Object.values(exception?.response?.data?.errors ?? {})[0]?.[0] ??
            'Não foi possível gerar a cobrança.'
    } finally {
        submitting.value = false
    }
}
function askDelete(type, item) {
    pendingDelete.value = { type, item }
}
async function confirmDelete() {
    deleting.value = true
    error.value = ''
    try {
        const { type, item } = pendingDelete.value
        await {
            agreement: financialStore.removeAgreement,
            time: financialStore.removeTime,
            expense: financialStore.removeExpense,
        }[type](props.folderId, item.id)
        pendingDelete.value = null
    } catch (exception) {
        error.value = exception?.response?.data?.message ?? 'Não foi possível excluir o registro.'
    } finally {
        deleting.value = false
    }
}
watch(
    () => billingForm.fee_agreement_id,
    (id) => {
        const agreement = activeAgreements.value.find((item) => Number(item.id) === Number(id))
        if (agreement?.client_id) billingForm.client_id = agreement.client_id
    },
)
onMounted(() =>
    financialStore
        .fetchAll(props.folderId, {
            finance: canViewFinance.value,
            time: canViewTime.value,
            expenses: canViewExpenses.value,
        })
        .catch(() => {
            error.value = 'Não foi possível carregar as informações financeiras.'
        }),
)
</script>

<style scoped>
.folder-financial {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}
.folder-financial__header {
    display: flex;
    justify-content: space-between;
    gap: var(--space-4);
}
.folder-financial h2,
.folder-financial h3,
.folder-financial p {
    margin: 0;
}
.folder-financial__header p,
.folder-financial__row span {
    color: var(--color-text-muted);
}
.folder-financial__actions,
.folder-financial__form-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}
.folder-financial__summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: var(--space-3);
}
.folder-financial__metric {
    display: flex;
    padding: var(--space-4);
    flex-direction: column;
    gap: var(--space-2);
    background: var(--color-surface-muted);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}
.folder-financial__metric span {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
.folder-financial__metric strong {
    color: var(--color-brand);
    font-size: 1.35rem;
}
.folder-financial__metric--brand {
    background: var(--color-surface-secondary-soft);
}
.folder-financial__form {
    display: flex;
    padding: var(--space-5);
    flex-direction: column;
    gap: var(--space-4);
    background: var(--color-surface-soft);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
}
.folder-financial__grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-4);
}
.folder-financial__grid label {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
    font-weight: 600;
}
.folder-financial__grid input,
.folder-financial__grid select,
.folder-financial__grid textarea {
    min-height: 2.65rem;
    padding: var(--space-2) var(--space-3);
    color: var(--color-text);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font: inherit;
}
.folder-financial__grid .folder-financial__check {
    flex-direction: row;
    align-items: center;
}
.folder-financial__check input {
    min-height: auto;
}
.folder-financial__wide {
    grid-column: 1 / -1;
}
.folder-financial__block {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}
.folder-financial__row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    padding: var(--space-3) var(--space-4);
    align-items: center;
    gap: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.folder-financial__row > div {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}
.folder-financial__row small {
    color: var(--color-text-muted);
}
.folder-financial__row-actions {
    flex-direction: row !important;
    gap: var(--space-2) !important;
}
.folder-financial__badge {
    padding: 0.25rem 0.55rem;
    color: var(--color-brand-secondary) !important;
    background: var(--color-surface-secondary-soft);
    border-radius: 999px;
    font-size: var(--font-size-xs);
}
.folder-financial__empty,
.folder-financial__loading {
    padding: var(--space-5);
    color: var(--color-text-muted);
    text-align: center;
    border: 1px dashed var(--color-border);
    border-radius: var(--radius-md);
}
.folder-financial__alert {
    padding: var(--space-3);
    color: var(--color-danger);
    background: var(--color-danger-soft);
    border-radius: var(--radius-md);
}
.folder-financial__success {
    padding: var(--space-3);
    color: var(--color-success);
    background: var(--color-success-soft);
    border-radius: var(--radius-md);
}
.folder-financial__billing-items {
    display: flex;
    padding: 0;
    flex-direction: column;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}
.folder-financial__billing-items legend {
    margin-left: var(--space-3);
    padding: 0 var(--space-2);
    color: var(--color-text-soft);
    font-weight: 700;
}
.folder-financial__billing-items > label {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    padding: var(--space-3) var(--space-4);
    align-items: center;
    gap: var(--space-3);
    border-bottom: 1px solid var(--color-border);
    cursor: pointer;
}
.folder-financial__billing-items > label:last-of-type {
    border-bottom: 0;
}
.folder-financial__billing-items > label span {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.folder-financial__billing-items small,
.folder-financial__billing-items > p {
    color: var(--color-text-muted);
}
.folder-financial__billing-items > p {
    margin: 0;
    padding: var(--space-4);
}
.folder-financial__billing-total {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: var(--space-3);
}
.folder-financial__billing-total strong {
    color: var(--color-brand-secondary);
    font-size: 1.4rem;
}
@media (max-width: 760px) {
    .folder-financial__header {
        flex-direction: column;
    }
    .folder-financial__actions {
        justify-content: flex-start;
    }
    .folder-financial__summary,
    .folder-financial__grid {
        grid-template-columns: 1fr;
    }
    .folder-financial__wide {
        grid-column: auto;
    }
}
</style>
