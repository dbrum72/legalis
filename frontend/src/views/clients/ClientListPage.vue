<template>
    <PageContainer>
        <div class="client-list">
            <header class="client-list__header">
                <div>
                    <h1 class="client-list__title">
                        Clientes
                    </h1>

                    <p class="client-list__description">
                        Consulte e gerencie os clientes cadastrados.
                    </p>
                </div>

                <AppButton v-if="canCreate" type="button" @click="createClient">
                    Novo cliente
                </AppButton>
            </header>

            <AppTable :columns="columns" :rows="clientsStore.clients" empty-text="Nenhum cliente cadastrado.">
                <template #cell-marital_status="{ row }">
                    {{ row.marital_status?.name ?? '—' }}
                </template>

                <template #cell-actions="{ row }">
                    <div class="client-list__actions">
                        <AppButton v-if="canUpdate" type="button" size="sm" variant="outline" @click="editClient(row)">
                            Editar
                        </AppButton>

                        <AppButton v-if="canDelete" type="button" size="sm" variant="ghost" @click="requestDelete(row)">
                            Excluir
                        </AppButton>
                    </div>
                </template>
            </AppTable>

            <div v-if="deleteError" class="client-list__error" role="alert">
                {{ deleteError }}
            </div>

            <AppConfirmDialog :open="Boolean(clientToDelete)" title="Excluir cliente" :message="deleteMessage"
                confirm-label="Excluir" cancel-label="Cancelar" :loading="deleting" @confirm="confirmDelete"
                @cancel="cancelDelete" />
        </div>
    </PageContainer>
</template>

<script setup>
import {
    computed,
    onMounted,
    ref
} from 'vue'

import { useRouter } from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'

import {
    AppButton,
    AppConfirmDialog,
    AppTable,
} from '@/components/ui'

import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'

const router = useRouter()

const authStore = useAuthStore()
const clientsStore = useClientsStore()

const clientToDelete = ref(null)
const deleting = ref(false)
const deleteError = ref('')

const columns = [
    {
        key: 'name',
        label: 'Nome',
    },
    {
        key: 'document',
        label: 'Documento',
    },
    {
        key: 'phone',
        label: 'Telefone',
    },
    {
        key: 'email',
        label: 'E-mail',
    },
    {
        key: 'marital_status',
        label: 'Estado civil',
    },
    {
        key: 'actions',
        label: 'Ações',
        align: 'end',
    },
]

const deleteMessage = computed(() => {
    if (!clientToDelete.value) {
        return ''
    }

    return `Deseja realmente excluir o cliente "${clientToDelete.value.name}"?`
})

const canCreate = computed(() =>
    authStore.hasPermission('clients.create'),
)

const canUpdate = computed(() =>
    authStore.hasPermission('clients.update'),
)

const canDelete = computed(() =>
    authStore.hasPermission('clients.delete'),
)

function createClient() {
    router.push({
        name: 'clients.create',
    })
}

function editClient(client) {
    router.push({
        name: 'clients.edit',
        params: {
            id: client.id,
        },
    })
}

function requestDelete(client) {
    clientToDelete.value = client
    deleteError.value = ''
}

function cancelDelete() {
    if (deleting.value) {
        return
    }

    clientToDelete.value = null
    deleteError.value = ''
}

async function confirmDelete() {
    if (
        !clientToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value = true
    deleteError.value = ''

    try {
        await clientsStore.remove(
            clientToDelete.value.id,
        )

        clientToDelete.value = null
    } catch {
        deleteError.value =
            'Não foi possível excluir o cliente. Tente novamente.'
    } finally {
        deleting.value = false
    }
}

onMounted(async () => {
    await clientsStore.fetchClients()
})
</script>

<style scoped>
.client-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.client-list__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.client-list__title {
    margin: 0;
    color: var(--color-text);
}

.client-list__description {
    margin:
        var(--space-2) 0 0;

    color: var(--color-text-muted);
}

.client-list__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
    white-space: nowrap;
}

.client-list__error {
    padding:
        var(--space-3) var(--space-4);

    border: 1px solid var(--color-danger);
    border-radius: var(--radius-md);

    background: var(--color-danger-soft);
    color: var(--color-danger);

    font-size: var(--font-size-sm);
}

@media (max-width: 640px) {
    .client-list__header {
        flex-direction: column;
    }
}
</style>