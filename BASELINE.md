# Baseline do Legalis

Atualização documental mais recente: 15/09/2026 — estudo da etapa 14 em
`ESTUDO_INTEGRACOES_FINANCEIRAS.md`. Integrações financeiras adiadas para o
futuro por decisão do usuário, incluindo importação OFX/CSV, cobrança externa
e emissão fiscal. Estudo preservado, sem data de retomada. A etapa 15, definida
pelo usuário, implementa a primeira versão da automação Word descrita em
`AUTOMACAO_DOCUMENTOS.md`: importação de modelos, preenchimento, prévia e geração
na pasta. Novas tabelas aplicadas sem reset; validações detalhadas ao final.
As validações abaixo continuam referentes às respectivas entregas de implementação.

Referência atualizada em 14/09/2026 a partir da leitura do repositório local.
Commit de referência: `22dc40e249b41f8158649e8cc391a921d99ca0be`, branch `main`
(`Implementação de contas a pagar`). A árvore de trabalho estava limpa antes
desta atualização. Não havia arquivo de baseline ou `AGENTS.md` no projeto;
este documento passa a registrar a referência técnica encontrada. A consolidação
das etapas 9 a 11 descritas abaixo inclui alterações locais posteriores ao commit de referência.

## Produto e escopo implementado

Sistema de gestão jurídica com organizações, usuários e permissões por organização.
Os itens abaixo estão presentes no código; sua presença não equivale à validação
de todos os fluxos em produção.

| Área | Implementação encontrada |
| --- | --- |
| Acesso | Landing page, login, cadastro, seleção de organização, JWT e aceite de convites. |
| Equipe | Membros, alteração de perfil/status, convites com reenvio e revogação, edição de permissões dos perfis. |
| Clientes | Listagem, cadastro, edição e detalhes; estados civis, qualificações, validação de CPF e consulta de CEP. |
| Pastas | Cadastro e detalhes, clientes vinculados, documentos, movimentações, prazos, eventos e tarefas. |
| Painel e agenda | Dashboard, atenção, trabalho do usuário, atividade recente, acompanhamento de sincronizações e agenda. |
| Publicações | Monitoramento de OABs, importação DJEN, consulta, revisão e vinculação de publicações. |
| DataJud | Consulta e sincronização de pastas, execução em fila e sincronização agendada. |
| Financeiro | Honorários, horas, despesas, faturamento de pastas, cobranças e parcelas, pagamentos, cancelamentos, recibos, lembretes, resumo e relatório CSV. |
| Contas a pagar | Cadastro, filtros por fornecedor/status/vencimento, pagamentos e cancelamentos. |
| Fluxo de caixa | Consulta por mês/período, realizado e pendente por vencimento, detalhamento paginado, indicadores e CSV filtrado. |

O histórico recente registra a implantação e complementação do financeiro,
filtros e ligação com o dashboard, e-mails para cobranças vencidas e contas a pagar.

## Estrutura e tecnologias

- `backend/`: API Laravel; controllers e requests em `app/Http`, modelos e escopos
  em `app/Models`, regras de negócio em `app/Services`, integrações em
  `app/Integrations`, jobs em `app/Jobs` e comandos em `app/Console/Commands`.
- `backend/database/`: migrations, factories e seeders. A migration
  `2026_09_08_120000_create_financial_tables.php` concentra tabelas financeiras.
- `frontend/`: SPA Vue; páginas em `src/views`, estado em `src/stores`, clientes
  HTTP em `src/api`, navegação em `src/router` e layouts em `src/layouts`.
- `frontend/src/components/`: componentes de UI, navegação e formulários;
  formulários separados em controls, fields, variants, selection, groups e files.
- `frontend/src/assets/styles/`: tokens, tema Terra Solar, estilos de componentes
  e layouts. Há playground e documentação em `frontend/docs/FORMS_API.md` e
  READMEs de componentes.

Versões declaradas nos manifests (faixas, não levantamento de versões instaladas):

