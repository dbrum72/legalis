import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { reactive } from 'vue'
import { createRouter, createMemoryHistory } from 'vue-router'
let auth
vi.mock('@/stores/auth.js', () => ({ useAuthStore: () => auth }))
import SettingsPage from '@/views/settings/SettingsPage.vue'
let wrapper
async function render(permissions) {
    auth = reactive({ organization: { name: 'Escritório A' }, hasPermission: name => permissions.includes(name) })
    const router = createRouter({ history: createMemoryHistory(), routes: ['settings.categories', 'settings.cost-centers', 'role-permissions', 'settings.document-templates'].map((name, index) => ({ name, path: `/test/${index}`, component: { template: '<div />' } })) })
    await router.push('/test/0')
    wrapper = mount(SettingsPage, { global: { plugins: [router] } })
}
afterEach(() => wrapper?.unmount())
describe('Central de configurações', () => {
    it('permite catálogo de modelos sem conceder acesso financeiro ou a perfis', async () => {
        await render(['documents.generate'])
        expect(wrapper.findAll('a')).toHaveLength(1)
        expect(wrapper.text()).toContain('Modelos de documentos')
        expect(wrapper.text()).not.toContain('Categorias financeiras')
        expect(wrapper.text()).not.toContain('Perfis e permissões')
    })
    it('mostra cadastros para despesas sem revelar administração de acessos', async () => {
        await render(['expenses.view'])
        expect(wrapper.findAll('a')).toHaveLength(2)
        expect(wrapper.text()).toContain('Categorias financeiras')
        expect(wrapper.text()).not.toContain('Perfis e permissões')
    })
    it('mostra apenas perfis quando essa é a permissão disponível', async () => {
        await render(['roles.view'])
        expect(wrapper.findAll('a')).toHaveLength(1)
        expect(wrapper.text()).toContain('Perfis e permissões')
        expect(wrapper.text()).not.toContain('Categorias financeiras')
    })
    it('atualiza o escritório e não apresenta cartões sem autorização', async () => {
        await render([])
        auth.organization.name = 'Escritório B'
        await wrapper.vm.$nextTick()
        expect(wrapper.text()).toContain('Escritório B')
        expect(wrapper.findAll('a')).toHaveLength(0)
    })
})
