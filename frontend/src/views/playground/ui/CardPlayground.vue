<template>
    <PlaygroundSection title="AppCard"
        description="Container estrutural do Design System com header, body, footer e variantes visuais.">
        <PlaygroundExample title="Básico" :code="basicCode">
            <AppCard>
                Conteúdo do card.
            </AppCard>
        </PlaygroundExample>

        <PlaygroundExample title="Com título" :code="titleCode">
            <AppCard title="Processo">
                Informações principais do processo.
            </AppCard>
        </PlaygroundExample>

        <PlaygroundExample title="Accent" :code="accentCode">
            <AppCard title="Informação em destaque" variant="accent">
                Conteúdo com destaque pela cor de acento.
            </AppCard>
        </PlaygroundExample>

        <PlaygroundExample title="Highlight" :code="highlightCode">
            <AppCard title="Atenção" variant="highlight">
                Conteúdo com destaque visual mais forte.
            </AppCard>
        </PlaygroundExample>

        <PlaygroundExample title="Header customizado" :code="customHeaderCode">
            <AppCard>
                <template #header>
                    <div class="card-playground__header">
                        <strong>Cliente</strong>

                        <AppButton size="sm" variant="ghost">
                            Editar
                        </AppButton>
                    </div>
                </template>

                Dados do cliente.
            </AppCard>
        </PlaygroundExample>

        <PlaygroundExample title="Com footer" :code="footerCode">
            <AppCard title="Documento">
                Informações do documento.

                <template #footer>
                    <div class="card-playground__actions">
                        <AppButton variant="outline" size="sm">
                            Cancelar
                        </AppButton>

                        <AppButton size="sm">
                            Salvar
                        </AppButton>
                    </div>
                </template>
            </AppCard>
        </PlaygroundExample>

        <PlaygroundExample title="Elemento semântico" :code="articleCode">
            <AppCard as="article" title="Publicação">
                Este card é renderizado como
                <code>&lt;article&gt;</code>.
            </AppCard>
        </PlaygroundExample>

        <PlaygroundExample title="Estrutura completa" :code="completeCode">
            <AppCard title="Resumo do processo" variant="accent">
                <p>
                    Processo nº 0000000-00.0000.0.00.0000
                </p>

                <p>
                    Situação: em andamento.
                </p>

                <template #footer>
                    <div class="card-playground__actions">
                        <AppButton variant="ghost" size="sm">
                            Detalhes
                        </AppButton>

                        <AppButton icon="arrow-right" icon-position="end" size="sm">
                            Abrir
                        </AppButton>
                    </div>
                </template>
            </AppCard>
        </PlaygroundExample>
    </PlaygroundSection>
</template>

<script setup>
import {
    AppButton,
    AppCard,
} from '@/components/ui'

import {
    PlaygroundExample,
    PlaygroundSection,
} from '@/playground/components'

const basicCode = `<AppCard>
  Conteúdo do card.
</AppCard>`

const titleCode = `<AppCard title="Processo">
  Informações principais do processo.
</AppCard>`

const accentCode = `<AppCard
  title="Informação em destaque"
  variant="accent"
>
  Conteúdo com destaque pela cor de acento.
</AppCard>`

const highlightCode = `<AppCard
  title="Atenção"
  variant="highlight"
>
  Conteúdo com destaque visual mais forte.
</AppCard>`

const customHeaderCode = `<AppCard>
  <template #header>
    <div>
      <strong>Cliente</strong>
      <AppButton size="sm" variant="ghost">
        Editar
      </AppButton>
    </div>
  </template>

  Dados do cliente.
</AppCard>`

const footerCode = `<AppCard title="Documento">
  Informações do documento.

  <template #footer>
    <AppButton variant="outline" size="sm">
      Cancelar
    </AppButton>

    <AppButton size="sm">
      Salvar
    </AppButton>
  </template>
</AppCard>`

const articleCode = `<AppCard
  as="article"
  title="Publicação"
>
  Conteúdo semântico.
</AppCard>`

const completeCode = `<AppCard
  title="Resumo do processo"
  variant="accent"
>
  Conteúdo...

  <template #footer>
    <AppButton variant="ghost" size="sm">
      Detalhes
    </AppButton>

    <AppButton
      icon="arrow-right"
      icon-position="end"
      size="sm"
    >
      Abrir
    </AppButton>
  </template>
</AppCard>`
</script>

<style scoped>
.card-playground__header,
.card-playground__actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
}

.card-playground__actions {
    justify-content: flex-end;
}
</style>