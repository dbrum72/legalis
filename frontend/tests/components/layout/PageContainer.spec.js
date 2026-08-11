import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import PageContainer from '@/components/layout/PageContainer/index.vue'

describe('PageContainer', () => {
    function mountComponent(slots = {}) {
        return mount(PageContainer, {
            slots: {
                default: '<p>Conteúdo da página</p>',
                ...slots,
            },
        })
    }

    it('renderiza section', () => {
        const wrapper = mountComponent()

        expect(wrapper.element.tagName.toLowerCase()).toBe('section')
    })

    it('aplica classe page', () => {
        const wrapper = mountComponent()

        expect(wrapper.classes()).toContain('page')
    })

    it('renderiza o slot padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.text()).toContain('Conteúdo da página')
    })

    it('preserva elementos fornecidos no slot', () => {
        const wrapper = mountComponent({
            default: `
        <div class="content">
          <h1>Título</h1>
          <p>Texto</p>
        </div>
      `,
        })

        expect(wrapper.find('.content').exists()).toBe(true)

        expect(wrapper.get('h1').text()).toBe('Título')

        expect(wrapper.get('p').text()).toBe('Texto')
    })

    it('permite slot vazio', () => {
        const wrapper = mount(PageContainer)

        expect(wrapper.element.tagName.toLowerCase()).toBe('section')

        expect(wrapper.classes()).toContain('page')
    })
})
