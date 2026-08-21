import { describe, expect, it } from 'vitest'

import fs from 'node:fs'

import path from 'node:path'

function read(relativePath) {
    return fs.readFileSync(path.resolve(process.cwd(), relativePath), 'utf8')
}

describe('design system da area publica', () => {
    it('landing nao define uma paleta paralela ao tema global', () => {
        const source = read('src/views/public/LandingPage.vue')

        expect(source).not.toMatch(
            /--landing-(ink|muted|green|green-deep|green-soft|olive|orange|orange-soft|beige|beige-light|border)\s*:/,
        )
    })

    it('landing utiliza tokens semanticos do design system', () => {
        const source = read('src/views/public/LandingPage.vue')

        expect(source).toContain('var(--color-brand)')

        expect(source).toContain('var(--color-highlight)')

        expect(source).toContain('var(--color-page)')

        expect(source).toContain('var(--color-surface)')

        expect(source).toContain('var(--color-text)')

        expect(source).toContain('var(--color-text-muted)')

        expect(source).toContain('var(--color-border)')
    })

    it('header publico nao depende de tokens semanticos inexistentes', () => {
        const source = read('src/components/public/PublicHeader.vue')

        expect(source).not.toContain('var(--color-background')

        expect(source).not.toContain('var(--color-primary')
    })

    it('layout publico nao depende de token de background inexistente', () => {
        const source = read('src/layouts/PublicLayout.vue')

        expect(source).not.toContain('var(--color-background')
    })

    it('auth shell nao depende de token primary inexistente', () => {
        const source = read('src/components/public/AuthShell.vue')

        expect(source).not.toContain('var(--color-primary')
    })
})
