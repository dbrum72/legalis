<template>
    <PageContainer>
        <div class="folder-list">
            <header class="folder-list__header">
                <div>
                    <h1 class="folder-list__title">
                        Pastas
                    </h1>

                    <p class="folder-list__description">
                        Consulte e gerencie as pastas jurídicas cadastradas.
                    </p>
                </div>

                <AppButton v-if="canCreate" type="button" @click="createFolder">
                    Nova pasta
                </AppButton>
            </header>

            <AppTable :columns="columns" :rows="foldersStore.folders" empty-text="Nenhuma pasta cadastrada.">
                <template #cell-process_number="{ value }">
                    {{ value || '—' }}
                </template>

                <template #cell-actions="{ row }">
                    <div class="folder-list__actions">
                        <AppButton v-if="canUpdate" type="button" size="sm" variant="outline" @click="editFolder(row)">
                            Editar
                        </AppButton>

                        <AppButton v-if="canDelete" type="button" size="sm" variant="ghost" @click="requestDelete(row)">
                            Excluir
                        </AppButton>
                    </div>
                </template>
            </AppTable>

            <div v-if="deleteError" class="folder-list__error" role="alert">
                {{ deleteError }}
            </div>

            <AppConfirmDialog :open="Boolean(folderToDelete)" title="Excluir pasta" :message="deleteMessage"
                confirm-label="Excluir" cancel-label="Cancelar" :loading="deleting" @confirm="confirmDelete"
                @cancel="cancelDelete" />
        </div>
    </PageContainer>
</template>

<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'

import { useRouter } from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'

import {
    AppButton,
    AppConfirmDialog,
    AppTable,
} from '@/components/ui'

import { useAuthStore } from '@/stores/auth.js'
import { useFoldersStore } from '@/stores/folders.js'

const router = useRouter()

const authStore = useAuthStore()
const foldersStore = useFoldersStore()

const folderToDelete = ref(null)
const deleting = ref(false)
const deleteError = ref('')

const columns = [
    {
        key: 'name',
        label: 'Nome',
    },
    {
        key: 'process_number',
        label: 'Número do processo',
    },
    {
        key: 'actions',
        label: 'Ações',
        align: 'end',
    },
]

const canCreate = computed(() =>
    authStore.hasPermission(
        'folders.create',
    ),
)

const canUpdate = computed(() =>
    authStore.hasPermission(
        'folders.update',
    ),
)

const canDelete = computed(() =>
    authStore.hasPermission(
        'folders.delete',
    ),
)

const deleteMessage = computed(() => {
    if (!folderToDelete.value) {
        return ''
    }

    return `Deseja realmente excluir a pasta "${folderToDelete.value.name}"?`
})

function createFolder() {
    router.push({
        name: 'folders.create',
    })
}

function editFolder(folder) {
    router.push({
        name: 'folders.edit',
        params: {
            id: folder.id,
        },
    })
}

function requestDelete(folder) {
    folderToDelete.value = folder
    deleteError.value = ''
}

function cancelDelete() {
    if (deleting.value) {
        return
    }

    folderToDelete.value = null
    deleteError.value = ''
}

async function confirmDelete() {
    if (
        !folderToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value = true
    deleteError.value = ''

    try {
        await foldersStore.remove(
            folderToDelete.value.id,
        )

        folderToDelete.value = null
    } catch {
        deleteError.value =
            'Não foi possível excluir a pasta. Tente novamente.'
    } finally {
        deleting.value = false
    }
}

onMounted(async () => {
    await foldersStore.fetchFolders()
})
</script>

<style scoped>
.folder-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.folder-list__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-list__title {
    margin: 0;
    color: var(--color-text);
}

.folder-list__description {
    margin:
        var(--space-2) 0 0;

    color: var(--color-text-muted);
}

.folder-list__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
    white-space: nowrap;
}

.folder-list__error {
    padding:
        var(--space-3) var(--space-4);

    border: 1px solid var(--color-danger);
    border-radius: var(--radius-md);

    background: var(--color-danger-soft);
    color: var(--color-danger);

    font-size: var(--font-size-sm);
}

@media (max-width: 640px) {
    .folder-list__header {
        flex-direction: column;
    }
}
</style>