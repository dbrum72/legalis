import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import InputVariant from '@/components/forms/internal/InputVariant/index.vue'

describe('InputVariant', () => {
    function mountComponent(props = {}, slots = {}) {
        return mount(InputVariant, {
            props: {
                modelValue: '',
                config: {},
                inputProps: {},
                ...props,
            },
            slots,
        })
    }

    it('renderiza input text por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('input').attributes('type')).toBe('text')
    })

    it('encaminha modelValue', () => {
        const wrapper = mountComponent({
            modelValue: 'valor',
        })

        expect(wrapper.get('input').element.value).toBe('valor')
    })

    it('encaminha type da config', () => {
        const wrapper = mountComponent({
            config: {
                type: 'email',
            },
        })

        expect(wrapper.get('input').attributes('type')).toBe('email')
    })

    it('encaminha autocomplete da config', () => {
        const wrapper = mountComponent({
            config: {
                autocomplete: 'email',
            },
        })

        expect(wrapper.get('input').attributes('autocomplete')).toBe('email')
    })

    it('encaminha inputmode da config', () => {
        const wrapper = mountComponent({
            config: {
                inputmode: 'numeric',
            },
        })

        expect(wrapper.get('input').attributes('inputmode')).toBe('numeric')
    })

    it('renderiza ícone configurado', () => {
        const wrapper = mountComponent({
            config: {
                icon: 'email',
            },
        })

        expect(wrapper.find('.lucide-mail').exists()).toBe(true)
    })

    it('não renderiza ícone quando showIcon é false', () => {
        const wrapper = mountComponent({
            config: {
                icon: 'email',
                showIcon: false,
            },
        })

        expect(wrapper.find('.lucide-mail').exists()).toBe(false)
    })

    it('encaminha iconSize', () => {
        const wrapper = mountComponent({
            config: {
                icon: 'email',
                iconSize: 24,
            },
        })

        expect(wrapper.get('.lucide-mail').attributes('width')).toBe('24')
    })

    it('preserva inputProps', () => {
        const wrapper = mountComponent({
            inputProps: {
                id: 'email',
                name: 'email',
                placeholder: 'Informe o e-mail',
                required: true,
            },
        })

        const input = wrapper.get('input')

        expect(input.attributes('id')).toBe('email')
        expect(input.attributes('name')).toBe('email')
        expect(input.attributes('placeholder')).toBe('Informe o e-mail')

        expect(input.attributes()).toHaveProperty('required')
    })

    it('emite update:modelValue', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').setValue('novo valor')

        expect(wrapper.emitted('update:modelValue')).toEqual([['novo valor']])
    })

    it('emite focus', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        expect(wrapper.emitted('focus')).toHaveLength(1)
    })

    it('emite blur', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('blur')

        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('preserva slot prepend customizado', () => {
        const wrapper = mountComponent(
            {},
            {
                prepend: '<span class="custom-prepend">@</span>',
            },
        )

        expect(wrapper.find('.custom-prepend').exists()).toBe(true)
    })

    it('slot prepend substitui o ícone padrão', () => {
        const wrapper = mountComponent(
            {
                config: {
                    icon: 'email',
                },
            },
            {
                prepend: '<span class="custom-prepend">@</span>',
            },
        )

        expect(wrapper.find('.custom-prepend').exists()).toBe(true)

        expect(wrapper.find('.lucide-mail').exists()).toBe(false)
    })

    it('preserva slot append', () => {
        const wrapper = mountComponent(
            {},
            {
                append: '<span class="custom-append">.com</span>',
            },
        )

        expect(wrapper.find('.custom-append').exists()).toBe(true)
    })
})