| Componente | Requisito |
| --- | --- |
| PHP / Laravel | PHP `^8.3`; Laravel `^13.8` |
| Autorização / autenticação | Spatie Permission `^8.3`; jwt-auth `^2.3` |
| Backend: testes | PHPUnit `^12.5.12` |
| Frontend | Vue `^3.5.40`; Pinia `^4.0.2`; Vue Router `^5.2.0`; Axios `^1.19.0` |
| Frontend: build e testes | Vite `^8.1.5`; Vitest `^4.1.10`; Vue Test Utils `^2.4.11`; jsdom `^30.0.1` |
| Node | `^22.18.0 || >=24.12.0` |

O backend também possui build Vite próprio para seus assets. Os READMEs principais
de backend e frontend ainda são os textos dos templates dos frameworks.

## Contratos de arquitetura

- API definida em `backend/routes/api.php`, com prefixo `/api`; health check em `/up`.
- O cliente Axios usa `VITE_API_URL`, `Authorization: Bearer` e `X-Tenant`.
  O exemplo do frontend aponta para `http://127.0.0.1:8000/api`.
- `X-Tenant` contém o slug da organização. `ResolveOrganization` exige organização
  ativa e vínculo ativo do usuário, configura o contexto de permissões e o limpa
  após a requisição. Executa antes da resolução dos bindings.
- O isolamento de modelos usa `BelongsToOrganization`, `OrganizationScope` e
  `CurrentOrganization`. Rotas usam middleware `can:`; o frontend usa guards de
  autenticação e permissão. A organização selecionada fica em `sessionStorage`.
- Integrações externas implementadas: DJEN, DataJud e ViaCEP. Configurações em
  `backend/config/services.php`; DataJud requer `DATAJUD_API_KEY`.

## Execução e tarefas periódicas

Comandos existentes, executados dentro da respectiva pasta:

| Pasta | Comando | Finalidade |
| --- | --- | --- |
| `frontend` | `npm run dev` | Servidor de desenvolvimento |
| `frontend` | `npm run build` | Build de produção |
| `frontend` | `npm test` | Suíte Vitest |
| `frontend` | `npm run test:coverage` | Cobertura V8 |
| `backend` | `composer dev` | API, fila, scheduler, logs e Vite |
| `backend` | `composer test` | Limpeza de configuração e testes Laravel |

`composer dev` escuta as filas `integrations,default`, com quatro tentativas e
timeout de 120 segundos. O scheduler em `backend/routes/console.php` registra:

- `djen:sync-publications`: diariamente às 05:00, timezone configurável.
- `datajud:sync-folders`: diariamente às 04:00 por padrão, horário e timezone configuráveis.
- `finance:generate-reminders`: diariamente às 08:00, America/Sao_Paulo.
- `finance:send-reminders`: a cada minuto.

Os agendamentos evitam sobreposição; DataJud e os comandos financeiros usam
`onOneServer()`. A execução real depende da infraestrutura de scheduler, filas,
banco, cache e e-mail configurada no ambiente.

## Verificação desta baseline

- Inventário: 48 arquivos `*Test.php` no backend e 101 arquivos `*.spec.js` no frontend.
- Backend: PHPUnit configurado para MySQL em `db_legalis_testing`, com mailer
  `array`, cache `array` e fila `sync` durante testes.
- Frontend: Vitest com jsdom. A configuração de cobertura exclui páginas, router,
  playground e `src/main.js`; cobertura não representa toda a aplicação.
- Ambiente observado: PHP CLI 8.3.10 e Node 26.5.1; `backend/vendor` e
  `frontend/node_modules` presentes.
- Backend, `php artisan test --compact`, reexecutado em 14/09/2026 com a
  configuração atual de `phpunit.xml`: 408 testes aprovados, 1.778 assertions,
  em 500,352 segundos (aproximadamente 8 min 20 s), código de saída zero,
  após a etapa 11.
  Este resultado substitui a execução anterior, que encontrou recusa de
  autenticação MySQL (`1045`) no banco de testes.
