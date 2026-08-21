import { describe, expect, it } from 'vitest'

import fs from 'node:fs'

import path from 'node:path'

function read(relativePath) {
    return fs.readFileSync(path.resolve(process.cwd(), relativePath), 'utf8')
}

describe('tema Terra Solar', () => {
    it('define escala primitiva de verde musgo', () => {
        const source = read('src/assets/styles/tokens.css')

        expect(source).toContain('--palette-moss-200:')

        expect(source).toContain('--palette-moss-700:')

        expect(source).toContain('--palette-moss-800:')
    })

    it('define verde musgo como marca institucional secundaria', () => {
        const source = read('src/assets/styles/themes/terra-solar.css')

        expect(source).toContain('--color-brand-secondary: var(--palette-moss-700);')

        expect(source).toContain('--color-brand-secondary-hover:')

        expect(source).toContain('--color-brand-secondary-active:')

        expect(source).toContain('--color-on-brand-secondary:')
    })

    it('mantem marrom como marca institucional primaria', () => {
        const source = read('src/assets/styles/themes/terra-solar.css')

        expect(source).toContain('--color-brand: var(--palette-earth-700);')
    })

    it('mantem laranja como destaque principal', () => {
        const source = read('src/assets/styles/themes/terra-solar.css')

        expect(source).toContain('--color-highlight: var(--palette-orange-500);')
    })

    it('mantem mint como accent de apoio', () => {
        const source = read('src/assets/styles/themes/terra-solar.css')

        expect(source).toContain('--color-accent: var(--palette-mint-500);')
    })

    it('define superficie suave para marca secundaria', () => {
        const source = read('src/assets/styles/themes/terra-solar.css')

        expect(source).toContain('--color-surface-secondary-soft: var(--palette-moss-200);')
    })

    it('define superficie suave para destaque laranja', () => {
        const source = read('src/assets/styles/themes/terra-solar.css')

        expect(source).toContain('--color-surface-highlight-soft:')
    })

    it('define superficie suave para apoio ambar', () => {
        const source = read('src/assets/styles/themes/terra-solar.css')

        expect(source).toContain('--color-surface-warning-soft:')
    })
})
