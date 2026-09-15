import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const api = vi.hoisted(() => ({
    getFinancialSummary: vi.fn(),
    getInvoice: vi.fn(),
    getPaymentReceipt: vi.fn(),
    sendPaymentReceipt: vi.fn(),
    listInvoices: vi.fn(),
    listPayables: vi.fn(),
    createPayable: vi.fn(),
    updatePayable: vi.fn(),
    deletePayable: vi.fn(),
    cancelPayable: vi.fn(),
    createPayablePayment: vi.fn(),
    cancelPayablePayment: vi.fn(),
    exportInvoices: vi.fn(),
    exportFinancialReport: vi.fn(),
    createInvoice: vi.fn(),
    updateInvoice: vi.fn(),
    createInvoiceInstallment: vi.fn(),
    createPayment: vi.fn(),
    cancelPayment: vi.fn(),
    createInvoiceReminder: vi.fn(),
    cancelInvoice: vi.fn(),
    deleteInvoice: vi.fn(),
    listReminderRules: vi.fn(),
    createReminderRule: vi.fn(),
    updateReminderRule: vi.fn(),
    deleteReminderRule: vi.fn(),
}))
vi.mock('@/api/finance.js', () => api)
import { useFinanceStore } from '@/stores/finance.js'

describe('finance store', () => {
    it('preserva filtros de contas a pagar após alterações', async () => {
        const store = useFinanceStore()
        api.listPayables.mockResolvedValue({ data: [] })
        api.getFinancialSummary.mockResolvedValue({ data: {} })
        api.createPayablePayment.mockResolvedValue({ data: {} })
        await store.fetchPayables({ supplier: 'Aluguel', status: 'open' })
        await store.payPayable(1, { amount_cents: 1000 })
        expect(api.listPayables).toHaveBeenLastCalledWith({ supplier: 'Aluguel', status: 'open' })
    })

    it('ignora respostas antigas quando um filtro mais recente já respondeu', async () => {
        const store = useFinanceStore()
        let resolveOld
        api.listPayables.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    resolveOld = resolve
                }),
        )
        const oldRequest = store.fetchPayables({ supplier: 'Antigo' })
        api.listPayables.mockResolvedValueOnce({ data: [{ id: 2 }] })
        await store.fetchPayables({ supplier: 'Novo' })
        resolveOld({ data: [{ id: 1 }] })
        await oldRequest
        expect(store.payables).toEqual([{ id: 2 }])
        expect(store.activePayableFilters).toEqual({ supplier: 'Novo' })
        expect(store.loadingPayables).toBe(false)
    })

    it('não repõe dados antigos após limpar o contexto da organização', async () => {
        const store = useFinanceStore()
        let resolveRequest
        api.listPayables.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    resolveRequest = resolve
                }),
        )
        const pending = store.fetchPayables({ status: 'open' })
        store.clear()
        resolveRequest({ data: [{ id: 1 }] })
        await pending
        expect(store.payables).toEqual([])
        expect(store.activePayableFilters).toEqual({})
        expect(store.loadingPayables).toBe(false)
    })

    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('carrega resumo e cobranças', async () => {
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 90000 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 1 }] })
        const store = useFinanceStore()
        await store.fetchAll()
        expect(store.summary.receivable_cents).toBe(90000)
        expect(store.invoices).toEqual([{ id: 1 }])
        expect(api.listInvoices).toHaveBeenCalledWith({ page: 1, per_page: 15 })
    })

    it('envia os filtros de cobrança para a API', async () => {
        api.getFinancialSummary.mockResolvedValue({ data: {} })
        api.listInvoices.mockResolvedValue({ data: [] })
        const store = useFinanceStore()

        await store.fetchAll({ client_id: 7, month: '2026-09' })

        expect(api.listInvoices).toHaveBeenCalledWith({
            client_id: 7,
            month: '2026-09',
            page: 1,
            per_page: 15,
        })
        expect(store.activeFilters).toEqual({ client_id: 7, month: '2026-09' })
    })

    it('combina situação e ordenação na consulta', async () => {
        api.getFinancialSummary.mockResolvedValue({ data: {} })
        api.listInvoices.mockResolvedValue({ data: [] })
        const store = useFinanceStore()

        await store.fetchAll({ status: 'overdue', sort: 'balance_desc' })

        expect(api.listInvoices).toHaveBeenCalledWith({
            status: 'overdue',
            sort: 'balance_desc',
            page: 1,
            per_page: 15,
        })
    })

    it('reconstrói o mapa com todas as parcelas quando o resumo carregado ainda não possui as faixas', async () => {
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 150000 } })
        api.listInvoices.mockResolvedValue({
            data: [
                {
                    id: 1,
                    status: 'open',
                    due_on: new Date().toLocaleDateString('en-CA'),
                    balance_cents: 50000,
                },
                {
                    id: 2,
                    status: 'open',
                    due_on: new Date().toLocaleDateString('en-CA'),
                    balance_cents: 50000,
                },
                {
                    id: 3,
                    status: 'open',
                    due_on: new Date().toLocaleDateString('en-CA'),
                    balance_cents: 50000,
                },
            ],
        })
        const store = useFinanceStore()

        await store.fetchAll()

        expect(store.summary.aging.current).toEqual({ count: 3, balance_cents: 150000 })
    })

    it('navega entre páginas mantendo os filtros ativos', async () => {
        api.getFinancialSummary.mockResolvedValue({
            data: {
                receivable_cents: 20000,
                aging: { current: { count: 2, balance_cents: 20000 } },
            },
        })
        api.listInvoices
            .mockResolvedValueOnce({
                data: {
                    data: [{ id: 1 }],
                    current_page: 1,
                    last_page: 2,
                    per_page: 15,
                    total: 16,
                    from: 1,
                    to: 15,
                },
            })
            .mockResolvedValueOnce({
                data: {
                    data: [{ id: 16 }],
                    current_page: 2,
                    last_page: 2,
                    per_page: 15,
                    total: 16,
                    from: 16,
                    to: 16,
                },
            })
        const store = useFinanceStore()

        await store.fetchAll({ client_id: 7 })
        await store.fetchPage(2)

        expect(api.listInvoices).toHaveBeenLastCalledWith({ client_id: 7, page: 2, per_page: 15 })
        expect(store.invoices).toEqual([{ id: 16 }])
        expect(store.pagination).toMatchObject({
            currentPage: 2,
            lastPage: 2,
            total: 16,
            from: 16,
            to: 16,
        })
    })

    it('carrega os detalhes de uma cobrança sob demanda', async () => {
        api.getInvoice.mockResolvedValue({ data: { id: 8, time_entries: [{ id: 1 }] } })
        const store = useFinanceStore()

        await store.fetchInvoice(8)

        expect(api.getInvoice).toHaveBeenCalledWith(8)
        expect(store.invoiceDetails.time_entries).toHaveLength(1)
        expect(store.loadingDetails).toBe(false)
    })

    it('envia lembrete e atualiza o histórico aberto', async () => {
        api.createInvoiceReminder.mockResolvedValue({ data: { id: 3 } })
        api.getInvoice.mockResolvedValue({ data: { id: 8, reminders: [{ id: 3 }] } })
        api.getFinancialSummary.mockResolvedValue({ data: {} })
        api.listInvoices.mockResolvedValue({ data: [{ id: 8, reminders_count: 1 }] })
        const store = useFinanceStore()
        store.invoiceDetails = { id: 8, reminders: [] }

        await store.sendReminder(8, { subject: 'Lembrete', message: 'Mensagem' })

        expect(api.createInvoiceReminder).toHaveBeenCalledWith(8, {
            subject: 'Lembrete',
            message: 'Mensagem',
        })
        expect(store.invoiceDetails.reminders).toEqual([{ id: 3 }])
        expect(store.invoices[0].reminders_count).toBe(1)
    })

    it('exporta as cobranças com os filtros ativos', async () => {
        const blob = new Blob(['csv'])
        api.exportInvoices.mockResolvedValue({ data: blob })
        const store = useFinanceStore()
        store.activeFilters = { client_id: 7 }

        await expect(store.exportInvoices()).resolves.toBe(blob)

        expect(api.exportInvoices).toHaveBeenCalledWith({ client_id: 7 })
        expect(store.exporting).toBe(false)
    })

    it('exporta o relatório financeiro consolidado', async () => {
        const blob = new Blob(['relatório'])
        api.exportFinancialReport.mockResolvedValue({ data: blob })
        const store = useFinanceStore()

        await expect(store.exportFinancialReport()).resolves.toBe(blob)

        expect(api.exportFinancialReport).toHaveBeenCalledOnce()
        expect(store.exportingReport).toBe(false)
    })

    it('obtém o comprovante de um pagamento', async () => {
        const blob = new Blob(['comprovante'])
        api.getPaymentReceipt.mockResolvedValue({ data: blob })
        const store = useFinanceStore()

        await expect(store.paymentReceipt(8, 12)).resolves.toBe(blob)
        expect(api.getPaymentReceipt).toHaveBeenCalledWith(8, 12)
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
        api.cancelPayment.mockResolvedValue({
            data: { id: 2, cancelled_at: '2026-09-10T12:00:00Z' },
        })
        api.getFinancialSummary.mockResolvedValue({ data: { receivable_cents: 10000 } })
        api.listInvoices.mockResolvedValue({ data: [{ id: 1, status: 'open' }] })
        api.getInvoice.mockResolvedValue({
            data: { id: 1, payments: [{ id: 2, cancelled_at: '2026-09-10T12:00:00Z' }] },
        })
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
