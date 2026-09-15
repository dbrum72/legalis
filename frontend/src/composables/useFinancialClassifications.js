import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth.js'
import { listClassifications } from '@/api/financial-classifications.js'

const revision = ref(0)
export const refreshFinancialClassifications = () => {
    revision.value++
}

export function useFinancialClassifications() {
    const auth = useAuthStore()
    const items = ref([])
    const loading = ref(false)
    const error = ref('')
    let request = 0
    async function reload() {
        const id = ++request
        loading.value = true
        error.value = ''
        try {
            const { data } = await listClassifications()
            if (id === request) items.value = Array.isArray(data) ? data : []
        } catch {
            if (id === request)
                error.value = 'Não foi possível carregar as categorias e os centros de custo.'
        } finally {
            if (id === request) loading.value = false
        }
    }
    watch(
        () => [auth.currentTenant, revision.value],
        () => {
            items.value = []
            void reload()
        },
        { immediate: true },
    )
    const onFocus = () => {
        if (!loading.value) void reload()
    }
    onMounted(() => window.addEventListener('focus', onFocus))
    onBeforeUnmount(() => {
        window.removeEventListener('focus', onFocus)
        request++
    })
    const options = (kind, selected, includeInactive = false) =>
        items.value
            .filter(
                (item) =>
                    item.kind === kind &&
                    (includeInactive || item.active || String(item.id) === String(selected)),
            )
            .map((item) => ({
                value: item.id,
                label: item.name + (item.active ? '' : ' (inativo)'),
            }))
    return { items, loading, error, reload, options }
}
