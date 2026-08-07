import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppSwitch from '@/components/forms/selection/AppSwitch/index.vue'

describe('AppSwitch', () => {
    function mountComponent(props = {}) {
        return mount(AppSwitch, {
            props: {
                modelValue: false,
                id: 'notifications',
                name: 'notifications',
                label: 'Ativar notificações',
                ...props,
            },
        })
    }

    it('renderiza o switch e a label', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')
        const label = wrapper.get('label')

        expect(input.attributes('type')).toBe('checkbox')
        expect(input.attributes('role')).toBe('switch')
        expect(input.attributes('id')).toBe('notifications')
        expect(input.attributes('name')).toBe('notifications')
        expect(label.attributes('for')).toBe('notifications')
        expect(label.text()).toContain('Ativar notificações')
    })

    it('reflete modelValue no estado checked', () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        expect(wrapper.get('input').element.checked).toBe(true)
    })

    it('reflete modelValue em aria-checked', () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        expect(wrapper.get('input').attributes('aria-checked')).toBe('true')
    })

    it('emite update:modelValue ao alterar', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[true]])
    })

    it('renderiza indicador obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        const required = wrapper.get('.app-switch__required')

        expect(required.text()).toBe('*')
        expect(required.attributes('aria-hidden')).toBe('true')
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Você pode alterar esta configuração depois.',
        })

        const hint = wrapper.get('.app-switch__hint')

        expect(hint.text()).toBe('Você pode alterar esta configuração depois.')

        expect(hint.attributes('id')).toBe('notifications-hint')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Esta configuração é obrigatória.',
        })

        const error = wrapper.get('.app-switch__error')

        expect(error.text()).toBe('Esta configuração é obrigatória.')

        expect(error.attributes('id')).toBe('notifications-error')
    })

    it('oculta o hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Você pode alterar esta configuração depois.',
            error: 'Esta configuração é obrigatória.',
        })

        expect(wrapper.find('.app-switch__hint').exists()).toBe(false)

        expect(wrapper.text()).toContain('Esta configuração é obrigatória.')
    })

    it('associa hint ao controle por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Você pode alterar esta configuração depois.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('notifications-hint')
    })

    it('associa erro ao controle e marca como inválido', () => {
        const wrapper = mountComponent({
            error: 'Esta configuração é obrigatória.',
        })

        const input = wrapper.get('input')

        expect(input.attributes('aria-describedby')).toBe('notifications-error')

        expect(input.attributes('aria-invalid')).toBe('true')
    })

    it('aplica classe checked', () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        expect(wrapper.get('.app-switch').classes()).toContain('app-switch--checked')
    })

    it('aplica classe invalid', () => {
        const wrapper = mountComponent({
            error: 'Esta configuração é obrigatória.',
        })

        expect(wrapper.get('.app-switch').classes()).toContain('app-switch--invalid')
    })

    it('aplica classe disabled', () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        expect(wrapper.get('.app-switch').classes()).toContain('app-switch--disabled')

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
            hint: 'Você pode alterar esta configuração depois.',
        })

        expect(wrapper.get('.app-switch__hint').attributes('id')).toBeUndefined()

        expect(wrapper.get('input').attributes('aria-describedby')).toBeUndefined()
    })

    it('reage à mudança do modelValue', async () => {
        const wrapper = mountComponent({
            modelValue: false,
        })

        const input = wrapper.get('input')

        expect(input.element.checked).toBe(false)
        expect(input.attributes('aria-checked')).toBe('false')

        await wrapper.setProps({
            modelValue: true,
        })

        expect(input.element.checked).toBe(true)
        expect(input.attributes('aria-checked')).toBe('true')
    })
})
