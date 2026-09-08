import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const requests = vi.hoisted(() => ({
    listFeeAgreements: vi.fn(), listTimeEntries: vi.fn(), listExpenses: vi.fn(),
    createFeeAgreement: vi.fn(), createTimeEntry: vi.fn(), createExpense: vi.fn(),
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
})
