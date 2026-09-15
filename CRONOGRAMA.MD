# Cronograma do projeto Legalis

Última atualização: 15/09/2026

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

## Consolidação concluída

### 9. Consolidação e qualidade do módulo financeiro

Estado: **ciclo de consolidação concluído em 14/09/2026**.

Objetivos:

- revisar a experiência completa de contas a receber e contas a pagar em desktop e mobile;
- eliminar inconsistências visuais remanescentes;
- ampliar os testes de interface dos fluxos financeiros;
- revisar estados de carregamento, vazio, erro e confirmação;
- verificar acessibilidade de modais, filtros, tabelas/cards e ações;
- executar a suíte completa de backend e frontend após a estabilização.

Entregas deste ciclo:

- Estados de carregamento, erro com nova tentativa e busca sem resultados em contas a pagar.
- Preservação dos filtros após operações e retorno à página; descarte de respostas antigas.
- Validação de intervalo de vencimento e formulário limpo ao iniciar nova conta.
- Erros dentro dos modais e bloqueio de envio duplicado nas operações de contas a pagar.
- Observações e auditoria dos pagamentos/estornos visíveis nos detalhes.
- Correção do ícone de fechamento, foco nas confirmações e Escape restrito ao diálogo superior.
- Sidebar recolhida inicialmente em mobile e abertura sem comprimir a página.
- Resumo financeiro adaptado à largura disponível, incluindo tablet.
- 22 testes adicionais, incluindo filtros, detalhes, permissões, pagamentos, cancelamentos, estornos, exclusão, erros e navegação responsiva.

Validação:

- Frontend: **1.588 testes aprovados em 99 arquivos**.
- Backend: **395 testes aprovados, 1.678 assertions**.
- Build de produção: aprovado, **2.132 módulos**.
- Navegador local: revisão em **390×844**, **768×1024** e **1440×900**, sem transbordamento horizontal nas seções financeiras inspecionadas.
- Gravações financeiras verificadas com API simulada nos testes; a revisão manual não criou pagamentos nem alterou lançamentos.

Detalhes técnicos e limites da verificação: `BASELINE.md`.

## Etapas pendentes — ordem reconstruída

### 10. Fluxo de caixa e relatórios gerenciais

Estado: **concluída em 14/09/2026**.

- Filtros por mês de referência ou período personalizado, com limite de 366 dias.
- Entradas e saídas detalhadas, com paginação e totais mensais.
- Realizado pela data de pagamento e projeção pelo saldo pendente atual por vencimento.
- CSV com os filtros efetivamente aplicados, incluindo todos os lançamentos do intervalo.
- Indicadores de margem de caixa, inadimplência e concentração no maior cliente.
- Isolamento por escritório e permissão financeira na consulta e na exportação.
- Revisão visual desktop/mobile e build de produção aprovados.
- Validação completa: backend com 402 testes e 1.737 assertions; frontend com
  100 arquivos e 1.594 testes aprovados. Build com 2.135 módulos transformados.

Critérios: pagamentos cancelados não compõem o realizado; rascunhos e cancelados
não compõem a projeção. Os resultados não incluem saldo inicial bancário e a
projeção não representa posição histórica. Fórmulas e contratos documentados em `BASELINE.md`.

### 11. Organização financeira

Estado: **concluída em 14/09/2026**.

- Cadastro de categorias e centros de custo por escritório, com renomeação,
  desativação e reativação. Catálogo compartilhado entre contas a pagar e despesas.
- Classificação opcional na criação/edição e filtros por categoria/centro de custo
  nas contas a pagar. Itens inativos permanecem disponíveis para consulta histórica.
- Vínculos opcionais de contas administrativas com pasta e cliente, validados
  dentro do escritório; quando ambos informados, o cliente deve pertencer à pasta.
- Despesas não reembolsáveis ficam fora do faturamento. Contas a pagar não geram
  cobrança nem despesa na pasta automaticamente, evitando duplicação de saídas.
- Alteração na migration financeira de origem e validação com `migrate:fresh --seed`
  no banco local de desenvolvimento, conforme a política deste cronograma.
- Backend: 408 testes e 1.778 assertions aprovados. Frontend: 101 arquivos e
  1.600 testes aprovados; 28 testes da tela reexecutados após ajustes da revisão visual.
- Build de produção e revisão de contas/cadastros em desktop e mobile aprovados.

### Complemento da etapa 11 — Central de configurações

Estado: **concluído em 14/09/2026**, após aprovação do estudo em
`ESTUDO_CONFIGURACOES.md`.

