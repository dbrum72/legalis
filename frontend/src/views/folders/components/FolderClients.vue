<template>
    <section class="folder-clients">
        <header class="folder-clients__header">
            <div>
                <h2 class="folder-clients__title">
                    Partes
                </h2>

                <p class="folder-clients__description">
                    Gerencie os clientes vinculados à pasta e suas qualificações.
                </p>
            </div>

            <AppButton v-if="canManage" type="button" @click="openCreateDialog">
                Adicionar parte
            </AppButton>
        </header>

        <AppTable :columns="columns" :rows="foldersStore.folderClients" empty-text="Nenhuma parte vinculada.">
            <template #cell-client="{ row }">
                <div class="folder-clients__client">
                    <strong>
                        {{ row.client?.name ?? '—' }}
                    </strong>

                    <span v-if="row.client?.document" class="folder-clients__client-document">
                        {{ row.client.document }}
                    </span>
                </div>
            </template>

            <template #cell-qualification="{ row }">
                {{ row.qualification?.name ?? '—' }}
            </template>

            <template #cell-actions="{ row }">
                <div v-if="canManage" class="folder-clients__actions">
                    <AppButton type="button" size="sm" variant="outline" @click="openEditDialog(row)">
                        Editar
                    </AppButton>

                    <AppButton type="button" size="sm" variant="ghost" @click="requestDelete(row)">
                        Excluir
                    </AppButton>
                </div>
            </template>
        </AppTable>

        <div v-if="operationError" class="folder-clients__error" role="alert">
            {{ operationError }}
        </div>

        <AppDialog :open="dialogOpen" :title="dialogTitle" size="md" :close-on-backdrop="!saving"
            :close-on-escape="!saving" @close="closeDialog">
            <form id="folder-client-form" class="folder-clients__form" novalidate @submit.prevent="save">
                <AppAutocomplete v-if="!isEditing" v-model="form.client_id" v-model:search-value="clientSearch"
                    label="Cliente" placeholder="Pesquise um cliente" :options="clientOptions" :error="errors.client_id"
                    :disabled="saving" :min-search-length="0" clearable required autofocus />

                <AppInput v-else :model-value="editingFolderClient?.client?.name ?? ''" label="Cliente" readonly />

                <AppSelect v-model="form.qualification_id" label="Qualificação" placeholder="Selecione a qualificação"
                    :options="qualificationsStore.options" :error="errors.qualification_id" :disabled="saving"
                    required />

                <div v-if="dialogError" class="folder-clients__dialog-error" role="alert">
                    {{ dialogError }}
                </div>
            </form>

            <template #footer>
                <AppButton type="button" variant="ghost" :disabled="saving" @click="closeDialog">
                    Cancelar
                </AppButton>

                <AppButton type="submit" form="folder-client-form" variant="primary" :loading="saving"
                    :disabled="saving">
                    {{ submitLabel }}
                </AppButton>
            </template>
        </AppDialog>

        <AppConfirmDialog :open="Boolean(folderClientToDelete)" title="Remover parte" :message="deleteMessage"
            confirm-label="Remover" cancel-label="Cancelar" :loading="deleting" @confirm="confirmDelete"
            @cancel="cancelDelete" />
    </section>
</template>

<script setup>
import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    AppAutocomplete,
    AppInput,
    AppSelect,
} from '@/components/forms'

import {
    AppButton,
    AppConfirmDialog,
    AppDialog,
    AppTable,
} from '@/components/ui'

import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'
import { useFoldersStore } from '@/stores/folders.js'
import { useQualificationsStore } from '@/stores/qualifications.js'
import {
    applyValidationErrors,
    clearValidationErrors,
} from '@/utils/validationErrors'

const props = defineProps({
    folderId: {
        type: [Number, String],
        required: true,
    },
})

const authStore = useAuthStore()
const clientsStore = useClientsStore()
const foldersStore = useFoldersStore()
const qualificationsStore =
    useQualificationsStore()

const dialogOpen = ref(false)
const editingFolderClient = ref(null)

const saving = ref(false)
const deleting = ref(false)

const clientSearch = ref('')

const dialogError = ref('')
const operationError = ref('')

const folderClientToDelete = ref(null)

const form = reactive({
    client_id: null,
    qualification_id: null,
})

const errors = reactive({
    client_id: '',
    qualification_id: '',
})

const columns = [
    {
        key: 'client',
        label: 'Cliente',
    },
    {
        key: 'qualification',
        label: 'Qualificação',
    },
    {
        key: 'actions',
        label: 'Ações',
        align: 'end',
    },
]

const canManage = computed(() =>
    authStore.hasPermission(
        'folders.update',
    ),
)

const isEditing = computed(() =>
    Boolean(editingFolderClient.value),
)

const dialogTitle = computed(() =>
    isEditing.value
        ? 'Editar qualificação'
        : 'Adicionar parte',
)

const submitLabel = computed(() =>
    isEditing.value
        ? 'Salvar alterações'
        : 'Adicionar',
)