- Frontend, `npm test -- --reporter=dot`, após a consolidação financeira:
  101 arquivos e 1.600 testes aprovados em 160,23 segundos, código zero,
  após a etapa 11. Após ajustes visuais, 28 testes financeiros foram reexecutados e aprovados.
- Após a central de configurações, `npm test`: 102 arquivos e 1.607 testes
  aprovados em 247,46 segundos; 114 testes direcionados também aprovados.
- Após a etapa 12: backend com 415 testes e 1.837 assertions aprovados em
  914,522 segundos; frontend com 103 arquivos e 1.612 testes aprovados em
  298,49 segundos. Ambos com código de saída zero.
- Após a etapa 13: backend com 422 testes e 1.901 assertions aprovados em
  700,650 segundos; frontend com 104 arquivos e 1.618 testes aprovados em
  222,77 segundos. Após a revisão visual, 48 testes de tela/navegação aprovados.
- `npm run build`: aprovado, 2.150 módulos transformados.
- Cobertura percentual não foi medida nesta atualização.

## Consolidação financeira — etapa 9

- Contas a pagar distinguem carregamento, falha de consulta e ausência de resultados;
  há nova tentativa junto à lista e validação do intervalo de vencimento.
- Filtros ativos são mantidos após operações e ao retornar à página. Respostas
  antigas não sobrescrevem uma consulta mais recente nem repõem dados após limpar o contexto.
- Erros das operações aparecem nos diálogos. O envio bloqueia fechamento e
  duplicação das operações de contas a pagar; nova conta começa com campos limpos.
- Detalhes exibem observações e informações disponíveis de autoria/data dos
  pagamentos e estornos. Ações da lista possuem nomes acessíveis por fornecedor.
- Diálogos tratam Escape apenas na camada superior. Confirmações possuem foco
  inicial, contenção de Tab, retorno de foco e ação destrutiva vermelha.
- Layout mobile abre com sidebar recolhida; a navegação pode ser aberta sem
  comprimir o conteúdo. Resumo financeiro se adapta à largura disponível.
- Verificação no navegador local em 390×844, 768×1024 e 1440×900: formulários,
  detalhes e filtros revisados; nenhum transbordamento horizontal nas seções
  financeiras inspecionadas. Busca sem resultados e limpeza conferidas em mobile.
- Operações de gravação, pagamento, cancelamento, estorno, exclusão e falhas
  verificadas em testes com API simulada; nenhum lançamento financeiro foi
  gravado manualmente durante a revisão visual.

## Fluxo de caixa e relatórios gerenciais — etapa 10

- Endpoints `GET /api/finance/cash-flow` e `GET /api/finance/cash-flow/export`,
  protegidos por autenticação, contexto do escritório e permissão `finance.view`.
- Filtros: `month=YYYY-MM` ou `from=YYYY-MM-DD&to=YYYY-MM-DD`, mutuamente
  exclusivos. Sem filtros, considera o mês atual. Intervalos inclusivos de até
  366 dias; consulta JSON paginada em 25 lançamentos via `page`.
- Realizado: recebimentos e pagamentos de contas a pagar não cancelados,
  selecionados pela data do pagamento. Não soma despesas de pasta novamente.
- Projetado: saldos positivos atuais de cobranças e contas a pagar abertas ou
  parciais, selecionados pelo vencimento. Inclui atrasos do intervalo consultado;
  exclui rascunhos, cancelados e itens já quitados.
- Resultados representam movimentação líquida, sem saldo inicial bancário.
  A projeção não reconstrói posições históricas e não garante recebimentos.
- Margem de caixa: resultado realizado / entradas realizadas. Inadimplência:
  saldo atualmente vencido das cobranças do período / valor total das cobranças
  com vencimento no período, excluídos rascunhos e cancelados. Concentração:
  participação do maior cliente nas entradas realizadas. Sem denominador,
  a API retorna `null` e a tela apresenta um traço.
- CSV inclui período, momento da consulta, totais, indicadores, resultados
  mensais e todos os lançamentos do filtro, independentemente da página aberta.
  Campos textuais são protegidos contra interpretação como fórmulas; valores
  negativos permanecem numéricos. A exportação reflete os dados atuais no
  momento da consulta, não uma cópia congelada da tela.
