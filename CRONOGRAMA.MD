# Cronograma do projeto Legalis

Última atualização: 16/09/2026

## Objetivo

Evoluir o Legalis como plataforma de gestão jurídica, mantendo consistência entre domínio, segurança, experiência de uso e operação financeira. O estado atual do projeto é sempre a baseline para novas alterações.

## Situação do cronograma

O planejamento dos próximos passos foi atualizado em 16/09/2026, a pedido do usuário, com base no [estudo do projeto e mercado](ESTUDO_PROJETO_MERCADO_2026-09-16.md). A seção **Cronograma dos próximos passos — 16 semanas ativas**, ao final deste documento, orienta a sequência futura. Os ciclos 1 e 2 foram iniciados em 16/09/2026 por solicitações de avanço; os ciclos 3 a 8 permanecem planejados. O ciclo 1 mantém a pendência de ampliar a amostra documental. As datas dependem das premissas de capacidade indicadas.

O histórico das etapas 1 a 15 foi preservado. A sequência anterior havia sido reconstruída a partir das entregas disponíveis; o novo planejamento substitui essa sequência futura, mantendo as integrações financeiras adiadas e as decisões de escopo já registradas.

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

## Histórico das etapas 10 a 14 — entregas e adiamentos

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

## Automação de documentos — histórico da frente ativa

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

Continuar o **ciclo 2**, iniciado após o inventário do ciclo 1: implementar
recuperação de senha com expiração, proteção de tentativas e revogação de sessões.
A restauração de contexto nos jobs DataJud/DJEN e comandos de lembretes foi
implementada; o workflow de CI está preparado localmente, aguardando primeira
execução no GitHub. Detalhes e pendências em
[CICLO_2_CONFIABILIDADE.md](CICLO_2_CONFIABILIDADE.md).

O ciclo 1 continua com pendência de ampliar a amostra para dez modelos ou revisar
explicitamente esse critério: o único modelo disponível foi validado no Word 16.0
em dois cenários e preservado. A proteção de acesso, a contagem regressiva e o
inventário técnico estão em [CICLO_1_VALIDACAO.md](CICLO_1_VALIDACAO.md).
Essa pendência documental não impede as entregas técnicas independentes do ciclo 2.

Múltiplas partes ficam previstas para o ciclo 6. As integrações financeiras
(14A, 14B e 14C) continuam adiadas, sem data de retomada. O catálogo
`qualifications` permanece global, sem manutenção nesta fase. A etapa 13
permanece concluída e validada.

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


## Cronograma dos próximos passos — 16 semanas ativas

Premissa de capacidade: dois desenvolvedores em tempo integral, apoio parcial de QA/UX e responsável do escritório disponível semanalmente. Reservar aproximadamente 25% da capacidade para testes, ajustes e imprevistos. Para um desenvolvedor, estimar inicialmente 24–32 semanas e recalibrar após os primeiros dois ciclos. São estimativas de planejamento, não compromissos de entrega.

Início proposto: 21/09/2026. As oito etapas têm duas semanas cada; há uma pausa de planejamento de duas semanas no fim do ano. Feriados e disponibilidade efetiva devem ser absorvidos pela reserva ou pelo ajuste de escopo. A numeração histórica das entregas pode ser mantida; abaixo são ciclos da nova proposta.

| Ciclo / período | Entrega delimitada | Critério de saída e dependência |
| --- | --- | --- |
| 1 — 21/09 a 02/10 | Fechar Word atual; caracterizar acesso/isolamento; proteger login; levantar operação e desempenho inicial. | Dez modelos representativos revisados visualmente, incluindo condicionais já existentes; falhas críticas de acesso tratadas; backlog e ambiente de homologação definidos. |
| 2 — 05/10 a 16/10 | CI, recuperação de senha, MFA administrativo mínimo, backup/restore e monitoramento de filas/scheduler. | Restauração demonstrada; recuperação e revogação testadas; jobs sem contexto negados ou explicitamente administrativos; pipeline reproduzível. Depende do inventário do ciclo 1. |
| 3 — 19/10 a 30/10 | Converter publicação em tarefa/prazo com revisão humana, vínculo de origem e responsável distinto do autor. | Uma publicação pode gerar atividade rastreável sem duplicação em reenvio; atribuição, conclusão e consulta isoladas por escritório. Depende da base de acesso. |
| 4 — 02/11 a 13/11 | Central de trabalho: filtros por responsável, prioridade, atraso, reagendamento e lembretes internos. | Usuário identifica e conclui suas pendências; gestor vê não atribuídas e atrasadas; histórico preservado; jornada mobile testada. Depende do ciclo 3. |
| 5 — 16/11 a 27/11 | Modelos de sequência de tarefas por tipo de pasta, com responsáveis e datas configuráveis. | Dois fluxos reais do escritório executados; reexecução não duplica tarefas; exceções são visíveis. Sem editor genérico de automação nesta versão. |
| 6 — 30/11 a 11/12 | Múltiplas partes no Word e busca unificada inicial por cliente, pasta e metadados de documentos. | Seleção explícita de partes sem ambiguidade; dez modelos novamente homologados; busca respeita permissões e oferece acesso direto. Depende dos testes do ciclo 1. |
| 7 — 14/12 a 18/12 e 04/01 a 08/01/2027 | Importação assistida de clientes/pastas, documentação e início do piloto; corrigir gargalos medidos. | Prévia de importação, relatório de erros e prevenção de duplicação; três escritórios ou grupos reais iniciam uso acompanhado. Sem importação bancária. |
| Reserva — 21/12 a 01/01 | Pausa de novas entregas; ajustar cobertura de suporte se houver piloto ativo. | Não pressupor equipe disponível nas festas. |
| 8 — 11/01 a 22/01/2027 | Estabilizar piloto, medir resultado e decidir versão candidata para ampliar uso. | Pelo menos duas semanas efetivas de uso; nenhum defeito crítico aberto; jornadas essenciais aprovadas; plano de suporte e reversão validado. Depende de todos os critérios anteriores. |

