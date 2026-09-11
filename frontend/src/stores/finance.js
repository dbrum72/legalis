import { ref } from 'vue'
import { defineStore } from 'pinia'
import { cancelInvoice as cancelInvoiceRequest, cancelPayment as cancelPaymentRequest, createInvoice, createInvoiceInstallment, createPayment, deleteInvoice, getFinancialSummary, getInvoice, listInvoices, updateInvoice as updateInvoiceRequest } from '@/api/finance.js'

export const useFinanceStore = defineStore('finance', () => {
    const summary = ref({ receivable_cents: 0, overdue_cents: 0, overdue_count: 0, received_this_month_cents: 0 })
    const invoices = ref([])
    const loading = ref(false)
    const activeFilters = ref({})
    const invoiceDetails = ref(null)
    const loadingDetails = ref(false)

    async function fetchAll(filters = activeFilters.value) {
        loading.value = true
        activeFilters.value = { ...filters }
        try {
            const [summaryResponse, invoicesResponse] = await Promise.all([getFinancialSummary(), listInvoices(activeFilters.value)])
            summary.value = summaryResponse.data
            invoices.value = invoicesResponse.data
        } finally {
            loading.value = false
        }
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

    function clear() {
        summary.value = { receivable_cents: 0, overdue_cents: 0, overdue_count: 0, received_this_month_cents: 0 }
        invoices.value = []
        activeFilters.value = {}
        invoiceDetails.value = null
        loadingDetails.value = false
        loading.value = false
    }

    return { summary, invoices, loading, activeFilters, invoiceDetails, loadingDetails, fetchAll, fetchInvoice, clearInvoiceDetails, addInvoice, updateInvoice, addInstallment, addPayment, cancelPayment, cancelInvoice, removeInvoice, refreshSummary, clear }
})
