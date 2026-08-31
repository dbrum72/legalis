<template>
    <section class="folder-movements">
        <header class="folder-movements__header">
            <div>
                <h2 class="folder-movements__title">
                    Movimentações
                </h2>

                <p class="folder-movements__description">
                    Acompanhe o histórico de movimentações da pasta.
                </p>
            </div>

            <AppButton v-if="canUpdate && !showCreateForm" type="button" variant="outline" @click="openCreateForm">
                Registrar movimentação
            </AppButton>
        </header>

        <form v-if="showCreateForm" class="folder-movements__form" @submit.prevent="submitMovement">
            <div class="folder-movements__form-grid">
                <div class="folder-movements__field">
                    <label class="folder-movements__label" for="folder-movement-occurred-at">
                        Data e hora
                    </label>

                    <input id="folder-movement-occurred-at" v-model="form.occurred_at" class="folder-movements__input"
                        name="occurred_at" type="datetime-local" :disabled="submitting" required>
                </div>

                <div class="folder-movements__field">
                    <label class="folder-movements__label" for="folder-movement-title">
                        Título
                    </label>

                    <input id="folder-movement-title" v-model="form.title" class="folder-movements__input" name="title"
                        type="text" maxlength="180" :disabled="submitting" required>
                </div>

                <div class="folder-movements__field folder-movements__field--full">
                    <label class="folder-movements__label" for="folder-movement-description">
                        Descrição
                    </label>

                    <textarea id="folder-movement-description" v-model="form.description"
                        class="folder-movements__textarea" name="description" rows="4" maxlength="10000"
                        :disabled="submitting" />
                </div>
            </div>

            <div v-if="createError" class="folder-movements__error" role="alert">
                {{ createError }}
            </div>

            <footer class="folder-movements__form-actions">
                <AppButton type="button" variant="ghost" :disabled="submitting" @click="cancelCreate">
                    Cancelar
                </AppButton>

                <AppButton type="submit" variant="primary" :loading="submitting" :disabled="submitting">
                    Registrar
                </AppButton>
            </footer>
        </form>

        <div v-if="loadError" class="folder-movements__error" role="alert">
            {{ loadError }}
        </div>

        <div v-if="deleteError" class="folder-movements__error" role="alert">
            {{ deleteError }}
        </div>

        <div v-if="
            !loadError &&
            folderMovementsStore.movements.length === 0
        " class="folder-movements__empty">
            Nenhuma movimentação registrada.
        </div>

        <div v-else-if="
            !loadError &&
            folderMovementsStore.movements.length > 0
        " class="folder-movements__timeline">
            <article v-for="movement in folderMovementsStore.movements" :key="movement.id"
                class="folder-movements__item">
                <div class="folder-movements__marker" />

                <div class="folder-movements__content">
                    <div class="folder-movements__item-header">
                        <div>
                            <div class="folder-movements__title-row">
                                <h3 class="folder-movements__item-title">
                                    {{ movement.title }}
                                </h3>

                                <span v-if="movement.source === 'datajud'" class="folder-movements__source">
                                    DataJud
                                </span>

                                <span v-if="movement.source_code" class="folder-movements__code">
                                    TPU {{ movement.source_code }}
                                </span>
                            </div>

                            <p class="folder-movements__meta">
                                {{ formatShortDateTime(movement.occurred_at) }}
                                ·
                                {{ movementActor(movement) }}
                            </p>
                        </div>

                        <AppButton v-if="canUpdate && !movement.source" type="button" size="sm" variant="ghost" :disabled="deleting"
                            @click="requestDelete(movement)">
                            Excluir
                        </AppButton>
                    </div>

                    <p v-if="movement.description" class="folder-movements__item-description">
                        {{ movement.description }}
                    </p>
                </div>
            </article>
        </div>

        <AppConfirmDialog :open="Boolean(movementToDelete)" title="Excluir movimentação" :message="deleteMessage"
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
} from '@/components/ui'

import { formatShortDateTime } from '@/utils/date'

import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation.js'

import { useAuthStore } from '@/stores/auth.js'

import { useFolderMovementsStore } from '@/stores/folder-movements.js'

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

const authStore =
    useAuthStore()

const folderMovementsStore =
    useFolderMovementsStore()

const {
    itemToDelete: movementToDelete,
    deleting,
    requestDelete,
    cancelDelete,
    clearDelete,
} = useDeleteConfirmation()

const loadError =
    ref('')

const createError =
    ref('')

const deleteError =
    ref('')

const showCreateForm =
    ref(false)

const submitting =
    ref(false)

const form =
    reactive({
        occurred_at: '',
        title: '',
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
        if (!movementToDelete.value) {
            return ''
        }

        return `Deseja realmente excluir a movimentação "${movementToDelete.value.title}"?`
    })

