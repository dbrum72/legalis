import { ref } from 'vue'

import { defineStore } from 'pinia'

import { getAgenda as getAgendaRequest } from '@/api/agenda.js'

function emptyPeriod() {
    return {
        start: null,

        end: null,
    }
}

export const useAgendaStore = defineStore('agenda', () => {
    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    const period = ref(emptyPeriod())

    const items = ref([])

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    async function fetchAgenda(params) {
        const response = await getAgendaRequest(params)

        const payload = response?.data ?? {}

        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        const payloadPeriod = payload?.period

        period.value =
            payloadPeriod !== null &&
            typeof payloadPeriod === 'object' &&
            !Array.isArray(payloadPeriod)
                ? {
                      start: typeof payloadPeriod.start === 'string' ? payloadPeriod.start : null,

                      end: typeof payloadPeriod.end === 'string' ? payloadPeriod.end : null,
                  }
                : emptyPeriod()

        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        */

        items.value = Array.isArray(payload?.items) ? payload.items : []

        /*
        |--------------------------------------------------------------------------
        | Contract
        |--------------------------------------------------------------------------
        */

        return {
            period: period.value,

            items: items.value,
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Clear
    |--------------------------------------------------------------------------
    */

    function clear() {
        period.value = emptyPeriod()

        items.value = []
    }

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    return {
        period,

        items,

        fetchAgenda,

        clear,
    }
})
