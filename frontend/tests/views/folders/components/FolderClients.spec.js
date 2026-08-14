import { beforeEach, describe, expect, it, vi } from 'vitest'

import { flushPromises, mount } from '@vue/test-utils'

import { createPinia, setActivePinia } from 'pinia'

import { AppAutocomplete, AppSelect } from '@/components/forms'

import FolderClients from '@/views/folders/components/FolderClients.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'
import { useFoldersStore } from '@/stores/folders.js'
import { useQualificationsStore } from '@/stores/qualifications.js'

vi.mock('@/api/clients.js', () => ({
    listClients: vi.fn(),
    getClient: vi.fn(),
    createClient: vi.fn(),
    updateClient: vi.fn(),
    deleteClient: vi.fn(),
}))

vi.mock('@/api/folders.js', () => ({
    listFolders: vi.fn(),
    getFolder: vi.fn(),
    createFolder: vi.fn(),
    updateFolder: vi.fn(),
    deleteFolder: vi.fn(),
}))

vi.mock('@/api/folder-clients.js', () => ({
    createFolderClient: vi.fn(),
    updateFolderClient: vi.fn(),
    deleteFolderClient: vi.fn(),
}))

vi.mock('@/api/qualifications.js', () => ({
    listQualifications: vi.fn(),
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

const DEFAULT_FOLDER_ID = 10

async function mountComponent({
    permissions = [],
    folderClients = [],
    clients = [],
    qualifications = [],
} = {}) {
    const pinia = createPinia()

    setActivePinia(pinia)

    const authStore = useAuthStore()
    const clientsStore = useClientsStore()
    const foldersStore = useFoldersStore()
    const qualificationsStore = useQualificationsStore()

    authStore.permissions = [...permissions]

    foldersStore.$patch({
        folder: {
            id: DEFAULT_FOLDER_ID,
            name: 'Pasta A',
            process_number: null,
            folder_clients: [...folderClients],
        },
    })

    expect(foldersStore.folderClients).toEqual(folderClients)

    vi.spyOn(clientsStore, 'fetchClients').mockImplementation(async () => {
        clientsStore.clients = [...clients]

        return clientsStore.clients
    })

    vi.spyOn(qualificationsStore, 'fetchQualifications').mockImplementation(async () => {
        qualificationsStore.qualifications = [...qualifications]

        return qualificationsStore.qualifications
    })

    const wrapper = mount(FolderClients, {
        attachTo: document.body,

        props: {
            folderId: DEFAULT_FOLDER_ID,
        },

        global: {
            plugins: [pinia],
        },
    })

    await flushPromises()

    return {
        wrapper,
        authStore,
        clientsStore,
        foldersStore,
        qualificationsStore,
    }
}

function findComponentButton(wrapper, label) {
    return wrapper.findAll('button').find((button) => button.text().trim() === label)
}

function findDialogButton(label) {
    return Array.from(document.querySelectorAll('.app-dialog button')).find(
        (button) => button.textContent.trim() === label,
    )
}

function findConfirmButton(label) {
    return Array.from(document.querySelectorAll('.app-confirm-dialog button')).find(
        (button) => button.textContent.trim() === label,
    )
}

function getDialog() {
    return document.querySelector('.app-dialog')
}

function getConfirmDialog() {
    return document.querySelector('.app-confirm-dialog')
}

async function openCreateDialog(wrapper) {
    const button = findComponentButton(wrapper, 'Adicionar parte')

    expect(button).toBeTruthy()

    await button.trigger('click')

    await flushPromises()

    expect(getDialog()).not.toBeNull()
}

async function openEditDialog(wrapper) {
    const button = findComponentButton(wrapper, 'Editar')

    expect(button).toBeTruthy()

    await button.trigger('click')

    await flushPromises()

    expect(getDialog()).not.toBeNull()
}

async function openDeleteDialog(wrapper) {
    const button = findComponentButton(wrapper, 'Excluir')

    expect(button).toBeTruthy()

    await button.trigger('click')

    await flushPromises()

    expect(getConfirmDialog()).not.toBeNull()
}

async function selectClient(wrapper, clientId) {
    const autocomplete = wrapper.findComponent(AppAutocomplete)

    expect(autocomplete.exists()).toBe(true)

    autocomplete.vm.$emit('update:modelValue', clientId)

    await wrapper.vm.$nextTick()
}

async function selectQualification(wrapper, qualificationId) {
    const select = wrapper.findComponent(AppSelect)

    expect(select.exists()).toBe(true)

    select.vm.$emit('update:modelValue', qualificationId)

    await wrapper.vm.$nextTick()
}

async function submitDialogForm() {
    const form = document.querySelector('#folder-client-form')

    expect(form).toBeTruthy()

    form.dispatchEvent(
        new Event('submit', {
            bubbles: true,
            cancelable: true,
        }),
    )

    await flushPromises()
}

const linkedClient = {
    id: 100,
    folder_id: 10,
    client_id: 20,
    qualification_id: 30,

    client: {
        id: 20,
        name: 'Cliente A',
        document: '12345678901',
    },

    qualification: {
        id: 30,
        name: 'Autor',
    },
}

describe('FolderClients', () => {
    beforeEach(() => {
        vi.clearAllMocks()

        document.body.innerHTML = ''
    })

    it('carrega clientes e qualificações ao montar', async () => {
        const { clientsStore, qualificationsStore } = await mountComponent()

        expect(clientsStore.fetchClients).toHaveBeenCalledTimes(1)

        expect(qualificationsStore.fetchQualifications).toHaveBeenCalledTimes(1)
    })

    it('renderiza título e descrição', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Partes')

        expect(wrapper.text()).toContain(
            'Gerencie os clientes vinculados à pasta e suas qualificações.',
        )
    })

    it('renderiza estado vazio', async () => {
        const { wrapper } = await mountComponent()

        expect(wrapper.text()).toContain('Nenhuma parte vinculada.')
    })

    it('renderiza partes vinculadas', async () => {
        const { wrapper } = await mountComponent({
            folderClients: [linkedClient],
        })

        expect(wrapper.text()).toContain('Cliente A')

        expect(wrapper.text()).toContain('12345678901')

        expect(wrapper.text()).toContain('Autor')

        expect(wrapper.text()).not.toContain('Nenhuma parte vinculada.')
    })

    it('não mostra ações sem folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.view'],

            folderClients: [linkedClient],
        })

        expect(wrapper.text()).not.toContain('Adicionar parte')

        expect(wrapper.text()).not.toContain('Editar')

        expect(wrapper.text()).not.toContain('Excluir')
    })

    it('mostra ações com folders.update', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],
        })

        expect(wrapper.text()).toContain('Adicionar parte')

        expect(wrapper.text()).toContain('Editar')

        expect(wrapper.text()).toContain('Excluir')
    })

    it('abre dialog de inclusão', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],
        })

        await openCreateDialog(wrapper)

        expect(document.body.textContent).toContain('Adicionar parte')

        expect(findDialogButton('Adicionar')).toBeTruthy()

        expect(findDialogButton('Cancelar')).toBeTruthy()
    })

    it('não salva inclusão sem cliente', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            qualifications: [
                {
                    id: 30,
                    name: 'Autor',
                },
            ],
        })

        const addSpy = vi.spyOn(foldersStore, 'addClient')

        await openCreateDialog(wrapper)

        await selectQualification(wrapper, 30)

        await submitDialogForm()

        expect(addSpy).not.toHaveBeenCalled()

        expect(document.body.textContent).toContain('Selecione o cliente.')
    })

    it('não salva inclusão sem qualificação', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            clients: [
                {
                    id: 20,
                    name: 'Cliente A',
                },
            ],
        })

        const addSpy = vi.spyOn(foldersStore, 'addClient')

        await openCreateDialog(wrapper)

        await selectClient(wrapper, 20)

        await submitDialogForm()

        expect(addSpy).not.toHaveBeenCalled()

        expect(document.body.textContent).toContain('Selecione a qualificação.')
    })

    it('adiciona cliente com qualificação', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            clients: [
                {
                    id: 20,
                    name: 'Cliente A',
                },
            ],

            qualifications: [
                {
                    id: 30,
                    name: 'Autor',
                },
            ],
        })

        const addSpy = vi.spyOn(foldersStore, 'addClient').mockResolvedValue({
            ...linkedClient,
        })

        await openCreateDialog(wrapper)

        await selectClient(wrapper, 20)

        await selectQualification(wrapper, 30)

        await submitDialogForm()

        await vi.waitFor(() => {
            expect(addSpy).toHaveBeenCalledTimes(1)
        })

        expect(addSpy).toHaveBeenCalledWith(10, {
            client_id: 20,
            qualification_id: 30,
        })

        await vi.waitFor(() => {
            expect(getDialog()).toBeNull()
        })
    })

    it('bloqueia vínculo exatamente duplicado', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],

            clients: [
                {
                    id: 20,
                    name: 'Cliente A',
                },
            ],

            qualifications: [
                {
                    id: 30,
                    name: 'Autor',
                },
            ],
        })

        const addSpy = vi.spyOn(foldersStore, 'addClient')

        await openCreateDialog(wrapper)

        await selectClient(wrapper, 20)

        await selectQualification(wrapper, 30)

        await submitDialogForm()

        expect(addSpy).not.toHaveBeenCalled()

        expect(document.body.textContent).toContain(
            'Este cliente já possui essa qualificação na pasta.',
        )

        expect(getDialog()).not.toBeNull()
    })

    it('abre dialog de edição com dados atuais', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],

            qualifications: [
                {
                    id: 30,
                    name: 'Autor',
                },
            ],
        })

        await openEditDialog(wrapper)

        expect(document.body.textContent).toContain('Editar qualificação')

        expect(document.body.textContent).toContain('Cliente A')

        const select = wrapper.findComponent(AppSelect)

        expect(select.exists()).toBe(true)

        expect(select.props('modelValue')).toBe(30)
    })

    it('atualiza qualificação do vínculo', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],

            qualifications: [
                {
                    id: 30,
                    name: 'Autor',
                },
                {
                    id: 40,
                    name: 'Interessado',
                },
            ],
        })

        const updateSpy = vi.spyOn(foldersStore, 'updateClientQualification').mockResolvedValue({
            ...linkedClient,

            qualification_id: 40,

            qualification: {
                id: 40,
                name: 'Interessado',
            },
        })

        await openEditDialog(wrapper)

        await selectQualification(wrapper, 40)

        await submitDialogForm()

        await vi.waitFor(() => {
            expect(updateSpy).toHaveBeenCalledTimes(1)
        })

        expect(updateSpy).toHaveBeenCalledWith(10, 100, {
            qualification_id: 40,
        })

        await vi.waitFor(() => {
            expect(getDialog()).toBeNull()
        })
    })

    it('exibe erros 422 ao salvar vínculo', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            clients: [
                {
                    id: 20,
                    name: 'Cliente A',
                },
            ],

            qualifications: [
                {
                    id: 30,
                    name: 'Autor',
                },
            ],
        })

        vi.spyOn(foldersStore, 'addClient').mockRejectedValue({
            response: {
                status: 422,

                data: {
                    errors: {
                        client_id: ['Cliente inválido.'],

                        qualification_id: ['Qualificação inválida.'],
                    },
                },
            },
        })

        await openCreateDialog(wrapper)

        await selectClient(wrapper, 20)

        await selectQualification(wrapper, 30)

        await submitDialogForm()

        await vi.waitFor(() => {
            expect(document.body.textContent).toContain('Cliente inválido.')
        })

        expect(document.body.textContent).toContain('Qualificação inválida.')

        expect(getDialog()).not.toBeNull()
    })

    it('exibe erro genérico ao salvar vínculo', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            clients: [
                {
                    id: 20,
                    name: 'Cliente A',
                },
            ],

            qualifications: [
                {
                    id: 30,
                    name: 'Autor',
                },
            ],
        })

        vi.spyOn(foldersStore, 'addClient').mockRejectedValue(new Error('Erro inesperado'))

        await openCreateDialog(wrapper)

        await selectClient(wrapper, 20)

        await selectQualification(wrapper, 30)

        await submitDialogForm()

        await vi.waitFor(() => {
            expect(document.body.textContent).toContain(
                'Não foi possível salvar a parte. Tente novamente.',
            )
        })

        expect(getDialog()).not.toBeNull()
    })

    it('abre confirmação de remoção', async () => {
        const { wrapper } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],
        })

        await openDeleteDialog(wrapper)

        expect(document.body.textContent).toContain('Remover parte')

        expect(document.body.textContent).toContain(
            'Deseja realmente remover "Cliente A" desta pasta?',
        )
    })

    it('cancela remoção sem chamar store', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],
        })

        const removeSpy = vi.spyOn(foldersStore, 'removeClient')

        await openDeleteDialog(wrapper)

        const cancelButton = findConfirmButton('Cancelar')

        expect(cancelButton).toBeTruthy()

        cancelButton.click()

        await flushPromises()

        expect(removeSpy).not.toHaveBeenCalled()

        expect(getConfirmDialog()).toBeNull()
    })

    it('remove vínculo confirmado', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],
        })

        const removeSpy = vi.spyOn(foldersStore, 'removeClient').mockResolvedValue()

        await openDeleteDialog(wrapper)

        const removeButton = findConfirmButton('Remover')

        expect(removeButton).toBeTruthy()

        removeButton.click()

        await vi.waitFor(() => {
            expect(removeSpy).toHaveBeenCalledTimes(1)
        })

        expect(removeSpy).toHaveBeenCalledWith(10, 100)

        await vi.waitFor(() => {
            expect(getConfirmDialog()).toBeNull()
        })
    })

    it('exibe erro quando remoção falha', async () => {
        const { wrapper, foldersStore } = await mountComponent({
            permissions: ['folders.update'],

            folderClients: [linkedClient],
        })

        vi.spyOn(foldersStore, 'removeClient').mockRejectedValue(new Error('Erro inesperado'))

        await openDeleteDialog(wrapper)

        const removeButton = findConfirmButton('Remover')

        expect(removeButton).toBeTruthy()

        removeButton.click()

        await vi.waitFor(() => {
            expect(wrapper.text()).toContain('Não foi possível remover a parte. Tente novamente.')
        })

        expect(getConfirmDialog()).not.toBeNull()
    })
})
