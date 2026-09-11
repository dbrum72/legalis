import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import {
    createExpense as createExpenseRequest,
    createFolderBilling as createFolderBillingRequest,
    createFeeAgreement as createFeeAgreementRequest,
    createTimeEntry as createTimeEntryRequest,
    deleteExpense as deleteExpenseRequest,
    deleteFeeAgreement as deleteFeeAgreementRequest,
    deleteTimeEntry as deleteTimeEntryRequest,
    listExpenses,
    listFeeAgreements,
    listTimeEntries,
    updateFeeAgreement as updateFeeAgreementRequest,
    updateTimeEntry as updateTimeEntryRequest,
    updateExpense as updateExpenseRequest,
} from '@/api/folder-financial.js'

export const useFolderFinancialStore = defineStore('folder-financial', () => {
    const agreements = ref([])
    const timeEntries = ref([])
    const expenses = ref([])
    const loading = ref(false)

    const billableMinutes = computed(() => timeEntries.value
        .filter((entry) => entry.billable && entry.status === 'open')
        .reduce((total, entry) => total + Number(entry.duration_minutes || 0), 0))
    const billableTimeCents = computed(() => timeEntries.value
        .filter((entry) => entry.billable && entry.status === 'open')
        .reduce((total, entry) => total + Math.round(Number(entry.duration_minutes || 0) * Number(entry.hourly_rate_cents || 0) / 60), 0))
    const reimbursableExpenseCents = computed(() => expenses.value
        .filter((expense) => expense.reimbursable && expense.status === 'open')
        .reduce((total, expense) => total + Number(expense.amount_cents || 0), 0))

    async function fetchAll(folderId, permissions = {}) {
        loading.value = true
        try {
            const requests = []
            if (permissions.finance) requests.push(listFeeAgreements(folderId).then(({ data }) => { agreements.value = data }))
            if (permissions.time) requests.push(listTimeEntries(folderId).then(({ data }) => { timeEntries.value = data }))
            if (permissions.expenses) requests.push(listExpenses(folderId).then(({ data }) => { expenses.value = data }))
            await Promise.all(requests)
        } finally {
            loading.value = false
        }
    }

    async function createAgreement(folderId, payload) {
        const { data } = await createFeeAgreementRequest(folderId, payload)
        agreements.value.unshift(data)
        return data
    }

    async function createBilling(folderId, payload) {
        const { data } = await createFolderBillingRequest(folderId, payload)
        const timeIds = new Set(payload.time_entry_ids ?? [])
        const expenseIds = new Set(payload.expense_ids ?? [])
        timeEntries.value = timeEntries.value.map((item) => timeIds.has(item.id) ? { ...item, invoice_id: data.id, status: 'billed' } : item)
        expenses.value = expenses.value.map((item) => expenseIds.has(item.id) ? { ...item, invoice_id: data.id, status: 'billed' } : item)
        return data
    }

    async function updateAgreement(folderId, id, payload) {
        const { data } = await updateFeeAgreementRequest(folderId, id, payload)
        agreements.value = agreements.value.map((item) => Number(item.id) === Number(id) ? data : item)
        return data
    }

    async function createTime(folderId, payload) {
        const { data } = await createTimeEntryRequest(folderId, payload)
        timeEntries.value.unshift(data)
        return data
    }

    async function updateTime(folderId, id, payload) {
        const { data } = await updateTimeEntryRequest(folderId, id, payload)
        timeEntries.value = timeEntries.value.map((item) => Number(item.id) === Number(id) ? data : item)
        return data
    }

    async function createExpense(folderId, payload) {
        const { data } = await createExpenseRequest(folderId, payload)
        expenses.value.unshift(data)
        return data
    }

    async function updateExpense(folderId, id, payload) {
        const { data } = await updateExpenseRequest(folderId, id, payload)
        expenses.value = expenses.value.map((item) => Number(item.id) === Number(id) ? data : item)
        return data
    }

    async function removeAgreement(folderId, id) {
        await deleteFeeAgreementRequest(folderId, id)
        agreements.value = agreements.value.filter((item) => Number(item.id) !== Number(id))
    }

    async function removeTime(folderId, id) {
        await deleteTimeEntryRequest(folderId, id)
        timeEntries.value = timeEntries.value.filter((item) => Number(item.id) !== Number(id))
    }

    async function removeExpense(folderId, id) {
        await deleteExpenseRequest(folderId, id)
        expenses.value = expenses.value.filter((item) => Number(item.id) !== Number(id))
    }

    function clear() {
        agreements.value = []
        timeEntries.value = []
        expenses.value = []
        loading.value = false
    }

    return { agreements, timeEntries, expenses, loading, billableMinutes, billableTimeCents, reimbursableExpenseCents, fetchAll, createBilling, createAgreement, updateAgreement, createTime, updateTime, createExpense, updateExpense, removeAgreement, removeTime, removeExpense, clear }
})
