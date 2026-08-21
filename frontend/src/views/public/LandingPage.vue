<template>
    <div class="landing-page">
        <!--
        |--------------------------------------------------------------------------
        | Hero
        |--------------------------------------------------------------------------
        -->

        <section class="landing-hero" data-testid="landing-hero">
            <div class="landing-container landing-hero__grid">
                <div class="landing-hero__content">
                    <span class="landing-eyebrow">
                        Gestão para escritórios de advocacia
                    </span>

                    <h1 class="landing-hero__title">
                        Uma gestão jurídica
                        <span>mais organizada.</span>
                    </h1>

                    <p class="landing-hero__description">
                        Centralize clientes, pastas, agenda e equipe
                        em um ambiente pensado para a rotina do
                        escritório.
                    </p>

                    <div class="landing-hero__actions">
                        <RouterLink :to="{ name: 'register' }" class="landing-button landing-button--primary"
                            data-testid="landing-primary-cta">
                            Começar agora

                            <AppIcon name="arrow-right" :size="17" decorative />
                        </RouterLink>

                        <a href="#recursos" class="landing-button landing-button--secondary"
                            data-testid="landing-secondary-cta">
                            Conhecer recursos
                        </a>
                    </div>

                    <div class="landing-hero__assurance">
                        <span class="landing-hero__assurance-mark" aria-hidden="true">
                            ✓
                        </span>

                        <span>
                            Crie sua organização e comece a estruturar
                            seu escritório.
                        </span>
                    </div>
                </div>

                <!--
                |--------------------------------------------------------------------------
                | Preview do produto
                |--------------------------------------------------------------------------
                -->

                <div class="product-preview" data-testid="landing-product-preview"
                    aria-label="Prévia da interface do Legalis">
                    <div class="product-preview__shape product-preview__shape--moss" aria-hidden="true"></div>

                    <div class="product-preview__shape product-preview__shape--orange" aria-hidden="true"></div>

                    <div class="product-preview__shape product-preview__shape--mint" aria-hidden="true"></div>

                    <div class="product-preview__window">
                        <header class="product-preview__topbar">
                            <div class="product-preview__brand">
                                <span class="product-preview__brand-mark">
                                    L
                                </span>

                                <strong>
                                    Legalis
                                </strong>
                            </div>

                            <div class="product-preview__user">
                                <span></span>
                                <span></span>
                            </div>
                        </header>

                        <div class="product-preview__body">
                            <aside class="product-preview__sidebar">
                                <span class="product-preview__nav-item product-preview__nav-item--active">
                                    <AppIcon name="dashboard" :size="11" decorative />

                                    <b>
                                        Dashboard
                                    </b>
                                </span>

                                <span class="product-preview__nav-item">
                                    <AppIcon name="user" :size="11" decorative />

                                    <b>
                                        Clientes
                                    </b>
                                </span>

                                <span class="product-preview__nav-item">
                                    <AppIcon name="scale" :size="11" decorative />

                                    <b>
                                        Pastas
                                    </b>
                                </span>

                                <span class="product-preview__nav-item">
                                    <AppIcon name="calendar" :size="11" decorative />

                                    <b>
                                        Agenda
                                    </b>
                                </span>

                                <span class="product-preview__nav-item">
                                    <AppIcon name="users" :size="11" decorative />

                                    <b>
                                        Equipe
                                    </b>
                                </span>
                            </aside>

                            <div class="product-preview__workspace">
                                <div class="product-preview__heading">
                                    <div>
                                        <span>
                                            Visão geral
                                        </span>

                                        <strong>
                                            Dashboard
                                        </strong>
                                    </div>

                                    <span class="product-preview__date">
                                        Hoje
                                    </span>
                                </div>

                                <div class="product-preview__metrics">
                                    <article class="preview-metric preview-metric--moss">
                                        <span>
                                            Clientes
                                        </span>

                                        <strong>
                                            24
                                        </strong>

                                        <small>
                                            ativos
                                        </small>
                                    </article>

                                    <article class="preview-metric preview-metric--orange">
                                        <span>
                                            Pastas
                                        </span>

                                        <strong>
                                            18
                                        </strong>

                                        <small>
                                            em andamento
                                        </small>
                                    </article>

                                    <article class="preview-metric preview-metric--mint">
                                        <span>
                                            Agenda
                                        </span>

                                        <strong>
                                            05
                                        </strong>

                                        <small>
                                            itens hoje
                                        </small>
                                    </article>
                                </div>

                                <section class="preview-activity">
                                    <header class="preview-activity__header">
                                        <div>
                                            <span>
                                                Atividade recente
                                            </span>

                                            <strong>
                                                Próximos compromissos
                                            </strong>
                                        </div>

                                        <span>
                                            Ver agenda
                                        </span>
                                    </header>

                                    <div class="preview-activity__items">
                                        <article class="preview-activity__item">
                                            <span
                                                class="preview-activity__marker preview-activity__marker--orange"></span>

                                            <div>
                                                <strong>
                                                    Prazo processual
                                                </strong>

                                                <small>
                                                    Pasta #1024
                                                </small>
                                            </div>

                                            <time>
                                                10:30
                                            </time>
                                        </article>

                                        <article class="preview-activity__item">
                                            <span
                                                class="preview-activity__marker preview-activity__marker--moss"></span>

                                            <div>
                                                <strong>
                                                    Audiência
                                                </strong>

                                                <small>
                                                    Pasta #1018
                                                </small>
                                            </div>

                                            <time>
                                                14:00
                                            </time>
                                        </article>

                                        <article class="preview-activity__item">
                                            <span
                                                class="preview-activity__marker preview-activity__marker--mint"></span>

                                            <div>
                                                <strong>
                                                    Revisar documentos
                                                </strong>

                                                <small>
                                                    Pasta #1007
                                                </small>
                                            </div>

                                            <time>
                                                16:15
                                            </time>
                                        </article>
                                    </div>
                                </section>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!--
        |--------------------------------------------------------------------------
        | Recursos
        |--------------------------------------------------------------------------
        -->

        <section id="recursos" class="landing-resources" data-testid="landing-resources">
            <div class="landing-container">
                <header class="landing-section-header">
                    <span class="landing-eyebrow">
                        Um ambiente integrado
                    </span>

                    <h2>
                        O essencial da rotina jurídica
                        em um só lugar.
                    </h2>

                    <p>
                        Menos dispersão entre ferramentas.
                        Mais contexto para conduzir o trabalho
                        do escritório.
                    </p>
                </header>

                <div class="landing-resources__grid">
                    <article class="resource-card resource-card--clients">
                        <div class="resource-card__icon" aria-hidden="true">
                            <AppIcon name="user" :size="20" decorative />
                        </div>

                        <span class="resource-card__number">
                            01
                        </span>

                        <h3>
                            Clientes
                        </h3>

                        <p>
                            Mantenha informações e relacionamentos
                            organizados para encontrar rapidamente
                            o que precisa.
                        </p>
                    </article>

                    <article class="resource-card resource-card--folders">
                        <div class="resource-card__icon" aria-hidden="true">
                            <AppIcon name="scale" :size="20" decorative />
                        </div>

                        <span class="resource-card__number">
                            02
                        </span>

                        <h3>
                            Pastas
                        </h3>

                        <p>
                            Reúna o contexto de cada demanda jurídica
                            e acompanhe sua evolução de forma
                            estruturada.
                        </p>
                    </article>

                    <article class="resource-card resource-card--agenda">
                        <div class="resource-card__icon" aria-hidden="true">
                            <AppIcon name="calendar" :size="20" decorative />
                        </div>

                        <span class="resource-card__number">
                            03
                        </span>

                        <h3>
                            Agenda
                        </h3>

                        <p>
                            Visualize tarefas, eventos e prazos
                            com clareza para organizar as prioridades
                            do dia.
                        </p>
                    </article>

                    <article class="resource-card resource-card--team">
                        <div class="resource-card__icon" aria-hidden="true">
                            <AppIcon name="users" :size="20" decorative />
                        </div>

                        <span class="resource-card__number">
                            04
                        </span>

                        <h3>
                            Equipe
                        </h3>

                        <p>
                            Organize os integrantes da organização
                            e mantenha o acesso ao sistema sob
                            controle.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!--
        |--------------------------------------------------------------------------
        | Benefícios
        |--------------------------------------------------------------------------
        -->

        <section id="beneficios" class="landing-benefits">
            <div class="landing-container landing-benefits__grid">
                <div class="landing-benefits__content">
                    <span class="landing-eyebrow landing-eyebrow--inverse">
                        Organização da rotina
                    </span>

                    <h2>
                        Informação jurídica com contexto,
                        não espalhada.
                    </h2>

                    <p>
                        O Legalis aproxima as informações que fazem
                        parte do mesmo trabalho. Cliente, pasta,
                        compromissos e equipe passam a integrar
                        uma rotina mais clara.
                    </p>

                    <div class="landing-benefits__list">
                        <article>
                            <span aria-hidden="true">
                                01
                            </span>

                            <div>
                                <strong>
                                    Visão centralizada
                                </strong>

                                <p>
                                    Acesse as informações essenciais
                                    sem depender de múltiplas fontes.
                                </p>
                            </div>
                        </article>

                        <article>
                            <span aria-hidden="true">
                                02
                            </span>

                            <div>
                                <strong>
                                    Prioridades visíveis
                                </strong>

                                <p>
                                    Agenda e atividades ajudam a
                                    manter o trabalho jurídico
                                    em perspectiva.
                                </p>
                            </div>
                        </article>

                        <article>
                            <span aria-hidden="true">
                                03
                            </span>

                            <div>
                                <strong>
                                    Estrutura por organização
                                </strong>

                                <p>
                                    Cada escritório trabalha dentro
                                    do seu próprio contexto
                                    organizacional.
                                </p>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="workflow-card">
                    <div class="workflow-card__header">
                        <span>
                            Fluxo de trabalho
                        </span>

                        <strong>
                            Tudo conectado
                        </strong>
                    </div>

                    <div class="workflow">
                        <article class="workflow__item">
                            <span class="workflow__number">
                                01
                            </span>

                            <div>
                                <small>
                                    Cliente
                                </small>

                                <strong>
                                    Empresa Exemplo
                                </strong>
                            </div>
                        </article>

                        <span class="workflow__connector" aria-hidden="true"></span>

                        <article class="workflow__item workflow__item--highlight">
                            <span class="workflow__number">
                                02
                            </span>

                            <div>
                                <small>
                                    Pasta
                                </small>

                                <strong>
                                    Demanda jurídica
                                </strong>
                            </div>
                        </article>

                        <span class="workflow__connector" aria-hidden="true"></span>

                        <div class="workflow__branches">
                            <article class="workflow__branch workflow__branch--deadline">
                                <small>
                                    Prazo
                                </small>

                                <strong>
                                    24 AGO
                                </strong>
                            </article>

                            <article class="workflow__branch workflow__branch--task">
                                <small>
                                    Tarefa
                                </small>

                                <strong>
                                    Revisar
                                </strong>
                            </article>

                            <article class="workflow__branch workflow__branch--event">
                                <small>
                                    Evento
                                </small>

                                <strong>
                                    Audiência
                                </strong>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!--
        |--------------------------------------------------------------------------
        | Síntese
        |--------------------------------------------------------------------------
        -->

        <section class="landing-synthesis">
            <div class="landing-container landing-synthesis__grid">
                <div class="landing-synthesis__visual">
                    <span class="landing-synthesis__circle"></span>

                    <div class="landing-synthesis__card">
                        <AppIcon name="scale" :size="26" decorative />

                        <strong>
                            Contexto jurídico
                        </strong>

                        <span>
                            cliente · pasta · agenda · equipe
                        </span>
                    </div>
                </div>

                <div class="landing-synthesis__content">
                    <span class="landing-eyebrow">
                        Trabalho conectado
                    </span>

                    <h2>
                        Cada informação no lugar certo.
                        Cada decisão com mais contexto.
                    </h2>

                    <p>
                        O Legalis foi estruturado para que a rotina
                        administrativa acompanhe a lógica do trabalho
                        jurídico, sem fragmentar informações importantes.
                    </p>

                    <div class="landing-synthesis__points">
                        <span>
                            <i></i>
                            Organização
                        </span>

                        <span>
                            <i></i>
                            Visibilidade
                        </span>

                        <span>
                            <i></i>
                            Continuidade
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!--
        |--------------------------------------------------------------------------
        | CTA final
        |--------------------------------------------------------------------------
        -->

        <section class="landing-final">
            <div class="landing-container">
                <div class="landing-final__panel">
                    <div class="landing-final__content">
                        <span class="landing-eyebrow">
                            Comece pelo essencial
                        </span>

                        <h2>
                            Seu escritório mais organizado
                            começa aqui.
                        </h2>

                        <p>
                            Crie sua conta, configure sua organização
                            e leve a rotina jurídica para um ambiente
                            estruturado.
                        </p>
                    </div>

                    <RouterLink :to="{ name: 'register' }"
                        class="landing-button landing-button--primary landing-final__button"
                        data-testid="landing-final-cta">
                        Criar minha conta

                        <AppIcon name="arrow-right" :size="17" decorative />
                    </RouterLink>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import {
    AppIcon,
} from '@/components/ui'
</script>

