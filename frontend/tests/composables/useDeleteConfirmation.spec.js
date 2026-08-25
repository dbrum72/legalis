import { describe, expect, it } from 'vitest'

import { useDeleteConfirmation } from '@/composables/useDeleteConfirmation.js'

describe('useDeleteConfirmation', () => {
    it('inicia sem item selecionado', () => {
        const { itemToDelete } = useDeleteConfirmation()

        expect(itemToDelete.value).toBeNull()
    })

    it('inicia sem exclusão em andamento', () => {
        const { deleting } = useDeleteConfirmation()

        expect(deleting.value).toBe(false)
    })

    it('seleciona o item para exclusão', () => {
        const { itemToDelete, requestDelete } = useDeleteConfirmation()

        const item = {
            id: 1,
            name: 'Item',
        }

        requestDelete(item)

        expect(itemToDelete.value).toStrictEqual(item)
    })

    it('cancela a exclusão quando não está processando', () => {
        const { itemToDelete, requestDelete, cancelDelete } = useDeleteConfirmation()

        requestDelete({
            id: 1,
        })

        cancelDelete()

        expect(itemToDelete.value).toBeNull()
    })

    it('não cancela enquanto a exclusão está processando', () => {
        const { itemToDelete, deleting, requestDelete, cancelDelete } = useDeleteConfirmation()

        const item = {
            id: 1,
        }

        requestDelete(item)

        deleting.value = true

        cancelDelete()

        expect(itemToDelete.value).toStrictEqual(item)
    })

    it('limpa o item independentemente do estado de processamento', () => {
        const { itemToDelete, deleting, requestDelete, clearDelete } = useDeleteConfirmation()

        requestDelete({
            id: 1,
        })

        deleting.value = true

        clearDelete()

        expect(itemToDelete.value).toBeNull()
    })

    it('permite selecionar outro item', () => {
        const { itemToDelete, requestDelete } = useDeleteConfirmation()

        const first = {
            id: 1,
        }

        const second = {
            id: 2,
        }

        requestDelete(first)
        requestDelete(second)

        expect(itemToDelete.value).toStrictEqual(second)
    })
})
