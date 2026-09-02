<template>
    <PageContainer>
        <div class="role-permissions">
            <header class="role-permissions__header">
                <div>
                    <h1>Perfis e permissões</h1>
                    <p>Configure os acessos concedidos a cada função da organização.</p>
                </div>

                <div class="role-permissions__actions">
                    <AppButton
                        variant="outline"
                        :disabled="!hasChanges || saving"
                        @click="discardChanges"
                    >
                        Descartar
                    </AppButton>
                    <AppButton
                        variant="highlight"
                        :loading="saving"
                        :disabled="!hasChanges || !editable"
                        @click="saveChanges"
                    >
                        Salvar alterações
                    </AppButton>
                </div>
            </header>

            <div v-if="error" class="role-permissions__error" role="alert">{{ error }}</div>

            <section class="role-permissions__workspace">
                <aside class="role-permissions__roles" aria-label="Funções da organização">
                    <span class="role-permissions__roles-label">Funções</span>
                    <button
                        v-for="role in rolesStore.roles"
                        :key="role.id"
                        type="button"
                        class="role-permissions__role"
                        :class="{ 'role-permissions__role--active': role.id === selectedRoleId }"
                        :disabled="loading || saving"
                        @click="selectRole(role.id)"
                    >
                        <span>{{ roleLabel(role.name) }}</span>
                        <small>{{ rolePermissionCount(role) }} permissões</small>
                    </button>
                </aside>

                <div class="role-permissions__matrix">
                    <header v-if="currentRole" class="role-permissions__role-header">
                        <div>
                            <h2>{{ roleLabel(currentRole.name) }}</h2>
                            <p>{{ currentRole.description }}</p>
                        </div>
                        <span class="role-permissions__badge"
                            >{{ selectedCount }} de {{ availablePermissions.length }}</span
                        >
                    </header>

                    <div v-if="loading" class="role-permissions__state">Carregando permissões…</div>

                    <div v-else-if="currentRole" class="role-permissions__table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Permissão</th>
                                    <th>Identificador técnico</th>
                                    <th>Concedida</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template
                                    v-for="(group, groupIndex) in permissionGroups"
                                    :key="group.key"
                                >
                                    <tr
                                        class="role-permissions__group"
                                        :class="`role-permissions__group--${groupIndex % 5}`"
                                    >
                                        <td colspan="2">{{ group.label }}</td>
                                        <td>
                                            <AppCheckbox
                                                :id="`permission-group-${group.key}`"
                                                class="role-permissions__checkbox"
                                                :model-value="isGroupChecked(group)"
                                                :indeterminate="isGroupIndeterminate(group)"
                                                :disabled="!editable"
                                                :label="`Alternar permissões de ${group.label}`"
                                                @update:model-value="toggleGroup(group, $event)"
                                            />
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="permission in group.permissions"
                                        :key="permission.name"
                                    >
                                        <td>{{ permission.label }}</td>
                                        <td>
                                            <code>{{ permission.name }}</code>
                                        </td>
                                        <td>
                                            <AppCheckbox
                                                :id="`permission-${permission.name.replace('.', '-')}`"
                                                class="role-permissions__checkbox"
                                                :model-value="
                                                    draftPermissions.includes(permission.name)
                                                "
                                                :disabled="!editable"
                                                :label="permission.label"
                                                @update:model-value="
                                                    togglePermission(permission.name, $event)
                                                "
                                            />
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <footer v-if="currentRole">
                        <span
                            >Marque o cabeçalho do módulo para alterar todas as permissões do
                            grupo.</span
                        >
                        <strong>{{ changeLabel }}</strong>
                    </footer>
                </div>
            </section>
        </div>
    </PageContainer>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import PageContainer from '@/components/layout/PageContainer/index.vue'
import { AppCheckbox } from '@/components/forms'
import { AppButton } from '@/components/ui'
import { useAuthStore } from '@/stores/auth.js'
import { useOrganizationRolesStore } from '@/stores/organization-roles.js'

const authStore = useAuthStore()
const rolesStore = useOrganizationRolesStore()
const selectedRoleId = ref(null)
const draftPermissions = ref([])
const savedPermissions = ref([])
const error = ref('')

const permissionLabels = {
    view: 'Visualizar',
    create: 'Criar',
    update: 'Editar',
    delete: 'Excluir',
    upload: 'Enviar arquivos',
    generate: 'Gerar documentos',
    review: 'Revisar publicações',
    'manage-monitoring': 'Gerenciar OABs monitoradas',
    sync: 'Executar sincronizações',
    invite: 'Convidar membros',
    'update-role': 'Alterar função de membros',
    'update-status': 'Ativar ou desativar membros',
}
const groupLabels = {
    clients: 'Clientes',
    files: 'Arquivos',
    folders: 'Pastas processuais',
    publications: 'Publicações',
    documents: 'Documentos',
    tasks: 'Tarefas',
    users: 'Usuários',
    roles: 'Funções e acessos',
    'organization-members': 'Equipe',
}

