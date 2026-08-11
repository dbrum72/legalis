import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import {
  createMemoryHistory,
  createRouter,
} from 'vue-router'

import AppBreadcrumb from '@/components/navigation/AppBreadcrumb.vue'

function createTestRouter(routes) {
  return createRouter({
    history: createMemoryHistory(),
    routes,
  })
}

describe('AppBreadcrumb', () => {
  async function mountComponent({
    routes,
    initialRoute,
  }) {
    const router = createTestRouter(routes)

    await router.push(initialRoute)
    await router.isReady()

    const wrapper = mount(AppBreadcrumb, {
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

  it('renderiza nav com aria-label Breadcrumb', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/',
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
      ],
    })

    const nav = wrapper.get('nav')

    expect(nav.classes()).toContain('breadcrumb')

    expect(nav.attributes('aria-label'))
      .toBe('Breadcrumb')
  })

  it('renderiza lista ordenada', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/',
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
      ],
    })

    expect(
      wrapper.find('ol.breadcrumb__list').exists(),
    ).toBe(true)
  })

  it('renderiza breadcrumb da rota atual', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/',
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
      ],
    })

    expect(wrapper.text()).toContain('Dashboard')
  })

  it('marca o último item como página atual', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/',
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
      ],
    })

    const current = wrapper.get(
      '.breadcrumb__current',
    )

    expect(current.attributes('aria-current'))
      .toBe('page')
  })

  it('não transforma o último item em link', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/',
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
      ],
    })

    expect(
      wrapper.find('.breadcrumb__link').exists(),
    ).toBe(false)
  })

  it('renderiza múltiplos níveis', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/clients/10',
      routes: [
        {
          path: '/clients',
          name: 'clients',
          component: {
            template: '<router-view />',
          },
          meta: {
            breadcrumb: 'Clientes',
          },
          children: [
            {
              path: ':id',
              name: 'client-show',
              component: {
                template: '<div>Cliente</div>',
              },
              meta: {
                breadcrumb: 'Cliente',
              },
            },
          ],
        },
      ],
    })

    const items = wrapper.findAll(
      '.breadcrumb__item',
    )

    expect(items).toHaveLength(2)

    expect(items[0].text()).toBe('Clientes')
    expect(items[1].text()).toBe('Cliente')
  })

  it('transforma itens intermediários em links', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/clients/10',
      routes: [
        {
          path: '/clients',
          name: 'clients',
          component: {
            template: '<router-view />',
          },
          meta: {
            breadcrumb: 'Clientes',
          },
          children: [
            {
              path: ':id',
              name: 'client-show',
              component: {
                template: '<div>Cliente</div>',
              },
              meta: {
                breadcrumb: 'Cliente',
              },
            },
          ],
        },
      ],
    })

    const link = wrapper.get(
      '.breadcrumb__link',
    )

    expect(link.text()).toBe('Clientes')
    expect(link.attributes('href')).toBe('/clients')
  })

  it('mantém o último item como texto em múltiplos níveis', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/clients/10',
      routes: [
        {
          path: '/clients',
          name: 'clients',
          component: {
            template: '<router-view />',
          },
          meta: {
            breadcrumb: 'Clientes',
          },
          children: [
            {
              path: ':id',
              name: 'client-show',
              component: {
                template: '<div>Cliente</div>',
              },
              meta: {
                breadcrumb: 'Cliente',
              },
            },
          ],
        },
      ],
    })

    const current = wrapper.get(
      '.breadcrumb__current',
    )

    expect(current.text()).toBe('Cliente')
  })

  it('ignora registros sem meta.breadcrumb', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/clients/10',
      routes: [
        {
          path: '/clients',
          name: 'clients',
          component: {
            template: '<router-view />',
          },
          children: [
            {
              path: ':id',
              name: 'client-show',
              component: {
                template: '<div>Cliente</div>',
              },
              meta: {
                breadcrumb: 'Cliente',
              },
            },
          ],
        },
      ],
    })

    const items = wrapper.findAll(
      '.breadcrumb__item',
    )

    expect(items).toHaveLength(1)
    expect(items[0].text()).toBe('Cliente')
  })

  it('renderiza lista vazia quando nenhuma rota possui breadcrumb', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/',
      routes: [
        {
          path: '/',
          name: 'dashboard',
          component: {
            template: '<div>Dashboard</div>',
          },
        },
      ],
    })

    expect(
      wrapper.findAll('.breadcrumb__item'),
    ).toHaveLength(0)
  })

  it('usa record.name como destino do link', async () => {
    const { wrapper } = await mountComponent({
      initialRoute: '/clients/10',
      routes: [
        {
          path: '/clients',
          name: 'clients',
          component: {
            template: '<router-view />',
          },
          meta: {
            breadcrumb: 'Clientes',
          },
          children: [
            {
              path: ':id',
              name: 'client-show',
              component: {
                template: '<div>Cliente</div>',
              },
              meta: {
                breadcrumb: 'Cliente',
              },
            },
          ],
        },
      ],
    })

    expect(
      wrapper.get('.breadcrumb__link')
        .attributes('href'),
    ).toBe('/clients')
  })

  it('reage à navegação entre rotas', async () => {
    const { wrapper, router } = await mountComponent({
      initialRoute: '/',
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
          path: '/settings',
          name: 'settings',
          component: {
            template: '<div>Configurações</div>',
          },
          meta: {
            breadcrumb: 'Configurações',
          },
        },
      ],
    })

    expect(wrapper.text())
      .toContain('Dashboard')

    await router.push({
      name: 'settings',
    })

    expect(wrapper.text())
      .toContain('Configurações')

    expect(wrapper.text())
      .not.toContain('Dashboard')
  })
})