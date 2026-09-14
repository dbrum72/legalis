# Cronograma do projeto Legalis

Última atualização: 14/09/2026

## Objetivo

Evoluir o Legalis como plataforma de gestão jurídica, mantendo consistência entre domínio, segurança, experiência de uso e operação financeira. O estado atual do projeto é sempre a baseline para novas alterações.

## Situação do cronograma

> **Nota sobre incerteza:** o histórico disponível registra as entregas e a sequência prática de evolução, mas não preserva uma versão única e integral do cronograma originalmente combinado. As etapas concluídas abaixo são confirmadas pelo histórico. A ordem das etapas pendentes foi reconstruída a partir da progressão do módulo financeiro e das referências a “prosseguir com o cronograma”; portanto, deve ser validada antes de iniciar uma nova frente estrutural.

## Etapas concluídas

### 1. Autenticação e navegação

- Menu dropdown do usuário na `HeaderBar`, com opção de logout.
- Aparência do botão de logout adaptada ao tema.
- Registro de `last_login_at` no estado de autenticação.
- Exibição do último login na `HeaderBar`, usando `Hoje, HH:mm` quando aplicável.

### 2. Perfis, roles e permissions

- Revisão das permissões dos tipos de usuário.
- Inclusão das permissões dos módulos de arquivos, financeiro, despesas e apontamentos de tempo.
- Atualização dos testes de provisionamento das roles organizacionais.

### 3. Design system e semântica de botões

- Padronização visual dos botões que indicam rotas.
- Formalização da semântica:
  - navegação/rota: verde;
  - criação, persistência e atualização: laranja;
  - abertura de modal e filtros: marrom;
  - ação destrutiva: vermelho.
- Criação dos aliases `route`, `action`, `modal`, `filter` e `danger` no `AppButton`.
- Documentação da convenção no componente.
- Correção estrutural do `AppField` para respeitar grids, com `min-width: 0` no campo e no controle.

### 4. Dashboard

- Reorganização do layout.
- Seção financeira movida para depois de “Meu trabalho”.
- Integração da visão financeira respeitando a permissão `finance.view`.

### 5. Cobranças e contas a receber

- Cadastro de cobrança.
- Normalização de valor e desconto como moeda.
- Digitação monetária com deslocamento decimal.
- Valor vazio iniciado visualmente como `0,00`.
- Parcelamento de cobranças.
- Identificador comum da cobrança em todas as parcelas.
- Inclusão de nova parcela em cobrança parcelada preexistente.
- Filtros por cliente, transação, mês, período, situação e faixa de atraso.
- Paginação e ordenação.
- Relação entre nova cobrança e contrato de honorários.
- Edição, exclusão e cancelamento de cobranças.
- Registro e estorno de pagamentos com trilha de auditoria.
- Modal de registro de pagamento adequado ao design system.
- Modal de nova parcela adequado ao design system.
- Correção do cálculo reativo do valor líquido da parcela.
- Modal de detalhes da cobrança revisado de acordo com a identidade visual.
- Reorganização da listagem de recebíveis para melhor uso do espaço.

### 6. Indicadores e acompanhamento financeiro

- Resumo de valores a receber, vencidos e recebidos no mês.
- Mapa de vencimentos/idade dos recebíveis.
- Tratamento das parcelas no mapa conforme a finalidade de valores pendentes e vencidos.
- Previsão de recebimentos por horizonte de vencimento.
- Comparativo financeiro dos últimos seis meses.
- Visão de inadimplência por cliente.
- Relatório financeiro consolidado em CSV.
- Evolução do comparativo para faturado, recebido e despesas pagas.
- Cálculo do resultado de caixa mensal e acumulado.
- Exclusão de pagamentos estornados dos totais financeiros.

### 7. Cobrança e comunicação por e-mail

- Configuração local do Mailpit no `.env`.
- Melhoria do HTML das mensagens financeiras.
- Identificação do escritório remetente no cabeçalho do e-mail.
- Lembretes de cobrança manuais e agendados.
- Regras de lembretes automáticos.
- Comandos agendados para geração e envio de lembretes.
- Geração de comprovante de pagamento.
- Envio do comprovante por e-mail com registro de entrega.

### 8. Contas a pagar

- Cadastro, edição e exclusão de contas.
- Filtros por fornecedor, situação e período de vencimento.
- Registro de pagamento parcial ou integral.
- Situações aberta, parcial, paga, vencida e cancelada.
- Cancelamento de conta com motivo.
- Estorno de pagamento com auditoria.
- Detalhes e histórico de pagamentos.
- Inclusão das saídas no resumo e no resultado de caixa.
- Reorganização visual da listagem em cards compactos.
- Ações secundárias agrupadas no modal de detalhes.
- Correções responsivas dos filtros e campos de data.

