import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import SelectControl from '@/components/forms/controls/SelectControl/index.vue'

describe('SelectControl', () => {
    function mountComponent(props = {}) {
        return mount(SelectControl, {
            props: {
                modelValue: null,
                id: 'status',
                name: 'status',
                placeholder: 'Selecione...',
                options: [
                    {
                        label: 'Ativo',
                        value: 1,
                    },
                    {
                        label: 'Inativo',
                        value: 2,
                    },
                ],
                ...props,
            },
        })
    }

    it('renderiza o select', () => {
        const wrapper = mountComponent()

        const select = wrapper.get('select')

        expect(select.exists()).toBe(true)
        expect(select.attributes('id')).toBe('status')
        expect(select.attributes('name')).toBe('status')
    })

    it('renderiza o placeholder desabilitado', () => {
        const wrapper = mountComponent()

        const placeholder = wrapper.findAll('option')[0]

        expect(placeholder.text()).toBe('Selecione...')
        expect(placeholder.attributes('value')).toBe('')
        expect(placeholder.attributes()).toHaveProperty('disabled')
    })

    it('não renderiza placeholder quando está vazio', () => {
        const wrapper = mountComponent({
            placeholder: '',
        })

        expect(wrapper.findAll('option')).toHaveLength(2)
    })

    it('renderiza todas as opções', () => {
        const wrapper = mountComponent()

        const options = wrapper.findAll('option')

        expect(options).toHaveLength(3)
        expect(options[1].text()).toBe('Ativo')
        expect(options[2].text()).toBe('Inativo')
    })

    it('aceita opções primitivas', () => {
        const wrapper = mountComponent({
            placeholder: '',
            options: ['Aberto', 'Fechado'],
        })

        const options = wrapper.findAll('option')

        expect(options[0].text()).toBe('Aberto')
        expect(options[1].text()).toBe('Fechado')
    })

    it('aceita optionLabel e optionValue personalizados', () => {
        const wrapper = mountComponent({
            placeholder: '',
            optionLabel: 'name',
            optionValue: 'id',
            options: [
                {
                    id: 10,
                    name: 'Administrador',
                },
                {
                    id: 20,
                    name: 'Operador',
                },
            ],
        })

        const options = wrapper.findAll('option')

        expect(options[0].text()).toBe('Administrador')
        expect(options[0].attributes('value')).toBe('10')
        expect(options[1].text()).toBe('Operador')
        expect(options[1].attributes('value')).toBe('20')
    })

    it('preserva valores numéricos', async () => {
        const wrapper = mountComponent()

        await wrapper.get('select').setValue('2')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates).toBeTruthy()
        expect(updates.at(-1)).toEqual([2])
    })

    it('preserva valores string', async () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Administrador',
                    value: 'admin',
                },
                {
                    label: 'Usuário',
                    value: 'user',
                },
            ],
        })

        await wrapper.get('select').setValue('user')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual(['user'])
    })

    it('preserva valores booleanos', async () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Sim',
                    value: true,
                },
                {
                    label: 'Não',
                    value: false,
                },
            ],
        })

        await wrapper.get('select').setValue('false')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([false])
    })

    it('preserva valores de opções primitivas', async () => {
        const wrapper = mountComponent({
            placeholder: '',
            options: ['Aberto', 'Fechado'],
        })

        await wrapper.get('select').setValue('Fechado')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual(['Fechado'])
    })

    it('emite null quando a seleção não corresponde às opções', async () => {
        const wrapper = mountComponent()

        const select = wrapper.get('select')

        select.element.selectedIndex = 0
        await select.trigger('change')

        const updates = wrapper.emitted('update:modelValue')

        expect(updates.at(-1)).toEqual([null])
    })

    it('encaminha disabled, required e autofocus', () => {
        const wrapper = mountComponent({
            disabled: true,
            required: true,
            autofocus: true,
        })

        const select = wrapper.get('select')

        expect(select.attributes()).toHaveProperty('disabled')
        expect(select.attributes()).toHaveProperty('required')
        expect(select.attributes()).toHaveProperty('autofocus')
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const select = wrapper.get('select')

        await select.trigger('focus')
        await select.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })
})
