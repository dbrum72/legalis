<template>
    <PageContainer>
        <div class="client-form-page">
            <header class="client-form-page__header">
                <div>
                    <h1 class="client-form-page__title">
                        {{ pageTitle }}
                    </h1>

                    <p class="client-form-page__description">
                        {{ pageDescription }}
                    </p>
                </div>
            </header>

            <AppCard>
                <form class="client-form-page__form" novalidate @submit.prevent="handleSubmit">
                    <section class="client-form-page__section">
                        <h2 class="client-form-page__section-title">
                            Identificação
                        </h2>

                        <div class="client-form-page__grid">
                            <div class="client-form-page__field-full">
                                <AppInput v-model="form.name" name="name" label="Nome" :error="errors.name"
                                    :disabled="submitting" required autofocus />
                            </div>

                            <AppInput v-model="form.document" name="document" label="CPF/CNPJ" :error="errors.document"
                                :disabled="submitting" :maxlength="14" required />

                            <AppInput v-model="form.identity_document" name="identity_document"
                                label="RG / Documento de identidade" :error="errors.identity_document"
                                :disabled="submitting" :maxlength="20" />

                            <AppInput v-model="form.identity_issuer" name="identity_issuer" label="Órgão expedidor"
                                :error="errors.identity_issuer" :disabled="submitting" :maxlength="30" />

                            <AppSelect v-model="form.marital_status_id" name="marital_status_id" label="Estado civil"
                                :options="maritalStatusesStore.options" :error="errors.marital_status_id"
                                :disabled="submitting" placeholder="Selecione" />

                            <AppInput v-model="form.profession" name="profession" label="Profissão"
                                :error="errors.profession" :disabled="submitting" :maxlength="100" />
                        </div>
                    </section>

                    <section class="client-form-page__section">
                        <h2 class="client-form-page__section-title">
                            Contato
                        </h2>

                        <div class="client-form-page__grid">
                            <AppPhone v-model="form.phone" name="phone" label="Telefone" :error="errors.phone"
                                :disabled="submitting" />

                            <AppEmail v-model="form.email" name="email" label="E-mail" :error="errors.email"
                                :disabled="submitting" />

                            <div class="client-form-page__field-full">
                                <AppCheckbox v-model="form.whatsapp" name="whatsapp"
                                    label="Este telefone possui WhatsApp" :disabled="submitting" />
                            </div>
                        </div>
                    </section>

                    <section class="client-form-page__section">
                        <h2 class="client-form-page__section-title">
                            Endereço
                        </h2>

                        <div class="client-form-page__grid">
                            <div class="client-form-page__field-full">
                                <AppInput v-model="form.address" name="address" label="Endereço" :error="errors.address"
                                    :disabled="submitting" :maxlength="150" />
                            </div>

                            <AppInput v-model="form.address_complement" name="address_complement" label="Complemento"
                                :error="errors.address_complement" :disabled="submitting" :maxlength="100" />

                            <AppInput v-model="form.district" name="district" label="Bairro" :error="errors.district"
                                :disabled="submitting" :maxlength="100" />

                            <AppInput v-model="form.city" name="city" label="Cidade" :error="errors.city"
                                :disabled="submitting" :maxlength="100" />

                            <AppInput v-model="form.postal_code" name="postal_code" label="CEP"
                                :error="errors.postal_code" :disabled="submitting" :maxlength="8" inputmode="numeric" />
                        </div>
                    </section>

                    <div v-if="submitError" class="client-form-page__error" role="alert">
                        {{ submitError }}
                    </div>

                    <footer class="client-form-page__actions">
                        <AppButton type="button" variant="ghost" :disabled="submitting" @click="goBack">
                            Cancelar
                        </AppButton>

                        <AppButton type="submit" variant="primary" :loading="submitting" :disabled="submitting">
                            {{ submitLabel }}
                        </AppButton>
                    </footer>
                </form>
            </AppCard>
        </div>
    </PageContainer>
</template>

