import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import FolderDeadlines from '@/views/folders/components/FolderDeadlines.vue'

import { useAuthStore } from '@/stores/auth.js'

import { useFolderDeadlinesStore } from '@/stores/folder-deadlines.js'

vi.mock('@/api/folder-deadlines.js', () => ({
    listFolderDeadlines: vi.fn(),
    createFolderDeadline: vi.fn(),
    completeFolderDeadline: vi.fn(),
    deleteFolderDeadline: vi.fn(),
}))

function defaultDeadlines() {
    return [
        {
            id: 1,
            folder_id: 10,
            user_id: 1,
            title: 'Apresentar contestação',
            description: 'Prazo para apresentação de contestação.',
            due_at: '2026-08-20T12:00:00.000000Z',
            status: 'pending',
            completed_at: null,

            user: {
                id: 1,
                name: 'Lucas',
            },
        },

        {
            id: 2,
            folder_id: 10,
            user_id: 2,
            title: 'Protocolar manifestação',
            description: null,
            due_at: '2026-08-25T12:00:00.000000Z',
            status: 'completed',
            completed_at: '2026-08-18T14:00:00.000000Z',

            user: {
                id: 2,
                name: 'Maria',
            },
        },
    ]
}

async function mountComponent({
    deadlines = defaultDeadlines(),
    permissions = [],
    fetchError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    const folderDeadlinesStore = useFolderDeadlinesStore()

    authStore.permissions = permissions

    const fetchDeadlinesSpy = vi.spyOn(folderDeadlinesStore, 'fetchDeadlines')

    if (fetchError) {
        fetchDeadlinesSpy.mockRejectedValue(fetchError)
    } else {
        fetchDeadlinesSpy.mockImplementation(async () => {
            folderDeadlinesStore.deadlines = deadlines

            return deadlines
        })
    }

    const wrapper = mount(FolderDeadlines, {
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
        folderDeadlinesStore,
        fetchDeadlinesSpy,
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

async function openCreateForm(wrapper) {
    const button = findButton(wrapper, 'Novo prazo')

    expect(button).toBeTruthy()

    await button.trigger('click')
}

describe('FolderDeadlines', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        vi.restoreAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega prazos ao montar', async () => {
        const { fetchDeadlinesSpy } = await mountComponent()

        expect(fetchDeadlinesSpy).toHaveBeenCalledTimes(1)

        expect(fetchDeadlinesSpy).toHaveBeenCalledWith(10)
    })

    it('renderiza título e descrição', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Prazos')

        expect(wrapper.text()).toContain('Acompanhe os prazos vinculados à pasta.')
    })

    it('renderiza prazos carregados', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Apresentar contestação')

        expect(wrapper.text()).toContain('Protocolar manifestação')
    })

    it('renderiza descrição do prazo', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Prazo para apresentação de contestação.')
    })

    it('renderiza responsável pelo prazo', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Lucas')

        expect(wrapper.text()).toContain('Maria')
    })

    it('renderiza status pendente e concluído', async () => {
        const { wrapper } = await mountComponent()

        const text = wrapper.text()

        expect(text).toContain('Pendente')

        expect(text).toContain('Concluído')
    })

    it('renderiza estado vazio quando não existem prazos', async () => {
        const { wrapper } = await mountComponent({
            deadlines: [],
        })

        expect(wrapper.text()).toContain('Nenhum prazo registrado.')
    })

    it('exibe erro quando carregamento falha', async () => {
        const { wrapper } = await mountComponent({
            fetchError: new Error('Falha ao carregar'),
        })

        expect(wrapper.text()).toContain('Não foi possível carregar os prazos. Tente novamente.')

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('mostra ação Novo prazo com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        expect(wrapper.text()).toContain('Novo prazo')
    })

    it('não mostra ação Novo prazo sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: [],
        })

        expect(wrapper.text()).not.toContain('Novo prazo')
    })

    it('abre formulário para criar prazo', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        await openCreateForm(wrapper)

        expect(wrapper.find('input[name="title"]').exists()).toBe(true)

        expect(wrapper.find('input[name="due_at"]').exists()).toBe(true)

        expect(wrapper.find('textarea[name="description"]').exists()).toBe(true)
    })

    it('envia novo prazo para a store', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderDeadlinesStore, 'createDeadline').mockResolvedValue({
            id: 3,
            folder_id: 10,
            title: 'Apresentar recurso',
            status: 'pending',
        })

        await openCreateForm(wrapper)

        await wrapper.get('input[name="title"]').setValue('Apresentar recurso')

        await wrapper.get('input[name="due_at"]').setValue('2026-08-30T18:00')

        await wrapper
            .get('textarea[name="description"]')
            .setValue('Prazo para interposição do recurso.')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(createSpy).toHaveBeenCalledTimes(1)
        })

        expect(createSpy).toHaveBeenCalledWith(10, {
            title: 'Apresentar recurso',

            description: 'Prazo para interposição do recurso.',

            due_at: new Date('2026-08-30T18:00').toISOString(),
        })
    })

    it('não cria prazo sem título', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderDeadlinesStore, 'createDeadline')

        await openCreateForm(wrapper)

        await wrapper.get('input[name="due_at"]').setValue('2026-08-30T18:00')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
    })

    it('não cria prazo sem vencimento', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderDeadlinesStore, 'createDeadline')

        await openCreateForm(wrapper)

        await wrapper.get('input[name="title"]').setValue('Apresentar recurso')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
    })

    it('cancela criação de prazo', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        await openCreateForm(wrapper)

        expect(wrapper.find('form').exists()).toBe(true)

        const cancelButton = findButton(wrapper, 'Cancelar')

        expect(cancelButton).toBeTruthy()

        await cancelButton.trigger('click')

        expect(wrapper.find('form').exists()).toBe(false)
    })

    it('exibe erro quando criação do prazo falha', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDeadlinesStore, 'createDeadline').mockRejectedValue(
            new Error('Falha ao criar'),
        )

        await openCreateForm(wrapper)

        await wrapper.get('input[name="title"]').setValue('Apresentar recurso')

        await wrapper.get('input[name="due_at"]').setValue('2026-08-30T18:00')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível criar o prazo. Tente novamente.')
        })

        expect(wrapper.find('form').exists()).toBe(true)
    })

    it('mostra Concluir apenas para prazo pendente com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        expect(findButtons(wrapper, 'Concluir')).toHaveLength(1)
    })

    it('não mostra Concluir sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: [],
        })

        expect(findButtons(wrapper, 'Concluir')).toHaveLength(0)
    })

    it('conclui prazo pendente', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const completeSpy = vi.spyOn(folderDeadlinesStore, 'completeDeadline').mockResolvedValue({
            id: 1,
            folder_id: 10,
            title: 'Apresentar contestação',
            status: 'completed',
            completed_at: '2026-08-18T15:00:00.000000Z',
        })

        const button = findButton(wrapper, 'Concluir')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledTimes(1)

            expect(completeSpy).toHaveBeenCalledWith(10, 1)
        })
    })

    it('exibe erro quando conclusão do prazo falha', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDeadlinesStore, 'completeDeadline').mockRejectedValue(
            new Error('Falha ao concluir'),
        )

        const button = findButton(wrapper, 'Concluir')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível concluir o prazo. Tente novamente.')
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

        expect(document.body.textContent).toContain('Excluir prazo')

        expect(document.body.textContent).toContain(
            'Deseja realmente excluir o prazo "Apresentar contestação"?',
        )
    })

    it('cancela exclusão sem remover prazo', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderDeadlinesStore, 'removeDeadline')

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        const cancelButton = findTeleportedButton('Cancelar')

        expect(cancelButton).toBeTruthy()

        cancelButton.click()

        await wrapper.vm.$nextTick()

        expect(removeSpy).not.toHaveBeenCalled()

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()
    })

    it('confirma exclusão do prazo', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderDeadlinesStore, 'removeDeadline').mockResolvedValue()

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

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
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDeadlinesStore, 'removeDeadline').mockRejectedValue(
            new Error('Falha ao excluir'),
        )

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível excluir o prazo. Tente novamente.')
        })

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })

    it('emite changed após criar prazo com sucesso', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDeadlinesStore, 'createDeadline').mockResolvedValue({
            id: 3,
            folder_id: 10,
            title: 'Apresentar recurso',
            due_at: '2026-08-30T21:00:00.000Z',
            status: 'pending',
        })

        await openCreateForm(wrapper)

        await wrapper.get('input[name="title"]').setValue('Apresentar recurso')

        await wrapper.get('input[name="due_at"]').setValue('2026-08-30T18:00')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.emitted('changed')).toHaveLength(1)
    })

    it('emite changed após concluir prazo com sucesso', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDeadlinesStore, 'completeDeadline').mockResolvedValue({
            id: 1,
            folder_id: 10,
            status: 'completed',
            completed_at: '2026-08-22T15:00:00.000Z',
        })

        const completeButton = findButton(wrapper, 'Concluir')

        expect(completeButton).toBeTruthy()

        await completeButton.trigger('click')

        await flushPromises()

        expect(wrapper.emitted('changed')).toHaveLength(1)
    })

    it('emite changed após excluir prazo com sucesso', async () => {
        const { wrapper, folderDeadlinesStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderDeadlinesStore, 'removeDeadline').mockResolvedValue()

        const deleteButton = findButtons(wrapper, 'Excluir')[0]

        expect(deleteButton).toBeTruthy()

        await deleteButton.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.emitted('changed')).toHaveLength(1)
        })
    })
})
