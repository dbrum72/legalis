<template>
    <PlaygroundSection title="AppButton"
        description="Botão base do Design System com variantes, tamanhos, ícones, loading e estados.">
        <PlaygroundExample title="Variantes" :code="variantsCode">
            <div class="button-playground__row">
                <AppButton variant="primary">
                    Primary
                </AppButton>

                <AppButton variant="accent">
                    Accent
                </AppButton>

                <AppButton variant="highlight">
                    Highlight
                </AppButton>

                <AppButton variant="outline">
                    Outline
                </AppButton>

                <AppButton variant="ghost">
                    Ghost
                </AppButton>
            </div>
        </PlaygroundExample>

        <PlaygroundExample title="Tamanhos" :code="sizesCode">
            <div class="button-playground__row button-playground__row--align-end">
                <AppButton size="sm">
                    Pequeno
                </AppButton>

                <AppButton size="md">
                    Médio
                </AppButton>

                <AppButton size="lg">
                    Grande
                </AppButton>
            </div>
        </PlaygroundExample>

        <PlaygroundExample title="Com ícone" :code="iconsCode">
            <div class="button-playground__row">
                <AppButton icon="save">
                    Salvar
                </AppButton>

                <AppButton variant="outline" icon="arrow-right" icon-position="end">
                    Continuar
                </AppButton>
            </div>
        </PlaygroundExample>

        <PlaygroundExample title="Somente ícone" :code="iconOnlyCode">
            <div class="button-playground__row">
                <AppButton icon="save" aria-label="Salvar" />

                <AppButton variant="ghost" icon="close" aria-label="Fechar" />
            </div>
        </PlaygroundExample>

        <PlaygroundExample title="Loading" :code="loadingCode">
            <div class="button-playground__row">
                <AppButton loading>
                    Salvando
                </AppButton>

                <AppButton variant="accent" loading>
                    Processando
                </AppButton>
            </div>
        </PlaygroundExample>

        <PlaygroundExample title="Desabilitado" :code="disabledCode">
            <div class="button-playground__row">
                <AppButton disabled>
                    Desabilitado
                </AppButton>

                <AppButton variant="outline" disabled>
                    Desabilitado
                </AppButton>
            </div>
        </PlaygroundExample>

        <PlaygroundExample title="Block" :code="blockCode">
            <AppButton block>
                Botão em largura total
            </AppButton>
        </PlaygroundExample>

        <PlaygroundExample title="Submit" :code="submitCode">
            <form @submit.prevent="handleSubmit">
                <AppButton type="submit" icon="save">
                    Salvar formulário
                </AppButton>
            </form>

            <p class="playground-value">
                Submits: {{ submitCount }}
            </p>
        </PlaygroundExample>

        <PlaygroundExample title="Evento click" :code="clickCode">
            <AppButton @click="clickCount++">
                Clique aqui
            </AppButton>

            <p class="playground-value">
                Cliques: {{ clickCount }}
            </p>
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import { ref } from 'vue'

import {
    AppButton,
} from '@/components/ui'

import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const clickCount = ref(0)
const submitCount = ref(0)

function handleSubmit() {
    submitCount.value++
}

const variantsCode = `<AppButton variant="primary">Primary</AppButton>
<AppButton variant="accent">Accent</AppButton>
<AppButton variant="highlight">Highlight</AppButton>
<AppButton variant="outline">Outline</AppButton>
<AppButton variant="ghost">Ghost</AppButton>`

const sizesCode = `<AppButton size="sm">Pequeno</AppButton>
<AppButton size="md">Médio</AppButton>
<AppButton size="lg">Grande</AppButton>`

const iconsCode = `<AppButton icon="save">
  Salvar
</AppButton>

<AppButton
  variant="outline"
  icon="arrow-right"
  icon-position="end"
>
  Continuar
</AppButton>`

const iconOnlyCode = `<AppButton
  icon="save"
  aria-label="Salvar"
/>`

const loadingCode = `<AppButton loading>
  Salvando
</AppButton>`

const disabledCode = `<AppButton disabled>
  Desabilitado
</AppButton>`

const blockCode = `<AppButton block>
  Botão em largura total
</AppButton>`

const submitCode = `<form @submit.prevent="handleSubmit">
  <AppButton
    type="submit"
    icon="save"
  >
    Salvar formulário
  </AppButton>
</form>`

const clickCode = `<AppButton @click="handleClick">
  Clique aqui
</AppButton>`
</script>

<style scoped>
.button-playground__row {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
}

.button-playground__row--align-end {
    align-items: flex-end;
}

.playground-value {
    margin-top: var(--space-2);

    color: var(--color-text-muted);
    font-size: var(--font-size-sm);
}
</style>