import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import FolderDocumentAutomation from '@/views/folders/components/FolderDocumentAutomation.vue'
import { useAuthStore } from '@/stores/auth.js'
import api from '@/api/client.js'
vi.mock('@/api/client.js', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

const fields = ['pasta.nome', 'campo.fatos']
const findButton = (wrapper, text) => wrapper.findAll('button').find(b => b.text() === text)
async function open(permissions = ['folders.update']) {
    const pinia = createPinia(); setActivePinia(pinia)
    useAuthStore().permissions = permissions
    const wrapper = mount(FolderDocumentAutomation, { props: { folderId: 10 }, global: { plugins: [pinia], stubs: { RouterLink: RouterLinkStub } } })
    await findButton(wrapper, 'Gerar a partir de modelo Word').trigger('click'); await flushPromises()
    return wrapper
}
beforeEach(() => {
    vi.clearAllMocks()
    api.get.mockImplementation(url => Promise.resolve({ data: url === '/document-templates'
        ? [{ id: 1, name: 'Inicial', fields }]
        : { catalog: { 'pasta.nome': 'Nome da pasta' }, parties: [] } }))
})

describe('Word document automation', () => {
    it('keeps the reviewed values and selection when models refresh on focus', async () => {
        const wrapper = await open()
        expect(wrapper.text()).not.toContain('Importar modelo .docx')
        expect(wrapper.text()).not.toContain('Baixar modelo original')
        expect(wrapper.findComponent(RouterLinkStub).props('to')).toEqual({ name: 'settings.document-templates' })
        expect(wrapper.findComponent(RouterLinkStub).attributes('target')).toBe('_blank')
        expect(wrapper.findComponent(RouterLinkStub).attributes('rel')).toBe('opener')
        await wrapper.find('select').setValue('1')
        api.post.mockResolvedValueOnce({ data: { values: { 'pasta.nome': 'Pasta', 'campo.fatos': 'Rascunho' }, missing: [], text: 'Texto revisado', signature: 'a'.repeat(64) } })
        await wrapper.find('form').trigger('submit'); await flushPromises()
        await wrapper.findAll('textarea')[1].setValue('Alteração ainda não enviada')
        api.get.mockResolvedValueOnce({ data: [{ id: 2, name: 'Modelo novo', fields }, { id: 1, name: 'Inicial', fields }] })
        window.dispatchEvent(new Event('focus')); await flushPromises()
        expect(wrapper.find('select').element.value).toBe('1')
        expect(wrapper.findAll('textarea')[1].element.value).toBe('Alteração ainda não enviada')
        expect(wrapper.text()).toContain('Texto revisado')
        expect(wrapper.text()).toContain('Modelo novo')
        expect(findButton(wrapper, 'Gerar Word e salvar na pasta').attributes('disabled')).toBeDefined()
        api.get.mockRejectedValueOnce(new Error('offline'))
        window.dispatchEvent(new Event('focus')); await flushPromises()
        expect(wrapper.text()).toContain('Seu preenchimento foi preservado')
        expect(wrapper.findAll('textarea')[1].element.value).toBe('Alteração ainda não enviada')
        wrapper.unmount()
    })
    it('requires refreshed preview after editing and emits saved document only after generation', async () => {
        const wrapper = await open()
        expect(wrapper.find('label[for="word-template-select"]').text()).toBe('Modelo')
        await wrapper.find('select').setValue('1'); await flushPromises()
        api.post.mockResolvedValueOnce({ data: { values: { 'pasta.nome': 'Pasta', 'campo.fatos': '' }, missing: ['campo.fatos'], text: 'Pasta', signature: 'a'.repeat(64) } })
        await wrapper.findAll('form').at(-1).trigger('submit'); await flushPromises()
        const generate = () => findButton(wrapper, 'Gerar Word e salvar na pasta')
        expect(generate().attributes('disabled')).toBeDefined()
        await wrapper.findAll('textarea')[1].setValue('Fatos revisados')
        expect(wrapper.text()).toContain('Atualize a prévia')
        api.post.mockResolvedValueOnce({ data: { values: { 'pasta.nome': 'Pasta', 'campo.fatos': 'Fatos revisados' }, missing: [], text: 'Pasta Fatos revisados', signature: 'b'.repeat(64) } })
        await wrapper.findAll('form').at(-1).trigger('submit'); await flushPromises()
        expect(generate().attributes('disabled')).toBeUndefined()
        api.post.mockResolvedValueOnce({ data: { id: 25, name: 'Inicial' } })
        await generate().trigger('click'); await flushPromises()
        expect(api.post).toHaveBeenLastCalledWith('/folders/10/document-generation', expect.objectContaining({ signature: 'b'.repeat(64), values: { 'pasta.nome': 'Pasta', 'campo.fatos': 'Fatos revisados' } }))
        expect(wrapper.emitted('generated')[0][0].id).toBe(25)
        expect(wrapper.text()).toContain('Documento salvo na pasta')
        wrapper.unmount()
    })

    it('shows retry on loading failure and recovers', async () => {
        api.get.mockRejectedValueOnce(new Error('offline'))
        const wrapper = await open()
        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
        await findButton(wrapper, 'Tentar novamente').trigger('click'); await flushPromises()
        expect(wrapper.find('select').exists()).toBe(true)
        wrapper.unmount()
    })

    it('hides import and generation for readers and discards old folder responses', async () => {
        const pending = []
        api.get.mockImplementation(() => new Promise(resolve => { pending.push(resolve) }))
        const pinia = createPinia(); setActivePinia(pinia); useAuthStore().permissions = []
        const wrapper = mount(FolderDocumentAutomation, { props: { folderId: 10 }, global: { plugins: [pinia] } })
        await findButton(wrapper, 'Gerar a partir de modelo Word').trigger('click')
        await wrapper.setProps({ folderId: 20 })
        pending[0]({ data: [{ id: 9, name: 'Modelo da pasta anterior', fields }] })
        pending[1]({ data: { catalog: {}, parties: [] } }); await flushPromises()
        expect(wrapper.text()).not.toContain('Modelo da pasta anterior')
        expect(wrapper.text()).not.toContain('Importar modelo .docx')
        expect(wrapper.text()).not.toContain('Carregando modelos')
        wrapper.unmount()
    })
})
