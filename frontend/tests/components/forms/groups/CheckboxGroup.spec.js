import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import CheckboxGroup from '@/components/forms/groups/CheckboxGroup/index.vue'

describe('CheckboxGroup', () => {
    function mountComponent(props = {}) {
        return mount(CheckboxGroup, {
            props: {
                modelValue: [],
                id: 'permissions',
                name: 'permissions',
                label: 'Permissões',
                options: [
                    {
                        label: 'Leitura',
                        value: 'read',
                    },
                    {
                        label: 'Escrita',
                        value: 'write',
                    },
                ],
                ...props,
            },
        })
    }

    it('renderiza fieldset e legend', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('fieldset').exists()).toBe(true)
        expect(wrapper.get('legend').text()).toBe('Permissões')
    })

    it('não renderiza legend quando label está vazia', () => {
        const wrapper = mountComponent({
            label: '',
        })

        expect(wrapper.find('legend').exists()).toBe(false)
    })

    it('renderiza todas as opções', () => {
        const wrapper = mountComponent()

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes).toHaveLength(2)
        expect(wrapper.text()).toContain('Leitura')
        expect(wrapper.text()).toContain('Escrita')
    })

    it('compartilha o mesmo name entre as opções', () => {
        const wrapper = mountComponent()

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes[0].attributes('name')).toBe('permissions')

        expect(checkboxes[1].attributes('name')).toBe('permissions')
    })

    it('gera ids previsíveis para as opções', () => {
        const wrapper = mountComponent()

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes[0].attributes('id')).toBe('permissions-option-0')

        expect(checkboxes[1].attributes('id')).toBe('permissions-option-1')
    })

    it('reflete modelValue nas opções selecionadas', () => {
        const wrapper = mountComponent({
            modelValue: ['write'],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes[0].element.checked).toBe(false)
        expect(checkboxes[1].element.checked).toBe(true)
    })

    it('adiciona uma opção ao selecionar', async () => {
        const wrapper = mountComponent({
            modelValue: ['read'],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        await checkboxes[1].setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[['read', 'write']]])
    })

    it('remove uma opção ao desmarcar', async () => {
        const wrapper = mountComponent({
            modelValue: ['read', 'write'],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        await checkboxes[0].setValue(false)

        expect(wrapper.emitted('update:modelValue')).toEqual([[['write']]])
    })

    it('não duplica valor já selecionado', async () => {
        const wrapper = mountComponent({
            modelValue: ['read'],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        await checkboxes[0].setValue(true)

        expect(wrapper.emitted('update:modelValue')).toBeUndefined()
    })

    it('preserva valores numéricos', async () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Um',
                    value: 1,
                },
                {
                    label: 'Dois',
                    value: 2,
                },
            ],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        await checkboxes[1].setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[[2]]])
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

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        await checkboxes[1].setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[[false]]])
    })

    it('diferencia number de string', () => {
        const wrapper = mountComponent({
            modelValue: [1],
            options: [
                {
                    label: 'Número',
                    value: 1,
                },
                {
                    label: 'String',
                    value: '1',
                },
            ],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes[0].element.checked).toBe(true)
        expect(checkboxes[1].element.checked).toBe(false)
    })

    it('aceita opções primitivas', () => {
        const wrapper = mountComponent({
            options: ['Leitura', 'Escrita'],
        })

        expect(wrapper.text()).toContain('Leitura')
        expect(wrapper.text()).toContain('Escrita')
    })

    it('aceita optionLabel e optionValue personalizados', async () => {
        const wrapper = mountComponent({
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

        expect(wrapper.text()).toContain('Administrador')

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        await checkboxes[1].setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[[20]]])
    })

    it('desabilita todas as opções quando disabled', () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes.every((checkbox) => checkbox.attributes('disabled') !== undefined)).toBe(
            true,
        )

        expect(wrapper.get('fieldset').attributes()).toHaveProperty('disabled')
    })

    it('desabilita uma opção individualmente', () => {
        const wrapper = mountComponent({
            options: [
                {
                    label: 'Disponível',
                    value: 'A',
                },
                {
                    label: 'Indisponível',
                    value: 'B',
                    disabled: true,
                },
            ],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes[0].attributes('disabled')).toBeUndefined()

        expect(checkboxes[1].attributes()).toHaveProperty('disabled')
    })

    it('aceita optionDisabled personalizado', () => {
        const wrapper = mountComponent({
            optionDisabled: 'blocked',
            options: [
                {
                    label: 'Livre',
                    value: 1,
                    blocked: false,
                },
                {
                    label: 'Bloqueada',
                    value: 2,
                    blocked: true,
                },
            ],
        })

        const checkboxes = wrapper.findAll('input[type="checkbox"]')

        expect(checkboxes[0].attributes('disabled')).toBeUndefined()

        expect(checkboxes[1].attributes()).toHaveProperty('disabled')
    })

    it('renderiza indicador obrigatório apenas no legend', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.findAll('.checkbox-group__required')).toHaveLength(1)

        expect(wrapper.findAll('.app-checkbox__required')).toHaveLength(0)
    })

    it('marca o grupo como obrigatório', () => {
        const wrapper = mountComponent({
            required: true,
        })

        expect(wrapper.get('[role="group"]').attributes('aria-required')).toBe('true')
    })

    it('renderiza hint quando não há erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma ou mais opções.',
        })

        const hint = wrapper.get('.checkbox-group__hint')

        expect(hint.text()).toBe('Selecione uma ou mais opções.')

        expect(hint.attributes('id')).toBe('permissions-hint')
    })

    it('renderiza mensagem de erro', () => {
        const wrapper = mountComponent({
            error: 'Selecione ao menos uma opção.',
        })

        const error = wrapper.get('.checkbox-group__error')

        expect(error.text()).toBe('Selecione ao menos uma opção.')

        expect(error.attributes('id')).toBe('permissions-error')
    })

    it('oculta hint quando existe erro', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma ou mais opções.',
            error: 'Selecione ao menos uma opção.',
        })

        expect(wrapper.find('.checkbox-group__hint').exists()).toBe(false)

        expect(wrapper.text()).toContain('Selecione ao menos uma opção.')
    })

    it('associa hint ao fieldset', () => {
        const wrapper = mountComponent({
            hint: 'Selecione uma ou mais opções.',
        })

        expect(wrapper.get('fieldset').attributes('aria-describedby')).toBe('permissions-hint')
    })

    it('associa erro ao fieldset e marca como inválido', () => {
        const wrapper = mountComponent({
            error: 'Selecione ao menos uma opção.',
        })

        const fieldset = wrapper.get('fieldset')

        expect(fieldset.attributes('aria-describedby')).toBe('permissions-error')

        expect(fieldset.attributes('aria-invalid')).toBe('true')
    })

    it('aplica orientação vertical por padrão', () => {
        const wrapper = mountComponent()

        expect(wrapper.get('fieldset').classes()).toContain('checkbox-group--vertical')
    })

    it('aplica orientação horizontal', () => {
        const wrapper = mountComponent({
            orientation: 'horizontal',
        })

        expect(wrapper.get('fieldset').classes()).toContain('checkbox-group--horizontal')
    })

    it('aplica classes de estado', () => {
        const wrapper = mountComponent({
            disabled: true,
            error: 'Selecione ao menos uma opção.',
        })

        const classes = wrapper.get('fieldset').classes()

        expect(classes).toContain('checkbox-group--disabled')

        expect(classes).toContain('checkbox-group--invalid')
    })

    it('emite focus e blur das opções', async () => {
        const wrapper = mountComponent()

        const checkbox = wrapper.findAll('input[type="checkbox"]')[0]

        await checkbox.trigger('focus')
        await checkbox.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })

    it('não gera ids descritivos sem id', () => {
        const wrapper = mountComponent({
            id: undefined,
            hint: 'Selecione uma ou mais opções.',
        })

        expect(wrapper.get('.checkbox-group__hint').attributes('id')).toBeUndefined()

        expect(wrapper.get('fieldset').attributes('aria-describedby')).toBeUndefined()
    })
})
