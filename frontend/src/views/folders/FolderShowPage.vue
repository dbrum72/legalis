<template>
    <PageContainer>
        <div class="folder-show-page">
            <header class="folder-show-page__header">
                <div class="folder-show-page__heading">
                    <h1 class="folder-show-page__title">
                        Detalhes da pasta
                    </h1>

                    <p class="folder-show-page__description">
                        Consulte os dados da pasta jurídica e suas partes.
                    </p>
                </div>

                <div class="folder-show-page__actions">
                    <AppButton type="button" variant="ghost" @click="goBack">
                        Voltar
                    </AppButton>

                    <AppButton v-if="canUpdate" type="button" variant="primary" @click="goToEdit">
                        Editar
                    </AppButton>
                </div>
            </header>

            <div v-if="loadError" class="folder-show-page__error" role="alert">
                {{ loadError }}
            </div>

            <template v-if="folder">
                <AppCard>
                    <section class="folder-show-page__section" aria-labelledby="folder-general-title">
                        <h2 id="folder-general-title" class="folder-show-page__section-title">
                            Dados gerais
                        </h2>

                        <dl class="folder-show-page__grid">
                            <div class="folder-show-page__field folder-show-page__field--full">
                                <dt class="folder-show-page__label">
                                    Nome
                                </dt>

                                <dd class="folder-show-page__value">
                                    {{ displayValue(folder.name) }}
                                </dd>
                            </div>

                            <div class="folder-show-page__field">
                                <dt class="folder-show-page__label">
                                    Número do processo
                                </dt>

                                <dd class="folder-show-page__value">
                                    {{
                                        displayValue(
                                            folder.process_number,
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </AppCard>

                <AppCard>
                    <section class="folder-show-page__section" aria-labelledby="folder-parts-title">
                        <h2 id="folder-parts-title" class="folder-show-page__section-title">
                            Partes
                        </h2>

                        <AppTable :columns="partsColumns" :rows="foldersStore.folderClients"
                            empty-text="Nenhuma parte vinculada.">
                            <template #cell-client="{ row }">
                                <div class="folder-show-page__client">
                                    <strong>
                                        {{
                                            row.client?.name ??
                                            '—'
                                        }}
                                    </strong>

                                    <span class="folder-show-page__client-document">
                                        {{
                                            row.client?.document ??
                                            '—'
                                        }}
                                    </span>
                                </div>
                            </template>

                            <template #cell-qualification="{ row }">
                                {{
                                    row.qualification?.name ??
                                    '—'
                                }}
                            </template>
                        </AppTable>
                    </section>
                </AppCard>

                <AppCard>
                    <FolderDocuments :folder-id="folder.id" />
                </AppCard>

                <AppCard>
                    <FolderMovements :folder-id="folder.id" />
                </AppCard>

                <AppCard>
                    <FolderDeadlines :folder-id="folder.id" />
                </AppCard>
            </template>
        </div>
    </PageContainer>
</template>

<script setup>
import {
    computed,
    onMounted,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'

import {
    AppButton,
    AppCard,
    AppTable,
} from '@/components/ui'

import FolderDeadlines from '@/views/folders/components/FolderDeadlines.vue'
import FolderDocuments from '@/views/folders/components/FolderDocuments.vue'
import FolderMovements from '@/views/folders/components/FolderMovements.vue'

import { useAuthStore } from '@/stores/auth.js'
import { useFoldersStore } from '@/stores/folders.js'

const route =
    useRoute()

const router =
    useRouter()

const authStore =
    useAuthStore()

const foldersStore =
    useFoldersStore()

const loadError =
    ref('')

const folderId =
    computed(() =>
        Number(
            route.params.id,
        ),
    )

const folder =
    computed(() =>
        foldersStore.folder,
    )

const canUpdate =
    computed(() =>
        authStore.hasPermission(
            'folders.update',
        ),
    )

const partsColumns = [
    {
        key: 'client',
        label: 'Cliente',
    },

    {
        key: 'qualification',
        label: 'Qualificação',
    },
]

function displayValue(value) {
    if (
        value === null ||
        value === undefined ||
        value === ''
    ) {
        return '—'
    }

    return value
}

function goBack() {
    return router.push({
        name: 'folders',
    })
}

function goToEdit() {
    if (!folder.value?.id) {
        return
    }

    return router.push({
        name: 'folders.edit',

        params: {
            id:
                folder.value.id,
        },
    })
}

async function loadFolder() {
    loadError.value =
        ''

    try {
        await foldersStore.fetchFolder(
            folderId.value,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar a pasta. Tente novamente.'
    }
}

onMounted(
    loadFolder,
)
</script>

<style scoped>
.folder-show-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.folder-show-page__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-show-page__heading {
    min-width: 0;
}

.folder-show-page__title {
    margin: 0;

    color:
        var(--color-text);
}

.folder-show-page__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-show-page__actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}

.folder-show-page__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-show-page__section-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-lg);

    font-weight:
        var(--font-weight-semibold);
}

.folder-show-page__grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-5) var(--space-6);

    margin: 0;
}

.folder-show-page__field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}

.folder-show-page__field--full {
    grid-column:
        1 / -1;
}

.folder-show-page__label {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);

    font-weight:
        600;
}

.folder-show-page__value {
    min-height: 1.5rem;
    margin: 0;

    overflow-wrap:
        anywhere;

    color:
        var(--color-text);
}

.folder-show-page__client {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.folder-show-page__client-document {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.folder-show-page__error {
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

@media (max-width: 760px) {
    .folder-show-page__header {
        flex-direction:
            column;
    }

    .folder-show-page__actions {
        width:
            100%;

        justify-content:
            flex-start;
    }

    .folder-show-page__grid {
        grid-template-columns:
            1fr;
    }

    .folder-show-page__field--full {
        grid-column:
            auto;
    }
}
</style>