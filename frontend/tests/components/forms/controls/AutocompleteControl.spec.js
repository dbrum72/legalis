import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import AutocompleteControl from '@/components/forms/controls/AutocompleteControl/index.vue'

describe('AutocompleteControl', () => {
    function mountComponent(props = {}) {
        return mount(AutocompleteControl, {
            props: {
                modelValue: null,
                searchValue: '',
                id: 'user',
                name: 'user',
                placeholder: 'Digite para pesquisar...',
                options: [
                    {
                        label: 'Administrador',
                        value: 10,
                    },
                    {
                        label: 'Operador',
                        value: 20,
                    },
                    {
                        label: 'Convidado',
                        value: 30,
                    },
                ],
                ...props,
            },
        })
    }

    it('renderiza o input como combobox', () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        expect(input.attributes('id')).toBe('user')
        expect(input.attributes('name')).toBe('user')
        expect(input.attributes('type')).toBe('text')
        expect(input.attributes('role')).toBe('combobox')
        expect(input.attributes('aria-expanded')).toBe('false')
    })

    it('encaminha placeholder e autocomplete', () => {
        const wrapper = mountComponent({
            autocomplete: 'off',
        })

        const input = wrapper.get('input')

        expect(input.attributes('placeholder')).toBe('Digite para pesquisar...')

        expect(input.attributes('autocomplete')).toBe('off')
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

    it('não abre a lista quando está desabilitado', async () => {
        const wrapper = mountComponent({
            disabled: true,
        })

        await wrapper.get('input').trigger('focus')

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false)

        expect(wrapper.emitted('open')).toBeUndefined()
    })

    it('abre a lista ao receber foco por padrão', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')

        expect(wrapper.find('[role="listbox"]').exists()).toBe(true)

        expect(input.attributes('aria-expanded')).toBe('true')

        expect(wrapper.emitted('open')).toHaveLength(1)
    })

    it('não abre ao receber foco quando openOnFocus é false', async () => {
        const wrapper = mountComponent({
            openOnFocus: false,
        })

        await wrapper.get('input').trigger('focus')

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false)

        expect(wrapper.emitted('focus')).toHaveLength(1)
    })

    it('abre ao digitar mesmo quando openOnFocus é false', async () => {
        const wrapper = mountComponent({
            openOnFocus: false,
        })

        await wrapper.get('input').setValue('ope')

        expect(wrapper.find('[role="listbox"]').exists()).toBe(true)

        expect(wrapper.emitted('open')).toHaveLength(1)
    })

    it('emite update:searchValue durante a digitação', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').setValue('ope')

        const updates = wrapper.emitted('update:searchValue')

        expect(updates).toBeTruthy()
        expect(updates.at(-1)).toEqual(['ope'])
    })

    it('renderiza todas as opções quando a busca está vazia', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        expect(options).toHaveLength(3)
        expect(options[0].text()).toBe('Administrador')
        expect(options[1].text()).toBe('Operador')
        expect(options[2].text()).toBe('Convidado')
    })

    it('filtra opções conforme searchValue', async () => {
        const wrapper = mountComponent({
            searchValue: 'ope',
        })

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        expect(options).toHaveLength(1)
        expect(options[0].text()).toBe('Operador')
    })

    it('exibe mensagem quando não há resultados', async () => {
        const wrapper = mountComponent({
            searchValue: 'inexistente',
        })

        await wrapper.get('input').trigger('focus')

        const empty = wrapper.get('.autocomplete-control__empty')

        expect(empty.text()).toBe('Nenhum resultado encontrado.')

        expect(empty.attributes('aria-disabled')).toBe('true')
    })

    it('permite personalizar a mensagem sem resultados', async () => {
        const wrapper = mountComponent({
            searchValue: 'inexistente',
            noResultsText: 'Nenhum usuário localizado.',
        })

        await wrapper.get('input').trigger('focus')

        expect(wrapper.text()).toContain('Nenhum usuário localizado.')
    })

    it('respeita minSearchLength', async () => {
        const wrapper = mountComponent({
            searchValue: 'a',
            minSearchLength: 2,
        })

        await wrapper.get('input').trigger('focus')

        expect(wrapper.findAll('.autocomplete-control__option')).toHaveLength(0)
    })

    it('aceita optionLabel e optionValue personalizados', async () => {
        const wrapper = mountComponent({
            optionLabel: 'name',
            optionValue: 'id',
            options: [
                {
                    id: 100,
                    name: 'Ana',
                },
                {
                    id: 200,
                    name: 'Carlos',
                },
            ],
        })

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        expect(options[0].text()).toBe('Ana')
        expect(options[1].text()).toBe('Carlos')

        await options[1].trigger('mousedown')

        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([200])

        expect(wrapper.emitted('update:searchValue').at(-1)).toEqual(['Carlos'])
    })

    it('aceita opções primitivas', async () => {
        const wrapper = mountComponent({
            options: ['Aberto', 'Fechado'],
        })

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[1].trigger('mousedown')

        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['Fechado'])
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

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[1].trigger('mousedown')

        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([false])
    })

    it('seleciona uma opção com o mouse', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[1].trigger('mousedown')

        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([20])

        expect(wrapper.emitted('update:searchValue').at(-1)).toEqual(['Operador'])
    })

    it('fecha a lista após selecionar uma opção', async () => {
        const wrapper = mountComponent()

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        await options[0].trigger('mousedown')

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false)

        expect(wrapper.emitted('close')).toHaveLength(1)
    })

    it('navega pelas opções com ArrowDown', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'ArrowDown',
        })

        expect(input.attributes('aria-activedescendant')).toBe('user-option-1')
    })

    it('navega pelas opções com ArrowUp', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'ArrowUp',
        })

        expect(input.attributes('aria-activedescendant')).toBe('user-option-2')
    })

    it('seleciona a opção ativa com Enter', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'ArrowDown',
        })
        await input.trigger('keydown', {
            key: 'Enter',
        })

        expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([20])

        expect(wrapper.emitted('update:searchValue').at(-1)).toEqual(['Operador'])
    })

    it('fecha a lista com Escape', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('keydown', {
            key: 'Escape',
        })

        expect(wrapper.find('[role="listbox"]').exists()).toBe(false)

        expect(wrapper.emitted('close')).toHaveLength(1)
    })

    it('associa o combobox à listbox', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')

        expect(input.attributes('aria-controls')).toBe('user-listbox')

        expect(wrapper.get('[role="listbox"]').attributes('id')).toBe('user-listbox')
    })

    it('marca a opção selecionada com aria-selected', async () => {
        const wrapper = mountComponent({
            modelValue: 20,
        })

        await wrapper.get('input').trigger('focus')

        const options = wrapper.findAll('.autocomplete-control__option')

        expect(options[0].attributes('aria-selected')).toBe('false')

        expect(options[1].attributes('aria-selected')).toBe('true')
    })

    it('emite eventos de foco e blur', async () => {
        const wrapper = mountComponent()

        const input = wrapper.get('input')

        await input.trigger('focus')
        await input.trigger('blur')

        expect(wrapper.emitted('focus')).toHaveLength(1)
        expect(wrapper.emitted('blur')).toHaveLength(1)
    })
})
