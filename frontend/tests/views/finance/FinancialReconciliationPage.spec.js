import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { reactive } from 'vue'
const api = vi.hoisted(() => ({
    getReconciliation: vi.fn(),
    saveReconciliation: vi.fn(),
    closeFinancialMonth: vi.fn(),
    reopenFinancialMonth: vi.fn(),
}))
vi.mock('@/api/financial-reconciliation.js', () => api)
let auth, wrapper, data
vi.mock('@/stores/auth.js', () => ({ useAuthStore: () => auth }))
import FinancialReconciliationPage from '@/views/finance/FinancialReconciliationPage.vue'
import { AppCurrency } from '@/components/forms'
const render = async () => {
    wrapper = mount(FinancialReconciliationPage, {
        global: {
            stubs: {
                AppIcon: true,
                RouterLink: { template: '<a><slot /></a>' },
                teleport: true,
                transition: false,
            },
        },
    })
    await flushPromises()
}
const click = async (text) => {
    await wrapper
        .findAll('button')
        .find((b) => b.text() === text)
        .trigger('click')
    await flushPromises()
}
describe('Conciliação e fechamento', () => {
    beforeEach(() => {
        vi.resetAllMocks()
        auth = reactive({
            currentTenant: 'primeiro',
            hasPermission: () => true,
            organization: { name: 'Escritório' },
        })
        data = {
            month: '2028-01',
            page: 1,
            total: 1,
            audit_page: 1,
            audit_total: 0,
            audit: [],
            state: 'open',
            can_close: false,
            fingerprint: 'a'.repeat(64),
            closing: null,
            summary: {
                incoming_cents: 0,
                outgoing_cents: 1000,
                net_cents: -1000,
                pending: 1,
                divergent: 0,
                reconciled: 0,
                cancelled: 0,
            },
            rows: [
                {
                    key: 'outgoing:1',
                    kind: 'outgoing',
                    id: 1,
                    party: 'Locador',
                    description: 'Aluguel',
                    source: { paid_at: '2028-01-10 00:00:00', amount_cents: 1000 },
                    source_hash: 'b'.repeat(64),
                    check: null,
                    status: 'pending',
                },
            ],
        }
        api.getReconciliation.mockImplementation(async () => ({ data: structuredClone(data) }))
    })
    afterEach(() => wrapper?.unmount())
    it('permite leitura sem ações de manutenção', async () => {
        auth.hasPermission = () => false
        await render()
        expect(wrapper.text()).toContain('Locador')
        expect(wrapper.get('#reconciliation-month').attributes('type')).toBe('month')
        expect(wrapper.text()).not.toContain('Revisar fechamento')
        expect(wrapper.find('[aria-label="Conferir Pagamento #1"]').exists()).toBe(false)
    })
    it('registra valor observado em centavos e conserva a identidade da prévia', async () => {
        await render()
        await click('Conferir')
        wrapper.findComponent(AppCurrency).vm.$emit('update:modelValue', 9.5)
        await wrapper.get('#reconciliation-date').setValue('2028-01-10')
        await wrapper.get('#reconciliation-reference').setValue('Extrato, linha 1')
        await wrapper.get('#reconciliation-note').setValue('Diferença identificada')
        api.saveReconciliation.mockResolvedValue({})
        await wrapper.get('#reconciliation-check-form').trigger('submit')
        await flushPromises()
        expect(api.saveReconciliation).toHaveBeenCalledWith('outgoing', 1, {
            observed_amount_cents: 950,
            observed_on: '2028-01-10',
            reference: 'Extrato, linha 1',
            note: 'Diferença identificada',
            source_hash: 'b'.repeat(64),
        })
        expect(wrapper.text()).toContain('Conferência registrada no histórico.')
    })
    it('exige revisão antes de confirmar o fechamento e envia o mês aplicado', async () => {
        data.can_close = true
        data.rows = []
        data.summary.pending = 0
        await render()
        await wrapper.get('#reconciliation-month').setValue('2028-02')
        await click('Revisar fechamento')
        expect(api.closeFinancialMonth).not.toHaveBeenCalled()
        await wrapper.get('#reconciliation-closing-note').setValue('Mês conferido')
        api.closeFinancialMonth.mockResolvedValue({})
        await wrapper.get('#reconciliation-closing-form').trigger('submit')
        await flushPromises()
        expect(api.closeFinancialMonth).toHaveBeenCalledWith({
            month: '2028-01',
            fingerprint: 'a'.repeat(64),
            note: 'Mês conferido',
        })
    })
    it('mostra totais originais e reabertura quando há alteração após fechamento', async () => {
        data.state = 'review_required'
        data.closing = {
            closed_at: '2028-02-01 12:00:00',
            totals: { incoming_cents: 0, outgoing_cents: 900, net_cents: -900 },
        }
        await render()
        expect(wrapper.text()).toContain('Os registros mudaram após o fechamento')
        expect(wrapper.text()).toContain('No fechamento:')
        await click('Reabrir mês')
        await wrapper.get('#reconciliation-closing-note').setValue('Revisar estorno')
        api.reopenFinancialMonth.mockResolvedValue({})
        await wrapper.get('#reconciliation-closing-form').trigger('submit')
        await flushPromises()
        expect(api.reopenFinancialMonth).toHaveBeenCalledWith(
            expect.objectContaining({ month: '2028-01', note: 'Revisar estorno' }),
        )
    })
    it('preserva observações ao receber conflito e bloqueia envio repetido', async () => {
        await render()
        await click('Conferir')
        await wrapper.get('#reconciliation-note').setValue('Observação preservada')
        let reject
        api.saveReconciliation.mockReturnValue(
            new Promise((resolve, fail) => {
                reject = fail
            }),
        )
        await wrapper.get('#reconciliation-check-form').trigger('submit')
        await wrapper.get('#reconciliation-check-form').trigger('submit')
        expect(api.saveReconciliation).toHaveBeenCalledTimes(1)
        reject({ response: { data: { message: 'O pagamento mudou.' } } })
        await flushPromises()
        expect(wrapper.text()).toContain('O pagamento mudou.')
        expect(wrapper.get('#reconciliation-note').element.value).toBe('Observação preservada')
    })
    it('descarta resultado antigo ao trocar de escritório', async () => {
        let finish
        api.getReconciliation.mockReturnValueOnce(
            new Promise((resolve) => {
                finish = resolve
            }),
        )
        await render()
        data.rows = []
        data.total = 0
        auth.currentTenant = 'segundo'
        await flushPromises()
        finish({
            data: {
                ...data,
                rows: [{ key: 'outgoing:8', source: {}, party: 'Escritório anterior' }],
            },
        })
        await flushPromises()
        expect(wrapper.text()).not.toContain('Escritório anterior')
    })
})
