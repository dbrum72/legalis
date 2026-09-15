import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { reactive } from 'vue'
const api = vi.hoisted(() => ({ getCashFlow: vi.fn(), exportCashFlow: vi.fn() }))
vi.mock('@/api/cash-flow.js', () => api)
let auth
vi.mock('@/stores/auth.js', () => ({ useAuthStore: () => auth }))
import CashFlowPanel from '@/views/finance/components/CashFlowPanel.vue'
import AppIcon from '@/components/ui/AppIcon/index.vue'

const report = (extra = {}) => ({
    period: { from: '2026-09-01', to: '2026-09-30' },
    realized: { in_cents: 10000, out_cents: 4000, net_cents: 6000 },
    projected: { in_cents: 3000, out_cents: 2000, net_cents: 1000 },
    combined_net_cents: 7000,
    indicators: {
        cash_margin_percent: 60,
        overdue_percent: null,
        largest_client_percent: 100,
        largest_client: { name: 'Cliente A' },
    },
    monthly: [],
    entries: [],
    pagination: { current_page: 1, last_page: 2, total: 26 },
    ...extra,
})
let wrapper
const button = (label) => wrapper.findAll('button').find((b) => b.text().trim() === label)
async function render() {
    wrapper = mount(CashFlowPanel, { global: { components: { AppIcon } } })
    await flushPromises()
}
describe('CashFlowPanel', () => {
    beforeEach(() => {
        vi.resetAllMocks()
        auth = reactive({ currentTenant: 'escritorio' })
        api.getCashFlow.mockResolvedValue({ data: report() })
    })
    afterEach(() => {
        wrapper?.unmount()
        vi.restoreAllMocks()
    })

    it('mostra totais, indicadores sem denominador e período efetivamente aplicado', async () => {
        await render()
        expect(wrapper.text()).toContain('01/09/2026 a 30/09/2026')
        expect(wrapper.text()).toContain('60%')
        expect(wrapper.text()).toContain('—')
        expect(wrapper.text()).toContain('Cliente A')
        expect(wrapper.text()).toContain('Nenhuma movimentação')
    })

    it('pagina e exporta com os filtros aplicados mesmo após editar o formulário', async () => {
        await render()
        await wrapper.get('#cash-flow-month').setValue('2026-09')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        await wrapper.get('#cash-flow-month').setValue('2026-10')
        await button('Próxima').trigger('click')
        await flushPromises()
        expect(api.getCashFlow).toHaveBeenLastCalledWith({ month: '2026-09', page: 2 })
        URL.createObjectURL = vi.fn(() => 'blob:test')
        URL.revokeObjectURL = vi.fn()
        vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {})
        api.exportCashFlow.mockResolvedValue({ data: new Blob(['csv']) })
        await button('Exportar fluxo CSV').trigger('click')
        await flushPromises()
        expect(api.exportCashFlow).toHaveBeenCalledWith({ month: '2026-09' })
        expect(URL.revokeObjectURL).toHaveBeenCalledWith('blob:test')
    })

    it('valida intervalo e envia as duas datas sem mês', async () => {
        await render()
        await wrapper.get('#cash-flow-period').setValue('range')
        await wrapper.get('#cash-flow-from').setValue('2026-10-10')
        await wrapper.get('#cash-flow-to').setValue('2026-10-01')
        await wrapper.get('form').trigger('submit')
        expect(api.getCashFlow).toHaveBeenCalledTimes(1)
        expect(wrapper.get('[role="alert"]').text()).toContain('data inicial')
        await wrapper.get('#cash-flow-to').setValue('2026-11-01')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(api.getCashFlow).toHaveBeenLastCalledWith({
            from: '2026-10-10',
            to: '2026-11-01',
            page: 1,
        })
    })

    it('permite repetir consulta após falha e bloqueia exportação', async () => {
        api.getCashFlow.mockRejectedValueOnce(new Error('offline'))
        await render()
        expect(wrapper.get('[role="alert"]').text()).toContain('Não foi possível')
        expect(button('Exportar fluxo CSV').attributes('disabled')).toBeDefined()
        await button('Tentar novamente').trigger('click')
        await flushPromises()
        expect(wrapper.find('[role="alert"]').exists()).toBe(false)
    })

    it('descarta resposta de escritório anterior e atualiza após mutações financeiras', async () => {
        let finish
        api.getCashFlow.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    finish = resolve
                }),
        )
        await render()
        expect(wrapper.get('[role="status"]').text()).toContain('Carregando')
        auth.currentTenant = 'outro'
        await flushPromises()
        finish({
            data: report({ entries: [{ id: 'old', party: 'DADO ANTIGO', date: '2026-09-01' }] }),
        })
        await flushPromises()
        expect(wrapper.text()).not.toContain('DADO ANTIGO')
        await wrapper.setProps({ refreshToken: {} })
        await flushPromises()
        expect(api.getCashFlow).toHaveBeenCalledTimes(3)
    })
})
