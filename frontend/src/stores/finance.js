import { ref } from 'vue'
import { defineStore } from 'pinia'
import { createInvoice, createInvoiceInstallment, createPayment, deleteInvoice, getFinancialSummary, listInvoices } from '@/api/finance.js'

export const useFinanceStore = defineStore('finance', () => {
    const summary = ref({ receivable_cents: 0, overdue_cents: 0, overdue_count: 0, received_this_month_cents: 0 })
    const invoices = ref([])
    const loading = ref(false)

    async function fetchAll() {
        loading.value = true
        try {
            const [summaryResponse, invoicesResponse] = await Promise.all([getFinancialSummary(), listInvoices()])
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

    async function addInstallment(invoiceId, payload) {
        const { data } = await createInvoiceInstallment(invoiceId, payload)
        await fetchAll()
        return data
    }

    async function addPayment(invoiceId, payload) {
        await createPayment(invoiceId, payload)
        await fetchAll()
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
        loading.value = false
    }

    return { summary, invoices, loading, fetchAll, addInvoice, addInstallment, addPayment, removeInvoice, refreshSummary, clear }
})