const currentRole = computed(() => rolesStore.selectedRole)
const loading = computed(() => rolesStore.loadingRole)
const saving = computed(() => rolesStore.updatingPermissions)
const canUpdate = computed(() => authStore.hasPermission('roles.update'))
const protectedRole = computed(() => currentRole.value?.name === 'super-admin')
const editable = computed(() => canUpdate.value && !protectedRole.value)
const availablePermissions = computed(() => currentRole.value?.available_permissions ?? [])
const selectedCount = computed(() => draftPermissions.value.length)
const hasChanges = computed(
    () =>
        JSON.stringify([...draftPermissions.value].sort()) !==
        JSON.stringify([...savedPermissions.value].sort()),
)
const changeCount = computed(
    () =>
        availablePermissions.value.filter(
            (name) =>
                draftPermissions.value.includes(name) !== savedPermissions.value.includes(name),
        ).length,
)
const changeLabel = computed(() =>
    changeCount.value
        ? `${changeCount.value} alteração${changeCount.value > 1 ? 'ões' : ''} pendente${changeCount.value > 1 ? 's' : ''}`
        : 'Nenhuma alteração pendente',
)
const permissionGroups = computed(() =>
    Object.entries(
        availablePermissions.value.reduce((groups, name) => {
            const [group, action] = name.split('.')
            ;(groups[group] ??= []).push({ name, label: permissionLabels[action] ?? action })
            return groups
        }, {}),
    ).map(([key, permissions]) => ({ key, label: groupLabels[key] ?? key, permissions })),
)

function roleLabel(name) {
    return name
        .split('-')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ')
}
function rolePermissionCount(role) {
    return role.id === currentRole.value?.id ? selectedCount.value : role.permissions_count
}
function isGroupChecked(group) {
    return group.permissions.every(({ name }) => draftPermissions.value.includes(name))
}
function isGroupIndeterminate(group) {
    const selected = group.permissions.filter(({ name }) =>
        draftPermissions.value.includes(name),
    ).length
    return selected > 0 && selected < group.permissions.length
}
function togglePermission(name, checked) {
    draftPermissions.value = checked
        ? [...new Set([...draftPermissions.value, name])]
        : draftPermissions.value.filter((item) => item !== name)
}
function toggleGroup(group, checked) {
    const names = group.permissions.map(({ name }) => name)
    draftPermissions.value = checked
        ? [...new Set([...draftPermissions.value, ...names])]
        : draftPermissions.value.filter((name) => !names.includes(name))
}
function setDraft(role) {
    savedPermissions.value = [...role.permissions]
    draftPermissions.value = [...role.permissions]
}
async function loadSelectedRole() {
    error.value = ''
    try {
        setDraft(await rolesStore.fetchRole(selectedRoleId.value))
    } catch {
        error.value = 'Não foi possível carregar as permissões desta função.'
    }
}
async function selectRole(roleId) {
    if (roleId === selectedRoleId.value) return
    selectedRoleId.value = roleId
    await loadSelectedRole()
}
function discardChanges() {
    draftPermissions.value = [...savedPermissions.value]
}
async function saveChanges() {
    error.value = ''
    try {
        setDraft(
            await rolesStore.updatePermissions(
                selectedRoleId.value,
                [...draftPermissions.value].sort(),
            ),
        )
    } catch {
        error.value = 'Não foi possível salvar as permissões. Tente novamente.'
    }
}

onMounted(async () => {
    try {
        await rolesStore.fetchRoles()
        selectedRoleId.value = rolesStore.roles[0]?.id ?? null
        if (selectedRoleId.value) await loadSelectedRole()
    } catch {
        error.value = 'Não foi possível carregar as funções da organização.'
    }
})
</script>

