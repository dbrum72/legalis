import { ref } from 'vue'

import { defineStore } from 'pinia'

import { createOrganizationInvitation } from '@/api/organization-invitations.js'

export const useOrganizationInvitationsStore = defineStore('organization-invitations', () => {
    const invitation = ref(null)

    const creating = ref(false)

    async function create(payload) {
        creating.value = true

        try {
            const result = await createOrganizationInvitation(payload)

            invitation.value = result

            return result
        } finally {
            creating.value = false
        }
    }

    function clear() {
        invitation.value = null

        creating.value = false
    }

    return {
        invitation,
        creating,

        create,
        clear,
    }
})
