import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AppDate from '@/components/forms/variants/AppDate/index.vue'

describe('AppDate', () => {
    function mountComponent(props = {}) {
        return mount(AppDate, {
            props: {
                modelValue: '',
                id: 'hearing-date',
                name: 'hearing-date',
                label: 'Data da audiência',
                ...props,
            },
        })
    }

    it('renderiza input do tipo date', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('type')).toBe('date')
        expect(input.attributes('id')).toBe('hearing-date')
        expect(input.attributes('name')).toBe('hearing-date')
    })

    it('renderiza a label associada ao controle', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('label').attributes('for')).toBe('hearing-date')

        expect(wrapper.get('label').text()).toContain('Data da audiência')
    })

    it('preserva modelValue no formato YYYY-MM-DD', () => {
        const wrapper = mountComponent({
            modelValue: '2026-08-07',
        })

        expect(wrapper.get('input').element.value).toBe('2026-08-07')
    })

    it('emite a data sem conversão de timezone', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').setValue('2026-08-07')

        expect(wrapper.emitted('update:modelValue')).toEqual([['2026-08-07']])
    })

    it('permite valor vazio', async () => {
        const wrapper = mountComponent({
            modelValue: '2026-08-07',
        })

        await wrapper.get('input').setValue('')

        expect(wrapper.emitted('update:modelValue')).toEqual([['']])
    })

    it('encaminha min e max', () => {
        const wrapper = mountComponent({
            min: '2026-01-01',
            max: '2026-12-31',
        })

        const input = wrapper.get('input')

        expect(input.attributes('min')).toBe('2026-01-01')

        expect(input.attributes('max')).toBe('2026-12-31')
    })

    it('encaminha step', () => {
        const wrapper = mountComponent({
            step: 2,
        })

        expect(wrapper.get('input').attributes('step')).toBe('2')
    })

    it('utiliza step 1 por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('input').attributes('step')).toBe('1')
    })

    it('encaminha autocomplete', () => {
        const wrapper = mountComponent({
            autocomplete: 'bday',
        })

        expect(wrapper.get('input').attributes('autocomplete')).toBe('bday')
    })

    it('utiliza autocomplete off por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('input').attributes('autocomplete')).toBe('off')
    })

    it('encaminha disabled, readonly e required', () => {
        const wrapper = mountComponent({
            disabled: true,
            readonly: true,
            required: true,
        })

        const input = wrapper.get('input')

        expect(input.attributes()).toHaveProperty('disabled')
        expect(input.attributes()).toHaveProperty('readonly')
        expect(input.attributes()).toHaveProperty('required')
    })

    it('encaminha autofocus', () => {
        const wrapper = mountComponent({
            autofocus: true,
        })

        expect(wrapper.get('input').attributes()).toHaveProperty('autofocus')
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Informe a data da audiência.',
        })

        expect(wrapper.text()).toContain('Informe a data da audiência.')
    })

    it('renderiza erro e oculta o hint', () => {
        const wrapper = mountComponent({
            hint: 'Informe a data da audiência.',
            error: 'Data inválida.',
        })

        expect(wrapper.text()).toContain('Data inválida.')

        expect(wrapper.text()).not.toContain('Informe a data da audiência.')
    })

    it('associa hint por aria-describedby', () => {
        const wrapper = mountComponent({
            hint: 'Informe a data da audiência.',
        })

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('hearing-date-hint')
    })

    it('associa erro e marca como inválido', () => {
        const wrapper = mountComponent({
            error: 'Data inválida.',
        })

        const input = wrapper.get('input')

        expect(input.attributes('aria-describedby')).toBe('hearing-date-error')

        expect(input.attributes('aria-invalid')).toBe('true')
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('preserva slots prepend e append', () => {
        const wrapper = mount(AppDate, {
            props: {
                id: 'date',
                label: 'Data',
            },
            slots: {
                prepend: 'Início',
                append: 'Fim',
            },
        })

        expect(wrapper.text()).toContain('Início')
        expect(wrapper.text()).toContain('Fim')
    })
})
