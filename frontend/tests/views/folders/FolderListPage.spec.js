import { beforeEach, describe, expect, it, vi } from 'vitest'

import { mount } from '@vue/test-utils'

import { createMemoryHistory, createRouter } from 'vue-router'

import { createPinia, setActivePinia } from 'pinia'

import FolderListPage from '@/views/folders/FolderListPage.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useFoldersStore } from '@/stores/folders.js'

vi.mock('@/api/folders.js', () => ({
    listFolders: vi.fn(),
    getFolder: vi.fn(),
    createFolder: vi.fn(),
    updateFolder: vi.fn(),
    deleteFolder: vi.fn(),
}))

vi.mock('@/api/auth.js', () => ({
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    refresh: vi.fn(),
}))

vi.mock('@/api/auth-token.js', () => ({
    getAccessToken: vi.fn(),
    setAccessToken: vi.fn(),
    removeAccessToken: vi.fn(),
}))

function createTestRouter() {
    return createRouter({
        history: createMemoryHistory(),

        routes: [
            {
                path: '/folders',
                name: 'folders',
                component: {
                    template: '<div>Pastas</div>',
                },
            },
            {
                path: '/folders/new',
                name: 'folders.create',
                component: {
                    template: '<div>Nova pasta</div>',
                },
            },
            {
                path: '/folders/:id/edit',
                name: 'folders.edit',
                component: {
                    template: '<div>Editar pasta</div>',
                },
            },
        ],
    })
}

async function mountPage({ permissions = [], folders = [] } = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const router = createTestRouter()

    await router.push('/folders')
    await router.isReady()

    const authStore = useAuthStore()
    const foldersStore = useFoldersStore()

    authStore.permissions = permissions

    vi.spyOn(foldersStore, 'fetchFolders').mockImplementation(async () => {
        foldersStore.folders = folders

        return folders
    })

    const wrapper = mount(FolderListPage, {
        global: {
            plugins: [pinia, router],
        },
    })

    await vi.waitFor(() => {
        expect(foldersStore.fetchFolders).toHaveBeenCalled()
    })

    return {
        wrapper,
        router,
        authStore,
        foldersStore,
    }
}

function findButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text() === label)
}

function findTeleportedButton(label) {
    return Array.from(document.querySelectorAll('button')).find(
        (button) => button.textContent.trim() === label,
    )
}

describe('FolderListPage', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega pastas ao montar', async () => {
        const { foldersStore } = await mountPage()

        expect(foldersStore.fetchFolders).toHaveBeenCalledTimes(1)
    })

    it('renderiza título da página', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Pastas')
    })

    it('renderiza descrição da página', async () => {
        const { wrapper } = await mountPage()

        expect(wrapper.text()).toContain('Consulte e gerencie as pastas jurídicas cadastradas.')
    })

    it('renderiza pastas retornadas pela store', async () => {
        const { wrapper } = await mountPage({
            folders: [
                {
                    id: 1,
                    name: 'Ação indenizatória',
                    process_number: '5000000-00.2026.8.21.0001',
                },
                {
                    id: 2,
                    name: 'Atendimento extrajudicial',
                    process_number: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Ação indenizatória')

        expect(wrapper.text()).toContain('5000000-00.2026.8.21.0001')

        expect(wrapper.text()).toContain('Atendimento extrajudicial')
    })

    it('renderiza fallback quando número do processo é nulo', async () => {
        const { wrapper } = await mountPage({
            folders: [
                {
                    id: 1,
                    name: 'Atendimento extrajudicial',
                    process_number: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('—')
    })

    it('renderiza estado vazio quando não existem pastas', async () => {
        const { wrapper } = await mountPage({
            folders: [],
        })

        expect(wrapper.text()).toContain('Nenhuma pasta cadastrada.')
    })

    it('não mostra Nova pasta sem folders.create', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view'],
        })

        expect(wrapper.text()).not.toContain('Nova pasta')
    })

    it('mostra Nova pasta com folders.create', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view', 'folders.create'],
        })

        expect(wrapper.text()).toContain('Nova pasta')
    })

    it('não mostra Editar sem folders.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        expect(wrapper.text()).not.toContain('Editar')
    })

    it('mostra Editar com folders.update', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view', 'folders.update'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Editar')
    })

    it('não mostra Excluir sem folders.delete', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        expect(wrapper.text()).not.toContain('Excluir')
    })

    it('mostra Excluir com folders.delete', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view', 'folders.delete'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Excluir')
    })

    it('mostra ações independentemente conforme permissions', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view', 'folders.update'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        expect(wrapper.text()).toContain('Editar')

        expect(wrapper.text()).not.toContain('Excluir')

        expect(wrapper.text()).not.toContain('Nova pasta')
    })

    it('navega para cadastro ao clicar em Nova pasta', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['folders.view', 'folders.create'],
        })

        const button = findButton(wrapper, 'Nova pasta')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('folders.create')
        })
    })

    it('navega para edição da pasta selecionada', async () => {
        const { wrapper, router } = await mountPage({
            permissions: ['folders.view', 'folders.update'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        const button = findButton(wrapper, 'Editar')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(router.currentRoute.value.name).toBe('folders.edit')

            expect(router.currentRoute.value.params.id).toBe('10')
        })
    })

    it('abre confirmação ao clicar em Excluir', async () => {
        const { wrapper } = await mountPage({
            permissions: ['folders.view', 'folders.delete'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        const deleteButton = findButton(wrapper, 'Excluir')

        expect(deleteButton).toBeTruthy()

        await deleteButton.trigger('click')

        expect(document.body.textContent).toContain('Excluir pasta')

        expect(document.body.textContent).toContain('Deseja realmente excluir a pasta "Pasta A"?')
    })

    it('cancela exclusão sem remover pasta', async () => {
        const { wrapper, foldersStore } = await mountPage({
            permissions: ['folders.view', 'folders.delete'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        const removeSpy = vi.spyOn(foldersStore, 'remove')

        const deleteButton = findButton(wrapper, 'Excluir')

        await deleteButton.trigger('click')

        const cancelButton = findTeleportedButton('Cancelar')

        expect(cancelButton).toBeTruthy()

        cancelButton.click()

        await wrapper.vm.$nextTick()

        expect(removeSpy).not.toHaveBeenCalled()

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()
    })

    it('confirma exclusão e remove pasta', async () => {
        const { wrapper, foldersStore } = await mountPage({
            permissions: ['folders.view', 'folders.delete'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        const removeSpy = vi.spyOn(foldersStore, 'remove').mockResolvedValue()

        const deleteButton = findButton(wrapper, 'Excluir')

        await deleteButton.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(removeSpy).toHaveBeenCalledTimes(1)

            expect(removeSpy).toHaveBeenCalledWith(10)
        })

        await vi.waitFor(() => {
            expect(document.querySelector('.app-confirm-dialog')).toBeNull()
        })
    })

    it('exibe erro quando exclusão falha', async () => {
        const { wrapper, foldersStore } = await mountPage({
            permissions: ['folders.view', 'folders.delete'],

            folders: [
                {
                    id: 10,
                    name: 'Pasta A',
                    process_number: null,
                },
            ],
        })

        vi.spyOn(foldersStore, 'remove').mockRejectedValue(new Error('Falha ao excluir'))

        const deleteButton = findButton(wrapper, 'Excluir')

        await deleteButton.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível excluir a pasta. Tente novamente.')
        })

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })
})