const clientOptions = computed(() =>
    clientsStore.clients.map(
        (client) => ({
            label: client.document
                ? `${client.name} — ${client.document}`
                : client.name,

            value: client.id,
        }),
    ),
)

const deleteMessage = computed(() => {
    const clientName =
        folderClientToDelete.value
            ?.client?.name

    if (!clientName) {
        return ''
    }

    return `Deseja realmente remover "${clientName}" desta pasta?`
})

function clearForm() {
    form.client_id = null
    form.qualification_id = null

    clientSearch.value = ''

    errors.qualification_id = ''

    dialogError.value = ''

    clearValidationErrors(
        errors,
    )
}

function resetDialogState() {
    dialogOpen.value = false
    editingFolderClient.value = null

    clearForm()
}

function openCreateDialog() {
    clearForm()

    editingFolderClient.value = null
    operationError.value = ''

    dialogOpen.value = true
}

function openEditDialog(folderClient) {
    clearForm()

    editingFolderClient.value =
        folderClient

    form.client_id =
        folderClient.client_id ??
        folderClient.client?.id ??
        null

    form.qualification_id =
        folderClient.qualification_id ??
        folderClient.qualification?.id ??
        null

    operationError.value = ''

    dialogOpen.value = true
}

function closeDialog() {
    if (saving.value) {
        return
    }

    resetDialogState()
}

function validate() {
    clearValidationErrors(errors)

    if (
        !isEditing.value &&
        !form.client_id
    ) {
        errors.client_id =
            'Selecione o cliente.'
    }

    if (!form.qualification_id) {
        errors.qualification_id =
            'Selecione a qualificação.'
    }

    return !(
        errors.client_id ||
        errors.qualification_id
    )
}

function hasDuplicateLink() {
    if (isEditing.value) {
        return false
    }

    return foldersStore.folderClients.some(
        (item) =>
            Number(
                item.client_id ??
                item.client?.id,
            ) === Number(form.client_id) &&
            Number(
                item.qualification_id ??
                item.qualification?.id,
            ) ===
            Number(
                form.qualification_id,
            ),
    )
}

async function save() {
    if (saving.value) {
        return
    }

    if (!validate()) {
        return
    }

    if (hasDuplicateLink()) {
        dialogError.value =
            'Este cliente já possui essa qualificação na pasta.'

        return
    }

    saving.value = true
    dialogError.value = ''
    operationError.value = ''

    let saved = false

    try {
        if (isEditing.value) {
            await foldersStore
                .updateClientQualification(
                    props.folderId,
                    editingFolderClient
                        .value.id,
                    {
                        qualification_id:
                            form.qualification_id,
                    },
                )
        } else {
            await foldersStore.addClient(
                props.folderId,
                {
                    client_id:
                        form.client_id,

                    qualification_id:
                        form.qualification_id,
                },
            )
        }

        saved = true
    } catch (error) {
        const validationErrors = error.response?.data?.errors ?? {}
        if (error.response?.status === 422) {
            applyValidationErrors(
                errors,
                validationErrors,
            )
            if (!errors.client_id && !errors.qualification_id) {
                dialogError.value = 'Não foi possível validar o vínculo informado.'
            }
            return
        }

        dialogError.value =
            'Não foi possível salvar a parte. Tente novamente.'
    } finally {
        saving.value = false
    }

    if (saved) {
        resetDialogState()
    }
}

function requestDelete(folderClient) {
    folderClientToDelete.value =
        folderClient

    operationError.value = ''
}

function cancelDelete() {
    if (deleting.value) {
        return
    }

    folderClientToDelete.value = null
}

async function confirmDelete() {
    if (
        !folderClientToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value = true
    operationError.value = ''

    try {
        await foldersStore.removeClient(
            props.folderId,
            folderClientToDelete.value.id,
        )

        folderClientToDelete.value = null
    } catch {
        operationError.value =
            'Não foi possível remover a parte. Tente novamente.'
    } finally {
        deleting.value = false
    }
}

onMounted(async () => {
    await Promise.all([
        clientsStore.fetchClients(),
        qualificationsStore
            .fetchQualifications(),
    ])
})
</script>

<style scoped>
.folder-clients {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-clients__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-clients__title {
    margin: 0;

    color: var(--color-text);
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
}

.folder-clients__description {
    margin:
        var(--space-2) 0 0;

    color: var(--color-text-muted);
}

.folder-clients__client {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.folder-clients__client-document {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}

.folder-clients__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-2);
}

.folder-clients__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-clients__error,
.folder-clients__dialog-error {
    padding:
        var(--space-3) var(--space-4);

    border:
        1px solid var(--color-danger);

    border-radius:
        var(--radius-md);

    background:
        var(--color-danger-soft);

    color:
        var(--color-danger);

    font-size:
        var(--font-size-sm);
}

@media (max-width: 640px) {
    .folder-clients__header {
        flex-direction: column;
    }

    .folder-clients__actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>