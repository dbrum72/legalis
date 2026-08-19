import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import FolderEvents from '@/views/folders/components/FolderEvents.vue'

import { useAuthStore } from '@/stores/auth.js'

import { useFolderEventsStore } from '@/stores/folder-events.js'

vi.mock('@/api/folder-events.js', () => ({
    listFolderEvents: vi.fn(),
    createFolderEvent: vi.fn(),
    completeFolderEvent: vi.fn(),
    deleteFolderEvent: vi.fn(),
}))

function defaultEvents() {
    return [
        {
            id: 1,
            folder_id: 10,
            user_id: 1,
            type: 'hearing',
            title: 'Audiência de instrução',
            description: 'Audiência de instrução e julgamento.',
            starts_at: '2026-09-10T14:00:00.000000Z',
            ends_at: '2026-09-10T15:30:00.000000Z',
            location: '3ª Vara Cível de Pelotas',
            status: 'scheduled',
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
            type: 'meeting',
            title: 'Reunião com cliente',
            description: null,
            starts_at: '2026-09-12T10:00:00.000000Z',
            ends_at: null,
            location: 'Escritório',
            status: 'completed',
            completed_at: '2026-09-12T11:00:00.000000Z',

            user: {
                id: 2,
                name: 'Maria',
            },
        },
    ]
}

