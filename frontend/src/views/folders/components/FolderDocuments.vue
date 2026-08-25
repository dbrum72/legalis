<template>
    <section class="folder-documents">
        <header class="folder-documents__header">
            <div>
                <h2 class="folder-documents__title">
                    Documentos
                </h2>

                <p class="folder-documents__description">
                    Consulte os documentos vinculados à pasta.
                </p>
            </div>

            <AppButton v-if="canUpdate && !showUploadForm" type="button" variant="outline" @click="openUploadForm">
                Anexar documento
            </AppButton>
        </header>

        <form v-if="showUploadForm" class="folder-documents__upload-form" @submit.prevent="submitUpload">
            <div class="folder-documents__upload-grid">
                <div class="folder-documents__field folder-documents__field--full">
                    <label class="folder-documents__label" for="folder-document-file">
                        Arquivo
                    </label>

                    <input id="folder-document-file" class="folder-documents__input" type="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt" :disabled="uploading" required
                        @change="handleFileChange">
                </div>

                <div class="folder-documents__field folder-documents__field--full">
                    <label class="folder-documents__label" for="folder-document-name">
                        Nome
                    </label>

                    <input id="folder-document-name" v-model="uploadForm.name" class="folder-documents__input"
                        name="name" type="text" maxlength="150" :disabled="uploading" required>
                </div>

                <div class="folder-documents__field folder-documents__field--full">
                    <label class="folder-documents__label" for="folder-document-description">
                        Descrição
                    </label>

                    <textarea id="folder-document-description" v-model="uploadForm.description"
                        class="folder-documents__textarea" name="description" rows="3" maxlength="5000"
                        :disabled="uploading" />
                </div>
            </div>

            <div v-if="uploadError" class="folder-documents__error" role="alert">
                {{ uploadError }}
            </div>

            <footer class="folder-documents__upload-actions">
                <AppButton type="button" variant="ghost" :disabled="uploading" @click="cancelUpload">
                    Cancelar
                </AppButton>

                <AppButton type="submit" variant="primary" :loading="uploading" :disabled="uploading">
                    Anexar
                </AppButton>
            </footer>
        </form>

        <AppTable :columns="columns" :rows="folderDocumentsStore.documents" empty-text="Nenhum documento anexado.">
            <template #cell-document="{ row }">
                <div class="folder-documents__document">
                    <strong>
                        {{ row.name }}
                    </strong>

                    <span class="folder-documents__original-name">
                        {{ row.original_name }}
                    </span>
                </div>
            </template>

            <template #cell-description="{ row }">
                {{ row.description || '—' }}
            </template>

            <template #cell-user="{ row }">
                {{ row.user?.name ?? '—' }}
            </template>

            <template #cell-actions="{ row }">
                <div class="folder-documents__actions">
                    <AppButton type="button" size="sm" variant="ghost" :disabled="downloadingId === row.id"
                        @click="downloadDocument(row)">
                        Baixar
                    </AppButton>

                    <AppButton v-if="canUpdate" type="button" size="sm" variant="ghost" :disabled="deleting"
                        @click="requestDelete(row)">
                        Excluir
                    </AppButton>
                </div>
            </template>
        </AppTable>

        <div v-if="loadError" class="folder-documents__error" role="alert">
            {{ loadError }}
        </div>

        <div v-if="downloadError" class="folder-documents__error" role="alert">
            {{ downloadError }}
        </div>

        <div v-if="deleteError" class="folder-documents__error" role="alert">
            {{ deleteError }}
        </div>

        <AppConfirmDialog :open="Boolean(documentToDelete)" title="Excluir documento" :message="deleteMessage"
            confirm-label="Excluir" cancel-label="Cancelar" :loading="deleting" @confirm="confirmDelete"
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
    AppButton,
    AppConfirmDialog,
    AppTable,
} from '@/components/ui'

import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation.js'

import { useAuthStore } from '@/stores/auth.js'

import { useFolderDocumentsStore } from '@/stores/folder-documents.js'

const props = defineProps({
    folderId: {
        type: [
            Number,
            String,
        ],

        required: true,
    },
})

const emit = defineEmits(['changed'])

const authStore = useAuthStore()

const folderDocumentsStore = useFolderDocumentsStore()

const {
    itemToDelete: documentToDelete,
    deleting,
    requestDelete,
    cancelDelete,
    clearDelete,
} = useDeleteConfirmation()

const loadError = ref('')

const uploadError = ref('')

const downloadError = ref('')

const deleteError = ref('')

const showUploadForm = ref(false)

const uploading = ref(false)

const downloadingId = ref(null)

const selectedFile = ref(null)

const uploadForm =
    reactive({
        name: '',
        description: '',
    })

const canUpdate =
    computed(() =>
        authStore.hasPermission(
            'folders.update',
        ),
    )

