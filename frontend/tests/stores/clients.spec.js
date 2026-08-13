import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useClientsStore } from '@/stores/clients.js'

vi.mock('@/api/clients.js', () => ({
    listClients: vi.fn(),
    getClient: vi.fn(),
    createClient: vi.fn(),
    updateClient: vi.fn(),
    deleteClient: vi.fn(),
}))

import { listClients, getClient, createClient, updateClient, deleteClient } from '@/api/clients.js'

describe('clients store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useClientsStore()

        expect(store.clients).toEqual([])
        expect(store.client).toBeNull()
        expect(store.count).toBe(0)
    })

    it('getById retorna cliente existente', () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
            {
                id: 2,
                name: 'Cliente B',
            },
        ]

        expect(store.getById(2)).toEqual({
            id: 2,
            name: 'Cliente B',
        })
    })

    it('getById aceita id em formato string', () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
        ]

        expect(store.getById('1')).toEqual({
            id: 1,
            name: 'Cliente A',
        })
    })

    it('getById retorna null quando cliente não existe', () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
        ]

        expect(store.getById(999)).toBeNull()
    })

    it('fetchClients popula lista de clientes', async () => {
        listClients.mockResolvedValue({
            data: [
                {
                    id: 1,
                    name: 'Cliente A',
                },
                {
                    id: 2,
                    name: 'Cliente B',
                },
            ],
        })

        const store = useClientsStore()

        const result = await store.fetchClients()

        expect(store.clients).toHaveLength(2)
        expect(store.count).toBe(2)

        expect(result).toEqual([
            {
                id: 1,
                name: 'Cliente A',
            },
            {
                id: 2,
                name: 'Cliente B',
            },
        ])

        expect(listClients).toHaveBeenCalledTimes(1)
    })

    it('fetchClients usa array vazio quando resposta não é array', async () => {
        listClients.mockResolvedValue({
            data: null,
        })

        const store = useClientsStore()

        const result = await store.fetchClients()

        expect(store.clients).toEqual([])
        expect(result).toEqual([])
    })

    it('fetchClient define cliente atual', async () => {
        getClient.mockResolvedValue({
            data: {
                id: 1,
                name: 'Cliente A',
            },
        })

        const store = useClientsStore()

        const result = await store.fetchClient(1)

        expect(getClient).toHaveBeenCalledWith(1)

        expect(store.client).toEqual({
            id: 1,
            name: 'Cliente A',
        })

        expect(result).toEqual({
            id: 1,
            name: 'Cliente A',
        })
    })

    it('create adiciona cliente à lista e define cliente atual', async () => {
        const payload = {
            name: 'Cliente Novo',
            document: '12345678901',
        }

        const createdClient = {
            id: 10,
            ...payload,
        }

        createClient.mockResolvedValue({
            data: createdClient,
        })

        const store = useClientsStore()

        const result = await store.create(payload)

        expect(createClient).toHaveBeenCalledWith(payload)

        expect(store.clients).toEqual([createdClient])

        expect(store.client).toEqual(createdClient)

        expect(result).toEqual(createdClient)
    })

    it('update substitui cliente existente na lista', async () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente Antigo',
            },
            {
                id: 2,
                name: 'Outro Cliente',
            },
        ]

        updateClient.mockResolvedValue({
            data: {
                id: 1,
                name: 'Cliente Atualizado',
            },
        })

        const result = await store.update(1, {
            name: 'Cliente Atualizado',
        })

        expect(updateClient).toHaveBeenCalledWith(1, {
            name: 'Cliente Atualizado',
        })

        expect(store.clients[0]).toEqual({
            id: 1,
            name: 'Cliente Atualizado',
        })

        expect(store.clients[1]).toEqual({
            id: 2,
            name: 'Outro Cliente',
        })

        expect(result).toEqual({
            id: 1,
            name: 'Cliente Atualizado',
        })
    })

    it('update atualiza também cliente atual', async () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente Antigo',
            },
        ]

        store.client = {
            id: 1,
            name: 'Cliente Antigo',
        }

        updateClient.mockResolvedValue({
            data: {
                id: 1,
                name: 'Cliente Atualizado',
            },
        })

        await store.update(1, {
            name: 'Cliente Atualizado',
        })

        expect(store.client).toEqual({
            id: 1,
            name: 'Cliente Atualizado',
        })
    })

    it('update não adiciona cliente que não estava na lista', async () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
        ]

        updateClient.mockResolvedValue({
            data: {
                id: 2,
                name: 'Cliente B Atualizado',
            },
        })

        await store.update(2, {
            name: 'Cliente B Atualizado',
        })

        expect(store.clients).toEqual([
            {
                id: 1,
                name: 'Cliente A',
            },
        ])
    })

    it('remove exclui cliente da lista', async () => {
        deleteClient.mockResolvedValue({
            data: null,
        })

        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
            {
                id: 2,
                name: 'Cliente B',
            },
        ]

        await store.remove(1)

        expect(deleteClient).toHaveBeenCalledWith(1)

        expect(store.clients).toEqual([
            {
                id: 2,
                name: 'Cliente B',
            },
        ])
    })

    it('remove aceita id em formato string', async () => {
        deleteClient.mockResolvedValue({
            data: null,
        })

        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
        ]

        await store.remove('1')

        expect(store.clients).toEqual([])
    })

    it('remove limpa cliente atual quando é o mesmo registro', async () => {
        deleteClient.mockResolvedValue({
            data: null,
        })

        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
        ]

        store.client = {
            id: 1,
            name: 'Cliente A',
        }

        await store.remove(1)

        expect(store.client).toBeNull()
    })

    it('clearCurrent limpa apenas cliente atual', () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
        ]

        store.client = {
            id: 1,
            name: 'Cliente A',
        }

        store.clearCurrent()

        expect(store.client).toBeNull()

        expect(store.clients).toEqual([
            {
                id: 1,
                name: 'Cliente A',
            },
        ])
    })

    it('clear limpa lista e cliente atual', () => {
        const store = useClientsStore()

        store.clients = [
            {
                id: 1,
                name: 'Cliente A',
            },
        ]

        store.client = {
            id: 1,
            name: 'Cliente A',
        }

        store.clear()

        expect(store.clients).toEqual([])
        expect(store.client).toBeNull()
        expect(store.count).toBe(0)
    })
})
