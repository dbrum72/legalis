import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppRadio from '@/components/forms/selection/AppRadio/index.vue'

describe('AppRadio', () => {
    function mountComponent(props = {}) {
        return mount(AppRadio, {
            props: {
                modelValue: null,
                value: 'active',
                id: 'status-active',
                name: 'status',
                label: 'Ativo',
                ...props,
            },
        })
    }

    it('renderiza o radio e a label', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')
        const label = wrapper.get('label')

        expect(input.attributes('type')).toBe('radio')
        expect(input.attributes('id')).toBe('status-active')
        expect(input.attributes('name')).toBe('status')
        expect(label.attributes('for')).toBe('status-active')
        expect(label.text()).toContain('Ativo')
    })

    it('reflete o estado selecionado', () => {
        const wrapper = mountComponent({
            modelValue: 'active',
            value: 'active',
        })

        expect(wrapper.get('input').element.checked).toBe(true)
    })

    it('permanece desmarcado para valores diferentes', () => {
        const wrapper = mountComponent({
            modelValue: 'inactive',
            value: 'active',
        })

        expect(wrapper.get('input').element.checked).toBe(false)
    })

    it('emite update:modelValue com o valor configurado', async () => {
        const wrapper = mountComponent({
            value: 20,
        })

        await wrapper.get('input').trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([[20]])
    })

    it('preserva valores booleanos', async () => {
        const wrapper = mountComponent({
            modelValue: true,
            value: false,
        })

        await wrapper.get('input').trigger('change')

        expect(wrapper.emitted('update:modelValue')).toEqual([[false]])
    })

    it('renderiza indicador obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        const required = wrapper.get('.app-radio__required')

        expect(required.text()).toBe('*')
        expect(required.attributes('aria-hidden')).toBe('true')
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione esta opção quando aplicável.',
        })

        const hint = wrapper.get('.app-radio__hint')

        expect(hint.text()).toBe('Selecione esta opção quando aplicável.')

        expect(hint.attributes('id')).toBe('status-active-hint')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        const error = wrapper.get('.app-radio__error')

        expect(error.text()).toBe('Seleção obrigatória.')
        expect(error.attributes('id')).toBe('status-active-error')
    })

    it('oculta o hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione esta opção quando aplicável.',
            error: 'Seleção obrigatória.',
        })

        expect(wrapper.find('.app-radio__hint').exists()).toBe(false)

        expect(wrapper.text()).toContain('Seleção obrigatória.')
    })

    it('associa hint ao controle por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Selecione esta opção quando aplicável.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('status-active-hint')
    })

    it('associa erro ao controle e marca como inválido', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        const input = wrapper.get('input')

        expect(input.attributes('aria-describedby')).toBe('status-active-error')

        expect(input.attributes('aria-invalid')).toBe('true')
    })

    it('aplica classe checked', () => {
        const wrapper = mountComponent({
            modelValue: 'active',
            value: 'active',
        })

        expect(wrapper.get('.app-radio').classes()).toContain('app-radio--checked')
    })

    it('aplica classe invalid', () => {
        const wrapper = mountComponent({
            error: 'Seleção obrigatória.',
        })

        expect(wrapper.get('.app-radio').classes()).toContain('app-radio--invalid')
    })

    it('aplica classe disabled', () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        expect(wrapper.get('.app-radio').classes()).toContain('app-radio--disabled')

        expect(wrapper.get('input').attributes()).toHaveProperty('disabled')
    })

    it('encaminha required e autofocus', () => {
        const wrapper = mountComponent({
            required: true,
            autofocus: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('required')

        expect(input.attributes()).toHaveProperty('autofocus')
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('não gera ids descritivos sem id', () => {
        const wrapper = mountComponent({
            id: undefined,
            hint: 'Selecione esta opção quando aplicável.',
        })

        expect(wrapper.get('.app-radio__hint').attributes('id')).toBeUndefined()

        expect(wrapper.get('input').attributes('aria-describedby')).toBeUndefined()
    })

    it('reage à mudança do modelValue', async () => {
        const wrapper = mountComponent({
            modelValue: 'inactive',
            value: 'active',
        })

        const input = wrapper.get('input')

        expect(input.element.checked).toBe(false)

        await wrapper.setProps({
            modelValue: 'active',
        })

        expect(input.element.checked).toBe(true)
    })
})
