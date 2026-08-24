import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import FolderDocuments from '@/views/folders/components/FolderDocuments.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useFolderDocumentsStore } from '@/stores/folder-documents.js'

vi.mock('@/api/folder-documents.js', () => ({
    listFolderDocuments: vi.fn(),
    uploadFolderDocument: vi.fn(),
    downloadFolderDocument: vi.fn(),
    deleteFolderDocument: vi.fn(),
}))

function defaultDocuments() {
    return [
        {
            id: 1,
            folder_id: 10,
            name: 'Petição inicial',
            original_name: 'peticao-inicial.pdf',
            mime_type: 'application/pdf',
            size: 2048,
            description: 'Petição inicial protocolada.',
            created_at: '2026-08-17T12:00:00.000000Z',

            user: {
                id: 1,
                name: 'Lucas',
            },
        },

        {
            id: 2,
            folder_id: 10,
            name: 'Contrato',
            original_name: 'contrato.docx',
            mime_type: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            size: 4096,
            description: null,
            created_at: '2026-08-17T13:00:00.000000Z',

            user: {
                id: 2,
                name: 'Maria',
            },
        },
    ]
}

async function mountComponent({
    documents = defaultDocuments(),
    permissions = [],
    fetchError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    const folderDocumentsStore = useFolderDocumentsStore()

    authStore.permissions = permissions

    const fetchDocumentsSpy = vi.spyOn(folderDocumentsStore, 'fetchDocuments')

    if (fetchError) {
        fetchDocumentsSpy.mockRejectedValue(fetchError)
    } else {
        fetchDocumentsSpy.mockImplementation(async () => {
            folderDocumentsStore.documents = documents

            return documents
        })
    }

    const wrapper = mount(FolderDocuments, {
        props: {
            folderId: 10,
        },

        global: {
            plugins: [pinia],
        },
    })

    await flushPromises()

    return {
        wrapper,
        authStore,
        folderDocumentsStore,
        fetchDocumentsSpy,
    }
}

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

function findButtons(wrapper, label) {
    return wrapper.findAll('button').filter((button) => button.text().trim() === label)
}

function findTeleportedButton(label) {
    return Array.from(document.querySelectorAll('button')).find(
        (button) => button.textContent.trim() === label,
    )
}

async function openUploadForm(wrapper) {
    const button = findButton(wrapper, 'Anexar documento')

    expect(button).toBeTruthy()

    await button.trigger('click')
}

async function selectFile(wrapper, file) {
    const fileInput = wrapper.get('input[type="file"]')

    Object.defineProperty(fileInput.element, 'files', {
        value: [file],

        configurable: true,
    })

    await fileInput.trigger('change')
}

describe('FolderDocuments', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        vi.restoreAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega documentos ao montar', async () => {
        const { fetchDocumentsSpy } = await mountComponent()

        expect(fetchDocumentsSpy).toHaveBeenCalledTimes(1)

        expect(fetchDocumentsSpy).toHaveBeenCalledWith(10)
    })

    it('renderiza título e descrição', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Documentos')

        expect(wrapper.text()).toContain('Consulte os documentos vinculados à pasta.')
    })

    it('renderiza documentos carregados', async () => {
        const { wrapper } = await mountComponent()

        const text = wrapper.text()

        expect(text).toContain('Petição inicial')

        expect(text).toContain('peticao-inicial.pdf')

        expect(text).toContain('Contrato')

        expect(text).toContain('contrato.docx')
    })

    it('renderiza responsável pelo documento', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Lucas')

        expect(wrapper.text()).toContain('Maria')
    })

    it('renderiza descrição quando disponível', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Petição inicial protocolada.')
    })

    it('renderiza travessão quando descrição está ausente', async () => {
        const { wrapper } = await mountComponent({
            documents: [
                {
                    id: 1,
                    folder_id: 10,
                    name: 'Contrato',
                    original_name: 'contrato.pdf',
                    mime_type: 'application/pdf',
                    size: 1024,
                    description: null,
                    created_at: '2026-08-17T12:00:00.000000Z',

                    user: {
                        id: 1,
                        name: 'Lucas',
                    },
                },
            ],
        })

        expect(wrapper.text()).toContain('—')
    })

    it('renderiza estado vazio quando não existem documentos', async () => {
        const { wrapper } = await mountComponent({
            documents: [],
        })

        expect(wrapper.text()).toContain('Nenhum documento anexado.')
    })

    it('exibe erro quando carregamento falha', async () => {
        const { wrapper } = await mountComponent({
            fetchError: new Error('Falha ao carregar'),
        })

        expect(wrapper.text()).toContain(
            'Não foi possível carregar os documentos. Tente novamente.',
        )

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('mostra ação Anexar documento com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        expect(wrapper.text()).toContain('Anexar documento')
    })

    it('não mostra ação Anexar documento sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: [],
        })

        expect(wrapper.text()).not.toContain('Anexar documento')
    })

    it('abre formulário para anexar documento', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        await openUploadForm(wrapper)

        expect(wrapper.find('input[type="file"]').exists()).toBe(true)

        expect(wrapper.find('input[name="name"]').exists()).toBe(true)

        expect(wrapper.find('textarea[name="description"]').exists()).toBe(true)
    })

    it('envia documento selecionado para a store', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const uploadSpy = vi.spyOn(folderDocumentsStore, 'uploadDocument').mockResolvedValue({
            id: 20,
            folder_id: 10,
            name: 'Petição inicial',
            original_name: 'peticao.pdf',
        })

        await openUploadForm(wrapper)

        const file = new File(['conteúdo do documento'], 'peticao.pdf', {
            type: 'application/pdf',
        })

        await selectFile(wrapper, file)

        await wrapper.get('input[name="name"]').setValue('Petição inicial')

        await wrapper.get('textarea[name="description"]').setValue('Petição inicial protocolada.')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(uploadSpy).toHaveBeenCalledTimes(1)
        })

        const [folderId, payload] = uploadSpy.mock.calls[0]

        expect(folderId).toBe(10)

        expect(payload).toBeInstanceOf(FormData)

        expect(payload.get('file')).toBe(file)

        expect(payload.get('name')).toBe('Petição inicial')

        expect(payload.get('description')).toBe('Petição inicial protocolada.')
    })

    it('cancela formulário de anexação', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        await openUploadForm(wrapper)

        expect(wrapper.find('form').exists()).toBe(true)

        const cancelButton = findButton(wrapper, 'Cancelar')

        expect(cancelButton).toBeTruthy()

        await cancelButton.trigger('click')

        expect(wrapper.find('form').exists()).toBe(false)

        expect(wrapper.text()).toContain('Anexar documento')
    })

    it('não envia documento sem arquivo', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const uploadSpy = vi.spyOn(folderDocumentsStore, 'uploadDocument')

        await openUploadForm(wrapper)

        await wrapper.get('input[name="name"]').setValue('Petição inicial')

        await wrapper.get('form').trigger('submit')

        expect(uploadSpy).not.toHaveBeenCalled()
    })

    it('não envia documento sem nome', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const uploadSpy = vi.spyOn(folderDocumentsStore, 'uploadDocument')

        await openUploadForm(wrapper)

        const file = new File(['conteúdo'], 'peticao.pdf', {
            type: 'application/pdf',
        })

        await selectFile(wrapper, file)

        await wrapper.get('form').trigger('submit')

        expect(uploadSpy).not.toHaveBeenCalled()
    })

    it('exibe erro quando anexação falha', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDocumentsStore, 'uploadDocument').mockRejectedValue(
            new Error('Falha no upload'),
        )

        await openUploadForm(wrapper)

        const file = new File(['conteúdo'], 'peticao.pdf', {
            type: 'application/pdf',
        })

        await selectFile(wrapper, file)

        await wrapper.get('input[name="name"]').setValue('Petição inicial')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível anexar o documento. Tente novamente.',
            )
        })

        expect(wrapper.find('form').exists()).toBe(true)
    })

    it('fecha formulário após anexação bem-sucedida', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDocumentsStore, 'uploadDocument').mockResolvedValue({
            id: 20,
            folder_id: 10,
            name: 'Petição inicial',
            original_name: 'peticao.pdf',
        })

        await openUploadForm(wrapper)

        const file = new File(['conteúdo'], 'peticao.pdf', {
            type: 'application/pdf',
        })

        await selectFile(wrapper, file)

        await wrapper.get('input[name="name"]').setValue('Petição inicial')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.find('form').exists()).toBe(false)

        expect(wrapper.text()).toContain('Anexar documento')
    })

    it('mostra ação Baixar para cada documento', async () => {
        const { wrapper } = await mountComponent()

        const buttons = findButtons(wrapper, 'Baixar')

        expect(buttons).toHaveLength(2)
    })

    it('solicita download do documento selecionado', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent()

        const blob = new Blob(['conteúdo'], {
            type: 'application/pdf',
        })

        const downloadSpy = vi
            .spyOn(folderDocumentsStore, 'downloadDocument')
            .mockResolvedValue(blob)

        const createObjectURLSpy = vi.fn(() => 'blob:http://localhost/documento')

        const revokeObjectURLSpy = vi.fn()

        Object.defineProperty(URL, 'createObjectURL', {
            value: createObjectURLSpy,

            configurable: true,
        })

        Object.defineProperty(URL, 'revokeObjectURL', {
            value: revokeObjectURLSpy,

            configurable: true,
        })

        const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {})

        const downloadButton = findButtons(wrapper, 'Baixar')[0]

        expect(downloadButton).toBeTruthy()

        await downloadButton.trigger('click')

        await vi.waitFor(() => {
            expect(downloadSpy).toHaveBeenCalledTimes(1)
        })

        expect(downloadSpy).toHaveBeenCalledWith(10, 1)

        expect(createObjectURLSpy).toHaveBeenCalledWith(blob)

        expect(clickSpy).toHaveBeenCalledTimes(1)

        expect(revokeObjectURLSpy).toHaveBeenCalledWith('blob:http://localhost/documento')
    })

    it('usa nome original do arquivo no download', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            documents: [
                {
                    id: 50,
                    folder_id: 10,
                    name: 'Procuração',
                    original_name: 'procuracao-assinada.pdf',
                    mime_type: 'application/pdf',
                    size: 1024,
                    description: null,
                    created_at: '2026-08-17T12:00:00.000000Z',

                    user: {
                        id: 1,
                        name: 'Lucas',
                    },
                },
            ],
        })

        vi.spyOn(folderDocumentsStore, 'downloadDocument').mockResolvedValue(
            new Blob(['conteúdo'], {
                type: 'application/pdf',
            }),
        )

        Object.defineProperty(URL, 'createObjectURL', {
            value: vi.fn(() => 'blob:http://localhost/procuracao'),

            configurable: true,
        })

        Object.defineProperty(URL, 'revokeObjectURL', {
            value: vi.fn(),

            configurable: true,
        })

        let downloadName = null

        vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function () {
            downloadName = this.download
        })

        const button = findButton(wrapper, 'Baixar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(downloadName).toBe('procuracao-assinada.pdf')
        })
    })

    it('mostra Excluir com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        expect(findButtons(wrapper, 'Excluir')).toHaveLength(2)
    })

    it('não mostra Excluir sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: [],
        })

        expect(findButtons(wrapper, 'Excluir')).toHaveLength(0)
    })

    it('abre confirmação ao clicar em Excluir', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        expect(document.body.textContent).toContain('Excluir documento')

        expect(document.body.textContent).toContain(
            'Deseja realmente excluir o documento "Petição inicial"?',
        )
    })

    it('cancela exclusão sem remover documento', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderDocumentsStore, 'removeDocument')

        const button = findButtons(wrapper, 'Excluir')[0]

        await button.trigger('click')

        const cancelButton = findTeleportedButton('Cancelar')

        expect(cancelButton).toBeTruthy()

        cancelButton.click()

        await wrapper.vm.$nextTick()

        expect(removeSpy).not.toHaveBeenCalled()

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()
    })

    it('confirma exclusão do documento', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderDocumentsStore, 'removeDocument').mockResolvedValue()

        const button = findButtons(wrapper, 'Excluir')[0]

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(removeSpy).toHaveBeenCalledTimes(1)

            expect(removeSpy).toHaveBeenCalledWith(10, 1)
        })

        await vi.waitFor(() => {
            expect(document.querySelector('.app-confirm-dialog')).toBeNull()
        })
    })

    it('mantém confirmação aberta quando exclusão falha', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDocumentsStore, 'removeDocument').mockRejectedValue(
            new Error('Falha ao excluir'),
        )

        const button = findButtons(wrapper, 'Excluir')[0]

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível excluir o documento. Tente novamente.',
            )
        })

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })

    it('emite changed após anexar documento com sucesso', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDocumentsStore, 'uploadDocument').mockResolvedValue({
            id: 20,
            folder_id: 10,
            name: 'Petição inicial',
            original_name: 'peticao.pdf',
        })

        await openUploadForm(wrapper)

        const file = new File(['conteúdo do documento'], 'peticao.pdf', {
            type: 'application/pdf',
        })

        await selectFile(wrapper, file)

        await wrapper.get('input[name="name"]').setValue('Petição inicial')

        await wrapper.get('textarea[name="description"]').setValue('Petição inicial protocolada.')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.emitted('changed')).toHaveLength(1)
    })

    it('emite changed após excluir documento com sucesso', async () => {
        const { wrapper, folderDocumentsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDocumentsStore, 'removeDocument').mockResolvedValue()

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.emitted('changed')).toHaveLength(1)
        })
    })
})
