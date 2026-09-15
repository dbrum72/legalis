import { describe, expect, it } from 'vitest'

import { mount } from '@vue/test-utils'

import AppConfirmDialog from '@/components/ui/AppConfirmDialog/index.vue'

function mountComponent(props = {}) {
    return mount(AppConfirmDialog, {
        attachTo: document.body,

        props: {
            open: true,
            message: 'Deseja continuar?',
            ...props,
        },
    })
}

describe('AppConfirmDialog', () => {
    it('mantém foco na confirmação, permite Escape e devolve foco ao acionador', async () => {
        const trigger = document.createElement('button')
        document.body.appendChild(trigger)
        trigger.focus()
        const wrapper = mountComponent()
        await wrapper.vm.$nextTick()
        const buttons = document.querySelectorAll('.app-confirm-dialog button')
        expect(document.activeElement).toBe(buttons[0])
        buttons[buttons.length - 1].focus()
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', cancelable: true }))
        expect(document.activeElement).toBe(buttons[0])
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
        expect(wrapper.emitted('cancel')).toHaveLength(1)
        await wrapper.setProps({ open: false })
        await wrapper.vm.$nextTick()
        expect(document.activeElement).toBe(trigger)
        wrapper.unmount()
        trigger.remove()
    })

    it('não renderiza quando fechado', () => {
        const wrapper = mountComponent({
            open: false,
        })

        expect(document.querySelector('.app-confirm-dialog')).toBeNull()

        wrapper.unmount()
    })

    it('renderiza quando aberto', () => {
        const wrapper = mountComponent()

        expect(document.querySelector('.app-confirm-dialog')).not.toBeNull()

        wrapper.unmount()
    })

    it('renderiza título e mensagem', () => {
        const wrapper = mountComponent({
            title: 'Excluir cliente',
            message: 'Confirma a exclusão?',
        })

        expect(document.body.textContent).toContain('Excluir cliente')

        expect(document.body.textContent).toContain('Confirma a exclusão?')

        wrapper.unmount()
    })

    it('emite confirm', async () => {
        const wrapper = mountComponent({
            confirmLabel: 'Excluir',
        })

        const buttons = document.querySelectorAll('button')

        const confirmButton = Array.from(buttons).find(
            (button) => button.textContent.trim() === 'Excluir',
        )

        confirmButton.click()

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('confirm')).toHaveLength(1)

        wrapper.unmount()
    })

    it('emite cancel', async () => {
        const wrapper = mountComponent()

        const buttons = document.querySelectorAll('button')

        const cancelButton = Array.from(buttons).find(
            (button) => button.textContent.trim() === 'Cancelar',
        )

        cancelButton.click()

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('cancel')).toHaveLength(1)

        wrapper.unmount()
    })

    it('não permite ações durante loading', async () => {
        const wrapper = mountComponent({
            loading: true,
        })

        const buttons = document.querySelectorAll('button')

        buttons.forEach((button) => button.click())

        await wrapper.vm.$nextTick()

        expect(wrapper.emitted('confirm')).toBeUndefined()

        expect(wrapper.emitted('cancel')).toBeUndefined()

        wrapper.unmount()
    })
})
