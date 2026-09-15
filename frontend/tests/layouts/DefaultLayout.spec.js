import { afterEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
vi.mock('@/components/layout', () => ({
    SideBar: { template: '<aside />' },
    HeaderBar: {
        props: ['sidebarOpen'],
        emits: ['toggle-sidebar'],
        template: '<button @click="$emit(\'toggle-sidebar\')">Menu</button>',
    },
}))
import DefaultLayout from '@/layouts/DefaultLayout.vue'

afterEach(() => vi.unstubAllGlobals())

describe('DefaultLayout responsivo', () => {
    it('inicia fechado no mobile, fecha ao navegar e remove listener ao desmontar', async () => {
        const media = { matches: true, addEventListener: vi.fn(), removeEventListener: vi.fn() }
        vi.stubGlobal('matchMedia', () => media)
        const router = createRouter({
            history: createMemoryHistory(),
            routes: [{ path: '/:pathMatch(.*)*', component: { template: '<p>Página</p>' } }],
        })
        await router.push('/finance')
        const wrapper = mount(DefaultLayout, { global: { plugins: [router] } })
        expect(wrapper.classes()).toContain('app-layout--sidebar-closed')
        await wrapper.get('button').trigger('click')
        expect(wrapper.classes()).not.toContain('app-layout--sidebar-closed')
        await router.push('/clients')
        expect(wrapper.classes()).toContain('app-layout--sidebar-closed')
        wrapper.unmount()
        expect(media.removeEventListener).toHaveBeenCalledWith('change', expect.any(Function))
    })
})
