import { describe, expect, it } from 'vitest'

import fs from 'node:fs'
import path from 'node:path'

const source = fs.readFileSync(
    path.resolve(process.cwd(), 'src/views/agenda/AgendaPage.vue'),
    'utf8',
)

describe('Agenda integrada ao Design System', () => {
    it('nao define cor primaria local', () => {
        expect(source).not.toMatch(/--agenda-primary\s*:/)

        expect(source).not.toMatch(/--agenda-primary-soft\s*:/)
    })

    it('utiliza verde institucional do Terra Solar', () => {
        expect(source).toContain('var(--color-brand-secondary)')

        expect(source).toContain('var(--color-surface-secondary-soft)')
    })

    it('utiliza laranja institucional para compromissos', () => {
        expect(source).toContain('var(--color-highlight)')

        expect(source).toContain('var(--color-surface-highlight-soft)')
    })

    it('utiliza tokens globais para superficies texto e bordas', () => {
        expect(source).toContain('var(--color-surface)')

        expect(source).toContain('var(--color-border)')

        expect(source).toContain('var(--color-text)')

        expect(source).toContain('var(--color-text-muted)')
    })

    it('nao recria os tokens globais com fallbacks locais', () => {
        expect(source).not.toMatch(/var\(--surface,\s*#[0-9a-fA-F]{3,8}\)/)

        expect(source).not.toMatch(/var\(--text-primary,\s*#[0-9a-fA-F]{3,8}\)/)

        expect(source).not.toMatch(/var\(--text-muted,\s*#[0-9a-fA-F]{3,8}\)/)

        expect(source).not.toMatch(/var\(--primary,\s*#[0-9a-fA-F]{3,8}\)/)
    })
})