async function mountComponent({
    events = defaultEvents(),
    permissions = [],
    fetchError = null,
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()

    const folderEventsStore = useFolderEventsStore()

    authStore.permissions = permissions

    const fetchEventsSpy = vi.spyOn(folderEventsStore, 'fetchEvents')

    if (fetchError) {
        fetchEventsSpy.mockRejectedValue(fetchError)
    } else {
        fetchEventsSpy.mockImplementation(async () => {
            folderEventsStore.events = events

            return events
        })
    }

    const wrapper = mount(FolderEvents, {
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
        folderEventsStore,
        fetchEventsSpy,
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

describe('FolderEvents', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega eventos ao montar', async () => {
        const { fetchEventsSpy } = await mountComponent()

        expect(fetchEventsSpy).toHaveBeenCalledTimes(1)

        expect(fetchEventsSpy).toHaveBeenCalledWith(10)
    })

    it('renderiza título e descrição', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Agenda')

        expect(wrapper.text()).toContain('Acompanhe os compromissos vinculados à pasta.')
    })

    it('renderiza eventos carregados', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Audiência de instrução')

        expect(wrapper.text()).toContain('Reunião com cliente')
    })

    it('renderiza descrição do evento', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Audiência de instrução e julgamento.')
    })

    it('renderiza local do evento', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('3ª Vara Cível de Pelotas')

        expect(wrapper.text()).toContain('Escritório')
    })

    it('renderiza responsável pelo evento', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Lucas')

        expect(wrapper.text()).toContain('Maria')
    })

    it('renderiza tipos de evento', async () => {
        const { wrapper } = await mountComponent()

        const text = wrapper.text()

        expect(text).toContain('Audiência')

        expect(text).toContain('Reunião')
    })

    it('renderiza status agendado e concluído', async () => {
        const { wrapper } = await mountComponent()

        const text = wrapper.text()

        expect(text).toContain('Agendado')

        expect(text).toContain('Concluído')
    })

    it('renderiza estado vazio quando não existem eventos', async () => {
        const { wrapper } = await mountComponent({
            events: [],
        })

        expect(wrapper.text()).toContain('Nenhum compromisso registrado.')
    })

    it('exibe erro quando carregamento falha', async () => {
        const { wrapper } = await mountComponent({
            fetchError: new Error('Falha ao carregar'),
        })

        expect(wrapper.text()).toContain(
            'Não foi possível carregar os compromissos. Tente novamente.',
        )

        expect(wrapper.find('[role="alert"]').exists()).toBe(true)
    })

    it('mostra ação Novo compromisso com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        expect(wrapper.text()).toContain('Novo compromisso')
    })

    it('não mostra ação Novo compromisso sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: [],
        })

        expect(wrapper.text()).not.toContain('Novo compromisso')
    })

    it('abre formulário para criar compromisso', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        const button = findButton(wrapper, 'Novo compromisso')

        expect(button).toBeTruthy()

        await button.trigger('click')

        expect(wrapper.find('select[name="type"]').exists()).toBe(true)

        expect(wrapper.find('input[name="title"]').exists()).toBe(true)

        expect(wrapper.find('input[name="starts_at"]').exists()).toBe(true)

        expect(wrapper.find('input[name="ends_at"]').exists()).toBe(true)

        expect(wrapper.find('input[name="location"]').exists()).toBe(true)

        expect(wrapper.find('textarea[name="description"]').exists()).toBe(true)
    })

    it('envia novo compromisso para a store em UTC', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderEventsStore, 'createEvent').mockResolvedValue({
            id: 3,
            folder_id: 10,
            type: 'hearing',
            title: 'Audiência de conciliação',
            status: 'scheduled',
        })

        const button = findButton(wrapper, 'Novo compromisso')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await wrapper.get('select[name="type"]').setValue('hearing')

        await wrapper.get('input[name="title"]').setValue('Audiência de conciliação')

        await wrapper.get('input[name="starts_at"]').setValue('2026-09-20T14:00')

        await wrapper.get('input[name="ends_at"]').setValue('2026-09-20T15:00')

        await wrapper.get('input[name="location"]').setValue('Fórum de Pelotas')

        await wrapper
            .get('textarea[name="description"]')
            .setValue('Audiência designada para tentativa de conciliação.')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(createSpy).toHaveBeenCalledTimes(1)
        })

        expect(createSpy).toHaveBeenCalledWith(10, {
            type: 'hearing',

            title: 'Audiência de conciliação',

            description: 'Audiência designada para tentativa de conciliação.',

            starts_at: new Date('2026-09-20T14:00').toISOString(),

            ends_at: new Date('2026-09-20T15:00').toISOString(),

            location: 'Fórum de Pelotas',
        })
    })

    it('não cria compromisso sem tipo', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderEventsStore, 'createEvent')

        const button = findButton(wrapper, 'Novo compromisso')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await wrapper.get('input[name="title"]').setValue('Audiência')

        await wrapper.get('input[name="starts_at"]').setValue('2026-09-20T14:00')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
    })

    it('não cria compromisso sem título ou início', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const createSpy = vi.spyOn(folderEventsStore, 'createEvent')

        const button = findButton(wrapper, 'Novo compromisso')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await wrapper.get('select[name="type"]').setValue('meeting')

        await wrapper.get('form').trigger('submit')

        expect(createSpy).not.toHaveBeenCalled()
    })

    it('cancela criação de compromisso', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        const openButton = findButton(wrapper, 'Novo compromisso')

        expect(openButton).toBeTruthy()

        await openButton.trigger('click')

        expect(wrapper.find('form').exists()).toBe(true)

        const cancelButton = findButton(wrapper, 'Cancelar')

        expect(cancelButton).toBeTruthy()

        await cancelButton.trigger('click')

        expect(wrapper.find('form').exists()).toBe(false)
    })

    it('exibe erro quando criação do compromisso falha', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderEventsStore, 'createEvent').mockRejectedValue(new Error('Falha ao criar'))

        const button = findButton(wrapper, 'Novo compromisso')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await wrapper.get('select[name="type"]').setValue('hearing')

        await wrapper.get('input[name="title"]').setValue('Audiência')

        await wrapper.get('input[name="starts_at"]').setValue('2026-09-20T14:00')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível criar o compromisso. Tente novamente.',
            )
        })

        expect(wrapper.find('form').exists()).toBe(true)
    })

    it('mostra Concluir apenas para compromisso agendado com folders.update', async () => {
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

    it('conclui compromisso agendado', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const completeSpy = vi.spyOn(folderEventsStore, 'completeEvent').mockResolvedValue({
            id: 1,
            folder_id: 10,
            type: 'hearing',
            title: 'Audiência de instrução',
            status: 'completed',
            completed_at: '2026-09-10T16:00:00.000000Z',
        })

        const button = findButton(wrapper, 'Concluir')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(completeSpy).toHaveBeenCalledTimes(1)

            expect(completeSpy).toHaveBeenCalledWith(10, 1)
        })
    })

    it('exibe erro quando conclusão do compromisso falha', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderEventsStore, 'completeEvent').mockRejectedValue(
            new Error('Falha ao concluir'),
        )

        const button = findButton(wrapper, 'Concluir')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível concluir o compromisso. Tente novamente.',
            )
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

        expect(document.body.textContent).toContain('Excluir compromisso')

        expect(document.body.textContent).toContain(
            'Deseja realmente excluir o compromisso "Audiência de instrução"?',
        )
    })

    it('cancela exclusão sem remover compromisso', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderEventsStore, 'removeEvent')

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

    it('confirma exclusão do compromisso', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        const removeSpy = vi.spyOn(folderEventsStore, 'removeEvent').mockResolvedValue()

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
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderEventsStore, 'removeEvent').mockRejectedValue(new Error('Falha ao excluir'))

        const button = findButtons(wrapper, 'Excluir')[0]

        expect(button).toBeTruthy()

        await button.trigger('click')

        const confirmButton = findTeleportedButton('Excluir')

        expect(confirmButton).toBeTruthy()

        confirmButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain(
                'Não foi possível excluir o compromisso. Tente novamente.',
            )
        })

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()
    })

    it('emite changed após criar compromisso com sucesso', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderEventsStore, 'createEvent').mockResolvedValue({
            id: 3,
            folder_id: 10,
            type: 'hearing',
            title: 'Nova audiência',
            status: 'scheduled',
        })

        const button = findButton(wrapper, 'Novo compromisso')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await wrapper.get('select[name="type"]').setValue('hearing')

        await wrapper.get('input[name="title"]').setValue('Nova audiência')

        await wrapper.get('input[name="starts_at"]').setValue('2026-09-20T14:00')

        await wrapper.get('form').trigger('submit')

        await vi.waitFor(() => {
            expect(wrapper.emitted('changed')).toHaveLength(1)
        })
    })

    it('emite changed após concluir compromisso com sucesso', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderEventsStore, 'completeEvent').mockResolvedValue({
            id: 1,
            folder_id: 10,
            title: 'Audiência de instrução',
            status: 'completed',
        })

        const button = findButton(wrapper, 'Concluir')

        expect(button).toBeTruthy()

        await button.trigger('click')

        await vi.waitFor(() => {
            expect(wrapper.emitted('changed')).toHaveLength(1)
        })
    })

    it('emite changed após excluir compromisso com sucesso', async () => {
        const { wrapper, folderEventsStore } = await mountComponent({
            permissions: ['folders.update'],
        })

        vi.spyOn(folderEventsStore, 'removeEvent').mockResolvedValue()

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
