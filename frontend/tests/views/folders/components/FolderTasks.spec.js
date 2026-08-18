import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import FolderTasks from '@/views/folders/components/FolderTasks.vue'

import { useAuthStore } from '@/stores/auth.js'

import { useFolderTasksStore } from '@/stores/folder-tasks.js'

vi.mock('@/api/folder-tasks.js', () => ({
    listFolderTasks: vi.fn(),
    createFolderTask: vi.fn(),
    completeFolderTask: vi.fn(),
    deleteFolderTask: vi.fn(),
}))

function defaultTasks() {
    return [
        {
            id: 1,
            folder_id: 10,
            user_id: 1,
            title: 'Revisar contestação',
            description: 'Revisar a minuta antes do protocolo.',
            priority: 'high',
            due_at: '2026-08-25T18:00:00.000000Z',
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
            title: 'Telefonar para cliente',
            description: null,
            priority: 'medium',
            due_at: null,
            status: 'completed',
            completed_at: '2026-08-18T19:00:00.000000Z',

            user: {
                id: 2,
                name: 'Maria',
            },
        },
    ]
}

async function mountComponent({
    tasks = defaultTasks(),
    permissions = [],
    fetchError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    const folderTasksStore = useFolderTasksStore()

    authStore.permissions = permissions

    const fetchTasksSpy = vi.spyOn(folderTasksStore, 'fetchTasks')

    if (fetchError) {
        fetchTasksSpy.mockRejectedValue(fetchError)
    } else {
        fetchTasksSpy.mockImplementation(async () => {
            folderTasksStore.tasks = tasks

            return tasks
        })
    }

    const wrapper = mount(FolderTasks, {
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
        folderTasksStore,
        fetchTasksSpy,
    }
}

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

function findButtons(wrapper, label) {
    return wrapper.findAll('button').filter((button) => button.text().trim() === label)
}

function findTeleportedButton(label) {
    const dialog = document.querySelector('.app-confirm-dialog')

    if (!dialog) {
        return undefined
    }

    return Array.from(dialog.querySelectorAll('button')).find(
        (button) => button.textContent.trim() === label,
    )
}

describe('FolderTasks', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega tarefas ao montar', async () => {
        const { fetchTasksSpy } = await mountComponent()

        expect(fetchTasksSpy).toHaveBeenCalledTimes(1)

        expect(fetchTasksSpy).toHaveBeenCalledWith(10)
    })

    it('renderiza título e descrição', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Tarefas')

        expect(wrapper.text()).toContain('Acompanhe as tarefas vinculadas à pasta.')
    })

    it('renderiza tarefas carregadas', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Revisar contestação')

        expect(wrapper.text()).toContain('Telefonar para cliente')
    })

    it('renderiza descrição da tarefa', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Revisar a minuta antes do protocolo.')
    })

    it('renderiza responsável pela tarefa', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Lucas')

        expect(wrapper.text()).toContain('Maria')
    })

    it('renderiza prioridades das tarefas', async () => {
        const { wrapper } = await mountComponent()

        const text = wrapper.text()

        expect(text).toContain('Alta')

        expect(text).toContain('Média')
    })

    it('renderiza status pendente e concluído', async () => {
        const { wrapper } = await mountComponent()

        const text = wrapper.text()

        expect(text).toContain('Pendente')

        expect(text).toContain('Concluído')
    })

    it('renderiza vencimento quando informado', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Vencimento:')
    })

    it('não exige vencimento para renderizar tarefa', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Telefonar para cliente')
    })

    it('renderiza estado vazio quando não existem tarefas', async () => {
        const { wrapper } = await mountComponent({
            tasks: [],
        })

        expect(wrapper.text()).toContain('Nenhuma tarefa registrada.')
    })

    it('exibe erro quando carregamento falha', async () => {
        const { wrapper } = await mountComponent({
            fetchError: new Error('Falha ao carregar'),
        })

        expect(wrapper.text()).toContain('Não foi possível carregar as tarefas. Tente novamente.')

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('mostra ação Nova tarefa com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        expect(wrapper.text()).toContain('Nova tarefa')
    })

    it('não mostra ação Nova tarefa sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: [],
        })

        expect(wrapper.text()).not.toContain('Nova tarefa')
    })

    it('abre formulário para criar tarefa', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        const button = findButton(wrapper, 'Nova tarefa')

        expect(button).toBeTruthy()

        await button.trigger('click')

        expect(wrapper.find('input[name="title"]').exists()).toBe(true)

        expect(wrapper.find('select[name="priority"]').exists()).toBe(true)

        expect(wrapper.find('input[name="due_at"]').exists()).toBe(true)

        expect(wrapper.find('textarea[name="description"]').exists()).toBe(true)
    })

    it('envia nova tarefa para a store', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderTasksStore, 'createTask').mockResolvedValue({
            id: 3,
            folder_id: 10,
            title: 'Preparar recurso',
            priority: 'high',
            status: 'pending',
        })

        const button = findButton(wrapper, 'Nova tarefa')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await wrapper.get('input[name="title"]').setValue('Preparar recurso')

        await wrapper.get('select[name="priority"]').setValue('high')

        await wrapper.get('input[name="due_at"]').setValue('2026-08-30T18:00')

        await wrapper.get('textarea[name="description"]').setValue('Preparar minuta do recurso.')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(createSpy).toHaveBeenCalledTimes(1)
        })

        expect(createSpy).toHaveBeenCalledWith(10, {
            title: 'Preparar recurso',

            description: 'Preparar minuta do recurso.',

            priority: 'high',

            due_at: '2026-08-30T18:00',
        })
    })

    it('cria tarefa sem vencimento', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderTasksStore, 'createTask').mockResolvedValue({
            id: 3,
            folder_id: 10,
            title: 'Telefonar para cliente',
            priority: 'medium',
            due_at: null,
            status: 'pending',
        })

        const button = findButton(wrapper, 'Nova tarefa')

        await button.trigger('click')

        await wrapper.get('input[name="title"]').setValue('Telefonar para cliente')

        await wrapper.get('select[name="priority"]').setValue('medium')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(createSpy).toHaveBeenCalledTimes(1)
        })

        expect(createSpy).toHaveBeenCalledWith(10, {
            title: 'Telefonar para cliente',

            description: null,

            priority: 'medium',

            due_at: null,
        })
    })

    it('não cria tarefa sem título', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderTasksStore, 'createTask')

        const button = findButton(wrapper, 'Nova tarefa')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await wrapper.get('select[name="priority"]').setValue('high')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
    })

    it('cancela criação de tarefa', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        const openButton = findButton(wrapper, 'Nova tarefa')

        expect(openButton).toBeTruthy()

        await openButton.trigger('click')

        expect(wrapper.find('form').exists()).toBe(true)

        const cancelButton = findButton(wrapper, 'Cancelar')

        expect(cancelButton).toBeTruthy()

        await cancelButton.trigger('click')

        expect(wrapper.find('form').exists()).toBe(false)
    })

    it('exibe erro quando criação da tarefa falha', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderTasksStore, 'createTask').mockRejectedValue(new Error('Falha ao criar'))

        const button = findButton(wrapper, 'Nova tarefa')

        await button.trigger('click')

        await wrapper.get('input[name="title"]').setValue('Preparar recurso')

        await wrapper.get('select[name="priority"]').setValue('high')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível criar a tarefa. Tente novamente.')
        })

        expect(wrapper.find('form').exists()).toBe(true)
    })

    it('mostra Concluir apenas para tarefa pendente com folders.update', async () => {
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

    it('conclui tarefa pendente', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const completeSpy = vi.spyOn(folderTasksStore, 'completeTask').mockResolvedValue({
            id: 1,
            folder_id: 10,
            title: 'Revisar contestação',
            status: 'completed',
            completed_at: '2026-08-25T19:00:00.000000Z',
        })

        const button = findButton(wrapper, 'Concluir')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledTimes(1)

            expect(completeSpy).toHaveBeenCalledWith(10, 1)
        })
    })

    it('exibe erro quando conclusão da tarefa falha', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderTasksStore, 'completeTask').mockRejectedValue(new Error('Falha ao concluir'))

        const button = findButton(wrapper, 'Concluir')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível concluir a tarefa. Tente novamente.')
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

        expect(document.body.textContent).toContain('Excluir tarefa')

        expect(document.body.textContent).toContain(
            'Deseja realmente excluir a tarefa "Revisar contestação"?',
        )
    })

    it('cancela exclusão sem remover tarefa', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderTasksStore, 'removeTask')

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

    it('confirma exclusão da tarefa', async () => {
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderTasksStore, 'removeTask').mockResolvedValue()

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
        const { wrapper, folderTasksStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderTasksStore, 'removeTask').mockRejectedValue(new Error('Falha ao excluir'))

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível excluir a tarefa. Tente novamente.')
        })

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })
})