<style scoped>
.landing-page {
    overflow: hidden;
    color: var(--color-text);
    background: var(--color-page);
}

.landing-container {
    width: min(100% - 3rem, 75rem);
    margin-inline: auto;
}

/*
|--------------------------------------------------------------------------
| Elementos compartilhados
|--------------------------------------------------------------------------
*/

.landing-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: var(--color-brand-secondary);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-bold);
    letter-spacing: 0.09em;
    text-transform: uppercase;
}

.landing-eyebrow::before {
    width: 1.65rem;
    height: 2px;
    flex: 0 0 auto;
    content: "";
    border-radius: var(--radius-pill);
    background: var(--color-highlight);
}

.landing-eyebrow--inverse {
    color: var(--color-surface-warning-soft);
}

.landing-button {
    display: inline-flex;
    min-height: 3rem;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    padding: 0.7rem 1.35rem;
    border: var(--border-width) solid transparent;
    border-radius: var(--radius-md);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-bold);
    text-decoration: none;
    transition:
        transform var(--duration-fast) var(--ease-standard),
        background var(--duration-fast) var(--ease-standard),
        border-color var(--duration-fast) var(--ease-standard),
        box-shadow var(--duration-fast) var(--ease-standard);
}

.landing-button:hover {
    transform: translateY(-1px);
}

