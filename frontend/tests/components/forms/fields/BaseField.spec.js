import { defineComponent, h } from 'vue'
import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import BaseField from '@/components/forms/fields/BaseField/index.vue'
import { useFieldContext } from '@/composables/useFieldContext.js'

const FieldContextProbe = defineComponent({
    name: 'FieldContextProbe',

    setup() {
        const { fieldContext, ariaDescribedBy, ariaInvalid } = useFieldContext()

        return () =>
            h('div', {
                class: 'field-context-probe',
                'data-id': fieldContext?.value?.id,
                'data-required': String(fieldContext?.value?.required ?? false),
                'data-disabled': String(fieldContext?.value?.disabled ?? false),
                'data-readonly': String(fieldContext?.value?.readonly ?? false),
                'data-invalid': String(fieldContext?.value?.invalid ?? false),
                'data-describedby': ariaDescribedBy.value,
                'data-aria-invalid': ariaInvalid.value,
            })
    },
})

describe('BaseField', () => {
    function mountComponent(props = {}, slots = {}) {
        return mount(BaseField, {
            props: {
                id: 'description',
                label: 'Descrição',
                ...props,
            },

            slots: {
                default: '<textarea id="description"></textarea>',
                ...slots,
            },
        })
    }

    it('renderiza o slot padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('textarea').exists()).toBe(true)
    })

    it('renderiza a label associada ao controle', () => {
        const wrapper = mountComponent()

        const label = wrapper.get('label')

        expect(label.text()).toBe('Descrição')
        expect(label.attributes('for')).toBe('description')
    })

    it('não renderiza label quando ela está vazia', () => {
        const wrapper = mountComponent({
            label: '',
        })

        expect(wrapper.find('label').exists()).toBe(false)
    })

    it('renderiza o indicador obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        const required = wrapper.get('.app-field__required')

        expect(required.text()).toBe('*')
        expect(required.attributes('aria-hidden')).toBe('true')
    })

    it('não renderiza o indicador obrigatório por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('.app-field__required').exists()).toBe(false)
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Informe uma descrição objetiva.',
        })

        const hint = wrapper.get('.app-field__hint')

        expect(hint.text()).toBe('Informe uma descrição objetiva.')

        expect(hint.attributes('id')).toBe('description-hint')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Campo obrigatório.',
        })

        const error = wrapper.get('.app-field__error')

        expect(error.text()).toBe('Campo obrigatório.')
        expect(error.attributes('id')).toBe('description-error')
    })

    it('oculta o hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Informe uma descrição objetiva.',
            error: 'Campo obrigatório.',
        })

        expect(wrapper.find('.app-field__hint').exists()).toBe(false)

        expect(wrapper.get('.app-field__error').text()).toBe('Campo obrigatório.')
    })

    it('renderiza o slot prepend', () => {
        const wrapper = mountComponent(
            {},
            {
                prepend: '<span class="prepend-content">R$</span>',
            },
        )

        expect(wrapper.get('.app-field__prepend').text()).toBe('R$')
    })

    it('não renderiza a área prepend sem slot', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('.app-field__prepend').exists()).toBe(false)
    })

    it('renderiza o slot append', () => {
        const wrapper = mountComponent(
            {},
            {
                append: '<span class="append-content">kg</span>',
            },
        )

        expect(wrapper.get('.app-field__append').text()).toBe('kg')
    })

    it('não renderiza a área append sem slot', () => {
        const wrapper = mountComponent()

        expect(wrapper.find('.app-field__append').exists()).toBe(false)
    })

    it('aplica a classe de campo obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.get('.app-field').classes()).toContain('app-field--required')
    })

    it('aplica a classe de campo inválido', () => {
        const wrapper = mountComponent({
            error: 'Campo obrigatório.',
        })

        expect(wrapper.get('.app-field').classes()).toContain('app-field--invalid')
    })

    it('aplica a classe de campo desabilitado', () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        expect(wrapper.get('.app-field').classes()).toContain('app-field--disabled')
    })

    it('aplica a classe de campo somente leitura', () => {
        const wrapper = mountComponent({
            readonly: true,
        })

        expect(wrapper.get('.app-field').classes()).toContain('app-field--readonly')
    })

    it('fornece o contexto do campo aos descendentes', () => {
        const wrapper = mountComponent(
            {
                required: true,
                disabled: true,
                readonly: true,
                error: 'Campo obrigatório.',
            },
            {
                default: FieldContextProbe,
            },
        )

        const probe = wrapper.get('.field-context-probe')

        expect(probe.attributes('data-id')).toBe('description')

        expect(probe.attributes('data-required')).toBe('true')

        expect(probe.attributes('data-disabled')).toBe('true')

        expect(probe.attributes('data-readonly')).toBe('true')

        expect(probe.attributes('data-invalid')).toBe('true')
    })

    it('fornece o id do hint em aria-describedby', () => {
        const wrapper = mountComponent(
            {
                hint: 'Informe uma descrição objetiva.',
            },
            {
                default: FieldContextProbe,
            },
        )

        const probe = wrapper.get('.field-context-probe')

        expect(probe.attributes('data-describedby')).toBe('description-hint')

        expect(probe.attributes('data-aria-invalid')).toBeUndefined()
    })

    it('fornece o id do erro e aria-invalid quando inválido', () => {
        const wrapper = mountComponent(
            {
                hint: 'Informe uma descrição objetiva.',
                error: 'Campo obrigatório.',
            },
            {
                default: FieldContextProbe,
            },
        )

        const probe = wrapper.get('.field-context-probe')

        expect(probe.attributes('data-describedby')).toBe('description-error')

        expect(probe.attributes('data-aria-invalid')).toBe('true')
    })

    it('não fornece ids de descrição quando id está ausente', () => {
        const wrapper = mountComponent(
            {
                id: undefined,
                label: '',
                hint: 'Texto auxiliar.',
            },
            {
                default: FieldContextProbe,
            },
        )

        const probe = wrapper.get('.field-context-probe')

        expect(probe.attributes('data-describedby')).toBeUndefined()

        expect(wrapper.get('.app-field__hint').attributes('id')).toBeUndefined()
    })
})
