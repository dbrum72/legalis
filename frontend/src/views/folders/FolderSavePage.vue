<template>
    <PageContainer>
        <div class="folder-save-page">
            <header class="folder-save-page__header">
                <div>
                    <h1 class="folder-save-page__title">
                        {{ pageTitle }}
                    </h1>

                    <p class="folder-save-page__description">
                        {{ pageDescription }}
                    </p>
                </div>
            </header>

            <AppCard>
                <form class="folder-save-page__form" novalidate @submit.prevent="handleSubmit">
                    <AppInput v-model="form.name" name="name" label="Nome" :error="errors.name" :disabled="submitting"
                        :maxlength="100" required autofocus />

                    <AppInput v-model="form.process_number" name="process_number" label="Número do processo"
                        :error="errors.process_number" :disabled="submitting" :maxlength="25" />

                    <AppSwitch v-model="form.datajud_monitoring_enabled" id="folder-datajud-monitoring"
                        label="Monitoramento diário pelo DataJud"
                        hint="Busca diariamente metadados e novas movimentações públicas deste processo."
                        :disabled="submitting || !form.process_number.trim()" />

                    <div v-if="submitError" class="folder-save-page__error" role="alert">
                        {{ submitError }}
                    </div>

                    <footer class="folder-save-page__actions">
                        <AppButton type="button" variant="ghost" :disabled="submitting" @click="goBack">
                            Cancelar
                        </AppButton>

                        <AppButton type="submit" variant="primary" :loading="submitting" :disabled="submitting">
                            {{ submitLabel }}
                        </AppButton>
                    </footer>
                </form>
            </AppCard>

            <AppCard v-if="isEditing">
                <FolderClients :folder-id="folderId" />
            </AppCard>
        </div>
    </PageContainer>
</template>

<script setup>
import {
    computed,
    reactive,
    ref,
    watch,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'

import {
    AppInput,
    AppSwitch,
} from '@/components/forms'

import {
    AppButton,
    AppCard,
} from '@/components/ui'

import { useAuthStore } from '@/stores/auth.js'
import { useFoldersStore } from '@/stores/folders.js'

import {
    applyValidationErrors,
    clearValidationErrors,
} from '@/utils/validationErrors'

import FolderClients from '@/views/folders/components/FolderClients.vue'

const route = useRoute()
const router = useRouter()

const authStore = useAuthStore()
const foldersStore = useFoldersStore()

const submitting = ref(false)
const loadingFolder = ref(false)
const submitError = ref('')

const form = reactive({
    name: '',
    process_number: '',
    datajud_monitoring_enabled: false,
})

const errors = reactive({
    name: '',
    process_number: '',
})

const isEditing = computed(() =>
    route.name === 'folders.edit',
)

const folderId = computed(() =>
    isEditing.value
        ? Number(route.params.id)
        : null,
)

const pageTitle = computed(() =>
    isEditing.value
        ? 'Editar pasta'
        : 'Nova pasta',
)

const pageDescription = computed(() =>
    isEditing.value
        ? 'Atualize os dados da pasta jurídica.'
        : 'Informe os dados para cadastrar uma nova pasta jurídica.',
)

const submitLabel = computed(() =>
    isEditing.value
        ? 'Salvar alterações'
        : 'Cadastrar pasta',
)

function clearErrors() {
    clearValidationErrors(
        errors,
    )
    submitError.value = ''
}

function clearForm() {
    form.name = ''
    form.process_number = ''
    form.datajud_monitoring_enabled = false
}

function applyFolder(folder) {
    form.name =
        folder?.name ?? ''

    form.process_number =
        folder?.process_number ?? ''

    form.datajud_monitoring_enabled =
        Boolean(folder?.datajud_monitoring_enabled)
}

function buildPayload() {
    return {
        name:
            form.name.trim(),

        process_number:
            form.process_number.trim() ||
            null,

        datajud_monitoring_enabled:
            Boolean(
                form.process_number.trim()
                && form.datajud_monitoring_enabled
            ),
    }
}

async function navigateAfterCreate(
    createdFolder,
) {
    if (
        createdFolder?.id &&
        authStore.hasPermission(
            'folders.update',
        )
    ) {
        await router.replace({
            name: 'folders.edit',

            params: {
                id: createdFolder.id,
            },
        })

        return
    }

    await router.replace({
        name: 'folders',
    })
}

async function handleSubmit() {
    if (submitting.value) {
        return
    }

    clearErrors()

    if (!form.name.trim()) {
        errors.name =
            'Informe o nome.'
    }

    if (errors.name) {
        return
    }

    submitting.value = true

    try {
        const payload =
            buildPayload()

        if (isEditing.value) {
            await foldersStore.update(
                folderId.value,
                payload,
            )

            await router.replace({
                name: 'folders',
            })

            return
        }

        const createdFolder =
            await foldersStore.create(
                payload,
            )

        await navigateAfterCreate(
            createdFolder,
        )
    } catch (error) {
        const validationErrors = error.response?.data?.errors ?? {}

        if (
            error.response?.status === 422
        ) {
            applyValidationErrors(
                errors,
                validationErrors,
            )

            return
        }

        submitError.value =
            'Não foi possível salvar a pasta. Tente novamente.'
    } finally {
        submitting.value = false
    }
}

function goBack() {
    router.push({
        name: 'folders',
    })
}

async function loadFolder() {
    clearErrors()

    if (!isEditing.value) {
        foldersStore.clearCurrent()
        clearForm()

        return
    }

    const id = folderId.value

    if (!id) {
        return
    }

    /*
     * O objeto presente na listagem é utilizado apenas
     * para preencher imediatamente os campos básicos.
     *
     * O detalhe completo é sempre carregado em seguida,
     * pois contém relacionamentos como folder_clients.
     */
    const cached =
        foldersStore.getById(id)

    if (cached) {
        applyFolder(cached)
    }

    loadingFolder.value = true

    try {
        const folder =
            await foldersStore.fetchFolder(
                id,
            )

        applyFolder(folder)
    } catch {
        submitError.value =
            'Não foi possível carregar a pasta. Tente novamente.'
    } finally {
        loadingFolder.value = false
    }
}

/*
 * Não usamos apenas onMounted porque folders.create e
 * folders.edit utilizam o mesmo componente.
 *
 * Ao criar uma pasta e navegar para folders.edit, o Vue
 * Router reutiliza esta instância. O watch garante que o
 * detalhe completo seja carregado também nessa transição.
 */
watch(
    () => [
        route.name,
        route.params.id,
    ],

    async () => {
        await loadFolder()
    },

    {
        immediate: true,
    },
)
</script>

<style scoped>
.folder-save-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.folder-save-page__header {
    display: flex;
    justify-content: space-between;
    gap: var(--space-4);
}

.folder-save-page__title {
    margin: 0;

    color:
        var(--color-text);
}

.folder-save-page__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.folder-save-page__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.folder-save-page__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);

    padding-top:
        var(--space-2);

    border-top:
        1px solid var(--color-divider);
}

.folder-save-page__error {
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
    .folder-save-page__actions {
        flex-direction:
            column-reverse;
    }
}
</style>