/* CTA principal agora visualmente verde */
.landing-button--primary {
    background:
        linear-gradient(135deg,
            var(--color-brand-secondary),
            var(--color-brand-secondary-active));
    color: var(--color-on-brand-secondary);
    box-shadow:
        0 0.8rem 1.8rem rgb(57 74 56 / 0.2);
}

.landing-button--primary:hover {
    background: var(--color-brand-secondary-active);
    color: var(--color-on-brand-secondary);
}

.landing-button--secondary {
    border-color:
        color-mix(in srgb,
            var(--color-brand) 32%,
            var(--color-border));
    background:
        color-mix(in srgb,
            var(--color-surface) 88%,
            var(--color-surface-soft));
    color: var(--color-brand);
}

.landing-button--secondary:hover {
    border-color: var(--color-brand-secondary);
    background: var(--color-surface-secondary-soft);
    color: var(--color-brand-secondary-active);
}

/*
|--------------------------------------------------------------------------
| Hero
|--------------------------------------------------------------------------
*/

.landing-hero {
    position: relative;
    padding:
        clamp(4.5rem, 8vw, 7.5rem) 0 clamp(5rem, 9vw, 8rem);

    background:
        radial-gradient(circle at 77% 15%,
            color-mix(in srgb,
                var(--color-brand-secondary) 19%,
                transparent),
            transparent 20rem),
        radial-gradient(circle at 94% 53%,
            color-mix(in srgb,
                var(--color-highlight) 16%,
                transparent),
            transparent 12rem),
        radial-gradient(circle at 4% 85%,
            color-mix(in srgb,
                var(--color-brand) 5%,
                transparent),
            transparent 18rem),
        linear-gradient(135deg,
            var(--color-page),
            color-mix(in srgb,
                var(--color-surface-secondary-soft) 18%,
                var(--color-page)));
}