## Etapa atual

### 9. Consolidação e qualidade do módulo financeiro

Estado: **em andamento**.

Objetivos:

- revisar a experiência completa de contas a receber e contas a pagar em desktop e mobile;
- eliminar inconsistências visuais remanescentes;
- ampliar os testes de interface dos fluxos financeiros;
- revisar estados de carregamento, vazio, erro e confirmação;
- verificar acessibilidade de modais, filtros, tabelas/cards e ações;
- executar a suíte completa de backend e frontend após a estabilização.

## Etapas pendentes — ordem reconstruída

### 10. Fluxo de caixa e relatórios gerenciais

- Filtros por competência e período nos indicadores gerenciais.
- Detalhamento das entradas e saídas que formam cada resultado.
- Relatório de fluxo de caixa realizado e projetado.
- Exportação coerente com os filtros ativos.
- Indicadores de margem, inadimplência e concentração de receita.

### 11. Organização financeira

- Categorias padronizadas para contas a pagar e despesas.
- Centros de custo ou agrupamentos equivalentes por escritório.
- Vinculação opcional de despesas administrativas a pastas/clientes quando aplicável.
- Regras claras para despesas reembolsáveis e não reembolsáveis.

### 12. Recorrência e automação financeira

- Contas a pagar recorrentes.
- Geração controlada das próximas ocorrências.
- Alertas de vencimento de obrigações do escritório.
- Prevenção de duplicidade em rotinas automáticas.

### 13. Conciliação e fechamento

- Conciliação manual de recebimentos e pagamentos.
- Registro de divergências e ajustes.
- Fechamento mensal com bloqueio ou sinalização de alterações retroativas.
- Trilha de auditoria para operações sensíveis.

### 14. Integrações financeiras

- Avaliar emissão de cobrança por provedor externo.
- Avaliar integração bancária ou importação de extrato.
- Avaliar emissão fiscal conforme o contexto operacional do escritório.

> **Incerteza:** as etapas 10 a 14 refletem a continuação funcional mais coerente para o estágio atual, mas não aparecem integralmente enumeradas no histórico disponível. A ordem deve ser tratada como proposta reconstruída, não como decisão irrevogável.

## Decisões importantes

### Baseline

- Toda alteração deve partir do estado atual da pasta do projeto.
- Alterações preexistentes no workspace devem ser preservadas.

### Banco de dados e migrations

- O banco de desenvolvimento pode ser considerado zerado e descartável.
- Não criar migrations incrementais apenas para adicionar ou ajustar campos do domínio em desenvolvimento.
- Alterar a migration de origem correspondente.
- Validar mudanças estruturais com `migrate:fresh --seed`.

### UI e design system

- Aplicar sempre o design system do projeto.
- Reutilizar componentes existentes, como `AppInput`, `AppDate`, `AppCurrency`, `AppSelect`, `AppSearch`, `AppTextarea`, `AppButton`, `AppDialog`, `AppConfirmDialog` e `AppCard`.
- Evitar controles HTML improvisados quando houver componente equivalente.
- Considerar responsividade e composição visual durante a implementação, e não apenas após relato de sobreposição.
- Ações frequentes devem permanecer visíveis; ações secundárias ou destrutivas devem ser agrupadas em detalhes ou confirmações adequadas.

### Semântica visual das ações

- Botão de rota: verde.
- Botão que efetiva criação, store ou update: laranja.
- Botão que abre modal ou aplica filtros: marrom.
- Botão destrutivo: vermelho, com confirmação quando necessária.

### Financeiro

- Parcelas de uma mesma cobrança compartilham o identificador da cobrança.
- Uma cobrança parcelada existente pode receber parcelas adicionais.
- O mapa de vencimentos representa saldos pendentes por idade/vencimento.
- Pagamentos cancelados ou estornados não compõem valores realizados.
- Cancelamentos e estornos devem registrar motivo, data e usuário responsável.
- A criação de cobrança pode ser vinculada a um contrato de honorários.

### Qualidade

- Cada etapa deve incluir testes proporcionais ao risco.
- Mudanças de frontend devem passar por build de produção.
- Mudanças financeiras de backend devem incluir testes de integração/feature.
- Ao concluir um ciclo relevante, executar a suíte completa novamente.

## Próxima ação prevista

Realizar uma revisão visual e funcional completa da `FinancePage.vue`, começando pelos fluxos de contas a pagar recém-adicionados, em larguras desktop, tablet e mobile. Corrigir os problemas encontrados no design system ou na composição da página, adicionar testes de interface para filtros, detalhes e ações e, ao final, executar as suítes completas de backend e frontend.

Depois dessa estabilização, iniciar a etapa 10 com filtros de competência e detalhamento do fluxo de caixa.
