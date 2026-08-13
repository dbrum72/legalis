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