.landing-hero::after {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 1px;
    content: "";
    background:
        linear-gradient(90deg,
            transparent,
            var(--color-border),
            transparent);
}

.landing-hero__grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns:
        minmax(0, 0.88fr) minmax(31rem, 1.12fr);
    align-items: center;
    gap: clamp(3.5rem, 7vw, 6.5rem);
}

.landing-hero__content {
    position: relative;
    z-index: 2;
}

.landing-hero__title {
    max-width: 10.8ch;
    margin:
        var(--space-5) 0 var(--space-6);

    color:
        color-mix(in srgb,
            var(--color-brand) 88%,
            var(--neutral-900));

    font-size:
        clamp(3rem,
            5.5vw,
            5.1rem);

    font-weight: var(--font-weight-bold);
    letter-spacing: -0.055em;
    line-height: 0.98;
}

.landing-hero__title span {
    color: var(--color-brand-secondary);
}

.landing-hero__description {
    max-width: 34rem;
    margin: 0;
    color:
        color-mix(in srgb,
            var(--color-brand) 67%,
            var(--color-text-soft));
    font-size:
        clamp(1rem,
            1.5vw,
            1.15rem);
    line-height: var(--line-height-relaxed);
}

.landing-hero__actions {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-3);
    margin-top: var(--space-8);
}

.landing-hero__assurance {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-top: var(--space-5);
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}

.landing-hero__assurance-mark {
    display: grid;
    width: 1.4rem;
    height: 1.4rem;
    flex: 0 0 auto;
    place-items: center;
    border:
        1px solid color-mix(in srgb,
            var(--color-brand-secondary) 32%,
            transparent);
    border-radius: 50%;
    background: var(--color-surface-secondary-soft);
    color: var(--color-brand-secondary-active);
    font-size: 0.68rem;
    font-weight: var(--font-weight-bold);
}

/*
|--------------------------------------------------------------------------
| Preview do produto
|--------------------------------------------------------------------------
*/

.product-preview {
    position: relative;
    isolation: isolate;
}

.product-preview__window {
    position: relative;
    z-index: 3;
    overflow: hidden;
    border:
        1px solid color-mix(in srgb,
            var(--color-brand-secondary) 24%,
            var(--color-border));
    border-radius: var(--radius-xl);
    background: var(--color-surface);
    box-shadow:
        0 2.1rem 5rem rgb(47 61 44 / 0.18);
}

.product-preview__shape {
    position: absolute;
    z-index: 1;
    pointer-events: none;
}

.product-preview__shape--moss {
    top: -3.1rem;
    left: -2.2rem;
    width: 11.5rem;
    height: 11.5rem;
    border-radius: 50%;
    background:
        color-mix(in srgb,
            var(--color-brand-secondary) 25%,
            transparent);
}

.product-preview__shape--orange {
    right: -2.6rem;
    bottom: -2.5rem;
    width: 8.5rem;
    height: 8.5rem;
    border-radius: 50%;
    background:
        color-mix(in srgb,
            var(--color-highlight) 36%,
            transparent);
}

.product-preview__shape--mint {
    right: 16%;
    top: -2rem;
    width: 4.6rem;
    height: 1rem;
    border-radius: var(--radius-pill);
    background:
        color-mix(in srgb,
            var(--color-accent) 72%,
            transparent);
    transform: rotate(-8deg);
}

.product-preview__topbar {
    min-height: 3.35rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 var(--space-5);
    border-bottom: 1px solid var(--color-divider);
    background:
        linear-gradient(90deg,
            var(--color-surface),
            color-mix(in srgb,
                var(--color-surface-warning-soft) 30%,
                var(--color-surface)));
}

.product-preview__brand,
.product-preview__user {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.product-preview__brand {
    color: var(--color-brand);
    font-size: var(--font-size-xs);
}

.product-preview__brand-mark {
    display: grid;
    width: 1.75rem;
    height: 1.75rem;
    place-items: center;
    border-radius: var(--radius-sm);
    background:
        linear-gradient(145deg,
            var(--color-brand-secondary),
            var(--color-brand-secondary-active));
    color: var(--color-on-brand-secondary);
    font-size: 0.7rem;
    font-weight: var(--font-weight-bold);
}

.product-preview__user span:first-child {
    width: 3.5rem;
    height: 0.4rem;
    border-radius: var(--radius-pill);
    background:
        color-mix(in srgb,
            var(--color-brand) 14%,
            var(--neutral-200));
}

.product-preview__user span:last-child {
    width: 1.65rem;
    height: 1.65rem;
    border:
        2px solid color-mix(in srgb,
            var(--color-highlight) 52%,
            transparent);
    border-radius: 50%;
    background: var(--color-surface-highlight-soft);
}

.product-preview__body {
    min-height: 25rem;
    display: grid;
    grid-template-columns: 7.8rem 1fr;
}

/*
|--------------------------------------------------------------------------
| Sidebar do preview — verde como protagonista
|--------------------------------------------------------------------------
*/

.product-preview__sidebar {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    padding:
        var(--space-5) var(--space-3);

    background:
        linear-gradient(165deg,
            var(--color-brand-secondary-active) 0%,
            var(--color-brand-secondary) 55%,
            color-mix(in srgb,
                var(--color-brand-secondary) 76%,
                var(--color-brand) 24%) 100%);
}

.product-preview__nav-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.64rem;
    border-radius: var(--radius-sm);

    color:
        color-mix(in srgb,
            var(--color-on-brand-secondary) 72%,
            transparent);

    transition:
        background var(--duration-fast) var(--ease-standard),
        color var(--duration-fast) var(--ease-standard);
}

