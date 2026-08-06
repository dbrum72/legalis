import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import CheckboxControl from '@/components/forms/controls/CheckboxControl/index.vue'

describe('CheckboxControl', () => {
    function mountComponent(props = {}) {
        return mount(CheckboxControl, {
            props: {
                modelValue: false,
                id: 'accept',
                name: 'accept',
                ...props,
            },
        })
    }

    it('renderiza o checkbox', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('checkbox')
        expect(input.attributes('id')).toBe('accept')
        expect(input.attributes('name')).toBe('accept')
    })

    it('reflete modelValue no estado checked', () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        expect(wrapper.get('input').element.checked).toBe(true)
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
            ariaDescribedBy: 'accept-error',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('accept-error')
    })

    it('emite update:modelValue ao marcar', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[true]])
    })

    it('emite update:modelValue ao desmarcar', async () => {
        const wrapper = mountComponent({
            modelValue: true,
        })

        const input = wrapper.get('input')

        await input.setValue(false)

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

    it('aplica indeterminate na montagem', () => {
        const wrapper = mountComponent({
            indeterminate: true,
        })

        expect(wrapper.get('input').element.indeterminate).toBe(true)
    })

    it('remove indeterminate quando false', async () => {
        const wrapper = mountComponent({
            indeterminate: true,
        })

        await wrapper.setProps({
            indeterminate: false,
        })

        expect(wrapper.get('input').element.indeterminate).toBe(false)
    })

    it('aplica indeterminate após atualização reativa', async () => {
        const wrapper = mountComponent({
            indeterminate: false,
        })

        await wrapper.setProps({
            indeterminate: true,
        })

        expect(wrapper.get('input').element.indeterminate).toBe(true)
    })

    it('mantém checked independente de indeterminate', async () => {
        const wrapper = mountComponent({
            modelValue: true,
            indeterminate: true,
        })

        const input = wrapper.get('input').element

        expect(input.checked).toBe(true)
        expect(input.indeterminate).toBe(true)
    })
})