- Sidebar e cabeçalho levam à central de Configurações, com cartões autorizados
  por usuário para categorias financeiras, centros de custo e perfis/permissões.
- Manutenção dos cadastros transferida da FinancePage para páginas dedicadas,
  com busca, filtro de situação e consulta sem ações de gestão para leitores.
- Atalhos em nova aba preservam formulários operacionais; catálogos recarregam
  ao retornar à aba. Isolamento por escritório e regras da API preservados.
- Nenhuma alteração de schema ou reinicialização do banco nesta entrega.
- Frontend: 102 arquivos e 1.607 testes aprovados; 114 testes direcionados aprovados.
  Build de produção aprovado e revisão de navegação/responsividade concluída.

### 12. Recorrência e automação financeira

Estado: **concluída em 14/09/2026**.

- Contas recorrentes mensais, trimestrais, semestrais e anuais, com término
  opcional e prévia de vencimentos. Dias de fim de mês preservados.
- Geração até 30 dias à frente, com antecipação manual até 90 dias mediante
  prévia. Bloqueio transacional e chave única impedem duplicidade de ocorrências.
- Pausa/retomada e edição de valor/descrição somente das contas ainda não
  geradas. Cancelamentos preservam o histórico e não recriam ocorrências.
- Alertas internos e filtro para contas com saldo que vencem em até sete dias.
- Rotina Laravel diária às 07h de America/Sao_Paulo, dependente de scheduler
  ativo no ambiente. Falhas visíveis na série; processamento isolado por escritório.
- Novas tabelas de séries e ocorrências aplicadas ao banco local com `migrate
  --force`, sem apagar dados. Regras e referências em `RECORRENCIA_FINANCEIRA.md`.
- Backend: **415 testes e 1.837 assertions aprovados**. Frontend: **103 arquivos
  e 1.612 testes aprovados**. Build de produção e revisão desktop/mobile concluídos.

### 13. Conciliação e fechamento

Estado: **concluída em 14/09/2026**.

- Tela dedicada em `/finance/reconciliation`, acessível pela análise do período
  no Financeiro, com consulta mensal, filtros e paginação de pagamentos e auditoria.
- Conferência manual de recebimentos e pagamentos por valor/data do extrato,
  referência e observação. Divergências impedem fechar; ajustes na conferência
  preservam antes/depois e não alteram o saldo financeiro.
- Fechamento gerencial de meses encerrados e conferidos, com prévia e motivo.
  Alterações posteriores sinalizam revisão necessária e preservam totais originais;
  reabertura exige motivo e mantém os fechamentos anteriores no histórico.
- Auditoria de criação/alteração/estorno de pagamentos, conferências e fechamentos,
  com usuário, data e motivo, limitada ao escritório atual. Sem retrospectiva fictícia.
- Três tabelas novas aplicadas por `migrate --force`, preservando dados existentes.
  Escopo e referências documentados em `CONCILIACAO_FECHAMENTO.md`.
- Backend: **422 testes e 1.901 assertions aprovados**. Frontend: **104 arquivos
  e 1.618 testes aprovados**, mais 48 testes reexecutados após revisão visual.
  Build final aprovado, com 2.150 módulos. Navegação e confirmação revisadas em
  desktop e mobile. Nenhum fechamento foi salvo durante a revisão visual local.

### 14. Integrações financeiras

Estado: **adiada para o futuro por decisão do usuário em 15/09/2026**.

O estudo está concluído e preservado. As frentes 14A, 14B e 14C ficam fora
da sequência ativa, sem data de retomada. Retomar apenas quando o usuário
solicitar, revalidando documentação dos provedores e necessidades do escritório.

- Avaliar emissão de cobrança por provedor externo.
- Avaliar integração bancária ou importação de extrato.
- Avaliar emissão fiscal conforme o contexto operacional do escritório.

Estudo em `ESTUDO_INTEGRACOES_FINANCEIRAS.md`, com fontes oficiais, avaliação
do código atual, critérios de aceite e dependências operacionais. Sequência proposta:
14A — contas bancárias e importação assistida OFX/CSV; 14B — homologação de provedor
de cobrança; 14C — emissão fiscal após caracterização do escritório.
Nenhum fornecedor contratado ou integração ativada neste ciclo.

> **Incerteza:** as etapas 10 a 14 refletem a continuação funcional mais coerente para o estágio atual, mas não aparecem integralmente enumeradas no histórico disponível. A ordem deve ser tratada como proposta reconstruída, não como decisão irrevogável.