Se segurança ou fidelidade documental demandarem mais esforço, deslocar múltiplas partes, busca ou templates de tarefas. Preservar critérios de proteção, recuperação e integridade; evitar comprimir validação para manter datas.

Não incluir cálculo automático abrangente de prazos nesta janela. Primeiro garantir captura, responsabilidade e revisão; um motor de calendários e regras necessita escopo próprio, fontes verificadas e validação especializada.

## Como medir sucesso

### Execução do ciclo 1 — 16/09/2026

- Implementados limites de login: cinco requisições por minuto por e-mail/IP
  e sessenta por IP; cadastro limitado a cinco por minuto por IP.
- Resposta HTTP 429 com intervalo para nova tentativa, exibida nas telas em português.
- Complemento: contagem regressiva no login/cadastro, bloqueio do envio durante
  a espera e liberação automática ao zerar; contador encerrado ao sair da tela.
- Revisados acesso por escritório, permissões, documentos e contexto de jobs/comandos;
  continuidade de isolamento e restauração de contexto registrada para o ciclo 2.
- Definidos requisitos do ambiente de homologação, ainda não provisionado,
  matriz de dez modelos e protocolo de carga com dados sintéticos.
- Microbenchmark XML disponível em `backend/scripts/benchmark-document-template.php`;
  não equivale a validação visual ou teste de capacidade da API.
- Backend completo: **446 testes e 2.117 assertions aprovados**. Frontend completo:
  **1.633 testes em 106 arquivos aprovados**, além de 25 testes direcionados.
  Build de produção aprovado, 2.155 módulos.
- Usuário informou um modelo de teste disponível; ampliar a amostra e concluir
  a homologação visual dos dez modelos permanece pendente.
  O modelo disponível foi renderizado e revisado no Word 16.0 em dois cenários,
  ambos aprovados, sem alteração do original. LibreOffice permanece ausente;
  a revisão visual foi realizada com o Word instalado.
  Nenhuma etapa posterior foi marcada como iniciada ou concluída nesta entrega.

### Execução do ciclo 2 — 16/09/2026

- Criada operação de contexto temporário com restauração garantida em sucesso,
  falha e chamadas aninhadas. Aplicada a DataJud, DJEN e lembretes financeiros.
- Jobs ignoram escritórios inativos; DataJud automático também ignora monitoramento
  desligado, preservando a sincronização manual autorizada.
  Envio de lembretes rejeita cobrança pertencente a outro escritório.
- Preparado `.github/workflows/ci.yml`: testes backend com MySQL descartável,
  testes frontend e build. YAML validado localmente; primeira execução remota pendente.
- Validação final direcionada: **23 testes e 111 assertions aprovados**.
  Backend completo: 455 de 456 aprovados; a falha de sincronização manual foi
  corrigida e revalidada no conjunto direcionado, sem nova execução integral.
  Frontend completo: **1.634 testes em 106 arquivos aprovados**.
- Próximas entregas: recuperação de senha, MFA administrativo, migração completa
  de consultas sem contexto, restauração de backup e monitoramento operacional.
- O ciclo 2 permanece em andamento; nenhuma implantação ou envio real de e-mail
  foi realizado nesta entrega.

### Metas do piloto

Metas abaixo são propostas para o piloto; não representam resultados medidos:

- Pelo menos 90% das jornadas centrais concluídas sem ajuda em observação guiada: cadastrar cliente/pasta, tratar publicação, concluir tarefa e gerar documento.
- Redução de pelo menos 30% no tempo mediano de preparar um documento em comparação com o processo atual do mesmo escritório.
- Todas as atividades originadas de publicação com origem e situação rastreáveis; alertar explicitamente sobre pendências sem responsável.
- Zero defeito crítico conhecido de isolamento, integridade financeira ou perda de documento para liberar uso ampliado.
- Meta inicial de restauração: perda máxima de 24 horas de dados e retorno em até quatro horas, a confirmar com o escritório e demonstrar em ensaio.
- Medir p95 das consultas centrais e memória dos relatórios com volume e concorrência definidos no ciclo 1; meta inicial de até dois segundos para consultas usuais, separando dependências externas e exportações.
- Registrar atraso de sincronização, falhas de jobs e última execução bem-sucedida. Não prometer cobertura total ou tempo real de DJEN/DataJud sem medir a fonte.
- Três escritórios/grupos no piloto; acompanhar uso semanal, chamados, abandono de fluxos e intenção de continuidade. Validar disposição a pagar antes de investir em expansão comercial.

## Depois do piloto

Escolher uma frente por evidência: portal mínimo para reduzir pedidos de andamento; CRM para organizar aquisição/atendimento; assinatura para concluir contratos; ou IA assistiva para resumo com origem identificada e revisão humana. Avaliar custos, dados enviados a terceiros e precisão antes de implantar IA.

Aplicativo nativo, marketplace, BI amplo e infraestrutura distribuída não têm justificativa demonstrada agora. Priorizar experiência web móvel e indicadores operacionais úteis. Para comercialização SaaS, ainda será necessário planejar planos/limites, contratação, suporte e custo por escritório; não confundir o piloto com lançamento comercial completo.
