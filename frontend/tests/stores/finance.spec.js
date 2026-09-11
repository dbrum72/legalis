import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const api = vi.hoisted(() => ({ getFinancialSummary: vi.fn(), getInvoice: vi.fn(), listInvoices: vi.fn(), createInvoice: vi.fn(), updateInvoice: vi.fn(), createInvoiceInstallment: vi.fn(), createPayment: vi.fn(), cancelPayment: vi.fn(), cancelInvoice: vi.fn(), deleteInvoice: vi.fn() }))
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

    it('carrega os detalhes de uma cobrança sob demanda', async () => {
        api.getInvoice.mockResolvedValue({ data: { id: 8, time_entries: [{ id: 1 }] } })
        const store = useFinanceStore()

        await store.fetchInvoice(8)

        expect(api.getInvoice).toHaveBeenCalledWith(8)
        expect(store.invoiceDetails.time_entries).toHaveLength(1)
        expect(store.loadingDetails).toBe(false)
    })

    it('cancela uma cobrança e atualiza a listagem e o resumo', async () => {
        api.cancelInvoice.mockResolvedValue({ data: { id: 4, status: 'cancelled' } })
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 0 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 4, status: 'cancelled' }] })
        const store = useFinanceStore()

        await store.cancelInvoice(4, { reason: 'Lançamento incorreto' })

        expect(api.cancelInvoice).toHaveBeenCalledWith(4, { reason: 'Lançamento incorreto' })
        expect(store.invoices[0].status).toBe('cancelled')
    })

    it('atualiza uma cobrança e recarrega a listagem', async () => {
        api.updateInvoice.mockResolvedValue({ data: { id: 4, balance_cents: 80000 } })
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 80000 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 4, balance_cents: 80000 }] })
        const store = useFinanceStore()

        await store.updateInvoice(4, { subtotal_cents: 80000 })

        expect(api.updateInvoice).toHaveBeenCalledWith(4, { subtotal_cents: 80000 })
        expect(store.invoices[0].balance_cents).toBe(80000)
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

    it('cancela um pagamento e atualiza listagem, resumo e detalhes', async () => {
        api.cancelPayment.mockResolvedValue({ data: { id: 2, cancelled_at: '2026-09-10T12:00:00Z' } })
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 10000 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 1, status: 'open' }] })
        api.getInvoice.mockResolvedValue({ data: { id: 1, payments: [{ id: 2, cancelled_at: '2026-09-10T12:00:00Z' }] } })
        const store = useFinanceStore()

        await store.cancelPayment(1, 2, { reason: 'Duplicidade' })

        expect(api.cancelPayment).toHaveBeenCalledWith(1, 2, { reason: 'Duplicidade' })
        expect(store.invoices[0].status).toBe('open')
        expect(store.invoiceDetails.payments[0].cancelled_at).toBeTruthy()
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
