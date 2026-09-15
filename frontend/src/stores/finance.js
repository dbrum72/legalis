import { ref } from 'vue'
import { defineStore } from 'pinia'
import {
    cancelInvoice as cancelInvoiceRequest,
    cancelPayable,
    cancelPayablePayment,
    cancelPayment as cancelPaymentRequest,
    createInvoice,
    createInvoiceInstallment,
    createInvoiceReminder,
    createPayable,
    createPayablePayment,
    createPayment,
    createReminderRule,
    deleteInvoice,
    deletePayable,
    deleteReminderRule,
    exportFinancialReport as exportFinancialReportRequest,
    exportInvoices as exportInvoicesRequest,
    getFinancialSummary,
    getInvoice,
    getPaymentReceipt,
    listInvoices,
    listPayables,
    listReminderRules,
    sendPaymentReceipt,
    updateInvoice as updateInvoiceRequest,
    updatePayable,
    updateReminderRule,
} from '@/api/finance.js'

export const useFinanceStore = defineStore('finance', () => {
    const emptySummary = () => ({
        receivable_cents: 0,
        overdue_cents: 0,
        overdue_count: 0,
        received_this_month_cents: 0,
        aging: {},
        forecast: {},
        forecast_90_days_cents: 0,
        monthly_performance: [],
        performance_totals: {
            billed_cents: 0,
            received_cents: 0,
            paid_expenses_cents: 0,
            cash_result_cents: 0,
        },
        overdue_clients: [],
    })
    const summary = ref(emptySummary())
    const invoices = ref([])
    const loading = ref(false)
    const activeFilters = ref({})
    const pagination = ref({ currentPage: 1, lastPage: 1, perPage: 15, total: 0, from: 0, to: 0 })
    const invoiceDetails = ref(null)
    const loadingDetails = ref(false)
    const exporting = ref(false)
    const exportingReport = ref(false)
    const reminderRules = ref([])
    const payables = ref([])
    const loadingPayables = ref(false)
    const payablesError = ref('')
    const activePayableFilters = ref({})
    let payableRequest = 0

    function agingFromInvoices(items) {
        const buckets = Object.fromEntries(
            ['current', 'days_1_30', 'days_31_60', 'days_61_90', 'over_90'].map((key) => [
                key,
                { count: 0, balance_cents: 0 },
            ]),
        )
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        items
            .filter(
                (invoice) =>
                    ['open', 'partial'].includes(invoice.status) &&
                    Number(invoice.balance_cents) > 0,
            )
            .forEach((invoice) => {
                const due = new Date(`${String(invoice.due_on).slice(0, 10)}T00:00:00`)
                const overdueDays = Math.floor((today - due) / 86400000)
                const key =
                    overdueDays <= 0
                        ? 'current'
                        : overdueDays <= 30
                          ? 'days_1_30'
                          : overdueDays <= 60
                            ? 'days_31_60'
                            : overdueDays <= 90
                              ? 'days_61_90'
                              : 'over_90'
                buckets[key].count += 1
                buckets[key].balance_cents += Number(invoice.balance_cents)
            })
        return buckets
    }

    async function fetchAll(filters = activeFilters.value, page = 1) {
        loading.value = true
        activeFilters.value = { ...filters }
        try {
            const [summaryResponse, invoicesResponse] = await Promise.all([
                getFinancialSummary(),
                listInvoices({ ...activeFilters.value, page, per_page: pagination.value.perPage }),
            ])
            const invoicePayload = invoicesResponse.data
            invoices.value = Array.isArray(invoicePayload)
                ? invoicePayload
                : (invoicePayload.data ?? [])
            pagination.value = Array.isArray(invoicePayload)
                ? {
                      ...pagination.value,
                      currentPage: 1,
                      lastPage: 1,
                      total: invoicePayload.length,
                      from: invoicePayload.length ? 1 : 0,
                      to: invoicePayload.length,
                  }
                : {
                      currentPage: Number(invoicePayload.current_page || 1),
                      lastPage: Number(invoicePayload.last_page || 1),
                      perPage: Number(invoicePayload.per_page || pagination.value.perPage),
                      total: Number(invoicePayload.total || 0),
                      from: Number(invoicePayload.from || 0),
                      to: Number(invoicePayload.to || 0),
                  }
            const receivedSummary = summaryResponse.data
            const agingBalance = Object.values(receivedSummary.aging ?? {}).reduce(
                (total, bucket) => total + Number(bucket?.balance_cents || 0),
                0,
            )
            summary.value = {
                ...receivedSummary,
                aging:
                    agingBalance > 0 || Number(receivedSummary.receivable_cents || 0) === 0
                        ? (receivedSummary.aging ?? {})
                        : agingFromInvoices(invoices.value),
            }
        } finally {
            loading.value = false
        }
    }

    async function fetchPage(page) {
        const target = Math.min(Math.max(1, Number(page)), pagination.value.lastPage)
        if (target === pagination.value.currentPage || loading.value) return
        await fetchAll(activeFilters.value, target)
    }

    async function addInvoice(payload) {
        const { data } = await createInvoice(payload)
        await fetchAll()
        return data
    }

    async function updateInvoice(id, payload) {
        const { data } = await updateInvoiceRequest(id, payload)
        await fetchAll()
        if (Number(invoiceDetails.value?.id) === Number(id)) invoiceDetails.value = data
        return data
    }

    async function fetchInvoice(id) {
        loadingDetails.value = true
        try {
            const { data } = await getInvoice(id)
            invoiceDetails.value = data
            return data
        } finally {
            loadingDetails.value = false
        }
    }

    function clearInvoiceDetails() {
        invoiceDetails.value = null
    }

    async function addInstallment(invoiceId, payload) {
        const { data } = await createInvoiceInstallment(invoiceId, payload)
        await fetchAll()
        return data
    }

    async function addPayment(invoiceId, payload) {
        await createPayment(invoiceId, payload)
        await fetchAll()
    }

    async function cancelPayment(invoiceId, paymentId, payload) {
        const { data } = await cancelPaymentRequest(invoiceId, paymentId, payload)
        await Promise.all([fetchAll(), fetchInvoice(invoiceId)])
        return data
    }

    async function paymentReceipt(invoiceId, paymentId) {
        const { data } = await getPaymentReceipt(invoiceId, paymentId)
        return data
    }
    async function deliverPaymentReceipt(invoiceId, paymentId, payload) {
        const { data } = await sendPaymentReceipt(invoiceId, paymentId, payload)
        await fetchInvoice(invoiceId)
        return data
    }

    async function sendReminder(invoiceId, payload) {
        const { data } = await createInvoiceReminder(invoiceId, payload)
        await Promise.all([
            fetchAll(activeFilters.value, pagination.value.currentPage),
            Number(invoiceDetails.value?.id) === Number(invoiceId)
                ? fetchInvoice(invoiceId)
                : Promise.resolve(),
        ])
        return data
    }

    async function fetchReminderRules() {
        const { data } = await listReminderRules()
        reminderRules.value = data
    }
    async function fetchPayables(filters = activePayableFilters.value) {
        const request = ++payableRequest
        activePayableFilters.value = { ...filters }
        loadingPayables.value = true
        payablesError.value = ''
        try {
            const { data } = await listPayables({ ...filters })
            if (request === payableRequest) payables.value = data
        } catch (exception) {
            if (request === payableRequest) {
                payablesError.value =
                    exception.response?.data?.message ||
                    'Não foi possível carregar as contas a pagar.'
            }
            throw exception
        } finally {
            if (request === payableRequest) loadingPayables.value = false
        }
    }
    async function addPayable(payload) {
        await createPayable(payload)
        await Promise.all([fetchPayables(), refreshSummary()])
    }
    async function payPayable(id, payload) {
        await createPayablePayment(id, payload)
        await Promise.all([fetchPayables(), refreshSummary()])
    }
    async function editPayable(id, payload) {
        await updatePayable(id, payload)
        await Promise.all([fetchPayables(), refreshSummary()])
    }
    async function removePayable(id) {
        await deletePayable(id)
        await Promise.all([fetchPayables(), refreshSummary()])
    }
    async function voidPayable(id, payload) {
        await cancelPayable(id, payload)
        await Promise.all([fetchPayables(), refreshSummary()])
    }
    async function voidPayablePayment(id, paymentId, payload) {
        await cancelPayablePayment(id, paymentId, payload)
        await Promise.all([fetchPayables(), refreshSummary()])
    }

    async function saveReminderRule(payload, id = null) {
        if (id) await updateReminderRule(id, payload)
        else await createReminderRule(payload)
        await fetchReminderRules()
    }

    async function removeReminderRule(id) {
        await deleteReminderRule(id)
        reminderRules.value = reminderRules.value.filter((rule) => Number(rule.id) !== Number(id))
    }

    async function cancelInvoice(invoiceId, payload) {
        const { data } = await cancelInvoiceRequest(invoiceId, payload)
        await fetchAll()
        return data
    }

    async function removeInvoice(id) {
        await deleteInvoice(id)
        invoices.value = invoices.value.filter((item) => Number(item.id) !== Number(id))
        await refreshSummary()
    }

    async function refreshSummary() {
        const { data } = await getFinancialSummary()
        summary.value = data
    }

    async function exportInvoices(filters = activeFilters.value) {
        exporting.value = true
        try {
            const { data } = await exportInvoicesRequest(filters)
            return data
        } finally {
            exporting.value = false
        }
    }

    async function exportFinancialReport() {
        exportingReport.value = true
        try {
            const { data } = await exportFinancialReportRequest()
            return data
        } finally {
            exportingReport.value = false
        }
    }

    function clear() {
        summary.value = emptySummary()
        invoices.value = []
        activeFilters.value = {}
        pagination.value = { currentPage: 1, lastPage: 1, perPage: 15, total: 0, from: 0, to: 0 }
        invoiceDetails.value = null
        loadingDetails.value = false
        loading.value = false
        exporting.value = false
        exportingReport.value = false
        reminderRules.value = []
        payables.value = []
        payableRequest++
        loadingPayables.value = false
        payablesError.value = ''
        activePayableFilters.value = {}
    }

    return {
        summary,
        invoices,
        payables,
        loadingPayables,
        payablesError,
        activePayableFilters,
        reminderRules,
        loading,
        exporting,
        exportingReport,
        activeFilters,
        pagination,
        invoiceDetails,
        loadingDetails,
        fetchAll,
        fetchPage,
        fetchInvoice,
        fetchPayables,
        addPayable,
        payPayable,
        editPayable,
        removePayable,
        voidPayable,
        voidPayablePayment,
        fetchReminderRules,
        saveReminderRule,
        removeReminderRule,
        clearInvoiceDetails,
        addInvoice,
        updateInvoice,
        addInstallment,
        addPayment,
        cancelPayment,
        paymentReceipt,
        deliverPaymentReceipt,
        sendReminder,
        cancelInvoice,
        removeInvoice,
        refreshSummary,
        exportInvoices,
        exportFinancialReport,
        clear,
    }
})