function movementActor(movement) {
    if (movement.source === 'datajud') {
        return movement.source_metadata?.orgao_julgador?.nome
            ?? movement.source_metadata?.orgao_julgador?.nomeOrgao
            ?? 'DataJud'
    }

    return movement.user?.name ?? 'Movimentação manual'
}

function openCreateForm() {
    createError.value =
        ''

    showCreateForm.value =
        true
}

function resetForm() {
    form.occurred_at =
        ''

    form.title =
        ''

    form.description =
        ''

    createError.value =
        ''
}

function cancelCreate() {
    if (submitting.value) {
        return
    }

    resetForm()

    showCreateForm.value =
        false
}

async function submitMovement() {
    if (
        submitting.value ||
        !form.occurred_at ||
        !form.title.trim()
    ) {
        return
    }

    submitting.value =
        true

    createError.value =
        ''

    const payload = {
        occurred_at:
            new Date(
                form.occurred_at,
            ).toISOString(),

        title:
            form.title.trim(),

        description:
            form.description.trim(),
    }

    try {
        await folderMovementsStore.createMovement(
            props.folderId,
            payload,
        )
        emit(
            'changed',
        )

        resetForm()

        showCreateForm.value =
            false
    } catch {
        createError.value =
            'Não foi possível registrar a movimentação. Tente novamente.'
    } finally {
        submitting.value =
            false
    }
}

async function confirmDelete() {
    if (
        !movementToDelete.value ||
        deleting.value
    ) {
        return
    }

    deleting.value =
        true

    deleteError.value =
        ''

    try {
        await folderMovementsStore.removeMovement(
            props.folderId,
            movementToDelete.value.id,
        )
        emit(
            'changed',
        )

        clearDelete()
    } catch {
        deleteError.value =
            'Não foi possível excluir a movimentação. Tente novamente.'
    } finally {
        deleting.value =
            false
    }
}

async function loadMovements() {
    loadError.value =
        ''

    try {
        await folderMovementsStore.fetchMovements(
            props.folderId,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar as movimentações. Tente novamente.'
    }
}

onMounted(
    loadMovements,
)
</script>

<style scoped>
.folder-movements {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-movements__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-movements__title {
    margin: 0;
    color: var(--color-text);
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
}

.folder-movements__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-movements__form {
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

.folder-movements__form-grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-4) var(--space-5);
}

.folder-movements__field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.folder-movements__field--full {
    grid-column:
        1 / -1;
}

.folder-movements__label {
    color:
        var(--color-text);

    font-size:
        var(--font-size-sm);

    font-weight:
        var(--font-weight-semibold);
}

.folder-movements__input,
.folder-movements__textarea {
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

.folder-movements__input {
    min-height:
        2.75rem;

    padding:
        var(--space-2) var(--space-3);
}

.folder-movements__textarea {
    min-height:
        7rem;

    padding:
        var(--space-3);

    resize:
        vertical;
}

.folder-movements__form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);
}

.folder-movements__timeline {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.folder-movements__item {
    position: relative;

    display: grid;

    grid-template-columns:
        auto 1fr;

    gap: var(--space-4);
}

.folder-movements__marker {
    width: 0.75rem;
    height: 0.75rem;
    margin-top: 0.4rem;

    border-radius: 999px;

    background:
        var(--color-primary);
}

.folder-movements__content {
    min-width: 0;
    padding-bottom: var(--space-3);
}

.folder-movements__item-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-movements__item-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-md);

    font-weight:
        var(--font-weight-semibold);
}

.folder-movements__title-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-2);
}

.folder-movements__source,
.folder-movements__code {
    display: inline-flex;
    align-items: center;
    min-height: 1.5rem;
    padding: 0 var(--space-2);
    border-radius: var(--radius-pill);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
}

.folder-movements__source {
    background: var(--color-surface-secondary-soft);
    color: var(--color-brand-secondary-active);
}

.folder-movements__code {
    background: var(--color-surface-muted);
    color: var(--color-text-muted);
}

.folder-movements__meta {
    margin:
        var(--space-1) 0 0;

    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-movements__item-description {
    margin:
        var(--space-3) 0 0;

    color:
        var(--color-text);

    line-height: 1.5;
}

.folder-movements__empty {
    padding:
        var(--space-6);

    border:
        1px dashed var(--color-divider);

    border-radius:
        var(--radius-md);

    color:
        var(--color-text-muted);

    text-align:
        center;
}

.folder-movements__error {
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
    .folder-movements__header {
        flex-direction:
            column;
    }

    .folder-movements__form-grid {
        grid-template-columns:
            1fr;
    }

    .folder-movements__field--full {
        grid-column:
            auto;
    }

    .folder-movements__form-actions {
        flex-wrap:
            wrap;
    }

    .folder-movements__item-header {
        flex-direction:
            column;
    }
}
</style>
