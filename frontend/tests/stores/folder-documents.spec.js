import { beforeEach, describe, expect, it, vi } from 'vitest'

import { createPinia, setActivePinia } from 'pinia'

import { useFolderDocumentsStore } from '@/stores/folder-documents.js'

vi.mock('@/api/folder-documents.js', () => ({
    listFolderDocuments: vi.fn(),
    uploadFolderDocument: vi.fn(),
    downloadFolderDocument: vi.fn(),
    deleteFolderDocument: vi.fn(),
}))

import {
    deleteFolderDocument,
    downloadFolderDocument,
    listFolderDocuments,
    uploadFolderDocument,
} from '@/api/folder-documents.js'

describe('folder documents store', () => {
    beforeEach(() => {
        setActivePinia(createPinia())

        vi.clearAllMocks()
    })

    it('inicia com estado vazio', () => {
        const store = useFolderDocumentsStore()

        expect(store.documents).toEqual([])
        expect(store.count).toBe(0)
    })

    it('fetchDocuments popula coleção', async () => {
        listFolderDocuments.mockResolvedValue({
            data: [
                {
                    id: 1,
                    folder_id: 10,
                    name: 'Petição inicial',
                    original_name: 'peticao-inicial.pdf',
                    mime_type: 'application/pdf',
                    size: 2048,
                    description: 'Petição protocolada.',
                },
                {
                    id: 2,
                    folder_id: 10,
                    name: 'Contrato',
                    original_name: 'contrato.pdf',
                    mime_type: 'application/pdf',
                    size: 4096,
                    description: null,
                },
            ],
        })

        const store = useFolderDocumentsStore()

        const result = await store.fetchDocuments(10)

        expect(listFolderDocuments).toHaveBeenCalledTimes(1)

        expect(listFolderDocuments).toHaveBeenCalledWith(10)

        expect(store.documents).toHaveLength(2)

        expect(store.count).toBe(2)

        expect(result).toEqual(store.documents)
    })

    it('fetchDocuments usa array vazio quando resposta não é array', async () => {
        listFolderDocuments.mockResolvedValue({
            data: null,
        })

        const store = useFolderDocumentsStore()

        await store.fetchDocuments(10)

        expect(store.documents).toEqual([])
        expect(store.count).toBe(0)
    })

    it('uploadDocument envia FormData e adiciona documento à coleção', async () => {
        const created = {
            id: 3,
            folder_id: 10,
            name: 'Contestação',
            original_name: 'contestacao.pdf',
            mime_type: 'application/pdf',
            size: 8192,
            description: 'Contestação apresentada.',
        }

        uploadFolderDocument.mockResolvedValue({
            data: created,
        })

        const store = useFolderDocumentsStore()

        const payload = new FormData()

        payload.append('name', 'Contestação')

        payload.append('description', 'Contestação apresentada.')

        const file = new File(['conteúdo'], 'contestacao.pdf', {
            type: 'application/pdf',
        })

        payload.append('file', file)

        const result = await store.uploadDocument(10, payload)

        expect(uploadFolderDocument).toHaveBeenCalledTimes(1)

        expect(uploadFolderDocument).toHaveBeenCalledWith(10, payload)

        expect(store.documents).toEqual([created])

        expect(store.count).toBe(1)

        expect(result).toEqual(created)
    })

    it('uploadDocument preserva documentos já carregados', async () => {
        const store = useFolderDocumentsStore()

        store.documents = [
            {
                id: 1,
                folder_id: 10,
                name: 'Documento existente',
            },
        ]

        uploadFolderDocument.mockResolvedValue({
            data: {
                id: 2,
                folder_id: 10,
                name: 'Novo documento',
            },
        })

        const payload = new FormData()

        await store.uploadDocument(10, payload)

        expect(store.documents).toEqual([
            {
                id: 1,
                folder_id: 10,
                name: 'Documento existente',
            },
            {
                id: 2,
                folder_id: 10,
                name: 'Novo documento',
            },
        ])

        expect(store.count).toBe(2)
    })

    it('downloadDocument solicita download do documento', async () => {
        const blob = new Blob(['conteúdo do documento'], {
            type: 'application/pdf',
        })

        downloadFolderDocument.mockResolvedValue({
            data: blob,
        })

        const store = useFolderDocumentsStore()

        const result = await store.downloadDocument(10, 20)

        expect(downloadFolderDocument).toHaveBeenCalledTimes(1)

        expect(downloadFolderDocument).toHaveBeenCalledWith(10, 20)

        expect(result).toBe(blob)
    })

    it('removeDocument exclui documento da coleção', async () => {
        deleteFolderDocument.mockResolvedValue({
            data: null,
        })

        const store = useFolderDocumentsStore()

        store.documents = [
            {
                id: 20,
                folder_id: 10,
                name: 'Documento A',
            },
            {
                id: 21,
                folder_id: 10,
                name: 'Documento B',
            },
        ]

        await store.removeDocument(10, 20)

        expect(deleteFolderDocument).toHaveBeenCalledTimes(1)

        expect(deleteFolderDocument).toHaveBeenCalledWith(10, 20)

        expect(store.documents).toEqual([
            {
                id: 21,
                folder_id: 10,
                name: 'Documento B',
            },
        ])

        expect(store.count).toBe(1)
    })

    it('removeDocument aceita id string', async () => {
        deleteFolderDocument.mockResolvedValue({
            data: null,
        })

        const store = useFolderDocumentsStore()

        store.documents = [
            {
                id: 20,
                folder_id: 10,
                name: 'Documento A',
            },
            {
                id: 21,
                folder_id: 10,
                name: 'Documento B',
            },
        ]

        await store.removeDocument(10, '20')

        expect(store.documents).toEqual([
            {
                id: 21,
                folder_id: 10,
                name: 'Documento B',
            },
        ])
    })

    it('removeDocument preserva coleção quando id não existe', async () => {
        deleteFolderDocument.mockResolvedValue({
            data: null,
        })

        const store = useFolderDocumentsStore()

        store.documents = [
            {
                id: 20,
                folder_id: 10,
                name: 'Documento A',
            },
        ]

        await store.removeDocument(10, 999)

        expect(store.documents).toEqual([
            {
                id: 20,
                folder_id: 10,
                name: 'Documento A',
            },
        ])
    })

    it('clear limpa documentos', () => {
        const store = useFolderDocumentsStore()

        store.documents = [
            {
                id: 20,
                folder_id: 10,
                name: 'Documento A',
            },
            {
                id: 21,
                folder_id: 10,
                name: 'Documento B',
            },
        ]

        store.clear()

        expect(store.documents).toEqual([])
        expect(store.count).toBe(0)
    })
})
