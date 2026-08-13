import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import {
    createClient as createClientRequest,
    deleteClient as deleteClientRequest,
    getClient as getClientRequest,
    listClients as listClientsRequest,
    updateClient as updateClientRequest,
} from '@/api/clients.js'

export const useClientsStore = defineStore('clients', () => {
    const clients = ref([])
    const client = ref(null)

    const count = computed(() => clients.value.length)

    function getById(id) {
        return clients.value.find((item) => item.id === Number(id)) ?? null
    }

    async function fetchClients() {
        const response = await listClientsRequest()

        clients.value = Array.isArray(response.data) ? response.data : []

        return clients.value
    }

    async function fetchClient(id) {
        const response = await getClientRequest(id)

        client.value = response.data

        return client.value
    }

    async function create(payload) {
        const response = await createClientRequest(payload)

        const createdClient = response.data

        clients.value.push(createdClient)
        client.value = createdClient

        return createdClient
    }

    async function update(id, payload) {
        const response = await updateClientRequest(id, payload)

        const updatedClient = response.data

        const index = clients.value.findIndex((item) => item.id === updatedClient.id)

        if (index !== -1) {
            clients.value[index] = updatedClient
        }

        if (client.value?.id === updatedClient.id) {
            client.value = updatedClient
        }

        return updatedClient
    }

    async function remove(id) {
        await deleteClientRequest(id)

        const clientId = Number(id)

        clients.value = clients.value.filter((item) => item.id !== clientId)

        if (client.value?.id === clientId) {
            client.value = null
        }
    }

    function clearCurrent() {
        client.value = null
    }

    function clear() {
        clients.value = []
        client.value = null
    }

    return {
        clients,
        client,

        count,

        getById,

        fetchClients,
        fetchClient,
        create,
        update,
        remove,

        clearCurrent,
        clear,
    }
})