- `CashFlowPanel.vue` mantém filtros aplicados separados da edição do formulário,
  trata falhas/nova tentativa e ignora respostas obsoletas após troca de escritório.
  Alterações financeiras que atualizam o resumo também recarregam o fluxo.
- O resumo geral e o relatório geral preservam seu escopo global. O filtro do
  fluxo se aplica ao novo painel e ao botão “Exportar fluxo CSV”.
- Consultas do relatório são limitadas por período, mas agregadas em memória;
  avaliar agregação SQL e exportação em lotes se o volume por escritório crescer.
- Nenhuma mudança estrutural de banco nesta etapa. `phpunit.xml` preexistente preservado.
- Revisão visual em 1440×1000 e 390×844, incluindo consulta por intervalo e
  lançamentos. A tabela mensal possui rolagem própria em mobile; a página não
  apresenta transbordamento horizontal. Nenhum lançamento foi alterado nessa revisão.

Integrações externas reais e configuração de produção não foram verificadas.

## Organização da página financeira — revisão de 14/09/2026

- Sequência: posição atual, contas a receber, contas a pagar, análise do período
  e lembretes automáticos. Atalhos permitem ir diretamente a cada área.
- Resumo reúne a receber, a pagar e posição líquida, com os vencidos junto de
  cada saldo. Removidos o resumo repetido de contas a pagar e o recebimento do
  mês no topo, já disponível no fluxo de caixa.
- Nova cobrança fica junto de contas a receber. Distribuição por atraso e
  próximos vencimentos ficam em detalhe expansível dessa seção; o vencido
  não é repetido na distribuição de vencimentos futuros.
- Fluxo de caixa concentra despesas e resultado realizado/projetado. O gráfico
  fixo que repetia esses valores foi substituído por um comparativo acessível
  de faturamento e recebimentos, recolhido por padrão. O relatório geral fica
  nesse contexto, com seu escopo fixo explícito.
- Validação desta revisão: 40 testes financeiros de frontend aprovados;
  build de produção aprovado e navegação/recolhimento revisados em desktop e
  mobile, sem transbordamento horizontal. Backend e cálculos não foram alterados.

## Organização financeira — etapa 11

- `financial_classifications` armazena nome, tipo (`category`/`cost_center`),
  escritório e estado ativo. Nome único por tipo/escritório. A configuração é
  feita pelo escritório, sem catálogo global imposto.
- `GET /api/finance/classifications`: disponível com `finance.view` ou
  `expenses.view`. Criação e atualização exigem `finance.manage`.
  Não há exclusão: desativação impede novos vínculos e preserva os existentes.
  Renomear altera o nome exibido nos registros vinculados.
- `category_id` e `cost_center_id` opcionais em `payables` e `expenses`, com
  verificação de escritório, tipo e estado ativo. Edição pode manter a classificação
  inativa já vinculada ou removê-la. O texto legado `payables.category` continua
  aceito pela API por compatibilidade; a interface usa o catálogo padronizado.
- Contas a pagar aceitam `folder_id` e `client_id` opcionais. Quando os dois
  estão presentes, o cliente precisa estar vinculado à pasta. Listagem aceita
  filtros pelos quatro identificadores e retorna os nomes relacionados.
- FinancePage inclui campos nos formulários, classificação na listagem/detalhes
  e filtros que incluem inativos. O gerenciamento inicialmente expansível foi
  transferido para Configurações no complemento da etapa 11 descrito abaixo.
- FolderFinancial utiliza o mesmo catálogo nas despesas e explicita o efeito
  de `reimbursable`: somente despesas reembolsáveis podem compor cobrança.
  Uma conta administrativa vinculada à pasta não cria outra despesa nem cobrança;
  o pagamento ao fornecedor continua sendo registrado uma vez, em contas a pagar.
