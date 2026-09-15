import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { reactive } from 'vue'
const api = vi.hoisted(() => ({
    listClassifications: vi.fn(),
    createClassification: vi.fn(),
    updateClassification: vi.fn(),
}))
vi.mock('@/api/financial-classifications.js', () => api)
let auth
vi.mock('@/stores/auth.js', () => ({ useAuthStore: () => auth }))
import FinancialOrganization from '@/views/settings/FinancialClassificationPage.vue'
import ClassificationFields from '@/views/finance/components/ClassificationFields.vue'
import AppIcon from '@/components/ui/AppIcon/index.vue'
let wrapper, items
const render = async (component = FinancialOrganization, props = {}) => {
    wrapper = mount(component, {
        props: component === FinancialOrganization ? { kind: 'category', ...props } : props,
        global: { components: { AppIcon }, stubs: { RouterLink: { template: '<a><slot /></a>' } } },
    })
    await flushPromises()
}
describe('Organização financeira', () => {
    it('oferece consulta sem ações de manutenção para quem não pode gerenciar', async () => {
        auth.hasPermission = () => false
        await render()
        expect(wrapper.find('form').exists()).toBe(false)
        expect(wrapper.find('[aria-label="Renomear Estrutura"]').exists()).toBe(false)
        expect(wrapper.text()).toContain('Estrutura')
    })
    it('separa os tipos e filtra nome e situação', async () => {
        await render(FinancialOrganization, { kind: 'cost_center' })
        expect(wrapper.text()).toContain('Sede')
        expect(wrapper.text()).not.toContain('Estrutura')
        await wrapper.setProps({ kind: 'category' })
        await wrapper.get('#classification-status').setValue('inactive')
        expect(wrapper.text()).toContain('Antiga')
        expect(wrapper.text()).not.toContain('Estrutura')
        await wrapper.get('#classification-search').setValue('inexistente')
        expect(wrapper.text()).toContain('Nenhum resultado')
    })
    beforeEach(() => {
        vi.resetAllMocks()
        auth = reactive({ currentTenant: 'escritorio', hasPermission: () => true })
        items = [
            { id: 1, kind: 'category', name: 'Estrutura', active: true },
            { id: 2, kind: 'category', name: 'Antiga', active: false },
            { id: 3, kind: 'cost_center', name: 'Sede', active: true },
        ]
        api.listClassifications.mockImplementation(async () => ({ data: items }))
    })
    afterEach(() => wrapper?.unmount())
    it('cadastra uma categoria e avisa a página para atualizar as listas', async () => {
        await render()
        api.createClassification.mockResolvedValue({ data: { id: 4 } })
        await wrapper.get('#classification-name').setValue('  Custas  ')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(api.createClassification).toHaveBeenCalledWith({ kind: 'category', name: 'Custas' })
        expect(wrapper.text()).toContain('Cadastro salvo.')
        expect(wrapper.get('#classification-name').element.value).toBe('')
    })
    it('renomeia sem modificar o tipo e desativa preservando o item', async () => {
        await render()
        api.updateClassification.mockResolvedValue({ data: {} })
        await wrapper.get('[aria-label="Renomear Estrutura"]').trigger('click')
        expect(wrapper.find('#classification-kind').exists()).toBe(false)
        await wrapper.get('#classification-name').setValue('Administrativo')
        await wrapper.get('form').trigger('submit')
        await flushPromises()
        expect(api.updateClassification).toHaveBeenCalledWith(1, { name: 'Administrativo' })
        await wrapper.get('[aria-label="Desativar Estrutura"]').trigger('click')
        await flushPromises()
        expect(api.updateClassification).toHaveBeenCalledWith(1, { active: false })
    })
    it('preserva nome e mostra erro de validação sem duplicar envio', async () => {
        await render()
        let reject
        api.createClassification.mockImplementation(
            () =>
                new Promise((_, fail) => {
                    reject = fail
                }),
        )
        await wrapper.get('#classification-name').setValue('Estrutura')
        await wrapper.get('form').trigger('submit')
        await wrapper.get('form').trigger('submit')
        expect(api.createClassification).toHaveBeenCalledTimes(1)
        reject({ response: { data: { errors: { name: ['Nome já cadastrado.'] } } } })
        await flushPromises()
        expect(wrapper.get('[role="alert"]').text()).toBe('Nome já cadastrado.')
        expect(wrapper.get('#classification-name').element.value).toBe('Estrutura')
    })
    it('mantém inativa apenas se já selecionada e permite pesquisar todas no filtro', async () => {
        await render(ClassificationFields, { prefix: 'test', categoryId: '', costCenterId: '' })
        expect(wrapper.get('#test-category').text()).not.toContain('Antiga')
        expect(wrapper.get('#test-category').text()).not.toContain('Sede')
        await wrapper.setProps({ categoryId: 2 })
        expect(wrapper.get('#test-category').text()).toContain('Antiga (inativo)')
        await wrapper.setProps({ categoryId: '', includeInactive: true })
        expect(wrapper.get('#test-category').text()).toContain('Antiga (inativo)')
    })
    it('descarta lista antiga ao trocar escritório e limpa edição pendente', async () => {
        await render()
        await wrapper.get('[aria-label="Renomear Estrutura"]').trigger('click')
        let finish
        api.listClassifications.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    finish = resolve
                }),
        )
        auth.currentTenant = 'segundo'
        await flushPromises()
        auth.currentTenant = 'terceiro'
        api.listClassifications.mockResolvedValue({ data: [] })
        await flushPromises()
        finish({ data: items })
        await flushPromises()
        expect(wrapper.text()).not.toContain('Estrutura')
        expect(wrapper.get('#classification-name').element.value).toBe('')
    })
    it('mostra falha de catálogo e permite nova tentativa', async () => {
        api.listClassifications.mockRejectedValueOnce(new Error('offline'))
        await render(ClassificationFields, { prefix: 'test' })
        expect(wrapper.get('[role="alert"]').text()).toContain('Não foi possível carregar')
        await wrapper.get('button').trigger('click')
        await flushPromises()
        expect(wrapper.find('[role="alert"]').exists()).toBe(false)
    })
})