.product-preview__nav-item::before {
    position: absolute;
    top: 50%;
    left: 0;
    width: 0.18rem;
    height: 0;
    content: "";
    border-radius: var(--radius-pill);
    background: var(--color-highlight);
    transform: translateY(-50%);
    transition: height var(--duration-fast) var(--ease-standard);
}

.product-preview__nav-item--active {
    background:
        linear-gradient(90deg,
            color-mix(in srgb,
                var(--color-accent) 24%,
                transparent),
            color-mix(in srgb,
                var(--color-warning) 11%,
                transparent));
    color: var(--color-on-brand-secondary);
}

.product-preview__nav-item--active::before {
    height: 62%;
}

.product-preview__nav-item:nth-child(3) {
    color:
        color-mix(in srgb,
            var(--color-surface-warning-soft) 82%,
            var(--color-on-brand-secondary));
}

.product-preview__nav-item:nth-child(4) {
    color:
        color-mix(in srgb,
            var(--color-accent) 80%,
            var(--color-on-brand-secondary));
}

.product-preview__nav-item b {
    font-size: 0.58rem;
    font-weight: var(--font-weight-semibold);
}

.product-preview__workspace {
    padding: var(--space-6);
    background:
        linear-gradient(145deg,
            color-mix(in srgb,
                var(--color-surface-secondary-soft) 16%,
                var(--color-surface-muted)),
            var(--color-page));
}

.product-preview__heading,
.preview-activity__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.product-preview__heading div,
.preview-activity__header div {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.product-preview__heading span,
.preview-activity__header span {
    color: var(--color-text-muted);
    font-size: 0.55rem;
}

.product-preview__heading strong {
    color: var(--color-brand);
    font-size: var(--font-size-md);
}

.product-preview__date {
    padding: 0.35rem 0.65rem;
    border:
        1px solid color-mix(in srgb,
            var(--color-brand-secondary) 24%,
            transparent);
    border-radius: var(--radius-pill);
    background: var(--color-surface-secondary-soft);
    color: var(--color-brand-secondary-active) !important;
    font-weight: var(--font-weight-semibold);
}

.product-preview__metrics {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-3);
    margin-top: var(--space-5);
}

.preview-metric {
    min-height: 6.2rem;
    display: flex;
    flex-direction: column;
    padding: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.preview-metric::before {
    width: 1.65rem;
    height: 0.22rem;
    margin-bottom: var(--space-3);
    content: "";
    border-radius: var(--radius-pill);
}

.preview-metric--moss {
    background:
        color-mix(in srgb,
            var(--color-surface-secondary-soft) 52%,
            var(--color-surface));
}

.preview-metric--moss::before {
    background: var(--color-brand-secondary);
}

.preview-metric--orange {
    background:
        color-mix(in srgb,
            var(--color-surface-highlight-soft) 64%,
            var(--color-surface));
}

.preview-metric--orange::before {
    background: var(--color-highlight);
}

.preview-metric--mint {
    background:
        color-mix(in srgb,
            var(--color-surface-accent) 64%,
            var(--color-surface));
}

.preview-metric--mint::before {
    background: var(--color-accent);
}

.preview-metric span,
.preview-metric small {
    color: var(--color-text-muted);
    font-size: 0.52rem;
}

.preview-metric strong {
    margin-top: auto;
    color:
        color-mix(in srgb,
            var(--color-brand) 88%,
            var(--neutral-900));
    font-size: 1.4rem;
}

.preview-activity {
    margin-top: var(--space-3);
    padding: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
}

.preview-activity__header strong {
    color: var(--color-brand);
    font-size: 0.72rem;
}

.preview-activity__header>span {
    color: var(--color-brand-secondary);
    font-weight: var(--font-weight-semibold);
}

.preview-activity__items {
    margin-top: var(--space-3);
}

.preview-activity__item {
    min-height: 3.15rem;
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--space-3);
    border-top: 1px solid var(--color-divider);
}

.preview-activity__item:first-child {
    border-top: 0;
}

.preview-activity__marker {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
}

.preview-activity__marker--orange {
    background: var(--color-highlight);
}

.preview-activity__marker--moss {
    background: var(--color-brand-secondary);
}

.preview-activity__marker--mint {
    background: var(--color-accent);
}