- Migration financeira de origem atualizada. Em 14/09/2026, executado
  `php artisan migrate:fresh --seed` com sucesso no banco local `db_legalis`,
  recriando seus dados conforme a política de desenvolvimento descartável do cronograma.
  O banco de testes separado foi validado pela suíte completa; `phpunit.xml` preservado.
- Revisão visual do catálogo e do formulário de conta a pagar em 1440×1000 e
  390×844, sem transbordamento horizontal. As opções de classificação e cliente
  permitem deixar ou limpar vínculos opcionais. Nenhum lançamento financeiro foi
  criado nesta revisão visual.

## Central de configurações — complemento da etapa 11

- `/settings` reúne cadastros de apoio e acessos, com cartões filtrados pelas
  permissões. Sidebar mantém Configurações ativo nas páginas internas; o
  cabeçalho também abre a central.
- `/settings/financial-categories` e `/settings/cost-centers` usam
  `FinancialClassificationPage.vue`, com busca, filtros ativo/inativo/todos,
  criação, renomeação e alternância de situação. Sem duplicação do gerenciador
  na FinancePage. `/settings/roles` mantém os perfis e permissões.
- Consulta dos catálogos exige `finance.view` ou `expenses.view`; manutenção,
  `finance.manage`; perfis, `roles.view`. Guardas respeitam regras de rotas
  ancestrais, além da permissão específica do destino.
- Seletores compartilhados abrem a manutenção em nova aba da mesma origem,
  mantendo a sessão do escritório e o formulário original. O catálogo é
  recarregado ao recuperar foco. Trocar escritório limpa o contexto da página
  e impede que respostas atrasadas atualizem o contexto seguinte.
- Revisados central, categorias e centros de custo, cancelamento de edição,
  layout móvel sem transbordamento horizontal e preservação do rascunho ao
  abrir o cadastro. Nenhum cadastro ou lançamento foi salvo pela revisão visual.
- Backend e banco não foram alterados nesta entrega. A última validação do
  backend continua sendo a suíte da etapa 11 (408 testes e 1.778 assertions).

## Recorrência e automação financeira — etapa 12

- Contas recorrentes ficam em detalhe expansível junto de contas a pagar,
  com prévia de vencimentos, criação, pausa/retomada e edição de valor/descrição
  para contas ainda não geradas. Periodicidades de 1, 3, 6 e 12 meses.
- Geração automática até 30 dias à frente e manual, com prévia, até 90 dias.
  O calendário preserva o dia original e ajusta meses curtos/anos bissextos.
  Pausa mantém contas existentes; retomada inclui ocorrências pendentes.
- Duas tabelas novas registram séries e suas ocorrências. Transação, bloqueio
  da série e chave única por série/vencimento evitam duplicidade e geração parcial.
  Contas geradas podem ser canceladas, mas não excluídas fisicamente.
- Rotas `/api/payable-recurrences` e `/{recurrence}/preview|generate` usam
  `finance.view` para consulta e `finance.manage` para alterações, com isolamento
  por escritório. Vínculos são revalidados antes de gerar novas contas.
- `/api/payables/alerts` e filtro `status=due_soon` destacam saldos em aberto
  com vencimento até sete dias à frente. Avisos são internos, sem envio externo.
- `finance:generate-payables` registrado diariamente às 07h de America/Sao_Paulo.
  Exige scheduler Laravel ativo no ambiente. Falhas são registradas na série e
  não interrompem o processamento das demais; contexto do escritório é restaurado.
- Migration de criação do novo domínio aplicada no banco local com `migrate
  --force`, preservando os dados. Não houve `migrate:fresh` no desenvolvimento.
- Frontend: 103 arquivos e 1.612 testes aprovados; build com 2.147 módulos.
  Revisão de formulário/prévia em desktop e mobile, sem salvar lançamentos locais.
- Backend: suíte completa com 415 testes e 1.837 assertions aprovados. Os sete
  testes novos exercitam calendário, duplicidade, pausa, término, preservação de
  contas existentes, isolamento, validações, alertas e recuperação após falha.
