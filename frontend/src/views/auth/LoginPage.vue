<template>
    <main class="login-page">
        <section class="login-page__container" aria-labelledby="login-title">
            <header class="login-page__brand">
                <AppLogo :to="{ name: 'login' }" aria-label="Legalis" />

                <p class="login-page__brand-description">
                    Gestão jurídica simples, organizada e segura.
                </p>
            </header>

            <AppCard class="login-page__card" as="section">
                <header class="login-page__header">
                    <h1 id="login-title" class="login-page__title">
                        Acesse sua conta
                    </h1>

                    <p class="login-page__description">
                        Informe suas credenciais para continuar.
                    </p>
                </header>

                <form class="login-page__form" novalidate @submit.prevent="handleSubmit">
                    <AppEmail v-model="form.email" id="login-email" name="email" label="E-mail"
                        placeholder="seu@email.com" :disabled="loading" :error="errors.email" required autofocus />

                    <AppPassword v-model="form.password" id="login-password" name="password" label="Senha"
                        autocomplete="current-password" :disabled="loading" :error="errors.password" required />

                    <div v-if="authError" class="login-page__error" role="alert">
                        {{ authError }}
                    </div>

                    <AppButton type="submit" variant="primary" size="lg" :loading="loading" :disabled="loading" block>
                        Entrar
                    </AppButton>
                </form>
            </AppCard>

            <footer class="login-page__footer">
                <span>Legalis</span>
                <span aria-hidden="true">·</span>
                <span>Gestão jurídica</span>
            </footer>
        </section>
    </main>
</template>

<script setup>
import {
    reactive,
    ref,
} from 'vue'

import {
    useRoute,
    useRouter,
} from 'vue-router'

import {
    AppEmail,
    AppPassword,
} from '@/components/forms'

import {
    AppButton,
    AppCard,
    AppLogo,
} from '@/components/ui'

import {
    useAuthStore,
} from '@/stores/auth.js'

const router =
    useRouter()

const route =
    useRoute()

const authStore =
    useAuthStore()

const loading =
    ref(false)

const authError =
    ref('')

const form = reactive({
    email: '',
    password: '',
})

const errors = reactive({
    email: '',
    password: '',
})

function clearErrors() {
    errors.email = ''
    errors.password = ''
    authError.value = ''
}

function validate() {
    clearErrors()

    if (!form.email.trim()) {
        errors.email =
            'Informe seu e-mail.'
    }

    if (!form.password) {
        errors.password =
            'Informe sua senha.'
    }

    return (
        !errors.email &&
        !errors.password
    )
}

function resolveRedirect() {
    const redirect =
        route.query.redirect

    if (
        typeof redirect ===
        'string' &&
        redirect.startsWith('/') &&
        !redirect.startsWith('//')
    ) {
        return redirect
    }

    return {
        name: 'dashboard',
    }
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
        await authStore.login({
            email:
                form.email.trim(),

            password:
                form.password,
        })

        if (
            !authStore.contextLoaded
        ) {
            await router.replace({
                name:
                    'organizations.select',

                query:
                    typeof route.query
                        .redirect ===
                        'string'
                        ? {
                            redirect:
                                route
                                    .query
                                    .redirect,
                        }
                        : {},
            })

            return
        }

        await router.replace(
            resolveRedirect(),
        )
    } catch (error) {
        const status =
            error.response?.status

        if (status === 422) {
            const validationErrors =
                error.response?.data
                    ?.errors ??
                {}

            errors.email =
                validationErrors
                    .email?.[0] ??
                ''

            errors.password =
                validationErrors
                    .password?.[0] ??
                ''

            if (
                !errors.email &&
                !errors.password
            ) {
                authError.value =
                    'Verifique os dados informados.'
            }

            return
        }

        if (
            status === 401 ||
            status === 403
        ) {
            authError.value =
                error.response?.data
                    ?.msg ??
                'E-mail ou senha inválidos.'

            return
        }

        authError.value =
            'Não foi possível acessar sua conta. Tente novamente.'
    } finally {
        loading.value = false
    }
}
</script>

<style src="./style.css"></style>