## Nova frente ativa

### 15. Automação de documentos jurídicos

Estado: **primeira versão implementada em 15/09/2026**, com limite de revisão
visual do Word registrado abaixo.

Frente definida pelo usuário: produção de iniciais, petições e outros textos
pré-redigidos a partir de modelos jurídicos e dados do processo.

- Catálogo de modelos por escritório e campos automáticos.
- Geração na pasta, com seleção explícita das partes e dados complementares.
- Revisão do resultado, versões preservadas e documento salvo na pasta.
- Importação e exportação `.docx`, conforme escolha do usuário.
- Entrada pela aba Documentos da pasta; instruções e catálogo de marcadores,
  seleção de cliente, campos livres, prévia textual e download pela lista existente.
- Preservação do modelo original, versões por importação e fotografia dos valores
  usados. Validação do pacote Word, marcadores e acesso por escritório/permissão.
- Novas tabelas aplicadas sem reset do banco. Build final aprovado (2.152 módulos).
- Revisão desktop/mobile realizada. A renderização visual do Word exportado
  permanece pendente: LibreOffice não está instalado no ambiente.
- Suíte completa de backend: 430 testes e 1.941 assertions aprovados. Frontend:
  1.621 testes em 105 arquivos exercitados; três timeouts na execução completa
  passaram na reexecução dos 28 testes desses arquivos com um processo. Mais
  31 testes de documentos aprovados após ajustes de acessibilidade.
  Backend direcionado final: **10 testes e 47 assertions aprovados**.

Escopo e critérios iniciais em `AUTOMACAO_DOCUMENTOS.md`.

### Complemento da etapa 15 — Modelos em Configurações

Implementado em 15/09/2026 após aprovação de `ESTUDO_MODELOS_SETTINGS.md`.

- `/settings/document-templates` concentra importação, busca, instruções,
  marcadores e download dos modelos originais, com acesso pela central.
- Pastas → Documentos mantém seleção, preenchimento, revisão e geração Word.
  Atalho em nova aba preserva a sessão; recuperar foco atualiza os modelos sem
  apagar os valores ou a prévia, inclusive quando a atualização falha.
- Acesso à central ampliado para `documents.generate`, mantendo autorização
  independente dos cartões financeiros e administrativos. Importação mantém
  a exigência adicional de `folders.update`, sem novos privilégios de gestão.
- Catálogo de campos disponível sem pasta em `GET /api/document-templates/fields`.
  Nenhuma alteração de schema, movimentação de arquivos ou reset do banco.
- Backend direcionado: **6 testes e 37 assertions aprovados**. Frontend completo:
  **1.627 testes em 106 arquivos exercitados**, com um caso de atualização após
  erro corrigido durante a revisão. Reexecução final dos dois componentes:
  **8 testes aprovados**; os demais 1.626 passaram na execução completa.
- Build final aprovado com **2.155 módulos**. Central, busca e formulário de
  modelos revisados no navegador em desktop/mobile, sem transbordamento horizontal.

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

As integrações financeiras (14A, 14B e 14C) foram adiadas para o futuro.
Frente ativa: **15 — Automação de documentos jurídicos**, definida pelo usuário,
conforme `AUTOMACAO_DOCUMENTOS.md`. O primeiro fluxo de importação, preenchimento
e exportação Word foi implementado. Próximo ciclo sugerido: validar modelos
reais do escritório e evoluir múltiplas partes, campos opcionais e blocos condicionais.
A etapa 13 foi concluída e validada.
O catálogo `qualifications` permanece global, sem manutenção nesta fase,
conforme decisão do usuário.

### Complemento da etapa 15 — ciclo de vida dos modelos (2026-09-15)

Concluídos: exclusão de modelos sem uso; arquivamento com preservação de histórico;
substituição por novo DOCX validado; consulta e download dos arquivados em Settings;
bloqueio de novas gerações com modelos arquivados. Migração incremental aplicada.
Validação direcionada: 9 testes backend (72 asserções), 10 frontend e build aprovados.

### Complemento da etapa 15 — trechos condicionais (2026-09-15)

Implementada omissão de trechos por ausência de um ou mais campos: exemplos de RG
com órgão emissor e referência à ação. Instruções de preparação disponíveis em
Settings. Para ativar em modelos existentes, inserir os marcadores e substituir
pelo arquivo atualizado. Testes: 18 backend/113 asserções e 10 frontend aprovados.
Build de produção aprovado (2155 módulos).
