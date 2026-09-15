import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { DOMWrapper, flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

const http = vi.hoisted(() => ({ get: vi.fn(), post: vi.fn(), patch: vi.fn(), delete: vi.fn() }))
const permissions = vi.hoisted(() => new Set())
vi.mock('@/api/client.js', () => ({ default: http }))
vi.mock('@/views/finance/components/CashFlowPanel.vue', () => ({
    default: { template: '<section>Fluxo de caixa</section>' },
}))
vi.mock('@/stores/auth.js', () => ({
    useAuthStore: () => ({ hasPermission: (name) => permissions.has(name) }),
}))
vi.mock('@/stores/clients.js', () => ({
    useClientsStore: () => ({ clients: [], fetchClients: vi.fn() }),
}))
vi.mock('@/stores/folders.js', () => ({
    useFoldersStore: () => ({ folders: [], fetchFolders: vi.fn() }),
}))

import FinancePage from '@/views/finance/FinancePage.vue'
import AppIcon from '@/components/ui/AppIcon/index.vue'

const payable = (extra = {}) => ({
    id: 7,
    supplier: 'Fornecedor de teste',
    description: 'Aluguel do escritório',
    category: 'Estrutura',
    amount_cents: 10000,
    balance_cents: 10000,
    paid_cents: 0,
    due_on: '2099-09-20',
    status: 'open',
    payments: [],
    ...extra,
})
let rows, wrapper
const body = () => new DOMWrapper(document.body)
const dialog = () => body().get('[role="dialog"]')
const button = (scope, label) =>
    scope.findAll('button').find((item) => item.text().trim() === label)
const click = async (scope, label) => {
    await button(scope, label).trigger('click')
    await flushPromises()
}
async function render() {
    wrapper = mount(FinancePage, {
        attachTo: document.body,
        global: { plugins: [createPinia()], components: { AppIcon } },
    })
    await flushPromises()
    return wrapper
}

describe('FinancePage — contas a pagar', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        permissions.clear()
        permissions.add('finance.manage')
        vi.resetAllMocks()
        rows = [payable()]
        http.get.mockImplementation(async (url) => ({
            data: url === '/payables' ? rows : url === '/finance/summary' ? {} : [],
        }))
        http.post.mockResolvedValue({ data: {} })
        http.patch.mockResolvedValue({ data: {} })
        http.delete.mockResolvedValue({ data: {} })
    })
    afterEach(() => {
        wrapper?.unmount()
        document.body.innerHTML = ''
    })

    it('mostra carregamento antes de afirmar que a lista está vazia', async () => {
        let finish
        http.get.mockImplementation((url) =>
            url === '/payables'
                ? new Promise((resolve) => {
                      finish = resolve
                  })
                : Promise.resolve({ data: {} }),
        )
        await render()
        const section = wrapper.get('.finance-payables')
        expect(section.attributes('aria-busy')).toBe('true')
        expect(section.text()).toContain('Carregando contas a pagar')
        expect(section.text()).not.toContain('Nenhuma conta')
        finish({ data: [] })
        await flushPromises()
        expect(section.text()).toContain('Nenhuma conta a pagar registrada.')
        expect(section.attributes('aria-busy')).toBe('false')
    })

    it('aplica e limpa filtros e diferencia uma busca sem resultados', async () => {
        await render()
        await wrapper.get('#payable-filter-supplier').setValue('Inexistente')
        await wrapper.get('#payable-filter-status').setValue('overdue')
        await wrapper.get('#payable-filter-from').setValue('2026-09-01')
        await wrapper.get('#payable-filter-to').setValue('2026-09-30')
        rows = []
        await wrapper.get('.finance-payables__filters').trigger('submit')
        await flushPromises()
        expect(http.get).toHaveBeenCalledWith('/payables', {
            params: {
                supplier: 'Inexistente',
                status: 'overdue',
                due_from: '2026-09-01',
                due_to: '2026-09-30',
            },
        })
        expect(wrapper.text()).toContain('Nenhuma conta encontrada para os filtros aplicados.')
        await click(wrapper.get('.finance-payables'), 'Limpar')
        expect(wrapper.get('#payable-filter-supplier').element.value).toBe('')
        expect(wrapper.text()).toContain('Nenhuma conta a pagar registrada.')
    })

    it('impede intervalo invertido e permite corrigir o filtro', async () => {
        await render()
        http.get.mockClear()
        await wrapper.get('#payable-filter-from').setValue('2026-09-30')
        await wrapper.get('#payable-filter-to').setValue('2026-09-01')
        await wrapper.get('.finance-payables__filters').trigger('submit')
        expect(http.get).not.toHaveBeenCalled()
        expect(wrapper.get('.finance-payables [role="alert"]').text()).toContain('data inicial')
        await click(wrapper.get('.finance-payables'), 'Limpar')
        expect(wrapper.find('.finance-payables [role="alert"]').exists()).toBe(false)
    })

    it('exibe falha de consulta junto à lista e permite tentar novamente', async () => {
        http.get.mockImplementation(async (url) => {
            if (url === '/payables') throw new Error('offline')
            return { data: {} }
        })
        await render()
        expect(wrapper.get('.finance-payables [role="alert"]').text()).toContain(
            'Não foi possível carregar',
        )
        expect(wrapper.get('.finance-payables').text()).not.toContain('Nenhuma conta')
        http.get.mockResolvedValue({ data: rows })
        await click(wrapper.get('.finance-payables > .finance-page__alert'), 'Tentar novamente')
        expect(wrapper.get('.finance-payables').text()).toContain('Fornecedor de teste')
    })

    it('exibe detalhes e auditoria sem ações de gestão para leitura', async () => {
        permissions.clear()
        rows = [
            payable({
                notes: 'Observação interna',
                payments: [
                    {
                        id: 2,
                        amount_cents: 1000,
                        method: 'pix',
                        paid_at: '2026-09-10',
                        recorded_by: { name: 'Ana' },
                        cancelled_at: '2026-09-11',
                        cancelled_by: { name: 'Bruno' },
                        cancellation_reason: 'Duplicidade',
                    },
                ],
            }),
        ]
        await render()
        expect(wrapper.text()).not.toContain('Nova conta')
        expect(wrapper.get('.finance-payables').text()).not.toContain('Registrar pagamento')
        await click(wrapper.get('.finance-payables'), 'Detalhes')
        expect(dialog().text()).toContain('Observação interna')
        expect(dialog().text()).toContain('Registrado por Ana')
        expect(dialog().text()).toContain('por Bruno')
        expect(dialog().text()).toContain('Duplicidade')
        expect(dialog().text()).not.toContain('Estornar')
        expect(dialog().text()).not.toContain('Excluir conta')
    })

    it('mantém os filtros após salvar e inicia a próxima conta com formulário limpo', async () => {
        await render()
        await wrapper.get('#payable-filter-supplier').setValue('Fornecedor')
        await wrapper.get('.finance-payables__filters').trigger('submit')
        await flushPromises()
        await click(wrapper, 'Nova conta')
        await dialog().get('#payable-supplier').setValue('Fornecedor novo')
        await dialog().get('#payable-description').setValue('Serviço')
        await dialog().get('#payable-amount').setValue('12345')
        await dialog().get('form').trigger('submit')
        await flushPromises()
        expect(http.post).toHaveBeenCalledWith(
            '/payables',
            expect.objectContaining({ supplier: 'Fornecedor novo', amount_cents: 12345 }),
        )
        expect(http.get).toHaveBeenCalledWith('/payables', {
            params: expect.objectContaining({ supplier: 'Fornecedor' }),
        })
        expect(body().find('[role="dialog"]').exists()).toBe(false)
        await click(wrapper, 'Nova conta')
        expect(dialog().get('#payable-supplier').element.value).toBe('')
        expect(dialog().get('#payable-amount').element.value).toBe('0,00')
    })

    it('preserva o formulário e apresenta erro dentro do modal de pagamento', async () => {
        await render()
        http.post.mockRejectedValue({ response: { data: { message: 'Pagamento não permitido.' } } })
        await click(wrapper.get('.finance-payables'), 'Registrar pagamento')
        await dialog().get('form').trigger('submit')
        await flushPromises()
        expect(dialog().get('[role="alert"]').text()).toBe('Pagamento não permitido.')
        expect(dialog().get('#payable-payment-amount').element.value).toBe('100,00')
        await click(dialog(), 'Cancelar')
        await click(wrapper, 'Nova conta')
        expect(dialog().find('[role="alert"]').exists()).toBe(false)
    })

    it.each(['2099-09-20', '2099-09-20T00:00:00.000000Z'])(
        'carrega vencimento %s na edição e preserva a data ao salvar',
        async (dueOn) => {
            rows = [payable({ due_on: dueOn })]
            await render()
            await click(wrapper.get('.finance-payables'), 'Detalhes')
            await click(dialog(), 'Editar conta')
            expect(body().findAll('[role="dialog"]')).toHaveLength(1)
            expect(dialog().get('#payable-edit-due').element.value).toBe('2099-09-20')
            await dialog().get('#payable-edit-description').setValue('Aluguel corrigido')
            await dialog().get('form').trigger('submit')
            await flushPromises()
            expect(http.patch).toHaveBeenCalledWith(
                '/payables/7',
                expect.objectContaining({
                    description: 'Aluguel corrigido',
                    amount_cents: 10000,
                    due_on: '2099-09-20',
                }),
            )
        },
    )

    it('exige confirmação antes de excluir e mantém o erro na confirmação', async () => {
        await render()
        await click(wrapper.get('.finance-payables'), 'Detalhes')
        await click(dialog(), 'Excluir')
        expect(http.delete).not.toHaveBeenCalled()
        http.delete.mockRejectedValue({
            response: { data: { message: 'Conta possui pagamentos.' } },
        })
        await click(dialog(), 'Excluir')
        expect(dialog().get('[role="alert"]').text()).toBe('Conta possui pagamentos.')
        expect(http.delete).toHaveBeenCalledWith('/payables/7')
    })

    it('Escape fecha somente o estorno, preservando os detalhes e o foco', async () => {
        rows = [
            payable({
                paid_cents: 1000,
                payments: [{ id: 3, amount_cents: 1000, paid_at: '2026-09-01', method: 'pix' }],
            }),
        ]
        await render()
        await click(wrapper.get('.finance-payables'), 'Detalhes')
        button(dialog(), 'Estornar').element.focus()
        await click(dialog(), 'Estornar')
        expect(body().findAll('[role="dialog"]')).toHaveLength(2)
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }))
        await flushPromises()
        expect(body().findAll('[role="dialog"]')).toHaveLength(1)
        expect(dialog().text()).toContain('Detalhes da conta')
        expect(document.activeElement.textContent.trim()).toBe('Estornar')
    })

    it('registra pagamento parcial em centavos e impede duplicação durante envio', async () => {
        await render()
        let complete
        http.post.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    complete = resolve
                }),
        )
        await click(wrapper.get('.finance-payables'), 'Registrar pagamento')
        await dialog().get('#payable-payment-amount').setValue('2500')
        await dialog().get('form').trigger('submit')
        await dialog().get('form').trigger('submit')
        expect(http.post).toHaveBeenCalledTimes(1)
        expect(http.post).toHaveBeenCalledWith(
            '/payables/7/payments',
            expect.objectContaining({ amount_cents: 2500, method: 'pix' }),
        )
        expect(button(dialog(), 'Cancelar').element.disabled).toBe(true)
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
        await flushPromises()
        expect(body().find('[role="dialog"]').exists()).toBe(true)
        complete({ data: {} })
        await flushPromises()
        expect(body().find('[role="dialog"]').exists()).toBe(false)
    })

    it('envia motivo de cancelamento da conta e motivo de estorno do pagamento', async () => {
        await render()
        await click(wrapper.get('.finance-payables'), 'Detalhes')
        await click(dialog(), 'Cancelar conta')
        await dialog().get('#payable-cancellation-reason').setValue('Contrato encerrado')
        await dialog().get('form').trigger('submit')
        await flushPromises()
        expect(http.post).toHaveBeenCalledWith('/payables/7/cancel', {
            reason: 'Contrato encerrado',
        })
        rows = [
            payable({
                paid_cents: 1000,
                payments: [{ id: 9, amount_cents: 1000, paid_at: '2026-09-01', method: 'pix' }],
            }),
        ]
        await wrapper.get('.finance-payables__filters').trigger('submit')
        await flushPromises()
        await click(wrapper.get('.finance-payables'), 'Detalhes')
        await click(dialog(), 'Estornar')
        const reversal = body().findAll('[role="dialog"]').at(-1)
        await reversal.get('#payable-payment-cancellation-reason').setValue('Lançamento duplicado')
        await reversal.get('form').trigger('submit')
        await flushPromises()
        expect(http.post).toHaveBeenCalledWith('/payables/7/payments/9/cancel', {
            reason: 'Lançamento duplicado',
        })
    })

    it.each([
        ['paid', 0, 10000, 'Paga'],
        ['cancelled', 0, 0, 'Cancelada'],
        ['partial', 5000, 5000, 'Parcial'],
    ])(
        'exibe situação %s e oferece somente ações válidas',
        async (status, balance, paid, label) => {
            rows = [payable({ status, balance_cents: balance, paid_cents: paid })]
            await render()
            expect(wrapper.get('.finance-payables__status').text()).toBe(label)
            expect(Boolean(button(wrapper.get('.finance-payables'), 'Registrar pagamento'))).toBe(
                balance > 0,
            )
            await click(wrapper.get('.finance-payables'), 'Detalhes')
            expect(button(dialog(), 'Editar conta')).toBeUndefined()
            expect(button(dialog(), 'Cancelar conta')).toBeUndefined()
        },
    )

    it('combina filtros de recebíveis sem alterar os filtros de contas a pagar', async () => {
        await render()
        await wrapper.get('#invoice-filter-transaction').setValue('COB-123')
        const form = wrapper.get('form[aria-label="Filtros de contas a receber"]')
        await form.trigger('submit')
        await flushPromises()
        expect(http.get).toHaveBeenCalledWith('/invoices', {
            params: expect.objectContaining({ transaction: 'COB-123', page: 1 }),
        })
        expect(wrapper.get('#payable-filter-supplier').element.value).toBe('')
    })
})