<style scoped>
.role-permissions {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.role-permissions__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-4);
    padding-bottom: var(--space-5);
    border-top: 3px solid var(--color-highlight);
    padding-top: var(--space-4);
}
h1 {
    margin: 0;
    color: var(--color-brand);
    font-size: var(--font-size-2xl);
}
p {
    margin: var(--space-2) 0 0;
    color: var(--color-text-muted);
}
.role-permissions__actions {
    display: flex;
    gap: var(--space-2);
}
.role-permissions__error,
.role-permissions__state {
    padding: var(--space-4);
    border: 1px solid var(--color-danger);
    background: var(--color-danger-soft);
    color: var(--color-danger);
}
.role-permissions__notice {
    display: flex;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-4);
    background: var(--color-surface-warning-soft);
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
}
.role-permissions__table-wrap {
    overflow-x: auto;
    border: 1px solid var(--color-border);
    border-bottom: 0;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    background: var(--color-surface);
}
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 38rem;
}
th,
td {
    padding: var(--space-3) var(--space-4);
    border-bottom: 1px solid var(--color-divider);
    text-align: left;
}
th {
    background: var(--color-surface-soft);
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
}
th:last-child,
td:last-child {
    width: 7rem;
    text-align: center;
}
.role-permissions__group td {
    font-weight: 600;
}
code {
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
footer {
    display: flex;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--color-border);
    border-top: 0;
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    background: var(--color-surface-soft);
    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
footer strong {
    color: var(--color-highlight);
}
@media (max-width: 700px) {
    .role-permissions__header {
        flex-direction: column;
    }
    .role-permissions__actions {
        width: 100%;
    }
    footer {
        flex-direction: column;
    }
}
.role-permissions__workspace {
    display: block;
    margin-top: var(--space-1);
    background: transparent;
}

.role-permissions__header {
    display: block;
}

.role-permissions__actions {
    margin-top: var(--space-5);
}

.role-permissions__roles {
    display: flex;
    gap: var(--space-1);
    overflow-x: auto;
    margin-bottom: var(--space-2);
    padding: var(--space-3);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    background: var(--color-surface-muted);
    box-shadow: var(--shadow-xs);
}

.role-permissions__roles-label {
    display: none;
}

.role-permissions__role {
    display: block;
    min-width: 12rem;
    padding: var(--space-3);
    border: 0;
    border-left: 3px solid transparent;
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--color-text);
    cursor: pointer;
    text-align: left;
    transition: background var(--duration-fast) var(--ease-standard);
}

.role-permissions__role:hover {
    background: var(--color-surface-soft);
}

.role-permissions__role--active {
    border-left-color: var(--color-highlight);
    background: var(--color-surface-accent);
    color: var(--color-brand-secondary);
}

.role-permissions__role--active:hover {
    background: var(--color-surface-accent);
}

.role-permissions__role span,
.role-permissions__role small {
    display: block;
}

.role-permissions__role span {
    font-weight: var(--font-weight-semibold);
}

.role-permissions__role small {
    margin-top: var(--space-1);
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}

.role-permissions__matrix {
    min-width: 0;
    background: transparent;
}

.role-permissions__checkbox {
    justify-items: center;
}

.role-permissions__checkbox :deep(.app-checkbox__content) {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
}

.role-permissions__role-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: var(--space-4);
    padding: var(--space-6);
    margin-bottom: var(--space-2);
    border: 1px solid var(--color-border);
    border-bottom: 2px solid var(--color-border-strong);
    border-left: 4px solid var(--color-highlight);
    border-radius: var(--radius-lg);
    background: var(--color-surface-highlight-soft);
    box-shadow: var(--shadow-xs);
}

.role-permissions__role-header h2 {
    margin: 0;
    color: var(--color-brand);
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    line-height: var(--line-height-tight);
}

.role-permissions__role-header p {
    margin-top: var(--space-2);
    color: var(--color-text-soft);
    font-size: var(--font-size-sm);
}

.role-permissions__badge {
    flex: none;
    padding: var(--space-2) var(--space-4);
    border: 1px solid var(--color-accent);
    border-radius: var(--radius-pill);
    background: var(--color-surface-accent);
    color: var(--color-brand-secondary);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-bold);
}

.role-permissions__notice {
    border-inline: 0;
    border-bottom: 1px solid var(--color-border);
}

.role-permissions__group--0 td {
    background: var(--color-surface-accent);
    color: var(--color-brand-secondary);
}

.role-permissions__group--1 td {
    background: var(--color-surface-highlight-soft);
    color: var(--color-highlight-hover);
}

.role-permissions__group--2 td {
    background: var(--color-info-soft);
    color: var(--color-info);
}

.role-permissions__group--3 td {
    background: var(--color-surface-warning-soft);
    color: var(--color-on-warning);
}

.role-permissions__group--4 td {
    background: var(--color-surface-secondary-soft);
    color: var(--color-brand-secondary-active);
}

.role-permissions__table-wrap tbody tr:not(.role-permissions__group):hover td {
    background: var(--color-surface-muted);
}

@media (max-width: 800px) {
    .role-permissions__workspace {
        grid-template-columns: 1fr;
    }

    .role-permissions__roles {
        display: flex;
        gap: var(--space-1);
        overflow-x: auto;
    }

    .role-permissions__roles-label {
        display: none;
    }

    .role-permissions__role {
        min-width: 10rem;
    }
}

@media (max-width: 700px) {
    .role-permissions__role-header {
        flex-direction: column;
    }
}
</style>