- Regras e referências de produto registradas em `RECORRENCIA_FINANCEIRA.md`.
- Decisão posterior à central: `qualifications` permanece global, sem tela de
  manutenção nem alteração de escopo nesta entrega.

## Conciliação e fechamento — etapa 13

- Página `/finance/reconciliation` com acesso pela análise do período no
  Financeiro. Consulta mensal de recebimentos/pagamentos, filtros de conferência,
  totais globais do mês e paginação de 25 registros/eventos.
- Conferência registra valor/data observados, referência e motivo. Diferenças
  permanecem visíveis; revisar uma conferência não altera pagamentos ou saldos.
  Erros no pagamento seguem o fluxo existente de estorno e novo registro.
- Fechamento exige mês encerrado e pagamentos ativos conciliados. A confirmação
  preserva uma fotografia dos dados, que permite sinalizar mudanças posteriores.
  Reabertura exige motivo e os fechamentos anteriores ficam na auditoria.
- `financial_reconciliations`, `financial_closings` e `financial_audit_events`
  criadas por nova migration, aplicada ao banco local sem reset. Novos pagamentos
  e estornos são auditados em suas transações, assim como conferências e fechamentos.
- Rotas protegidas por `finance.view`/`finance.manage` e escritório atual. Assinaturas
  da versão consultada impedem conferir ou fechar dados alterados. Transações
  serializam conferências/fechamentos por escritório; alterações nos pagamentos
  continuam permitidas e são sinalizadas na consulta do mês fechado.
- Suítes completas: 422 testes de backend (1.901 assertions) e 1.618 de frontend
  (104 arquivos). Build final aprovado, 2.150 módulos. Mais 48 testes de tela e
  navegação aprovados após ajuste do seletor mensal e destaque da sidebar.
- Revisão de navegação, seleção de mês e confirmação em desktop/mobile, sem
  transbordamento horizontal e sem salvar conferências ou fechamentos locais.
- Detalhamento, limites e referências em `CONCILIACAO_FECHAMENTO.md`.

## Automação Word — primeira versão da etapa 15

- Acesso em Documentos da pasta, condicionado a `documents.generate`. Importação
  e geração exigem também `folders.update`; contexto/prévia exigem `folders.view`.
- Modelos `.docx` privados por escritório, com catálogo de marcadores predefinidos
  e campos livres `campo.*`. Cada importação cria uma versão independente.
- Seleção explícita do cliente vinculado, preenchimento dos dados disponíveis,
  edição dos valores somente para a cópia e prévia textual. Campos vazios impedem
  gerar; a assinatura liga a geração à prévia revisada.
- O arquivo gerado aparece na lista existente de documentos da pasta. O original
  do modelo é preservado, com hash e fotografia dos valores em
  `document_templates` e `document_generations`. Migration aplicada sem reset.
- ZIP/DOM nativos do PHP substituem marcadores divididos entre trechos Word,
  preservando os demais itens do pacote. Fixture completo e testes cobrem quebras
  de linha, caracteres especiais, tabelas, cabeçalho e rodapé.
- Uma parte automática por geração; outras partes/signatários usam campos livres.
  Sem editor rico, persistência de rascunhos, blocos condicionais ou exclusão de
  modelos nesta primeira versão. Escopo completo em `AUTOMACAO_DOCUMENTOS.md`.
- Backend: execução completa com **430 testes e 1.941 assertions aprovados**.
  Essa execução começou antes dos dois casos de regressão adicionados ao final;
  a reexecução direcionada do estado final passou com **10 testes e 47 assertions**.
- Frontend: **1.621 testes em 105 arquivos** exercitados. A execução completa
  aprovou 1.618 e teve três timeouts; os **28 testes** dos três arquivos afetados
  passaram com `--maxWorkers=1`. Mais **31 testes de documentos** passaram após
  o ajuste de rótulos acessíveis e o teste de descarte de resposta da pasta anterior.
- Build final aprovado, **2.152 módulos**. Revisão no navegador em desktop e
  390×844 sem transbordamento horizontal no formulário. Nenhum documento foi
  gerado ou modelo importado por esta revisão manual; usou-se apenas a prévia.
