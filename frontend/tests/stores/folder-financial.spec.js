import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const requests = vi.hoisted(() => ({
    listFeeAgreements: vi.fn(), listTimeEntries: vi.fn(), listExpenses: vi.fn(),
    createFolderBilling: vi.fn(), createFeeAgreement: vi.fn(), updateFeeAgreement: vi.fn(), createTimeEntry: vi.fn(), updateTimeEntry: vi.fn(), createExpense: vi.fn(), updateExpense: vi.fn(),
    deleteFeeAgreement: vi.fn(), deleteTimeEntry: vi.fn(), deleteExpense: vi.fn(),
}))
vi.mock('@/api/folder-financial.js', () => requests)

import { useFolderFinancialStore } from '@/stores/folder-financial.js'

describe('folder financial store', () => {
    beforeEach(() => { setActivePinia(createPinia()); vi.clearAllMocks() })

    it('carrega apenas os recursos permitidos', async () => {
        requests.listTimeEntries.mockResolvedValue({ data: [{ id: 1 }] })
        requests.listExpenses.mockResolvedValue({ data: [{ id: 2 }] })
        const store = useFolderFinancialStore()

        await store.fetchAll(10, { finance: false, time: true, expenses: true })

        expect(requests.listFeeAgreements).not.toHaveBeenCalled()
        expect(requests.listTimeEntries).toHaveBeenCalledWith(10)
        expect(store.timeEntries).toEqual([{ id: 1 }])
    })

    it('calcula horas, honorários e despesas abertas', () => {
        const store = useFolderFinancialStore()
        store.timeEntries = [
            { duration_minutes: 90, hourly_rate_cents: 20000, billable: true, status: 'open' },
            { duration_minutes: 60, hourly_rate_cents: 50000, billable: true, status: 'billed' },
        ]
        store.expenses = [
            { amount_cents: 15000, reimbursable: true, status: 'open' },
            { amount_cents: 5000, reimbursable: false, status: 'open' },
        ]

        expect(store.billableMinutes).toBe(90)
        expect(store.billableTimeCents).toBe(30000)
        expect(store.reimbursableExpenseCents).toBe(15000)
    })

    it('adiciona e remove um apontamento', async () => {
        requests.createTimeEntry.mockResolvedValue({ data: { id: 7, description: 'Reunião' } })
        requests.deleteTimeEntry.mockResolvedValue({})
        const store = useFolderFinancialStore()

        await store.createTime(10, { description: 'Reunião' })
        expect(store.timeEntries).toHaveLength(1)
        await store.removeTime(10, 7)
        expect(store.timeEntries).toEqual([])
    })

    it('atualiza um contrato de honorários na coleção', async () => {
        requests.updateFeeAgreement.mockResolvedValue({ data: { id: 4, status: 'closed' } })
        const store = useFolderFinancialStore()
        store.agreements = [{ id: 4, status: 'active' }]

        await store.updateAgreement(10, 4, { status: 'closed' })

        expect(requests.updateFeeAgreement).toHaveBeenCalledWith(10, 4, { status: 'closed' })
        expect(store.agreements[0].status).toBe('closed')
    })

    it('atualiza apontamentos e despesas nas coleções', async () => {
        requests.updateTimeEntry.mockResolvedValue({ data: { id: 1, duration_minutes: 120 } })
        requests.updateExpense.mockResolvedValue({ data: { id: 2, amount_cents: 25000 } })
        const store = useFolderFinancialStore()
        store.timeEntries = [{ id: 1, duration_minutes: 60 }]
        store.expenses = [{ id: 2, amount_cents: 10000 }]

        await store.updateTime(10, 1, { duration_minutes: 120 })
        await store.updateExpense(10, 2, { amount_cents: 25000 })

        expect(store.timeEntries[0].duration_minutes).toBe(120)
        expect(store.expenses[0].amount_cents).toBe(25000)
    })

    it('gera cobrança e marca os lançamentos como faturados', async () => {
        requests.createFolderBilling.mockResolvedValue({ data: { id: 20, charge_identifier: 'COB-20' } })
        const store = useFolderFinancialStore()
        store.timeEntries = [{ id: 1, status: 'open' }]
        store.expenses = [{ id: 2, status: 'open' }]

        await store.createBilling(10, { time_entry_ids: [1], expense_ids: [2] })

        expect(store.timeEntries[0]).toMatchObject({ status: 'billed', invoice_id: 20 })
        expect(store.expenses[0]).toMatchObject({ status: 'billed', invoice_id: 20 })
    })
})