.preview-activity__item div {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.preview-activity__item strong {
    color: var(--color-brand);
    font-size: 0.62rem;
}

.preview-activity__item small,
.preview-activity__item time {
    color: var(--color-text-muted);
    font-size: 0.5rem;
}

/*
|--------------------------------------------------------------------------
| Cabeçalhos de seção
|--------------------------------------------------------------------------
*/

.landing-section-header {
    max-width: 44rem;
}

.landing-section-header h2,
.landing-benefits__content h2,
.landing-synthesis__content h2,
.landing-final h2 {
    margin:
        var(--space-4) 0 var(--space-5);

    color:
        color-mix(in srgb,
            var(--color-brand) 92%,
            var(--neutral-900));

    font-size:
        clamp(2.15rem,
            4vw,
            3.3rem);

    letter-spacing: -0.045em;
    line-height: 1.04;
}

.landing-section-header p {
    max-width: 38rem;
    margin: 0;
    color: var(--color-text-soft);
    line-height: var(--line-height-relaxed);
}

/*
|--------------------------------------------------------------------------
| Recursos
|--------------------------------------------------------------------------
*/

.landing-resources {
    position: relative;
    padding:
        clamp(5rem, 8vw, 7.5rem) 0;
    background:
        linear-gradient(180deg,
            var(--color-surface),
            color-mix(in srgb,
                var(--color-surface-secondary-soft) 10%,
                var(--color-surface)));
}

.landing-resources__grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--space-4);
    margin-top: var(--space-12);
}

.resource-card {
    position: relative;
    min-height: 18rem;
    overflow: hidden;
    padding: var(--space-6);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    transition:
        transform var(--duration-normal) var(--ease-standard),
        box-shadow var(--duration-normal) var(--ease-standard);
}

.resource-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.resource-card::after {
    position: absolute;
    right: -2.5rem;
    bottom: -3rem;
    width: 8rem;
    height: 8rem;
    content: "";
    border-radius: 50%;
    opacity: 0.24;
}

.resource-card--clients {
    background:
        color-mix(in srgb,
            var(--color-surface-accent) 64%,
            var(--color-surface));
}

.resource-card--clients::after {
    background: var(--color-accent);
}

.resource-card--folders {
    background:
        color-mix(in srgb,
            var(--color-surface-highlight-soft) 72%,
            var(--color-surface));
}

.resource-card--folders::after {
    background: var(--color-highlight);
}

.resource-card--agenda {
    background:
        color-mix(in srgb,
            var(--color-surface-secondary-soft) 72%,
            var(--color-surface));
}

.resource-card--agenda::after {
    background: var(--color-brand-secondary);
}

.resource-card--team {
    background:
        linear-gradient(145deg,
            var(--color-surface-warning-soft),
            color-mix(in srgb,
                var(--color-brand) 5%,
                var(--color-surface-warning-soft)));
}

.resource-card--team::after {
    background: var(--color-warning);
}

.resource-card__icon {
    display: grid;
    width: 3rem;
    height: 3rem;
    place-items: center;
    margin-bottom: var(--space-8);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-xs);
}

.resource-card--clients .resource-card__icon {
    background: var(--color-surface-accent);
    color: var(--color-brand-secondary-active);
}

.resource-card--folders .resource-card__icon {
    background: var(--color-surface-highlight-soft);
    color: var(--color-highlight-hover);
}

.resource-card--agenda .resource-card__icon {
    background: var(--color-surface-secondary-soft);
    color: var(--color-brand-secondary-active);
}

.resource-card--team .resource-card__icon {
    background: var(--color-surface-warning-soft);
    color: var(--color-brand);
}

.resource-card__number {
    position: absolute;
    top: var(--space-6);
    right: var(--space-6);
    color:
        color-mix(in srgb,
            var(--color-brand) 45%,
            var(--color-text-muted));
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
}

.resource-card h3 {
    position: relative;
    z-index: 1;
    margin: 0 0 var(--space-3);
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}

.resource-card p {
    position: relative;
    z-index: 1;
    margin: 0;
    color:
        color-mix(in srgb,
            var(--color-brand) 42%,
            var(--color-text-soft));
    font-size: var(--font-size-sm);
    line-height: var(--line-height-relaxed);
}

/*
|--------------------------------------------------------------------------
| Benefícios — verde institucional dominante
|--------------------------------------------------------------------------
*/

.landing-benefits {
    position: relative;
    padding:
        clamp(5rem, 8vw, 7.5rem) 0;
    overflow: hidden;

    background:
        radial-gradient(circle at 94% 90%,
            color-mix(in srgb,
                var(--color-warning) 15%,
                transparent),
            transparent 18rem),
        linear-gradient(135deg,
            var(--color-brand-secondary-active),
            var(--color-brand-secondary));

    color: var(--color-on-brand-secondary);
}

.landing-benefits__grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns:
        minmax(0, 1fr) minmax(24rem, 0.9fr);
    align-items: center;
    gap: clamp(3.5rem, 8vw, 7rem);
}

.landing-benefits__content h2 {
    color: var(--color-on-brand-secondary);
}

.landing-benefits__content>p {
    max-width: 38rem;
    color:
        color-mix(in srgb,
            var(--color-on-brand-secondary) 76%,
            transparent);
    line-height: var(--line-height-relaxed);
}

.landing-benefits__list {
    display: grid;
    gap: var(--space-5);
    margin-top: var(--space-10);
}

.landing-benefits__list article {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: var(--space-4);
}

.landing-benefits__list article>span {
    display: grid;
    width: 2.35rem;
    height: 2.35rem;
    place-items: center;
    border:
        1px solid color-mix(in srgb,
            var(--color-warning) 35%,
            var(--color-on-brand-secondary));
    border-radius: 50%;
    background:
        color-mix(in srgb,
            var(--color-warning) 7%,
            transparent);
    color: var(--color-surface-warning-soft);
    font-size: 0.62rem;
}

