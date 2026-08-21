<template>
    <AuthShell>
        <section class="register-page" aria-labelledby="register-title">
            <header class="register-page__header">
                <span class="register-page__eyebrow">
                    Comece agora
                </span>

                <h1 id="register-title" class="register-page__title">
                    Crie sua conta
                </h1>

                <p class="register-page__description">
                    Cadastre seu escritório e comece a organizar
                    sua operação jurídica.
                </p>
            </header>

            <AppCard class="register-page__card" as="section">
                <form class="register-page__form" novalidate @submit.prevent="handleSubmit">
                    <div class="register-page__field">
                        <label for="register-name" class="register-page__label">
                            Nome completo
                        </label>

                        <input id="register-name" v-model="form.name" class="register-page__input" :class="{
                            'register-page__input--error':
                                errors.name,
                        }" type="text" name="name" autocomplete="name" placeholder="Seu nome" :disabled="loading"
                            autofocus />

                        <span v-if="errors.name" class="register-page__field-error">
                            {{ errors.name }}
                        </span>
                    </div>

                    <div class="register-page__field">
                        <label for="register-organization-name" class="register-page__label">
                            Nome do escritório
                        </label>

                        <input id="register-organization-name" v-model="form.organizationName"
                            class="register-page__input" :class="{
                                'register-page__input--error':
                                    errors.organizationName,
                            }" type="text" name="organization_name" autocomplete="organization"
                            placeholder="Ex.: Silva Advocacia" :disabled="loading" />

                        <span v-if="errors.organizationName" class="register-page__field-error">
                            {{ errors.organizationName }}
                        </span>
                    </div>

                    <AppEmail id="register-email" v-model="form.email" name="email" label="E-mail profissional"
                        placeholder="seu@email.com" autocomplete="email" :disabled="loading" :error="errors.email"
                        required />

                    <AppPassword id="register-password" v-model="form.password" name="password" label="Senha"
                        autocomplete="new-password" :disabled="loading" :error="errors.password" required />

                    <AppPassword id="register-password-confirmation" v-model="form.passwordConfirmation"
                        name="password_confirmation" label="Confirmar senha" autocomplete="new-password"
                        :disabled="loading" :error="errors.passwordConfirmation" required />

                    <p class="register-page__password-hint">
                        Use pelo menos 8 caracteres.
                    </p>

                    <div v-if="registerError" class="register-page__error" role="alert">
                        {{ registerError }}
                    </div>

                    <AppButton type="submit" variant="primary" size="lg" :loading="loading" :disabled="loading" block>
                        Criar minha conta
                    </AppButton>
                </form>
            </AppCard>

            <footer class="register-page__login">
                <span>
                    Já possui uma conta?
                </span>

                <RouterLink :to="{ name: 'login' }" data-testid="register-login-link">
                    Entrar
                </RouterLink>
            </footer>
        </section>
    </AuthShell>
</template>

<script setup>
import {
    reactive,
    ref,
} from 'vue'

import {
    useRouter,
} from 'vue-router'

import {
    AppEmail,
    AppPassword,
} from '@/components/forms'

import AuthShell
    from '@/components/public/AuthShell.vue'

import {
    AppButton,
    AppCard
} from '@/components/ui'

import {
    useAuthStore,
} from '@/stores/auth.js'

const router =
    useRouter()

const authStore =
    useAuthStore()

const loading =
    ref(false)

const registerError =
    ref('')

const form =
    reactive({
        name: '',

        organizationName: '',

        email: '',

        password: '',

        passwordConfirmation: '',
    })

const errors =
    reactive({
        name: '',

        organizationName: '',

        email: '',

        password: '',

        passwordConfirmation: '',
    })

function clearErrors() {
    errors.name = ''

    errors.organizationName = ''

    errors.email = ''

    errors.password = ''

    errors.passwordConfirmation = ''

    registerError.value = ''
}

