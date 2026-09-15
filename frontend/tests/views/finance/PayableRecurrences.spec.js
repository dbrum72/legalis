import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { reactive } from 'vue'
const api = vi.hoisted(() => ({
    listRecurrences: vi.fn(),
    createRecurrence: vi.fn(),
    updateRecurrence: vi.fn(),
    previewRecurrence: vi.fn(),
    generateRecurrence: vi.fn(),
    payableAlerts: vi.fn(),
}))
vi.mock('@/api/payable-recurrences.js', () => api)
let auth, wrapper
vi.mock('@/stores/auth.js', () => ({ useAuthStore: () => auth }))
import PayableRecurrences from '@/views/finance/components/PayableRecurrences.vue'
import { AppCurrency } from '@/components/forms'
const rule = {
    id: 1,
    template: { supplier: 'Locador', description: 'Aluguel', amount_cents: 12345 },
    interval_months: 1,
    next_due_on: '2028-02-29',
    active: true,
}
const render = async () => {
    wrapper = mount(PayableRecurrences, {
        global: {
            stubs: {
                ClassificationFields: { props: ['prefix'], template: '<div />' },
                AppIcon: true,
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
describe('Contas recorrentes', () => {
    beforeEach(() => {
        vi.resetAllMocks()
        auth = reactive({ currentTenant: 'primeiro', hasPermission: () => true })
        api.listRecurrences.mockResolvedValue({ data: [rule] })
        api.payableAlerts.mockResolvedValue({
            data: { due_soon: 2, due_today: 1, through: '2028-01-22' },
        })
        api.previewRecurrence.mockResolvedValue({ data: { dates: ['2028-02-29'] } })
    })
    afterEach(() => wrapper?.unmount())
    it('mostra alertas e permite consulta sem ações de gestão', async () => {
        auth.hasPermission = () => false
        await render()
        expect(wrapper.text()).toContain('2 conta(s) com saldo')
        expect(wrapper.text()).not.toContain('Nova recorrência')
        expect(wrapper.text()).not.toContain('Editar futuras')
        await click('Consultar vencimentos')
        expect(wrapper.emitted('due-soon')).toHaveLength(1)
        await click('Ver próximas')
        expect(wrapper.text()).toContain('29/02/2028')
        expect(wrapper.text()).not.toContain('Gerar 1 conta(s)')
    })
    it('envia valor em centavos e respeita fim de mês na prévia de criação', async () => {
        await render()
        await click('Nova recorrência')
        await wrapper.get('#recurrence-supplier').setValue('Locador')
        await wrapper.get('#recurrence-description').setValue('Aluguel')
        wrapper.findComponent(AppCurrency).vm.$emit('update:modelValue', 123.45)
        await wrapper.get('#recurrence-start').setValue('2028-01-31')
        expect(wrapper.text()).toContain('31/01/2028 · 29/02/2028 · 31/03/2028 · 30/04/2028')
        api.createRecurrence.mockResolvedValue({ data: { created: 1 } })
        await wrapper.get('#recurrence-create').trigger('submit')
        await flushPromises()
        expect(api.createRecurrence).toHaveBeenCalledWith(
            expect.objectContaining({
                interval_months: 1,
                amount_cents: 12345,
                due_on: '2028-01-31',
            }),
        )
        expect(wrapper.text()).toContain('Recorrência criada. 1 conta(s) gerada(s).')
        expect(wrapper.emitted('changed')).toHaveLength(1)
    })
    it('invalida a prévia quando muda o horizonte de geração', async () => {
        await render()
        await click('Ver próximas')
        expect(wrapper.text()).toContain('29/02/2028')
        await wrapper.get('#recurrence-through').setValue('2028-03-01')
        const generate = wrapper.findAll('button').find((b) => b.text() === 'Gerar 1 conta(s)')
        expect(generate.attributes('disabled')).toBeDefined()
        await click('Atualizar prévia')
        expect(api.previewRecurrence).toHaveBeenLastCalledWith(1, '2028-03-01')
        api.generateRecurrence.mockResolvedValue({ data: { created: 1 } })
        await click('Gerar 1 conta(s)')
        expect(api.generateRecurrence).toHaveBeenCalledWith(1, '2028-03-01')
    })
    it('pausa a série e mantém erro de validação no formulário', async () => {
        await render()
        api.updateRecurrence.mockResolvedValue({ data: {} })
        await click('Pausar')
        expect(api.updateRecurrence).toHaveBeenCalledWith(1, { active: false })
        await click('Editar futuras')
        api.updateRecurrence.mockRejectedValue({
            response: { data: { errors: { amount_cents: ['Valor inválido'] } } },
        })
        await wrapper.get('#recurrence-edit').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('Valor inválido')
        expect(wrapper.find('#recurrence-edit').exists()).toBe(true)
    })
    it('descarta respostas atrasadas após trocar de escritório', async () => {
        let finish
        api.listRecurrences.mockReturnValueOnce(
            new Promise((resolve) => {
                finish = resolve
            }),
        )
        await render()
        api.listRecurrences.mockResolvedValue({ data: [] })
        auth.currentTenant = 'segundo'
        await flushPromises()
        finish({ data: [rule] })
        await flushPromises()
        expect(wrapper.text()).not.toContain('Locador')
    })
})