.landing-benefits__list strong {
    color: var(--color-on-brand-secondary);
    font-size: var(--font-size-md);
}

.landing-benefits__list p {
    margin: var(--space-1) 0 0;
    color:
        color-mix(in srgb,
            var(--color-on-brand-secondary) 68%,
            transparent);
    font-size: var(--font-size-sm);
    line-height: 1.6;
}

/*
|--------------------------------------------------------------------------
| Workflow
|--------------------------------------------------------------------------
*/

.workflow-card {
    padding: var(--space-6);
    border:
        1px solid color-mix(in srgb,
            var(--color-brand) 18%,
            var(--color-border));
    border-radius: var(--radius-xl);
    background:
        linear-gradient(145deg,
            var(--color-page),
            color-mix(in srgb,
                var(--color-surface-soft) 58%,
                var(--color-page)));
    color: var(--color-text);
    box-shadow:
        0 2rem 4.5rem rgb(25 38 24 / 0.24);
}

.workflow-card__header {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    padding-bottom: var(--space-4);
    border-bottom: 1px solid var(--color-border);
}

.workflow-card__header span {
    color: var(--color-brand-secondary);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-semibold);
}

.workflow-card__header strong {
    color: var(--color-brand);
    font-size: var(--font-size-md);
}

.workflow {
    padding-top: var(--space-5);
}

.workflow__item {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: var(--color-surface);
}

.workflow__item--highlight {
    border-color:
        color-mix(in srgb,
            var(--color-brand-secondary) 36%,
            var(--color-border));
    background: var(--color-surface-secondary-soft);
}

.workflow__number {
    display: grid;
    width: 2.1rem;
    height: 2.1rem;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 50%;
    background: var(--color-brand-secondary-active);
    color: var(--color-on-brand-secondary);
    font-size: 0.6rem;
}

.workflow__item div {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}

.workflow__item small,
.workflow__branches small {
    color: var(--color-text-muted);
    font-size: 0.65rem;
}

.workflow__item strong {
    color: var(--color-brand);
    font-size: var(--font-size-sm);
}

.workflow__connector {
    display: block;
    width: 1px;
    height: 1.15rem;
    margin-inline: 2rem;
    background:
        color-mix(in srgb,
            var(--color-brand-secondary) 48%,
            transparent);
}

.workflow__branches {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--space-2);
}

.workflow__branch {
    display: flex;
    min-height: 5.3rem;
    flex-direction: column;
    justify-content: space-between;
    padding: var(--space-3);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
}

.workflow__branch--deadline {
    background: var(--color-surface-highlight-soft);
}

.workflow__branch--task {
    background: var(--color-surface-warning-soft);
}

.workflow__branch--event {
    background: var(--color-surface-accent);
}

.workflow__branches strong {
    color: var(--color-brand);
    font-size: var(--font-size-xs);
}

/*
|--------------------------------------------------------------------------
| Síntese
|--------------------------------------------------------------------------
*/

.landing-synthesis {
    padding:
        clamp(5rem, 8vw, 7rem) 0;

    background:
        linear-gradient(180deg,
            var(--color-page),
            color-mix(in srgb,
                var(--color-surface-secondary-soft) 22%,
                var(--color-page)));
}

.landing-synthesis__grid {
    display: grid;
    grid-template-columns:
        minmax(20rem, 0.85fr) minmax(0, 1.15fr);
    align-items: center;
    gap: clamp(3rem, 8vw, 7rem);
}

.landing-synthesis__visual {
    position: relative;
    min-height: 22rem;
    display: grid;
    place-items: center;
}

.landing-synthesis__circle {
    position: absolute;
    width: 18rem;
    height: 18rem;
    border-radius: 50%;

    background:
        linear-gradient(145deg,
            var(--color-surface-secondary-soft),
            color-mix(in srgb,
                var(--color-brand-secondary) 22%,
                var(--color-surface-secondary-soft)));
}

.landing-synthesis__circle::before {
    position: absolute;
    top: 1.2rem;
    right: -1rem;
    width: 4.5rem;
    height: 4.5rem;
    content: "";
    border-radius: 50%;
    background: var(--color-surface-highlight-soft);
}

.landing-synthesis__circle::after {
    position: absolute;
    bottom: 1.5rem;
    left: -1.5rem;
    width: 3.6rem;
    height: 3.6rem;
    content: "";
    border-radius: 50%;
    background: var(--color-surface-accent);
}

.landing-synthesis__card {
    position: relative;
    z-index: 1;
    display: flex;
    width: min(100%, 18rem);
    flex-direction: column;
    align-items: center;
    gap: var(--space-3);
    padding:
        var(--space-8) var(--space-6);
    border:
        1px solid color-mix(in srgb,
            var(--color-brand) 28%,
            var(--color-border));
    border-radius: var(--radius-xl);
    background:
        color-mix(in srgb,
            var(--color-surface) 92%,
            transparent);
    color: var(--color-brand-secondary);
    text-align: center;
    box-shadow: var(--shadow-lg);
}

.landing-synthesis__card strong {
    color: var(--color-brand);
    font-size: var(--font-size-lg);
}

.landing-synthesis__card span {
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}

.landing-synthesis__content {
    max-width: 42rem;
}

.landing-synthesis__content p {
    max-width: 38rem;
    margin: 0;
    color: var(--color-text-soft);
    line-height: var(--line-height-relaxed);
}

