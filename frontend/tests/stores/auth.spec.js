import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useAuthStore } from '@/stores/auth.js'
import { useFolderDeadlinesStore } from '@/stores/folder-deadlines.js'
import { useFolderDocumentsStore } from '@/stores/folder-documents.js'
import { useFolderEventsStore } from '@/stores/folder-events.js'
import { useFolderMovementsStore } from '@/stores/folder-movements.js'

vi.mock('@/api/auth.js', () => ({
    context: vi.fn(),
    login: vi.fn(),
    logout: vi.fn(),
    me: vi.fn(),
    refresh: vi.fn(),
}))

vi.mock('@/api/auth-token.js', () => ({
    getAccessToken: vi.fn(),
    setAccessToken: vi.fn(),
    removeAccessToken: vi.fn(),
}))

vi.mock('@/api/tenant.js', () => ({
    getCurrentTenant: vi.fn(),
    setCurrentTenant: vi.fn(),
    removeCurrentTenant: vi.fn(),
}))

import { context, login, logout, me, refresh } from '@/api/auth.js'

import { getAccessToken, removeAccessToken, setAccessToken } from '@/api/auth-token.js'

import { getCurrentTenant, removeCurrentTenant, setCurrentTenant } from '@/api/tenant.js'

describe('auth store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()

        getAccessToken.mockReturnValue(null)

        getCurrentTenant.mockReturnValue(null)
    })

    it('inicia desautenticada e sem contexto', () => {
        const store = useAuthStore()

        expect(store.token).toBeNull()

        expect(store.user).toBeNull()

        expect(store.organizations).toEqual([])

        expect(store.organization).toBeNull()

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])

        expect(store.hydrated).toBe(false)

        expect(store.contextLoaded).toBe(false)

        expect(store.isAuthenticated).toBe(false)

        expect(store.hasOrganization).toBe(false)

        expect(store.currentTenant).toBeNull()

        expect(store.userName).toBe('')

        expect(store.userEmail).toBe('')
    })

    it('aplica payload de autenticação sem carregar rbac', () => {
        const store = useAuthStore()

        store.applyAuthPayload({
            access_token: 'jwt-token',

            user: {
                id: 1,
                name: 'Super Admin',
                email: 'super-admin@legalis.local',
            },

            organizations: [
                {
                    id: 10,
                    name: 'Escritório Legalis',
                    slug: 'escritorio-legalis',
                },
            ],

            roles: ['super-admin'],

            permissions: ['clients.view', 'clients.create'],
        })

        expect(store.token).toBe('jwt-token')

        expect(store.user).toEqual({
            id: 1,
            name: 'Super Admin',
            email: 'super-admin@legalis.local',
        })

        expect(store.organizations).toEqual([
            {
                id: 10,
                name: 'Escritório Legalis',
                slug: 'escritorio-legalis',
            },
        ])

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])

        expect(store.isAuthenticated).toBe(true)

        expect(setAccessToken).toHaveBeenCalledWith('jwt-token')
    })

    it('aplica payload de contexto', () => {
        const store = useAuthStore()

        store.applyContextPayload({
            organization: {
                id: 10,
                name: 'Escritório Legalis',
                slug: 'escritorio-legalis',
            },

            roles: ['super-admin'],

            permissions: ['clients.view', 'clients.create'],
        })

        expect(store.organization).toEqual({
            id: 10,
            name: 'Escritório Legalis',
            slug: 'escritorio-legalis',
        })

        expect(store.roles).toEqual(['super-admin'])

        expect(store.permissions).toEqual(['clients.view', 'clients.create'])

        expect(store.contextLoaded).toBe(true)

        expect(store.hasOrganization).toBe(true)

        expect(store.currentTenant).toBe('escritorio-legalis')
    })

    it('limpa apenas contexto', () => {
        const store = useAuthStore()

        store.organization = {
            id: 10,
            slug: 'escritorio-legalis',
        }

        store.roles = ['super-admin']

        store.permissions = ['clients.view']

        store.contextLoaded = true

        store.clearContext()

        expect(store.organization).toBeNull()

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])

        expect(store.contextLoaded).toBe(false)

        expect(removeCurrentTenant).not.toHaveBeenCalled()
    })

    it('limpa contexto e tenant quando solicitado', () => {
        const store = useAuthStore()

        store.organization = {
            id: 10,
            slug: 'escritorio-legalis',
        }

        store.clearContext({
            removeTenant: true,
        })

        expect(store.organization).toBeNull()

        expect(removeCurrentTenant).toHaveBeenCalledTimes(1)
    })

    it('limpa autenticação completa', () => {
        const store = useAuthStore()

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Super Admin',
        }

        store.organizations = [
            {
                id: 10,
                slug: 'escritorio-legalis',
            },
        ]

        store.organization = {
            id: 10,
            slug: 'escritorio-legalis',
        }

        store.roles = ['super-admin']

        store.permissions = ['clients.view']

        store.clearAuth()

        expect(store.token).toBeNull()

        expect(store.user).toBeNull()

        expect(store.organizations).toEqual([])

        expect(store.organization).toBeNull()

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])

        expect(store.isAuthenticated).toBe(false)

        expect(removeAccessToken).toHaveBeenCalledTimes(1)

        expect(removeCurrentTenant).toHaveBeenCalledTimes(1)
    })

    it('executa login e seleciona automaticamente única organização', async () => {
        login.mockResolvedValue({
            data: {
                access_token: 'jwt-token',

                user: {
                    id: 1,
                    name: 'Super Admin',
                    email: 'super-admin@legalis.local',
                },

                organizations: [
                    {
                        id: 10,
                        name: 'Escritório Legalis',
                        slug: 'escritorio-legalis',
                    },
                ],
            },
        })

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 10,
                    name: 'Escritório Legalis',
                    slug: 'escritorio-legalis',
                },

                roles: ['super-admin'],

                permissions: ['clients.view'],
            },
        })

        getCurrentTenant.mockReturnValueOnce(null).mockReturnValue('escritorio-legalis')

        const store = useAuthStore()

        const credentials = {
            email: 'super-admin@legalis.local',

            password: 'password',
        }

        const result = await store.login(credentials)

        expect(login).toHaveBeenCalledWith(credentials)

        expect(store.token).toBe('jwt-token')

        expect(store.user.email).toBe('super-admin@legalis.local')

        expect(store.organizations).toHaveLength(1)

        expect(setCurrentTenant).toHaveBeenCalledWith('escritorio-legalis')

        expect(context).toHaveBeenCalledTimes(1)

        expect(store.roles).toEqual(['super-admin'])

        expect(store.permissions).toEqual(['clients.view'])

        expect(store.contextLoaded).toBe(true)

        expect(result.access_token).toBe('jwt-token')
    })

    it('login não escolhe organização quando existem várias e nenhuma está persistida', async () => {
        login.mockResolvedValue({
            data: {
                access_token: 'jwt-token',

                user: {
                    id: 1,
                    name: 'Super Admin',
                },

                organizations: [
                    {
                        id: 10,
                        name: 'Organização A',
                        slug: 'org-a',
                    },
                    {
                        id: 20,
                        name: 'Organização B',
                        slug: 'org-b',
                    },
                ],
            },
        })

        const store = useAuthStore()

        await store.login({
            email: 'admin@legalis.local',

            password: 'password',
        })

        expect(store.organizations).toHaveLength(2)

        expect(store.organization).toBeNull()

        expect(store.contextLoaded).toBe(false)

        expect(setCurrentTenant).not.toHaveBeenCalled()

        expect(context).not.toHaveBeenCalled()
    })

    it('restaura token persistido', () => {
        getAccessToken.mockReturnValue('persisted-token')

        const store = useAuthStore()

        const result = store.restoreToken()

        expect(getAccessToken).toHaveBeenCalledTimes(1)

        expect(store.token).toBe('persisted-token')

        expect(store.hydrated).toBe(true)

        expect(result).toBe('persisted-token')
    })

    it('fetchMe atualiza apenas identidade e organizações', async () => {
        me.mockResolvedValue({
            data: {
                user: {
                    id: 1,
                    name: 'Super Admin',
                    email: 'super-admin@legalis.local',
                },

                organizations: [
                    {
                        id: 10,
                        name: 'Escritório Legalis',
                        slug: 'escritorio-legalis',
                    },
                ],

                roles: ['super-admin'],

                permissions: ['clients.view'],
            },
        })

        const store = useAuthStore()

        store.roles = ['role-anterior']

        store.permissions = ['permission.anterior']

        const result = await store.fetchMe()

        expect(me).toHaveBeenCalledTimes(1)

        expect(store.user).toEqual({
            id: 1,
            name: 'Super Admin',
            email: 'super-admin@legalis.local',
        })

        expect(store.organizations).toHaveLength(1)

        expect(store.roles).toEqual(['role-anterior'])

        expect(store.permissions).toEqual(['permission.anterior'])

        expect(result.user.email).toBe('super-admin@legalis.local')
    })

    it('fetchContext atualiza rbac do tenant persistido', async () => {
        getCurrentTenant.mockReturnValue('escritorio-legalis')

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 10,
                    name: 'Escritório Legalis',
                    slug: 'escritorio-legalis',
                },

                roles: ['super-admin'],

                permissions: ['clients.view', 'clients.create'],
            },
        })

        const store = useAuthStore()

        const result = await store.fetchContext()

        expect(context).toHaveBeenCalledTimes(1)

        expect(store.organization.slug).toBe('escritorio-legalis')

        expect(store.roles).toEqual(['super-admin'])

        expect(store.permissions).toEqual(['clients.view', 'clients.create'])

        expect(result.organization.id).toBe(10)
    })

    it('fetchContext não chama api sem tenant', async () => {
        getCurrentTenant.mockReturnValue(null)

        const store = useAuthStore()

        const result = await store.fetchContext()

        expect(result).toBeNull()

        expect(context).not.toHaveBeenCalled()

        expect(store.organization).toBeNull()

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])
    })

    it('seleciona organização e carrega contexto', async () => {
        const store = useAuthStore()

        store.organizations = [
            {
                id: 10,
                name: 'Organização A',
                slug: 'org-a',
            },
        ]

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 10,
                    name: 'Organização A',
                    slug: 'org-a',
                },

                roles: ['super-admin'],

                permissions: ['folders.view'],
            },
        })

        getCurrentTenant.mockReturnValue('org-a')

        await store.selectOrganization('org-a')

        expect(setCurrentTenant).toHaveBeenCalledWith('org-a')

        expect(context).toHaveBeenCalledTimes(1)

        expect(store.organization.slug).toBe('org-a')

        expect(store.permissions).toEqual(['folders.view'])
    })

    it('rejeita organização inexistente', async () => {
        const store = useAuthStore()

        store.organizations = [
            {
                id: 10,
                slug: 'org-a',
            },
        ]

        await expect(store.selectOrganization('org-inexistente')).rejects.toThrow(
            'Organização inválida.',
        )

        expect(context).not.toHaveBeenCalled()
    })

    it('remove tenant quando carregamento de contexto falha', async () => {
        const store = useAuthStore()

        store.organizations = [
            {
                id: 10,
                slug: 'org-a',
            },
        ]

        getCurrentTenant.mockReturnValue('org-a')

        context.mockRejectedValue(new Error('Forbidden'))

        await expect(store.selectOrganization('org-a')).rejects.toThrow('Forbidden')

        expect(removeCurrentTenant).toHaveBeenCalled()

        expect(store.organization).toBeNull()

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])
    })

    it('refresh substitui token sem alterar rbac atual', async () => {
        refresh.mockResolvedValue({
            data: {
                access_token: 'new-token',

                user: {
                    id: 1,
                    name: 'Super Admin',
                    email: 'super-admin@legalis.local',
                },

                organizations: [
                    {
                        id: 10,
                        slug: 'org-a',
                    },
                ],

                roles: ['role-incorreta'],

                permissions: ['permission.incorreta'],
            },
        })

        const store = useAuthStore()

        store.token = 'old-token'

        store.organization = {
            id: 10,
            slug: 'org-a',
        }

        store.roles = ['super-admin']

        store.permissions = ['clients.view']

        const result = await store.refresh()

        expect(refresh).toHaveBeenCalledTimes(1)

        expect(store.token).toBe('new-token')

        expect(store.user.email).toBe('super-admin@legalis.local')

        expect(store.roles).toEqual(['super-admin'])

        expect(store.permissions).toEqual(['clients.view'])

        expect(result.access_token).toBe('new-token')
    })

    it('logout chama api e limpa identidade e contexto', async () => {
        logout.mockResolvedValue({
            data: {
                msg: 'Desconectado com sucesso',
            },
        })

        const store = useAuthStore()

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Super Admin',
        }

        store.organizations = [
            {
                id: 10,
                slug: 'org-a',
            },
        ]

        store.organization = {
            id: 10,
            slug: 'org-a',
        }

        store.roles = ['super-admin']

        store.permissions = ['clients.view']

        await store.logout()

        expect(logout).toHaveBeenCalledTimes(1)

        expect(store.token).toBeNull()

        expect(store.user).toBeNull()

        expect(store.organizations).toEqual([])

        expect(store.organization).toBeNull()

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])

        expect(removeAccessToken).toHaveBeenCalled()

        expect(removeCurrentTenant).toHaveBeenCalled()
    })

    it('hydrate encerra sem token', async () => {
        getAccessToken.mockReturnValue(null)

        const store = useAuthStore()

        await store.hydrate()

        expect(store.hydrated).toBe(true)

        expect(store.token).toBeNull()

        expect(me).not.toHaveBeenCalled()

        expect(context).not.toHaveBeenCalled()
    })

    it('hydrate restaura usuário e contexto persistido', async () => {
        getAccessToken.mockReturnValue('persisted-token')

        getCurrentTenant.mockReturnValue('escritorio-legalis')

        me.mockResolvedValue({
            data: {
                user: {
                    id: 1,
                    name: 'Super Admin',
                    email: 'super-admin@legalis.local',
                },

                organizations: [
                    {
                        id: 10,
                        name: 'Escritório Legalis',
                        slug: 'escritorio-legalis',
                    },
                ],
            },
        })

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 10,
                    name: 'Escritório Legalis',
                    slug: 'escritorio-legalis',
                },

                roles: ['super-admin'],

                permissions: ['clients.view', 'clients.create'],
            },
        })

        const store = useAuthStore()

        await store.hydrate()

        expect(store.hydrated).toBe(true)

        expect(store.token).toBe('persisted-token')

        expect(store.user.email).toBe('super-admin@legalis.local')

        expect(store.organization.slug).toBe('escritorio-legalis')

        expect(store.roles).toEqual(['super-admin'])

        expect(store.permissions).toEqual(['clients.view', 'clients.create'])

        expect(store.contextLoaded).toBe(true)

        expect(me).toHaveBeenCalledTimes(1)

        expect(context).toHaveBeenCalledTimes(1)
    })

    it('hydrate seleciona automaticamente organização única sem tenant persistido', async () => {
        getAccessToken.mockReturnValue('persisted-token')

        me.mockResolvedValue({
            data: {
                user: {
                    id: 1,
                    name: 'Super Admin',
                },

                organizations: [
                    {
                        id: 10,
                        name: 'Organização única',
                        slug: 'org-unica',
                    },
                ],
            },
        })

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 10,
                    name: 'Organização única',
                    slug: 'org-unica',
                },

                roles: ['super-admin'],

                permissions: ['clients.view'],
            },
        })

        getCurrentTenant.mockReturnValueOnce(null).mockReturnValue('org-unica')

        const store = useAuthStore()

        await store.hydrate()

        expect(setCurrentTenant).toHaveBeenCalledWith('org-unica')

        expect(context).toHaveBeenCalledTimes(1)

        expect(store.organization.slug).toBe('org-unica')
    })

    it('hydrate não escolhe automaticamente quando há várias organizações', async () => {
        getAccessToken.mockReturnValue('persisted-token')

        me.mockResolvedValue({
            data: {
                user: {
                    id: 1,
                    name: 'Super Admin',
                },

                organizations: [
                    {
                        id: 10,
                        slug: 'org-a',
                    },
                    {
                        id: 20,
                        slug: 'org-b',
                    },
                ],
            },
        })

        const store = useAuthStore()

        await store.hydrate()

        expect(store.isAuthenticated).toBe(true)

        expect(store.organization).toBeNull()

        expect(store.contextLoaded).toBe(false)

        expect(context).not.toHaveBeenCalled()
    })

    it('hydrate limpa autenticação quando me falha', async () => {
        getAccessToken.mockReturnValue('expired-token')

        me.mockRejectedValue(new Error('Unauthorized'))

        const store = useAuthStore()

        await store.hydrate()

        expect(store.hydrated).toBe(true)

        expect(store.token).toBeNull()

        expect(store.user).toBeNull()

        expect(store.organizations).toEqual([])

        expect(store.organization).toBeNull()

        expect(store.roles).toEqual([])

        expect(store.permissions).toEqual([])

        expect(removeAccessToken).toHaveBeenCalled()

        expect(removeCurrentTenant).toHaveBeenCalled()
    })

    it('verifica roles do contexto atual', () => {
        const store = useAuthStore()

        store.roles = ['super-admin', 'advogado']

        expect(store.hasRole('super-admin')).toBe(true)

        expect(store.hasRole('advogado')).toBe(true)

        expect(store.hasRole('financeiro')).toBe(false)
    })

    it('verifica permissions do contexto atual', () => {
        const store = useAuthStore()

        store.permissions = ['clients.view', 'clients.create']

        expect(store.hasPermission('clients.view')).toBe(true)

        expect(store.hasPermission('clients.create')).toBe(true)

        expect(store.hasPermission('clients.delete')).toBe(false)
    })

    it('limpa documentos da pasta ao trocar de organização', async () => {
        const store = useAuthStore()

        const folderDocumentsStore = useFolderDocumentsStore()

        folderDocumentsStore.documents = [
            {
                id: 1,
                folder_id: 10,
                name: 'Documento da organização anterior',
            },
        ]

        store.organizations = [
            {
                id: 10,
                name: 'Organização A',
                slug: 'org-a',
            },
            {
                id: 20,
                name: 'Organização B',
                slug: 'org-b',
            },
        ]

        store.organization = {
            id: 10,
            name: 'Organização A',
            slug: 'org-a',
        }

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 20,
                    name: 'Organização B',
                    slug: 'org-b',
                },

                roles: ['advogado'],

                permissions: ['folders.view'],
            },
        })

        getCurrentTenant.mockReturnValue('org-b')

        await store.selectOrganization('org-b')

        expect(folderDocumentsStore.documents).toEqual([])

        expect(folderDocumentsStore.count).toBe(0)
    })

    it('limpa documentos da pasta ao limpar autenticação', () => {
        const store = useAuthStore()

        const folderDocumentsStore = useFolderDocumentsStore()

        folderDocumentsStore.documents = [
            {
                id: 1,
                folder_id: 10,
                name: 'Documento privado',
            },
        ]

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Usuário',
        }

        store.organization = {
            id: 10,
            slug: 'org-a',
        }

        store.clearAuth()

        expect(folderDocumentsStore.documents).toEqual([])

        expect(folderDocumentsStore.count).toBe(0)
    })

    it('limpa movimentações da pasta ao trocar de organização', async () => {
        const store = useAuthStore()

        const folderMovementsStore = useFolderMovementsStore()

        store.organizations = [
            {
                id: 1,
                slug: 'org-a',
                name: 'Organização A',
            },
            {
                id: 2,
                slug: 'org-b',
                name: 'Organização B',
            },
        ]

        store.organization = {
            id: 1,
            slug: 'org-a',
            name: 'Organização A',
        }

        folderMovementsStore.movements = [
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação da organização anterior',
            },
        ]

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 2,
                    slug: 'org-b',
                    name: 'Organização B',
                },

                roles: [],
                permissions: [],
            },
        })

        await store.selectOrganization('org-b')

        expect(folderMovementsStore.movements).toEqual([])

        expect(folderMovementsStore.count).toBe(0)
    })

    it('limpa movimentações da pasta ao limpar autenticação', () => {
        const store = useAuthStore()

        const folderMovementsStore = useFolderMovementsStore()

        folderMovementsStore.movements = [
            {
                id: 1,
                folder_id: 10,
                title: 'Movimentação privada',
            },
        ]

        store.clearAuth()

        expect(folderMovementsStore.movements).toEqual([])

        expect(folderMovementsStore.count).toBe(0)
    })

    it('limpa prazos da pasta ao trocar de organização', async () => {
        const store = useAuthStore()

        const folderDeadlinesStore = useFolderDeadlinesStore()

        folderDeadlinesStore.deadlines = [
            {
                id: 1,
                folder_id: 10,
                title: 'Prazo da organização anterior',
                due_at: '2026-08-25T12:00:00.000000Z',
                status: 'pending',
            },
        ]

        store.organizations = [
            {
                id: 10,
                name: 'Organização A',
                slug: 'org-a',
            },
            {
                id: 20,
                name: 'Organização B',
                slug: 'org-b',
            },
        ]

        store.organization = {
            id: 10,
            name: 'Organização A',
            slug: 'org-a',
        }

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 20,
                    name: 'Organização B',
                    slug: 'org-b',
                },

                roles: ['advogado'],

                permissions: ['folders.view'],
            },
        })

        getCurrentTenant.mockReturnValue('org-b')

        await store.selectOrganization('org-b')

        expect(folderDeadlinesStore.deadlines).toEqual([])

        expect(folderDeadlinesStore.count).toBe(0)
    })

    it('limpa prazos da pasta ao limpar autenticação', () => {
        const store = useAuthStore()

        const folderDeadlinesStore = useFolderDeadlinesStore()

        folderDeadlinesStore.deadlines = [
            {
                id: 1,
                folder_id: 10,
                title: 'Prazo privado',
                due_at: '2026-08-25T12:00:00.000000Z',
                status: 'pending',
            },
        ]

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Usuário',
        }

        store.organization = {
            id: 10,
            slug: 'org-a',
        }

        store.clearAuth()

        expect(folderDeadlinesStore.deadlines).toEqual([])

        expect(folderDeadlinesStore.count).toBe(0)
    })

    it('limpa eventos da pasta ao trocar de organização', async () => {
        const store = useAuthStore()

        const folderEventsStore = useFolderEventsStore()

        folderEventsStore.events = [
            {
                id: 1,
                folder_id: 10,
                type: 'hearing',
                title: 'Evento da organização anterior',
                starts_at: '2026-09-10T14:00:00.000000Z',
                status: 'scheduled',
            },
        ]

        store.organizations = [
            {
                id: 10,
                name: 'Organização A',
                slug: 'org-a',
            },

            {
                id: 20,
                name: 'Organização B',
                slug: 'org-b',
            },
        ]

        store.organization = {
            id: 10,
            name: 'Organização A',
            slug: 'org-a',
        }

        context.mockResolvedValue({
            data: {
                organization: {
                    id: 20,
                    name: 'Organização B',
                    slug: 'org-b',
                },

                roles: ['advogado'],

                permissions: ['folders.view'],
            },
        })

        getCurrentTenant.mockReturnValue('org-b')

        await store.selectOrganization('org-b')

        expect(folderEventsStore.events).toEqual([])

        expect(folderEventsStore.count).toBe(0)
    })

    it('limpa eventos da pasta ao limpar autenticação', () => {
        const store = useAuthStore()

        const folderEventsStore = useFolderEventsStore()

        folderEventsStore.events = [
            {
                id: 1,
                folder_id: 10,
                type: 'hearing',
                title: 'Evento privado',
                starts_at: '2026-09-10T14:00:00.000000Z',
                status: 'scheduled',
            },
        ]

        store.token = 'jwt-token'

        store.user = {
            id: 1,
            name: 'Usuário',
        }

        store.organization = {
            id: 10,
            slug: 'org-a',
        }

        store.clearAuth()

        expect(folderEventsStore.events).toEqual([])

        expect(folderEventsStore.count).toBe(0)
    })
})