const deleteMessage =
    computed(() => {
        if (!documentToDelete.value) {
            return ''
        }

        return `Deseja realmente excluir o documento "${documentToDelete.value.name}"?`
    })

const columns = [
    {
        key: 'document',
        label: 'Documento',
    },

    {
        key: 'description',
        label: 'Descrição',
    },

    {
        key: 'user',
        label: 'Responsável',
    },

    {
        key: 'actions',
        label: 'Ações',
        align: 'end',
    },
]

function openUploadForm() {
    uploadError.value =
        ''

    showUploadForm.value =
        true
}

function resetUploadForm() {
    selectedFile.value =
        null

    uploadForm.name =
        ''

    uploadForm.description =
        ''

    uploadError.value =
        ''
}

function cancelUpload() {
    if (uploading.value) {
        return
    }

    resetUploadForm()

    showUploadForm.value =
        false
}

function handleFileChange(event) {
    selectedFile.value =
        event.target.files?.[0] ??
        null
}

async function submitUpload() {
    if (
        uploading.value ||
        !selectedFile.value ||
        !uploadForm.name.trim()
    ) {
        return
    }

    uploading.value =
        true

    uploadError.value =
        ''

    const payload =
        new FormData()

    payload.append(
        'file',
        selectedFile.value,
    )

    payload.append(
        'name',
        uploadForm.name.trim(),
    )

    if (
        uploadForm.description.trim()
    ) {
        payload.append(
            'description',
            uploadForm.description.trim(),
        )
    }

    try {
        await folderDocumentsStore.uploadDocument(
            props.folderId,
            payload,
        )
        emit(
            'changed',
        )

        resetUploadForm()

        showUploadForm.value =
            false
    } catch {
        uploadError.value =
            'Não foi possível anexar o documento. Tente novamente.'
    } finally {
        uploading.value =
            false
    }
}

async function downloadDocument(document) {
    if (
        downloadingId.value !== null
    ) {
        return
    }

    downloadingId.value =
        document.id

    downloadError.value =
        ''

    try {
        const blob =
            await folderDocumentsStore.downloadDocument(
                props.folderId,
                document.id,
            )

        const objectUrl =
            URL.createObjectURL(
                blob,
            )

        const link =
            window.document.createElement(
                'a',
            )

        link.href =
            objectUrl

        link.download =
            document.original_name

        link.style.display =
            'none'

        window.document.body.appendChild(
            link,
        )

        link.click()

        link.remove()

        URL.revokeObjectURL(
            objectUrl,
        )
    } catch {
        downloadError.value =
            'Não foi possível baixar o documento. Tente novamente.'
    } finally {
        downloadingId.value =
            null
    }
}

async function confirmDelete() {
    if (
        !documentToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value =
        true

    deleteError.value =
        ''

    try {
        await folderDocumentsStore.removeDocument(
            props.folderId,
            documentToDelete.value.id,
        )

        emit(
            'changed',
        )

        clearDelete()
    } catch {
        deleteError.value =
            'Não foi possível excluir o documento. Tente novamente.'
    } finally {
        deleting.value =
            false
    }
}

async function loadDocuments() {
    loadError.value =
        ''

    try {
        await folderDocumentsStore.fetchDocuments(
            props.folderId,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar os documentos. Tente novamente.'
    }
}

onMounted(
    loadDocuments,
)
</script>

<style scoped>
.folder-documents {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-documents__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-documents__title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-lg);

    font-weight:
        var(--font-weight-semibold);
}

.folder-documents__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-documents__upload-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);

    padding:
        var(--space-5);

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);
}

.folder-documents__upload-grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-4) var(--space-5);
}

.folder-documents__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.folder-documents__field--full {
    grid-column:
        1 / -1;
}

.folder-documents__label {
    color:
        var(--color-text);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-documents__input,
.folder-documents__textarea {
    width: 100%;

    box-sizing:
        border-box;

    border:
        1px solid var(--color-divider);

    border-radius:
        var(--radius-md);

    background:
        var(--color-surface);

    color:
        var(--color-text);

    font: inherit;
}

.folder-documents__input {
    min-height:
        2.75rem;

    padding:
        var(--space-2) var(--space-3);
}

.folder-documents__textarea {
    min-height:
        6rem;

    padding:
        var(--space-3);

    resize:
        vertical;
}

.folder-documents__upload-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}

.folder-documents__document {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.folder-documents__original-name {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-documents__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);

    white-space: nowrap;
}

.folder-documents__error {
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
    .folder-documents__header {
        flex-direction:
            column;
    }

    .folder-documents__upload-grid {
        grid-template-columns:
            1fr;
    }

    .folder-documents__field--full {
        grid-column:
            auto;
    }

    .folder-documents__upload-actions {
        flex-wrap:
            wrap;
    }

    .folder-documents__actions {
        white-space:
            normal;
    }
}
</style>