.landing-synthesis__points {
    display: flex;
    flex-wrap: wrap;
    gap:
        var(--space-3) var(--space-6);
    margin-top: var(--space-8);
}

.landing-synthesis__points span {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    color: var(--color-brand);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-semibold);
}

.landing-synthesis__points i {
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 50%;
    background: var(--color-brand-secondary);
}

.landing-synthesis__points span:nth-child(2) i {
    background: var(--color-highlight);
}

.landing-synthesis__points span:nth-child(3) i {
    background: var(--color-accent);
}

/*
|--------------------------------------------------------------------------
| CTA final
|--------------------------------------------------------------------------
*/

.landing-final {
    padding:
        clamp(4.5rem, 7vw, 6.5rem) 0;

    background:
        linear-gradient(120deg,
            color-mix(in srgb,
                var(--color-brand-secondary) 15%,
                var(--color-page)),
            var(--color-surface-warning-soft));
}

.landing-final__panel {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-12);
    overflow: hidden;
    padding:
        clamp(2.5rem,
            5vw,
            4rem);
    border:
        1px solid color-mix(in srgb,
            var(--color-brand-secondary) 28%,
            var(--color-border));
    border-radius: var(--radius-xl);
    background: var(--color-surface);
    box-shadow: var(--shadow-sm);
}

.landing-final__panel::after {
    position: absolute;
    top: -4rem;
    right: 12%;
    width: 9rem;
    height: 9rem;
    content: "";
    border-radius: 50%;
    background:
        color-mix(in srgb,
            var(--color-highlight) 18%,
            transparent);
}

.landing-final__content {
    position: relative;
    z-index: 1;
    max-width: 43rem;
}

.landing-final h2 {
    margin-bottom: var(--space-4);
}

.landing-final p {
    max-width: 37rem;
    margin: 0;
    color:
        color-mix(in srgb,
            var(--color-brand) 54%,
            var(--color-text-soft));
    line-height: var(--line-height-relaxed);
}

.landing-final__button {
    position: relative;
    z-index: 1;
    flex: 0 0 auto;
}

/*
|--------------------------------------------------------------------------
| Responsividade
|--------------------------------------------------------------------------
*/

@media (max-width: 991.98px) {

    .landing-hero__grid,
    .landing-benefits__grid,
    .landing-synthesis__grid {
        grid-template-columns: 1fr;
    }

    .landing-hero__content {
        max-width: 43rem;
    }

    .landing-hero__title {
        max-width: 11.5ch;
    }

    .product-preview {
        width: min(100%, 43rem);
    }

    .landing-resources__grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .workflow-card {
        max-width: 38rem;
    }

    .landing-synthesis__visual {
        min-height: 19rem;
    }

    .landing-synthesis__content {
        max-width: 43rem;
    }
}

@media (max-width: 767.98px) {
    .landing-container {
        width: min(100% - 2rem, 75rem);
    }

    .landing-hero {
        padding-top: 3.75rem;
    }

    .landing-hero__grid {
        gap: 4rem;
    }

    .product-preview__body {
        min-height: 22rem;
        grid-template-columns: 5rem 1fr;
    }

    .product-preview__sidebar {
        padding-inline: 0.45rem;
    }

    .product-preview__nav-item {
        justify-content: center;
    }

    .product-preview__nav-item b {
        display: none;
    }

    .product-preview__workspace {
        padding: var(--space-4);
    }

    .landing-final__panel {
        align-items: flex-start;
        flex-direction: column;
        gap: var(--space-8);
    }
}

@media (max-width: 575.98px) {
    .landing-container {
        width: min(100% - 1.5rem, 75rem);
    }

    .landing-hero {
        padding:
            3.25rem 0 4.5rem;
    }

    .landing-hero__title {
        max-width: 11ch;
        font-size:
            clamp(2.55rem,
                12vw,
                3.65rem);
    }

    .landing-hero__actions {
        display: grid;
    }

    .landing-button {
        width: 100%;
    }

    .product-preview {
        width: calc(100% + 0.25rem);
    }

    .product-preview__window {
        border-radius: var(--radius-lg);
    }

    .product-preview__body {
        grid-template-columns: 3.5rem 1fr;
    }

    .product-preview__sidebar {
        gap: 0.2rem;
        padding-inline: 0.35rem;
    }

    .product-preview__nav-item {
        padding-inline: 0.3rem;
    }

    .product-preview__metrics {
        grid-template-columns: repeat(3, 1fr);
        gap: var(--space-1);
    }

    .preview-metric {
        min-height: 5.4rem;
        padding: var(--space-2);
    }

    .preview-metric strong {
        font-size: var(--font-size-md);
    }

    .preview-metric small {
        display: none;
    }

    .landing-resources__grid {
        grid-template-columns: 1fr;
    }

    .resource-card {
        min-height: 15rem;
    }

    .resource-card__icon {
        margin-bottom: var(--space-6);
    }

    .workflow__branches {
        grid-template-columns: 1fr;
    }

    .landing-synthesis__visual {
        min-height: 17rem;
    }

    .landing-synthesis__circle {
        width: 15rem;
        height: 15rem;
    }

    .landing-final__panel {
        padding: var(--space-6);
    }
}

@media (prefers-reduced-motion: reduce) {

    .landing-button,
    .resource-card,
    .product-preview__nav-item {
        transition: none;
    }
}
</style>