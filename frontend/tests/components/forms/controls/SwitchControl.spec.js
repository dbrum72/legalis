import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import SwitchControl from '@/components/forms/controls/SwitchControl/index.vue'

describe('SwitchControl', () => {
    function mountComponent(props = {}) {
        return mount(SwitchControl, {
            props: {
                modelValue: false,
                id: 'notifications',
                name: 'notifications',
                ...props,
            },
        })
    }

    it('renderiza o switch', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('checkbox')
        expect(input.attributes('role')).toBe('switch')
        expect(input.attributes('id')).toBe('notifications')
        expect(input.attributes('name')).toBe('notifications')
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

    it('utiliza aria-checked false quando desativado', () => {
        const wrapper = mountComponent({
            modelValue: false,
        })

        expect(wrapper.get('input').attributes('aria-checked')).toBe('false')
    })

    it('encaminha disabled, required e autofocus', () => {
        const wrapper = mountComponent({
            disabled: true,
            required: true,
            autofocus: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('disabled')
        expect(input.attributes()).toHaveProperty('required')
        expect(input.attributes()).toHaveProperty('autofocus')
    })

    it('encaminha aria-invalid', () => {
        const wrapper = mountComponent({
            ariaInvalid: 'true',
        })

        expect(wrapper.get('input').attributes('aria-invalid')).toBe('true')
    })

    it('encaminha aria-describedby', () => {
        const wrapper = mountComponent({
            ariaDescribedBy: 'notifications-error',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('notifications-error')
    })

    it('emite update:modelValue ao ativar', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[true]])
    })

    it('emite update:modelValue ao desativar', async () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        await wrapper.get('input').setValue(false)

        expect(wrapper.emitted('update:modelValue')).toEqual([[false]])
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('reage à alteração de modelValue', async () => {
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

    it('mantém semântica nativa de checkbox com role switch', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('checkbox')
        expect(input.attributes('role')).toBe('switch')
    })
})
