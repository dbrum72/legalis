import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const api = vi.hoisted(() => ({ getFinancialSummary: vi.fn(), listInvoices: vi.fn(), createInvoice: vi.fn(), createInvoiceInstallment: vi.fn(), createPayment: vi.fn(), deleteInvoice: vi.fn() }))
vi.mock('@/api/finance.js', () => api)
import { useFinanceStore } from '@/stores/finance.js'

describe('finance store', () => {
    beforeEach(() => { setActivePinia(createPinia()); vi.clearAllMocks() })

    it('carrega resumo e cobranças', async () => {
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 90000 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 1 }] })
        const store = useFinanceStore()
        await store.fetchAll()
        expect(store.summary.receivable_cents).toBe(90000)
        expect(store.invoices).toEqual([{ id: 1 }])
        expect(api.listInvoices).toHaveBeenCalledWith({})
    })

    it('envia os filtros de cobrança para a API', async () => {
        api.getFinancialSummary.mockResolvedValue({ data: {} })
        api.listInvoices.mockResolvedValue({ data: [] })
        const store = useFinanceStore()

        await store.fetchAll({ client_id: 7, month: '2026-09' })

        expect(api.listInvoices).toHaveBeenCalledWith({ client_id: 7, month: '2026-09' })
        expect(store.activeFilters).toEqual({ client_id: 7, month: '2026-09' })
    })

    it('recarrega os dados após registrar pagamento', async () => {
        api.createPayment.mockResolvedValue({ data: { id: 2 } })
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 0 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 1, status: 'paid' }] })
        const store = useFinanceStore()
        await store.addPayment(1, { amount_cents: 10000 })
        expect(api.createPayment).toHaveBeenCalledWith(1, { amount_cents: 10000 })
        expect(store.invoices[0].status).toBe('paid')
    })

    it('adiciona uma parcela e recarrega a cobrança', async () => {
        api.createInvoiceInstallment.mockResolvedValue({ data: { id: 3, installment_number: 3 } })
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 30000 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 3, installment_number: 3 }] })
        const store = useFinanceStore()

        await store.addInstallment(1, { amount_cents: 10000 })

        expect(api.createInvoiceInstallment).toHaveBeenCalledWith(1, { amount_cents: 10000 })
        expect(store.invoices[0].installment_number).toBe(3)
    })
})
