import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import {
  createMemoryHistory,
  createRouter,
} from 'vue-router'

import HeaderBar from '@/components/layout/HeaderBar/index.vue'

function createTestRouter() {
  return createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/',
        name: 'dashboard',
        component: {
          template: '<div>Dashboard</div>',
        },
        meta: {
          breadcrumb: 'Dashboard',
        },
      },
      {
        path: '/playground',
        name: 'playground',
        component: {
          template: '<div>Playground</div>',
        },
        meta: {
          breadcrumb: 'Playground',
        },
      },
    ],
  })
}

describe('HeaderBar', () => {
  async function mountComponent(
    initialRoute = '/',
  ) {
    const router = createTestRouter()

    await router.push(initialRoute)
    await router.isReady()

    const wrapper = mount(HeaderBar, {
      global: {
        plugins: [
          router,
        ],
      },
    })

    return {
      wrapper,
      router,
    }
  }

  it('renderiza header', async () => {
    const { wrapper } = await mountComponent()

    expect(wrapper.element.tagName.toLowerCase())
      .toBe('header')
  })

  it('aplica classe app-header', async () => {
    const { wrapper } = await mountComponent()

    expect(wrapper.classes())
      .toContain('app-header')
  })

  it('aplica classe app-header-bar', async () => {
    const { wrapper } = await mountComponent()

    expect(wrapper.classes())
      .toContain('app-header-bar')
  })

  it('renderiza região inicial', async () => {
    const { wrapper } = await mountComponent()

    expect(
      wrapper.find('.app-header-bar__start').exists(),
    ).toBe(true)
  })

  it('renderiza AppBreadcrumb', async () => {
    const { wrapper } = await mountComponent()

    expect(
      wrapper.find('.breadcrumb').exists(),
    ).toBe(true)
  })

  it('renderiza breadcrumb da rota atual', async () => {
    const { wrapper } = await mountComponent()

    expect(wrapper.text())
      .toContain('Dashboard')
  })

  it('reage à mudança de rota', async () => {
    const { wrapper, router } = await mountComponent()

    await router.push({
      name: 'playground',
    })

    expect(wrapper.text())
      .toContain('Playground')

    expect(wrapper.text())
      .not.toContain('Dashboard')
  })

  it('renderiza região final', async () => {
    const { wrapper } = await mountComponent()

    expect(
      wrapper.find('.app-header-bar__end').exists(),
    ).toBe(true)
  })

  it('mantém região inicial antes da região final', async () => {
    const { wrapper } = await mountComponent()

    const children = Array.from(
      wrapper.element.children,
    )

    expect(children).toHaveLength(2)

    expect(children[0].classList)
      .toContain('app-header-bar__start')

    expect(children[1].classList)
      .toContain('app-header-bar__end')
  })
})