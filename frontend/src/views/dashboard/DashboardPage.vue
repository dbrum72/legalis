<template>
  <PageContainer>
    <div class="dashboard-page">
      <header class="dashboard-header">
        <div class="dashboard-heading">
          <h1 class="dashboard-title">
            Dashboard
          </h1>

          <p class="dashboard-description">
            Acompanhe um resumo do escritório.
          </p>
        </div>

        <div v-if="hasQuickActions" class="dashboard-actions">
          <AppButton v-if="
            authStore.hasPermission(
              'clients.create',
            )
          " type="button" variant="outline" @click="goToClientCreate">
            Novo cliente
          </AppButton>

          <AppButton v-if="
            authStore.hasPermission(
              'folders.create',
            )
          " type="button" @click="goToFolderCreate">
            Nova pasta
          </AppButton>
        </div>
      </header>

      <div v-if="errorMessage" class="dashboard-alert" role="alert">
        {{ errorMessage }}
      </div>

      <section class="dashboard-summary" aria-label="Resumo do escritório">
        <AppCard class="dashboard-summary-card">
          <div class="dashboard-metric">
            <span class="dashboard-metric-label">
              Clientes
            </span>

            <strong class="dashboard-metric-value">
              {{ dashboardStore.summary.clients }}
            </strong>
          </div>
        </AppCard>

        <AppCard class="dashboard-summary-card">
          <div class="dashboard-metric">
            <span class="dashboard-metric-label">
              Pastas
            </span>

            <strong class="dashboard-metric-value">
              {{ dashboardStore.summary.folders }}
            </strong>
          </div>
        </AppCard>

        <AppCard class="dashboard-summary-card">
          <div class="dashboard-metric">
            <span class="dashboard-metric-label">
              Membros ativos
            </span>

            <strong class="dashboard-metric-value">
              {{
                dashboardStore.summary
                  .active_members
              }}
            </strong>
          </div>
        </AppCard>
      </section>

      <section class="dashboard-recent" aria-labelledby="recent-folders-title">
        <header class="dashboard-section-header">
          <div>
            <h2 id="recent-folders-title" class="dashboard-section-title">
              Pastas recentes
            </h2>

            <p class="dashboard-section-description">
              Últimas pastas adicionadas ao escritório.
            </p>
          </div>
        </header>

        <AppCard>
          <div v-if="
            dashboardStore.recentFolders
              .length === 0
          " class="dashboard-empty">
            Nenhuma pasta recente.
          </div>

          <div v-else class="dashboard-folders">
            <article v-for="
folder in
                                  dashboardStore.recentFolders
                            " :key="folder.id" class="dashboard-folder">
              <div class="dashboard-folder-main">
                <strong class="dashboard-folder-name">
                  {{ folder.name }}
                </strong>

                <span class="dashboard-folder-process">
                  {{
                    folder.process_number ||
                    '—'
                  }}
                </span>
              </div>

              <time v-if="folder.created_at" class="dashboard-folder-date" :datetime="folder.created_at">
                {{
                  formatDate(
                    folder.created_at,
                  )
                }}
              </time>
            </article>
          </div>
        </AppCard>
      </section>
    </div>
  </PageContainer>
</template>

<script setup>
import {
  computed,
  onMounted,
  ref,
} from 'vue'

import { useRouter } from 'vue-router'

import {
  PageContainer,
} from '@/components/layout'

import {
  AppButton,
  AppCard,
} from '@/components/ui'

import { useAuthStore } from '@/stores/auth.js'
import { useDashboardStore } from '@/stores/dashboard.js'

const router =
  useRouter()

const authStore =
  useAuthStore()

const dashboardStore =
  useDashboardStore()

const errorMessage =
  ref('')

const hasQuickActions =
  computed(
    () =>
      authStore.hasPermission(
        'clients.create',
      ) ||
      authStore.hasPermission(
        'folders.create',
      ),
  )

function formatDate(value) {
  if (!value) {
    return ''
  }

  const date =
    new Date(value)

  if (
    Number.isNaN(
      date.getTime(),
    )
  ) {
    return ''
  }

  return new Intl.DateTimeFormat(
    'pt-BR',
    {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    },
  ).format(date)
}

function goToClientCreate() {
  return router.push({
    name: 'clients.create',
  })
}

function goToFolderCreate() {
  return router.push({
    name: 'folders.create',
  })
}

async function loadDashboard() {
  errorMessage.value =
    ''

  try {
    await dashboardStore
      .fetchDashboard()
  } catch {
    errorMessage.value =
      'Não foi possível carregar o resumo do escritório.'
  }
}

onMounted(
  loadDashboard,
)
</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.dashboard-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
}

.dashboard-heading {
  min-width: 0;
}

.dashboard-title {
  margin: 0;
  color: var(--color-text);
}

.dashboard-description,
.dashboard-section-description {
  margin:
    var(--space-2) 0 0;

  color:
    var(--color-text-muted);
}

.dashboard-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: var(--space-2);
}

.dashboard-alert {
  padding:
    var(--space-3) var(--space-4);

  border:
    1px solid var(--color-danger);

  border-radius:
    var(--radius-md);

  background:
    var(--color-danger-soft);

  color:
    var(--color-danger);

  font-size:
    var(--font-size-sm);
}

.dashboard-summary {
  display: grid;

  grid-template-columns:
    repeat(3,
      minmax(0, 1fr));

  gap:
    var(--space-4);
}

.dashboard-summary-card {
  min-width: 0;
}

.dashboard-metric {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.dashboard-metric-label {
  color:
    var(--color-text-muted);

  font-size:
    var(--font-size-sm);

  font-weight:
    600;
}

.dashboard-metric-value {
  color:
    var(--color-text);

  font-size:
    2rem;

  font-weight:
    700;

  line-height:
    1;
}

.dashboard-recent {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.dashboard-section-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-4);
}

.dashboard-section-title {
  margin: 0;

  color:
    var(--color-text);

  font-size:
    1.125rem;

  font-weight:
    700;
}

.dashboard-folders {
  display: flex;
  flex-direction: column;
}

.dashboard-folder {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);

  padding:
    var(--space-4) 0;

  border-bottom:
    1px solid var(--color-border);
}

.dashboard-folder:first-child {
  padding-top: 0;
}

.dashboard-folder:last-child {
  padding-bottom: 0;
  border-bottom: 0;
}

.dashboard-folder-main {
  display: flex;
  min-width: 0;
  flex-direction: column;
  gap: var(--space-1);
}

.dashboard-folder-name {
  overflow: hidden;

  color:
    var(--color-text);

  font-weight:
    600;

  text-overflow:
    ellipsis;

  white-space:
    nowrap;
}

.dashboard-folder-process,
.dashboard-folder-date {
  color:
    var(--color-text-muted);

  font-size:
    var(--font-size-sm);
}

.dashboard-folder-date {
  flex:
    0 0 auto;
}

.dashboard-empty {
  padding:
    var(--space-4) 0;

  color:
    var(--color-text-muted);

  font-size:
    var(--font-size-sm);

  text-align:
    center;
}

@media (max-width: 900px) {
  .dashboard-summary {
    grid-template-columns:
      1fr;
  }
}

@media (max-width: 640px) {
  .dashboard-header {
    flex-direction:
      column;
  }

  .dashboard-actions {
    width:
      100%;

    justify-content:
      flex-start;
  }

  .dashboard-folder {
    align-items:
      flex-start;

    flex-direction:
      column;

    gap:
      var(--space-2);
  }
}
</style>