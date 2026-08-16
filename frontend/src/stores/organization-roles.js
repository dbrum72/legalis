import { computed, ref } from 'vue'

import { defineStore } from 'pinia'

import { listOrganizationRoles as listOrganizationRolesRequest } from '@/api/organization-roles.js'

export const useOrganizationRolesStore = defineStore('organization-roles', () => {
    const roles = ref([])

    const count = computed(() => roles.value.length)

    const options = computed(() =>
        roles.value.map((role) => ({
            label: role.name,

            value: role.name,
        })),
    )

    function getById(id) {
        return roles.value.find((role) => role.id === Number(id)) ?? null
    }

    function getByName(name) {
        return roles.value.find((role) => role.name === name) ?? null
    }

    async function fetchRoles() {
        const response = await listOrganizationRolesRequest()

        roles.value = Array.isArray(response.data) ? response.data : []

        return roles.value
    }

    function clear() {
        roles.value = []
    }

    return {
        roles,

        count,
        options,

        getById,
        getByName,

        fetchRoles,

        clear,
    }
})
