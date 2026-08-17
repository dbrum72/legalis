<template>
    <PageContainer>
        <div class="client-show-page">
            <header class="client-show-page__header">
                <div class="client-show-page__heading">
                    <h1 class="client-show-page__title">
                        Detalhes do cliente
                    </h1>

                    <p class="client-show-page__description">
                        Consulte os dados cadastrais do cliente.
                    </p>
                </div>

                <div class="client-show-page__header-actions">
                    <AppButton type="button" variant="ghost" @click="goBack">
                        Voltar
                    </AppButton>

                    <AppButton v-if="canUpdate" type="button" variant="primary" @click="goToEdit">
                        Editar
                    </AppButton>
                </div>
            </header>

            <div v-if="loadError" class="client-show-page__error" role="alert">
                {{ loadError }}
            </div>

            <template v-if="client">
                <AppCard>
                    <section class="client-show-page__section" aria-labelledby="client-identification-title">
                        <h2 id="client-identification-title" class="client-show-page__section-title">
                            Identificação
                        </h2>

                        <dl class="client-show-page__grid">
                            <div class="client-show-page__field client-show-page__field--full">
                                <dt class="client-show-page__label">
                                    Nome
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ displayValue(client.name) }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    CPF/CNPJ
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ displayValue(client.document) }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    RG / Documento de identidade
                                </dt>

                                <dd class="client-show-page__value">
                                    {{
                                        displayValue(
                                            client.identity_document,
                                        )
                                    }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    Órgão expedidor
                                </dt>

                                <dd class="client-show-page__value">
                                    {{
                                        displayValue(
                                            client.identity_issuer,
                                        )
                                    }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    Estado civil
                                </dt>

                                <dd class="client-show-page__value">
                                    {{
                                        displayValue(
                                            client
                                                .marital_status
                                                ?.name,
                                        )
                                    }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    Profissão
                                </dt>

                                <dd class="client-show-page__value">
                                    {{
                                        displayValue(
                                            client.profession,
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </AppCard>

                <AppCard>
                    <section class="client-show-page__section" aria-labelledby="client-contact-title">
                        <h2 id="client-contact-title" class="client-show-page__section-title">
                            Contato
                        </h2>

                        <dl class="client-show-page__grid">
                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    Telefone
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ displayValue(client.phone) }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    E-mail
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ displayValue(client.email) }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    WhatsApp
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ whatsappLabel }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </AppCard>

                <AppCard>
                    <section class="client-show-page__section" aria-labelledby="client-address-title">
                        <h2 id="client-address-title" class="client-show-page__section-title">
                            Endereço
                        </h2>

                        <dl class="client-show-page__grid">
                            <div class="client-show-page__field client-show-page__field--full">
                                <dt class="client-show-page__label">
                                    Endereço
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ displayValue(client.address) }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    Complemento
                                </dt>

                                <dd class="client-show-page__value">
                                    {{
                                        displayValue(
                                            client.address_complement,
                                        )
                                    }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    Bairro
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ displayValue(client.district) }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    Cidade
                                </dt>

                                <dd class="client-show-page__value">
                                    {{ displayValue(client.city) }}
                                </dd>
                            </div>

                            <div class="client-show-page__field">
                                <dt class="client-show-page__label">
                                    CEP
                                </dt>

                                <dd class="client-show-page__value">
                                    {{
                                        displayValue(
                                            client.postal_code,
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>
                    </section>
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
} from '@/components/ui'

import { useAuthStore } from '@/stores/auth.js'
import { useClientsStore } from '@/stores/clients.js'

const route =
    useRoute()

const router =
    useRouter()

const authStore =
    useAuthStore()

const clientsStore =
    useClientsStore()

const loadError =
    ref('')

const clientId =
    computed(() =>
        Number(
            route.params.id,
        ),
    )

const client =
    computed(() =>
        clientsStore.client,
    )

const canUpdate =
    computed(() =>
        authStore.hasPermission(
            'clients.update',
        ),
    )

const whatsappLabel =
    computed(() =>
        client.value?.whatsapp
            ? 'Sim'
            : 'Não',
    )

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
        name: 'clients',
    })
}

function goToEdit() {
    if (!client.value?.id) {
        return
    }

    return router.push({
        name: 'clients.edit',

        params: {
            id:
                client.value.id,
        },
    })
}

async function loadClient() {
    loadError.value =
        ''

    try {
        await clientsStore.fetchClient(
            clientId.value,
        )
    } catch {
        loadError.value =
            'Não foi possível carregar os dados do cliente.'
    }
}

onMounted(
    loadClient,
)
</script>

<style scoped>
.client-show-page {
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
}

.client-show-page__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
}

.client-show-page__heading {
    min-width: 0;
}

.client-show-page__title {
    margin: 0;

    color:
        var(--color-text);
}

.client-show-page__description {
    margin:
        var(--space-2) 0 0;

    color:
        var(--color-text-muted);
}

.client-show-page__header-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--space-2);
}

.client-show-page__section {
    display: flex;
    flex-direction: column;
    gap: var(--space-5);
}

.client-show-page__section-title {
    margin: 0;

    color:
        var(--color-text);

    font-size:
        1.125rem;

    font-weight:
        700;
}

.client-show-page__grid {
    display: grid;

    grid-template-columns:
        repeat(2,
            minmax(0, 1fr));

    gap:
        var(--space-5) var(--space-6);

    margin: 0;
}

.client-show-page__field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: var(--space-1);
}

.client-show-page__field--full {
    grid-column:
        1 / -1;
}

.client-show-page__label {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);

    font-weight:
        600;
}

.client-show-page__value {
    min-height: 1.5rem;
    margin: 0;

    overflow-wrap:
        anywhere;

    color:
        var(--color-text);
}

.client-show-page__error {
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
    .client-show-page__header {
        flex-direction:
            column;
    }

    .client-show-page__header-actions {
        width:
            100%;

        justify-content:
            flex-start;
    }

    .client-show-page__grid {
        grid-template-columns:
            1fr;
    }

    .client-show-page__field--full {
        grid-column:
            auto;
    }
}
</style>