function validate() {
    clearErrors()

    if (
        !form.name.trim()
    ) {
        errors.name =
            'Informe seu nome.'
    }

    if (
        !form.organizationName.trim()
    ) {
        errors.organizationName =
            'Informe o nome do escritório.'
    }

    if (
        !form.email.trim()
    ) {
        errors.email =
            'Informe seu e-mail.'
    }

    if (
        !form.password
    ) {
        errors.password =
            'Informe uma senha.'
    }

    if (
        !form.passwordConfirmation
    ) {
        errors.passwordConfirmation =
            'Confirme sua senha.'
    }

    if (
        form.password &&
        form.passwordConfirmation &&
        form.password !==
        form.passwordConfirmation
    ) {
        errors.passwordConfirmation =
            'As senhas não coincidem.'
    }

    return (
        !errors.name &&
        !errors.organizationName &&
        !errors.email &&
        !errors.password &&
        !errors.passwordConfirmation
    )
}

function applyValidationErrors(
    validationErrors,
) {
    errors.name =
        validationErrors
            .name?.[0] ??
        ''

    errors.organizationName =
        validationErrors
            .organization_name?.[0] ??
        ''

    errors.email =
        validationErrors
            .email?.[0] ??
        ''

    errors.password =
        validationErrors
            .password?.[0] ??
        ''

    errors.passwordConfirmation =
        validationErrors
            .password_confirmation?.[0] ??
        ''
}

async function handleSubmit() {
    if (
        loading.value ||
        !validate()
    ) {
        return
    }

    loading.value = true

    try {
        await authStore.register({
            name:
                form.name.trim(),

            organization_name:
                form.organizationName.trim(),

            email:
                form.email.trim(),

            password:
                form.password,

            password_confirmation:
                form.passwordConfirmation,
        })

        await router.replace({
            name:
                'dashboard',
        })
    } catch (error) {
        const status =
            error.response?.status

        if (
            status === 422
        ) {
            const validationErrors =
                error.response?.data
                    ?.errors ??
                {}

            applyValidationErrors(
                validationErrors
            )

            if (
                !errors.name &&
                !errors.organizationName &&
                !errors.email &&
                !errors.password &&
                !errors.passwordConfirmation
            ) {
                registerError.value =
                    'Verifique os dados informados.'
            }

            return
        }

        registerError.value =
            'Não foi possível criar sua conta. Tente novamente.'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped>
.register-page {
    width: 100%;
}

.register-page__header {
    margin-bottom: var(--space-6);
}

.register-page__eyebrow {
    display: inline-block;
    margin-bottom: var(--space-3);
    color: var(--color-primary);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.register-page__title {
    margin: 0;
    color: var(--color-text);
    font-size: clamp(var(--font-size-2xl),
            5vw,
            var(--font-size-3xl));
    font-weight: var(--font-weight-semibold);
    line-height: 1.15;
}

.register-page__description {
    margin:
        var(--space-2) 0 0;
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
    line-height: 1.6;
}

.register-page__card {
    width: 100%;
    box-shadow: var(--shadow-md);
}

.register-page__form {
    display: grid;
    gap: var(--space-5);
}

.register-page__field {
    display: grid;
    gap: var(--space-2);
}

.register-page__label {
    color: var(--color-text);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
}

.register-page__input {
    width: 100%;
    min-height: 2.75rem;
    padding:
        var(--space-3) var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
    color: var(--color-text);
    font: inherit;
    outline: none;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}

.register-page__input:focus {
    border-color: var(--color-primary);
    box-shadow:
        0 0 0 3px color-mix(in srgb,
            var(--color-primary) 15%,
            transparent);
}

.register-page__input:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

.register-page__input--error {
    border-color: var(--color-danger);
}

.register-page__field-error {
    color: var(--color-danger);
    font-size: var(--font-size-xs);
}

.register-page__password-hint {
    margin:
        calc(var(--space-2) * -1) 0 0;
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}

.register-page__error {
    padding:
        var(--space-3) var(--space-4);
    border: 1px solid var(--color-danger);
    border-radius: var(--radius-md);
    background: var(--color-danger-soft);
    color: var(--color-danger);
    font-size: var(--font-size-sm);
}

.register-page__login {
    display: flex;
    justify-content: center;
    gap: var(--space-2);
    margin-top: var(--space-6);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}

.register-page__login a {
    color: var(--color-primary);
    font-weight: var(--font-weight-semibold);
    text-decoration: none;
}

.register-page__login a:hover {
    text-decoration: underline;
}

@media (max-width: 30rem) {
    .register-page__header {
        margin-bottom: var(--space-5);
    }

    .register-page__login {
        flex-wrap: wrap;
        text-align: center;
    }
}
</style>