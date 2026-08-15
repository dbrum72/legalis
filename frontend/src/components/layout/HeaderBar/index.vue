<template>
    <header class="app-header app-header-bar">
        <div class="app-header-bar__start">
            <AppBreadcrumb />
        </div>

        <div class="app-header-bar__end">
            <div v-if="authStore.organization" class="app-header-bar__organization">
                <AppSelect v-if="authStore.hasMultipleOrganizations" :model-value="authStore.currentTenant"
                    name="organization" label="Escritório" :options="organizationOptions" option-label="label"
                    option-value="value" :disabled="switchingOrganization || loggingOut"
                    @update:model-value="handleOrganizationChange" />

                <div v-else class="app-header-bar__organization-current">
                    <span class="app-header-bar__organization-label">
                        Escritório
                    </span>

                    <strong class="app-header-bar__organization-name">
                        {{ authStore.organization.name }}
                    </strong>
                </div>
            </div>

            <div v-if="authStore.user" class="app-header-bar__user">
                <span class="app-header-bar__user-name">
                    {{ authStore.userName }}
                </span>

                <AppButton variant="ghost" size="sm" :loading="loggingOut"
                    :disabled="loggingOut || switchingOrganization" aria-label="Sair da aplicação"
                    @click="handleLogout">
                    Sair
                </AppButton>
            </div>
        </div>
    </header>
</template>

<script setup>
import {
    computed,
    ref,
} from 'vue'

import {
    useRouter,
} from 'vue-router'

import {
    AppSelect,
} from '@/components/forms'

import {
    AppBreadcrumb,
} from '@/components/navigation'

import {
    AppButton,
} from '@/components/ui'

import {
    useAuthStore,
} from '@/stores/auth.js'

const router =
    useRouter()

const authStore =
    useAuthStore()

const loggingOut =
    ref(false)

const switchingOrganization =
    ref(false)

const organizationOptions =
    computed(
        () =>
            authStore.organizations.map(
                (organization) => ({
                    label:
                        organization.name,

                    value:
                        organization.slug,
                }),
            ),
    )

async function handleOrganizationChange(
    tenant,
) {
    if (
        switchingOrganization.value ||
        !tenant ||
        tenant ===
        authStore.currentTenant
    ) {
        return
    }

    switchingOrganization.value =
        true

    try {
        await authStore.selectOrganization(
            tenant,
        )

        await router.replace({
            name: 'dashboard',
        })
    } catch {
        await router.replace({
            name:
                'organizations.select',
        })
    } finally {
        switchingOrganization.value =
            false
    }
}

async function handleLogout() {
    if (loggingOut.value) {
        return
    }

    loggingOut.value = true

    try {
        await authStore.logout()
    } finally {
        loggingOut.value = false

        await router.replace({
            name: 'login',
        })
    }
}
</script>

<style scoped>
.app-header-bar__end {
    display: flex;
    align-items: center;

    gap: var(--space-4);
}

.app-header-bar__organization {
    min-width: 220px;
}

.app-header-bar__organization-current {
    display: flex;
    flex-direction: column;

    gap: var(--space-1);
}

.app-header-bar__organization-label {
    color:
        var(--color-text-muted);

    font-size:
        var(--font-size-sm);
}

.app-header-bar__organization-name {
    max-width: 240px;

    overflow: hidden;

    color:
        var(--color-text);

    text-overflow: ellipsis;
    white-space: nowrap;
}

.app-header-bar__user {
    display: flex;
    align-items: center;

    gap: var(--space-3);
}

@media (max-width: 760px) {
    .app-header-bar__end {
        gap:
            var(--space-2);
    }

    .app-header-bar__organization {
        min-width: 160px;
    }

    .app-header-bar__organization-current {
        display: none;
    }

    .app-header-bar__user-name {
        display: none;
    }
}
</style>