- **Limite de validação:** o exportador gerou um Word de demonstração, mas
  `render_docx.py` não conseguiu renderizá-lo porque `soffice.exe` não está
  instalado. A aparência/paginação do Word não foi visualmente certificada.
  Não confundir preservação estrutural testada com garantia de paginação idêntica.

## Modelos Word em Configurações — complemento da etapa 15

- Página `/settings/document-templates`: lista com busca, versão/data, importação,
  catálogo de marcadores e download do original. Consulta exige `documents.generate`;
  importação exige também `folders.update`, preservando o contrato anterior.
- Cartão em Cadastros de apoio e acesso pela central, sidebar e cabeçalho para
  usuários de documentos sem depender de permissões financeiras ou de perfis.
- A pasta mantém apenas seleção de modelo/cliente, valores, prévia e geração.
  Link de gestão na mesma origem abre nova aba com o padrão `rel=opener` já
  utilizado nos cadastros financeiros, preservando a sessão em sessionStorage.
- Ao recuperar foco, somente a lista de modelos é atualizada. Os campos editados,
  a seleção e a prévia permanecem; erro de atualização não oculta o formulário.
  Troca de escritório limpa os dados e invalida respostas anteriores.
- `GET /api/document-templates/fields` fornece o catálogo de campos sem consultar
  pasta ou clientes. Nenhuma migration ou mudança nos arquivos já armazenados.
- Backend: **6 testes e 37 assertions aprovados**. Frontend: execução completa
  de **1.627 testes em 106 arquivos**; 1.626 passaram e um caso de formulário
  oculto após erro foi corrigido durante a revisão. Os dois componentes afetados
  passaram na reexecução final com **8 testes**. Antes disso, 43 testes de central,
  navegação, cabeçalho, guardas e geração também passaram.
- Guia atual: `AUTOMACAO_DOCUMENTOS.md`; estudo aprovado: `ESTUDO_MODELOS_SETTINGS.md`.
- Build final aprovado com **2.155 módulos**. Revisão da central, busca e
  formulário em desktop e 390×844 sem transbordamento horizontal. Nenhum modelo
  foi importado nem documento gerado durante esta revisão manual.

## 2026-09-15 — Substituição e arquivamento de modelos Word

Implementadas exclusão de modelos ativos sem gerações, arquivamento dos utilizados
com preservação dos originais, e substituição transacional após validar o novo DOCX.
Settings inclui confirmação de retirada e filtro de arquivados; pastas recebem
somente modelos ativos. Prévias arquivadas são bloqueadas no servidor, inclusive
com nova checagem sob bloqueio de registro ao salvar a geração.

Migração incremental `2026_09_15_150000_add_archived_at_to_document_templates`
aplicada no ambiente local, sem alterar modelos ou documentos existentes.
Validação: backend DocumentAutomationTest 9 testes/72 asserções; frontend
DocumentTemplatesPage e FolderDocumentAutomation 10 testes aprovados; build de
produção aprovado (2155 módulos). Testes cobrem preservação binária do documento,
originais arquivados, exclusão sem uso, substituição inválida, permissões e tenant.
Suítes completas não repetidas neste incremento. Nenhum modelo real foi excluído
ou substituído durante a validação.

## 2026-09-15 — Trechos condicionais na automação Word

Sintaxe #se e /se no mesmo parágrafo, com uma ou várias condições de preenchimento.
Casos orientadores: identidade + órgão emissor e referência à ação com número de
processo. Prévia e exportação usam o mesmo plano de substituições; campos em
blocos omitidos não são exigidos. Validação rejeita blocos malformados, aninhados,
entre parágrafos e campos desconhecidos. Valores são inseridos literalmente.
Instruções adicionadas a Settings e ao preenchimento da pasta. O arquivo original
fornecido pelo usuário não foi modificado nem substituído no catálogo.
Testes direcionados aprovados: backend 18 testes/113 asserções; frontend 10 testes.
Build de produção aprovado: 2155 módulos. Sem migração de banco neste incremento.
