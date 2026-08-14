import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useFoldersStore } from '@/stores/folders.js'

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

import { createFolder, deleteFolder, getFolder, listFolders, updateFolder } from '@/api/folders.js'

import { createFolderClient, deleteFolderClient, updateFolderClient } from '@/api/folder-clients.js'

describe('folders store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useFoldersStore()

        expect(store.folders).toEqual([])
        expect(store.folder).toBeNull()
        expect(store.count).toBe(0)
    })

    it('fetchFolders popula coleção', async () => {
        listFolders.mockResolvedValue({
            data: [
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

        const store = useFoldersStore()

        const result = await store.fetchFolders()

        expect(listFolders).toHaveBeenCalledTimes(1)

        expect(store.folders).toHaveLength(2)

        expect(store.count).toBe(2)

        expect(result).toEqual(store.folders)
    })

    it('fetchFolders usa array vazio quando resposta não é array', async () => {
        listFolders.mockResolvedValue({
            data: null,
        })

        const store = useFoldersStore()

        await store.fetchFolders()

        expect(store.folders).toEqual([])

        expect(store.count).toBe(0)
    })

    it('fetchFolder carrega pasta atual', async () => {
        getFolder.mockResolvedValue({
            data: {
                id: 10,
                name: 'Pasta Teste',
                process_number: null,
            },
        })

        const store = useFoldersStore()

        const result = await store.fetchFolder(10)

        expect(getFolder).toHaveBeenCalledWith(10)

        expect(store.folder).toEqual({
            id: 10,
            name: 'Pasta Teste',
            process_number: null,
        })

        expect(result).toEqual(store.folder)
    })

    it('getById localiza pasta por id numérico', () => {
        const store = useFoldersStore()

        store.folders = [
            {
                id: 10,
                name: 'Pasta A',
            },
        ]

        expect(store.getById(10)).toEqual({
            id: 10,
            name: 'Pasta A',
        })
    })

    it('getById aceita id string', () => {
        const store = useFoldersStore()

        store.folders = [
            {
                id: 10,
                name: 'Pasta A',
            },
        ]

        expect(store.getById('10')).toEqual({
            id: 10,
            name: 'Pasta A',
        })
    })

    it('getById retorna null quando pasta não existe', () => {
        const store = useFoldersStore()

        store.folders = [
            {
                id: 10,
                name: 'Pasta A',
            },
        ]

        expect(store.getById(999)).toBeNull()
    })

    it('create adiciona pasta à coleção', async () => {
        createFolder.mockResolvedValue({
            data: {
                id: 20,
                name: 'Nova pasta',
                process_number: null,
            },
        })

        const store = useFoldersStore()

        const payload = {
            name: 'Nova pasta',
            process_number: null,
        }

        const result = await store.create(payload)

        expect(createFolder).toHaveBeenCalledWith(payload)

        expect(store.folders).toEqual([
            {
                id: 20,
                name: 'Nova pasta',
                process_number: null,
            },
        ])

        expect(result).toEqual({
            id: 20,
            name: 'Nova pasta',
            process_number: null,
        })
    })

    it('update atualiza pasta na coleção', async () => {
        updateFolder.mockResolvedValue({
            data: {
                id: 10,
                name: 'Pasta atualizada',
                process_number: '5000001-00.2026.8.21.0001',
            },
        })

        const store = useFoldersStore()

        store.folders = [
            {
                id: 10,
                name: 'Pasta antiga',
                process_number: null,
            },
            {
                id: 11,
                name: 'Outra pasta',
                process_number: null,
            },
        ]

        const payload = {
            name: 'Pasta atualizada',
            process_number: '5000001-00.2026.8.21.0001',
        }

        const result = await store.update(10, payload)

        expect(updateFolder).toHaveBeenCalledWith(10, payload)

        expect(store.folders[0]).toEqual({
            id: 10,
            name: 'Pasta atualizada',
            process_number: '5000001-00.2026.8.21.0001',
        })

        expect(store.folders[1].name).toBe('Outra pasta')

        expect(result).toEqual(store.folders[0])
    })

    it('update atualiza também pasta atual quando ids coincidem', async () => {
        updateFolder.mockResolvedValue({
            data: {
                id: 10,
                name: 'Pasta atualizada',
                process_number: null,
            },
        })

        const store = useFoldersStore()

        store.folder = {
            id: 10,
            name: 'Pasta antiga',
            process_number: null,
        }

        await store.update(10, {
            name: 'Pasta atualizada',
            process_number: null,
        })

        expect(store.folder).toEqual({
            id: 10,
            name: 'Pasta atualizada',
            process_number: null,
        })
    })

    it('update não altera pasta atual quando ids são diferentes', async () => {
        updateFolder.mockResolvedValue({
            data: {
                id: 10,
                name: 'Pasta atualizada',
                process_number: null,
            },
        })

        const store = useFoldersStore()

        store.folder = {
            id: 99,
            name: 'Pasta atual',
            process_number: null,
        }

        await store.update(10, {
            name: 'Pasta atualizada',
            process_number: null,
        })

        expect(store.folder).toEqual({
            id: 99,
            name: 'Pasta atual',
            process_number: null,
        })
    })

    it('remove exclui pasta da coleção', async () => {
        deleteFolder.mockResolvedValue({
            data: null,
        })

        const store = useFoldersStore()

        store.folders = [
            {
                id: 10,
                name: 'Pasta A',
            },
            {
                id: 11,
                name: 'Pasta B',
            },
        ]

        await store.remove(10)

        expect(deleteFolder).toHaveBeenCalledWith(10)

        expect(store.folders).toEqual([
            {
                id: 11,
                name: 'Pasta B',
            },
        ])
    })

    it('remove limpa pasta atual quando ids coincidem', async () => {
        deleteFolder.mockResolvedValue({
            data: null,
        })

        const store = useFoldersStore()

        store.folder = {
            id: 10,
            name: 'Pasta A',
        }

        await store.remove(10)

        expect(store.folder).toBeNull()
    })

    it('remove preserva pasta atual quando ids são diferentes', async () => {
        deleteFolder.mockResolvedValue({
            data: null,
        })

        const store = useFoldersStore()

        store.folder = {
            id: 99,
            name: 'Pasta atual',
        }

        await store.remove(10)

        expect(store.folder).toEqual({
            id: 99,
            name: 'Pasta atual',
        })
    })

    it('clearCurrent limpa apenas pasta atual', () => {
        const store = useFoldersStore()

        store.folders = [
            {
                id: 1,
                name: 'Pasta A',
            },
        ]

        store.folder = {
            id: 1,
            name: 'Pasta A',
        }

        store.clearCurrent()

        expect(store.folder).toBeNull()

        expect(store.folders).toHaveLength(1)
    })

    it('clear limpa coleção e pasta atual', () => {
        const store = useFoldersStore()

        store.folders = [
            {
                id: 1,
                name: 'Pasta A',
            },
        ]

        store.folder = {
            id: 1,
            name: 'Pasta A',
        }

        store.clear()

        expect(store.folders).toEqual([])

        expect(store.folder).toBeNull()

        expect(store.count).toBe(0)
    })

    it('folderClients retorna vínculos da pasta atual', () => {
        const store = useFoldersStore()

        store.folder = {
            id: 10,

            folder_clients: [
                {
                    id: 1,
                    client_id: 20,
                    qualification_id: 30,
                },
            ],
        }

        expect(store.folderClients).toEqual([
            {
                id: 1,
                client_id: 20,
                qualification_id: 30,
            },
        ])
    })

    it('folderClients retorna array vazio sem pasta atual', () => {
        const store = useFoldersStore()

        expect(store.folderClients).toEqual([])
    })

    it('addClient adiciona vínculo à pasta atual', async () => {
        createFolderClient.mockResolvedValue({
            data: {
                id: 100,
                folder_id: 10,

                client: {
                    id: 20,
                    name: 'Cliente A',
                },

                qualification: {
                    id: 30,
                    name: 'Autor',
                },
            },
        })

        const store = useFoldersStore()

        store.folder = {
            id: 10,
            folder_clients: [],
        }

        const payload = {
            client_id: 20,
            qualification_id: 30,
        }

        const result = await store.addClient(10, payload)

        expect(createFolderClient).toHaveBeenCalledWith(10, payload)

        expect(store.folderClients).toHaveLength(1)

        expect(store.folderClients[0]).toEqual(result)
    })

    it('addClient inicializa folder_clients quando ausente', async () => {
        createFolderClient.mockResolvedValue({
            data: {
                id: 100,
            },
        })

        const store = useFoldersStore()

        store.folder = {
            id: 10,
        }

        await store.addClient(10, {
            client_id: 20,
            qualification_id: 30,
        })

        expect(store.folderClients).toEqual([
            {
                id: 100,
            },
        ])
    })

    it('addClient não altera pasta atual quando folderId é diferente', async () => {
        createFolderClient.mockResolvedValue({
            data: {
                id: 100,
            },
        })

        const store = useFoldersStore()

        store.folder = {
            id: 99,
            folder_clients: [],
        }

        await store.addClient(10, {
            client_id: 20,
            qualification_id: 30,
        })

        expect(store.folderClients).toEqual([])
    })

    it('updateClientQualification atualiza vínculo existente', async () => {
        updateFolderClient.mockResolvedValue({
            data: {
                id: 100,
                folder_id: 10,

                client: {
                    id: 20,
                    name: 'Cliente A',
                },

                qualification: {
                    id: 40,
                    name: 'Interessado',
                },
            },
        })

        const store = useFoldersStore()

        store.folder = {
            id: 10,

            folder_clients: [
                {
                    id: 100,

                    qualification: {
                        id: 30,
                        name: 'Autor',
                    },
                },
            ],
        }

        const payload = {
            qualification_id: 40,
        }

        const result = await store.updateClientQualification(10, 100, payload)

        expect(updateFolderClient).toHaveBeenCalledWith(10, 100, payload)

        expect(store.folderClients[0]).toEqual(result)
    })

    it('updateClientQualification não altera vínculo ausente', async () => {
        updateFolderClient.mockResolvedValue({
            data: {
                id: 999,
            },
        })

        const store = useFoldersStore()

        store.folder = {
            id: 10,

            folder_clients: [
                {
                    id: 100,
                },
            ],
        }

        await store.updateClientQualification(10, 999, {
            qualification_id: 40,
        })

        expect(store.folderClients).toEqual([
            {
                id: 100,
            },
        ])
    })

    it('removeClient remove vínculo da pasta atual', async () => {
        deleteFolderClient.mockResolvedValue({
            data: null,
        })

        const store = useFoldersStore()

        store.folder = {
            id: 10,

            folder_clients: [
                {
                    id: 100,
                },
                {
                    id: 101,
                },
            ],
        }

        await store.removeClient(10, 100)

        expect(deleteFolderClient).toHaveBeenCalledWith(10, 100)

        expect(store.folderClients).toEqual([
            {
                id: 101,
            },
        ])
    })

    it('removeClient não altera pasta atual quando folderId é diferente', async () => {
        deleteFolderClient.mockResolvedValue({
            data: null,
        })

        const store = useFoldersStore()

        store.folder = {
            id: 99,

            folder_clients: [
                {
                    id: 100,
                },
            ],
        }

        await store.removeClient(10, 100)

        expect(store.folderClients).toEqual([
            {
                id: 100,
            },
        ])
    })
})