<script setup>
import {
    computed,
    onMounted,
    reactive,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import PageContainer from '@/components/layout/PageContainer/index.vue'

import {
    AppCheckbox,
    AppEmail,
    AppInput,
    AppPhone,
    AppSelect,
} from '@/components/forms'

import {
    AppButton,
    AppCard,
} from '@/components/ui'

import { useClientsStore } from '@/stores/clients.js'

import {
    useMaritalStatusesStore,
} from '@/stores/marital-statuses.js'

const route =
    useRoute()

const router =
    useRouter()

const clientsStore =
    useClientsStore()

const maritalStatusesStore =
    useMaritalStatusesStore()

const submitting =
    ref(false)

const submitError =
    ref('')

const form =
    reactive({
        name: '',
        document: '',
        identity_document: '',
        identity_issuer: '',
        marital_status_id: '',
        profession: '',
        address: '',
        address_complement: '',
        district: '',
        city: '',
        postal_code: '',
        phone: '',
        whatsapp: false,
        email: '',
    })

const errors =
    reactive({
        name: '',
        document: '',
        identity_document: '',
        identity_issuer: '',
        marital_status_id: '',
        profession: '',
        address: '',
        address_complement: '',
        district: '',
        city: '',
        postal_code: '',
        phone: '',
        whatsapp: '',
        email: '',
    })

const isEditing =
    computed(() =>
        route.name ===
        'clients.edit',
    )

const clientId =
    computed(() =>
        isEditing.value
            ? Number(
                route.params.id,
            )
            : null,
    )

const pageTitle =
    computed(() =>
        isEditing.value
            ? 'Editar cliente'
            : 'Novo cliente',
    )

const pageDescription =
    computed(() =>
        isEditing.value
            ? 'Atualize os dados cadastrais do cliente.'
            : 'Informe os dados para cadastrar um novo cliente.',
    )

const submitLabel =
    computed(() =>
        isEditing.value
            ? 'Salvar alterações'
            : 'Cadastrar cliente',
    )

function clearErrors() {
    Object
        .keys(errors)
        .forEach(
            (key) => {
                errors[key] =
                    ''
            },
        )

    submitError.value =
        ''
}

function applyClient(client) {
    form.name =
        client?.name ??
        ''

    form.document =
        client?.document ??
        ''

    form.identity_document =
        client?.identity_document ??
        ''

    form.identity_issuer =
        client?.identity_issuer ??
        ''

    form.marital_status_id =
        client?.marital_status_id ??
        ''

    form.profession =
        client?.profession ??
        ''

    form.address =
        client?.address ??
        ''

    form.address_complement =
        client?.address_complement ??
        ''

    form.district =
        client?.district ??
        ''

    form.city =
        client?.city ??
        ''

    form.postal_code =
        client?.postal_code ??
        ''

    form.phone =
        client?.phone ??
        ''

    form.whatsapp =
        Boolean(
            client?.whatsapp,
        )

    form.email =
        client?.email ??
        ''
}

function nullable(value) {
    if (
        value === '' ||
        value === undefined
    ) {
        return null
    }

    return value
}

function buildPayload() {
    return {
        name:
            form.name.trim(),

        document:
            form.document.trim(),

        identity_document:
            nullable(
                form
                    .identity_document
                    .trim(),
            ),

        identity_issuer:
            nullable(
                form
                    .identity_issuer
                    .trim(),
            ),

        marital_status_id:
            nullable(
                form.marital_status_id,
            ),

        profession:
            nullable(
                form
                    .profession
                    .trim(),
            ),

        address:
            nullable(
                form
                    .address
                    .trim(),
            ),

        address_complement:
            nullable(
                form
                    .address_complement
                    .trim(),
            ),

        district:
            nullable(
                form
                    .district
                    .trim(),
            ),

        city:
            nullable(
                form
                    .city
                    .trim(),
            ),

        postal_code:
            nullable(
                form
                    .postal_code
                    .trim(),
            ),

        phone:
            nullable(
                form
                    .phone
                    .trim(),
            ),

        whatsapp:
            Boolean(
                form.whatsapp,
            ),

        email:
            nullable(
                form
                    .email
                    .trim(),
            ),
    }
}

function applyValidationErrors(
    validationErrors = {},
) {
    Object
        .keys(errors)
        .forEach(
            (key) => {
                errors[key] =
                    validationErrors[
                    key
                    ]?.[0] ??
                    ''
            },
        )
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

    if (!form.document.trim()) {
        errors.document =
            'Informe o CPF ou CNPJ.'
    }

    if (
        errors.name ||
        errors.document
    ) {
        return
    }

    submitting.value =
        true

    try {
        const payload =
            buildPayload()

        if (isEditing.value) {
            await clientsStore.update(
                clientId.value,
                payload,
            )
        } else {
            await clientsStore.create(
                payload,
            )
        }

        await router.replace({
            name: 'clients',
        })
    } catch (error) {
        const status =
            error.response?.status

        if (status === 422) {
            applyValidationErrors(
                error.response
                    ?.data
                    ?.errors,
            )

            return
        }

        submitError.value =
            'Não foi possível salvar o cliente. Tente novamente.'
    } finally {
        submitting.value =
            false
    }
}

function goBack() {
    return router.push({
        name: 'clients',
    })
}

async function loadClientForEditing() {
    const currentClient =
        clientsStore.client

    if (
        currentClient &&
        Number(
            currentClient.id,
        ) ===
        clientId.value
    ) {
        applyClient(
            currentClient,
        )

        return
    }

    const loadedClient =
        await clientsStore.fetchClient(
            clientId.value,
        )

    applyClient(
        loadedClient,
    )
}

onMounted(async () => {
    await maritalStatusesStore
        .fetchMaritalStatuses()

    if (!isEditing.value) {
        clientsStore.clearCurrent()

        return
    }

    await loadClientForEditing()
})
</script>

<style scoped>
.client-form-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.client-form-page__header {
    display: flex;
    justify-content: space-between;
    gap: var(--space-4);
}

.client-form-page__title {
    margin: 0;

    color:
        var(--color-text);
}

.client-form-page__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.client-form-page__form {
    display: flex;
    flex-direction: column;
    gap: var(--space-8);
}

.client-form-page__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.client-form-page__section-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        var(--font-size-lg);

    font-weight:
        var(--font-weight-semibold);
}

.client-form-page__grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-5) var(--space-6);
}

.client-form-page__field-full {
    grid-column:
        1 / -1;
}

.client-form-page__actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--space-3);

    padding-top:
        var(--space-2);

    border-top:
        1px solid var(--color-divider);
}

.client-form-page__error {
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
    .client-form-page__grid {
        grid-template-columns:
            1fr;
    }

    .client-form-page__field-full {
        grid-column:
            auto;
    }

    .client-form-page__actions {
        flex-wrap:
            wrap;
    }
}
</style>