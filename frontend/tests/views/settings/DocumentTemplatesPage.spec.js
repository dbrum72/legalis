import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises, RouterLinkStub } from '@vue/test-utils'
import { reactive } from 'vue'
import DocumentTemplatesPage from '@/views/settings/DocumentTemplatesPage.vue'
import { AppFileUpload } from '@/components/forms'
import * as api from '@/api/document-templates.js'
let auth, wrapper
vi.mock('@/stores/auth.js', () => ({ useAuthStore: () => auth }))
vi.mock('@/api/document-templates.js', () => ({
    listDocumentTemplates: vi.fn(),
    getDocumentTemplateFields: vi.fn(),
    importDocumentTemplate: vi.fn(),
    downloadDocumentTemplate: vi.fn(),
    replaceDocumentTemplate: vi.fn(),
    removeDocumentTemplate: vi.fn(),
}))
const model = {
    id: 1,
    name: 'Inicial',
    fields: ['cliente.nome'],
    created_at: '2026-09-15T10:00:00Z',
}
const button = (text) => wrapper.findAll('button').find((item) => item.text() === text)
async function render(permissions = ['documents.generate', 'folders.update']) {
    auth = reactive({
        currentTenant: 'office-a',
        organization: { name: 'Escritório A' },
        hasPermission: (permission) => permissions.includes(permission),
    })
    wrapper = mount(DocumentTemplatesPage, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
}
beforeEach(() => {
    vi.clearAllMocks()
    api.listDocumentTemplates.mockResolvedValue({ data: [model] })
    api.getDocumentTemplateFields.mockResolvedValue({ data: { 'cliente.nome': 'Nome do cliente' } })
})
afterEach(() => wrapper?.unmount())
describe('Modelos Word em Configurações', () => {
    it('replaces only after a successful upload and keeps archived originals accessible', async () => {
        api.listDocumentTemplates.mockResolvedValue({ data: [{ ...model }] })
        await render()
        await button('Substituir modelo').trigger('click')
        expect(wrapper.find('#template-name').element.value).toBe('Inicial')
        wrapper
            .findComponent(AppFileUpload)
            .vm.$emit('update:modelValue', [new File(['x'], 'novo.docx')])
        await flushPromises()
        api.replaceDocumentTemplate.mockRejectedValueOnce({
            response: { data: { message: 'Arquivo inválido' } },
        })
        await wrapper.find('form').trigger('submit')
        await flushPromises()
        expect(wrapper.text()).toContain('Arquivo inválido')
        expect(wrapper.findAll('.templates-page__list li')).toHaveLength(1)
        api.replaceDocumentTemplate.mockResolvedValueOnce({ data: { ...model, id: 2 } })
        await wrapper.find('form').trigger('submit')
        await flushPromises()
        expect(api.replaceDocumentTemplate.mock.calls[0][0]).toBe(1)
        expect(api.importDocumentTemplate).not.toHaveBeenCalled()
        expect(wrapper.findAll('.templates-page__list li')).toHaveLength(1)
        await wrapper.find('input[type="checkbox"]').setValue(true)
        expect(wrapper.findAll('.templates-page__list li')).toHaveLength(2)
        expect(wrapper.text()).toContain('Arquivado')
    })
    it('requires confirmation to remove and uses the server archive result', async () => {
        api.listDocumentTemplates.mockResolvedValue({ data: [{ ...model, generations_count: 1 }] })
        await render()
        await button('Arquivar modelo').trigger('click')
        expect(api.removeDocumentTemplate).not.toHaveBeenCalled()
        api.removeDocumentTemplate.mockResolvedValueOnce({ data: { archived: true } })
        await button('Confirmar').trigger('click')
        await flushPromises()
        expect(api.removeDocumentTemplate).toHaveBeenCalledWith(1)
        expect(wrapper.findAll('.templates-page__list li')).toHaveLength(0)
        await wrapper.find('input[type="checkbox"]').setValue(true)
        expect(button('Baixar original')).toBeDefined()
        expect(button('Substituir modelo')).toBeUndefined()
    })
    it('imports without selecting a folder and makes the new model visible', async () => {
        await render()
        expect(wrapper.text()).toContain('Modelos Word de Escritório A')
        expect(wrapper.text()).toContain('{{ cliente.nome }}')
        await wrapper.find('#template-name').setValue('Procuração')
        const file = new File(['fixture'], 'modelo.docx')
        wrapper.findComponent(AppFileUpload).vm.$emit('update:modelValue', [file])
        await flushPromises()
        api.importDocumentTemplate.mockResolvedValue({
            data: { ...model, id: 2, name: 'Procuração' },
        })
        await wrapper.find('form').trigger('submit')
        await flushPromises()
        const payload = api.importDocumentTemplate.mock.calls[0][0]
        expect(payload.get('name')).toBe('Procuração')
        expect(payload.get('file').name).toBe('modelo.docx')
        expect(payload.has('folder_id')).toBe(false)
        expect(wrapper.text()).toContain('Modelo importado')
        expect(wrapper.findAll('.templates-page__list li')).toHaveLength(2)
        expect(wrapper.find('#template-name').element.value).toBe('')
    })
    it('allows readers to search and download but not import', async () => {
        await render(['documents.generate'])
        expect(wrapper.find('form').exists()).toBe(false)
        expect(button('Baixar original')).toBeDefined()
        await wrapper.find('#template-search').setValue('inexistente')
        expect(wrapper.text()).toContain('Nenhum modelo encontrado')
    })
    it('preserves upload fields after validation failure and supports loading retry', async () => {
        api.listDocumentTemplates.mockRejectedValueOnce(new Error('offline'))
        await render()
        await button('Tentar novamente').trigger('click')
        await flushPromises()
        await wrapper.find('#template-name').setValue('Documento')
        wrapper
            .findComponent(AppFileUpload)
            .vm.$emit('update:modelValue', [new File(['x'], 'bad.docx')])
        await flushPromises()
        api.importDocumentTemplate.mockRejectedValue({
            response: { data: { errors: { file: ['Marcador inválido'] } } },
        })
        await wrapper.find('form').trigger('submit')
        await flushPromises()
        expect(wrapper.find('[role="alert"]').text()).toBe('Marcador inválido')
        expect(wrapper.find('#template-name').element.value).toBe('Documento')
    })
    it('clears the form and ignores an old import after the organization changes', async () => {
        await render()
        await wrapper.find('#template-name').setValue('Escritório anterior')
        wrapper
            .findComponent(AppFileUpload)
            .vm.$emit('update:modelValue', [new File(['x'], 'old.docx')])
        await flushPromises()
        let resolve
        api.importDocumentTemplate.mockImplementation(
            () =>
                new Promise((r) => {
                    resolve = r
                }),
        )
        await wrapper.find('form').trigger('submit')
        api.listDocumentTemplates.mockResolvedValueOnce({ data: [] })
        auth.currentTenant = 'office-b'
        auth.organization.name = 'Escritório B'
        await flushPromises()
        resolve({ data: { ...model, name: 'Arquivo do escritório anterior' } })
        await flushPromises()
        expect(wrapper.text()).not.toContain('Arquivo do escritório anterior')
        expect(wrapper.text()).toContain('Nenhum modelo importado')
        expect(wrapper.find('#template-name').element.value).toBe('')
    })
})
