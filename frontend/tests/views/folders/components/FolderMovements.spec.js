import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import FolderMovements from '@/views/folders/components/FolderMovements.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useFolderMovementsStore } from '@/stores/folder-movements.js'

vi.mock('@/api/folder-movements.js', () => ({
    listFolderMovements: vi.fn(),
    createFolderMovement: vi.fn(),
    deleteFolderMovement: vi.fn(),
}))

function defaultMovements() {
    return [
        {
            id: 2,
            folder_id: 10,
            user_id: 2,
            occurred_at: '2026-08-17',
            title: 'Despacho judicial',
            description: 'Despacho disponibilizado no diário eletrônico.',
            created_at: '2026-08-17T15:00:00.000000Z',

            user: {
                id: 2,
                name: 'Maria',
            },
        },

        {
            id: 1,
            folder_id: 10,
            user_id: 1,
            occurred_at: '2026-08-15',
            title: 'Petição protocolada',
            description: null,
            created_at: '2026-08-15T12:00:00.000000Z',

            user: {
                id: 1,
                name: 'Lucas',
            },
        },
    ]
}

async function mountComponent({
    movements = defaultMovements(),
    permissions = [],
    fetchError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    const folderMovementsStore = useFolderMovementsStore()

    authStore.permissions = permissions

    const fetchMovementsSpy = vi.spyOn(folderMovementsStore, 'fetchMovements')

    if (fetchError) {
        fetchMovementsSpy.mockRejectedValue(fetchError)
    } else {
        fetchMovementsSpy.mockImplementation(async () => {
            folderMovementsStore.movements = movements

            return movements
        })
    }

    const wrapper = mount(FolderMovements, {
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
        folderMovementsStore,
        fetchMovementsSpy,
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
    const button = findButton(wrapper, 'Registrar movimentação')

    expect(button).toBeTruthy()

    await button.trigger('click')
}

describe('FolderMovements', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        vi.restoreAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega movimentações ao montar', async () => {
        const { fetchMovementsSpy } = await mountComponent()

        expect(fetchMovementsSpy).toHaveBeenCalledTimes(1)

        expect(fetchMovementsSpy).toHaveBeenCalledWith(10)
    })

    it('renderiza título e descrição', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Movimentações')

        expect(wrapper.text()).toContain('Acompanhe o histórico de movimentações da pasta.')
    })

    it('renderiza movimentações carregadas', async () => {
        const { wrapper } = await mountComponent()

        const text = wrapper.text()

        expect(text).toContain('Despacho judicial')

        expect(text).toContain('Petição protocolada')
    })

    it('renderiza descrição da movimentação', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Despacho disponibilizado no diário eletrônico.')
    })

    it('renderiza responsável pela movimentação', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Maria')

        expect(wrapper.text()).toContain('Lucas')
    })

    it('renderiza estado vazio quando não existem movimentações', async () => {
        const { wrapper } = await mountComponent({
            movements: [],
        })

        expect(wrapper.text()).toContain('Nenhuma movimentação registrada.')
    })

    it('exibe erro quando carregamento falha', async () => {
        const { wrapper } = await mountComponent({
            fetchError: new Error('Falha ao carregar'),
        })

        expect(wrapper.text()).toContain(
            'Não foi possível carregar as movimentações. Tente novamente.',
        )

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('mostra ação Registrar movimentação com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        expect(wrapper.text()).toContain('Registrar movimentação')
    })

    it('não mostra ação Registrar movimentação sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: [],
        })

        expect(wrapper.text()).not.toContain('Registrar movimentação')
    })

    it('abre formulário para registrar movimentação', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        await openCreateForm(wrapper)

        expect(wrapper.find('input[name="occurred_at"]').exists()).toBe(true)

        expect(wrapper.find('input[name="title"]').exists()).toBe(true)

        expect(wrapper.find('textarea[name="description"]').exists()).toBe(true)
    })

    it('envia nova movimentação para a store', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderMovementsStore, 'createMovement').mockResolvedValue({
            id: 3,
            folder_id: 10,
            title: 'Audiência designada',
        })

        await openCreateForm(wrapper)

        await wrapper.get('input[name="occurred_at"]').setValue('2026-08-18T14:30')

        await wrapper.get('input[name="title"]').setValue('Audiência designada')

        await wrapper
            .get('textarea[name="description"]')
            .setValue('Audiência marcada para instrução.')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(createSpy).toHaveBeenCalledTimes(1)
        })

        expect(createSpy).toHaveBeenCalledWith(10, {
            occurred_at: new Date('2026-08-18T14:30').toISOString(),

            title: 'Audiência designada',

            description: 'Audiência marcada para instrução.',
        })
    })

    it('não registra movimentação sem data', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderMovementsStore, 'createMovement')

        await openCreateForm(wrapper)

        await wrapper.get('input[name="title"]').setValue('Movimentação')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
    })

    it('não registra movimentação sem título', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderMovementsStore, 'createMovement')

        await openCreateForm(wrapper)

        await wrapper.get('input[name="occurred_at"]').setValue('2026-08-18T14:30')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
    })

    it('cancela registro de movimentação', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        await openCreateForm(wrapper)

        expect(wrapper.find('form').exists()).toBe(true)

        const cancelButton = findButton(wrapper, 'Cancelar')

        expect(cancelButton).toBeTruthy()

        await cancelButton.trigger('click')

        expect(wrapper.find('form').exists()).toBe(false)

        expect(wrapper.text()).toContain('Registrar movimentação')
    })

    it('exibe erro quando registro da movimentação falha', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderMovementsStore, 'createMovement').mockRejectedValue(
            new Error('Falha ao registrar'),
        )

        await openCreateForm(wrapper)

        await wrapper.get('input[name="occurred_at"]').setValue('2026-08-18T14:30')

        await wrapper.get('input[name="title"]').setValue('Audiência designada')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível registrar a movimentação. Tente novamente.',
            )
        })

        expect(wrapper.find('form').exists()).toBe(true)
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

        expect(document.body.textContent).toContain('Excluir movimentação')

        expect(document.body.textContent).toContain(
            'Deseja realmente excluir a movimentação "Despacho judicial"?',
        )
    })

    it('cancela exclusão sem remover movimentação', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderMovementsStore, 'removeMovement')

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

    it('confirma exclusão da movimentação', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderMovementsStore, 'removeMovement').mockResolvedValue()

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(removeSpy).toHaveBeenCalledTimes(1)

            expect(removeSpy).toHaveBeenCalledWith(10, 2)
        })

        await vi.waitFor(() => {
            expect(document.querySelector('.app-confirm-dialog')).toBeNull()
        })
    })

    it('mantém confirmação aberta quando exclusão falha', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderMovementsStore, 'removeMovement').mockRejectedValue(
            new Error('Falha ao excluir'),
        )

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível excluir a movimentação. Tente novamente.',
            )
        })

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })

    it('emite changed após registrar movimentação com sucesso', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderMovementsStore, 'createMovement').mockResolvedValue({
            id: 3,
            folder_id: 10,
            title: 'Audiência designada',
        })

        await openCreateForm(wrapper)

        await wrapper.get('input[name="occurred_at"]').setValue('2026-08-18T14:30')

        await wrapper.get('input[name="title"]').setValue('Audiência designada')

        await wrapper.get('form').trigger('submit')

        await flushPromises()

        expect(wrapper.emitted('changed')).toHaveLength(1)
    })

    it('emite changed após excluir movimentação com sucesso', async () => {
        const { wrapper, folderMovementsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderMovementsStore, 'removeMovement').mockResolvedValue()

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
