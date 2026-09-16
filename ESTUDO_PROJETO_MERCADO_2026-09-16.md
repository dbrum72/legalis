# Legalis — estudo do projeto, mercado e próximos passos

Data: 16/09/2026. Referência local: commit `a8ccf36`; árvore inicialmente limpa.

## Parecer executivo

O Legalis tem amplitude de um MVP avançado de gestão jurídica: pastas, clientes, equipe, publicações, agenda, financeiro e geração Word já se conectam em uma mesma aplicação. A maturidade operacional ainda precisa ser demonstrada. A recomendação é consolidar um produto para escritórios pequenos e médios, com foco em transformar informação processual em trabalho concluído e documentos revisados.

O financeiro é uma das áreas mais desenvolvidas. O maior ganho marginal agora está na operação jurídica e na confiabilidade. Acrescentar mais módulos financeiros ou iniciar uma IA generalista antes de validar os fluxos atuais dispersaria o esforço.

Essa avaliação é uma inferência técnica e de produto, não uma certificação de segurança, conformidade ou prontidão comercial. Não há dados de usuários, receita, retenção ou operação em produção suficientes para medir adequação ao mercado.

## Método e limites

- Inventário do repositório; leitura dos manifests, rotas, autenticação, isolamento organizacional, serviços de documentos e caixa, publicações, prazos, configurações e testes.
- Leitura da baseline, cronograma e estudos anteriores. Resultados antigos de testes são históricos, não resultados desta análise.
- Comparação com páginas oficiais de Astrea, ADVBOX, Projuris ADV e Clio consultadas nesta data. São referências relevantes, sem alegação de ranking independente ou liderança comprovada.
- Recursos dos concorrentes são declarações dos fornecedores; podem variar por plano, módulo, país e contratação. Não houve teste prático das plataformas ou comparação de SLA e preço total.
- Não foi feita inspeção visual nova da aplicação, auditoria exaustiva de cada arquivo, teste de invasão, carga ou validação de integrações externas reais. Ausência no repositório não prova ausência na infraestrutura.

## Estado do produto

| Área | Evidência e avaliação | Próximo avanço útil |
| --- | --- | --- |
| Acesso e equipe | JWT, organizações, vínculos ativos, convites, perfis e permissões. Base estruturada. | Recuperação de acesso, MFA administrativo e proteção de autenticação. |
| Clientes e pastas | Cadastros, vínculos, documentos, movimentações e atividades. Núcleo funcional presente. | Importação assistida, busca transversal e histórico do atendimento. |
| Publicações e processos | DJEN/OAB, revisão e vínculo com pasta; DataJud com jobs e agenda de sincronização. | Fechar o ciclo publicação → atividade/prazo → responsável → conclusão. |
| Agenda e tarefas | Dashboard, agenda, prazos, eventos e tarefas. | Delegação explícita, prioridades, lembretes, reagendamento e carga da equipe. |
| Documentos | Modelos por escritório, versões, arquivamento, substituição e geração DOCX. Condicionais já implementados. | Validar renderização real; múltiplas partes e ergonomia de preparação dos modelos. |
| Financeiro | Honorários, horas, despesas, recebíveis, pagamentos, contas a pagar, recorrência, fluxo de caixa, lembretes e CSV. | Consolidar confiabilidade e desempenho com volume representativo. |
| Conciliação | Conferência manual, divergências, fotografia do fechamento e auditoria. | Melhorar uso e métricas; preservar o adiamento das integrações bancárias. |
| Experiência | Design system próprio, componentes compartilhados e registros de revisão mobile. | Testar jornadas com usuários; reduzir densidade e navegação desnecessária. |
| Qualidade | 52 arquivos de testes backend e 106 frontend no inventário atual. | CI reproduzível e testes de jornada com frontend e API reais. |
| Operação | Scheduler, filas e health check no código. | Implantação documentada, restauração, alertas, métricas e gestão de incidentes. |

Há diferenças importantes entre nomes de recursos e seu alcance: o fechamento financeiro é gerencial, não bloqueio contábil; a conciliação não importa extratos; a projeção representa pendências atuais, não reconstrução histórica; a prévia textual do Word não comprova fidelidade de paginação.

## Avaliação técnica

### Arquitetura e manutenção

Laravel com serviços, jobs e clientes de integração, junto de Vue/Pinia, é uma arquitetura adequada ao estágio. Não há justificativa observada para reescrita, microserviços ou troca de framework. Os manifests declaram PHP ^8.3, Laravel ^13.8 e Vue ^3.5.40; essas faixas não certificam versões instaladas ou ausência de vulnerabilidades.

O custo de mudança tende a aumentar nas páginas grandes: a contagem de linhas não vazias encontrou cerca de 5.314 em `FinancePage.vue`, 2.985 em `AgendaPage.vue` e 2.750 em `DashboardPage.vue`. Isso não prova defeito, mas recomenda extrair seções e composables ao trabalhar nessas áreas, mantendo contratos e comportamento. Evitar uma refatoração geral desvinculada de entregas.

### Acesso e isolamento

`ResolveOrganization` valida organização e vínculo ativos, configura permissões e limpa o contexto ao final. As rotas têm verificações de permissão e existem testes de isolamento, sinais positivos.

Em `OrganizationScope`, a ausência de organização faz o escopo retornar sem filtro. Isso exige disciplina em jobs e comandos. Não foi demonstrado vazamento em endpoint; é um risco arquitetural a reduzir com falha explícita em operações comuns e uma via deliberada para consultas administrativas globais, acompanhada de testes.

Não foi localizada limitação explícita das rotas de login/cadastro nos arquivos consultados. O limitador encontrado no provider é de DataJud. Priorizar proteção por IP e identidade, respostas consistentes e testes; verificar também se existe proteção no proxy. Recuperação de senha e MFA não foram identificados nos fluxos inventariados.

O token fica em `sessionStorage`, acessível a JavaScript da origem. Revisar ameaça de XSS, política de conteúdo, expiração, revogação e gestão de sessão; não trocar automaticamente por cookies sem avaliar também CSRF e arquitetura de implantação.

### Dados, documentos e financeiro

O serviço DOCX valida XML e rejeita entidades externas. Suporta condicionais no mesmo parágrafo, sem aninhamento. É importante registrar esses limites na interface e validar tabelas, cabeçalhos, rodapés, estilos e quebras com documentos reais. A renderização Word permanece uma pendência registrada no cronograma anterior.

`CashFlowService` carrega pagamentos e cobranças do intervalo com `get()` e agrega em memória. Paginar a resposta final não limita esse trabalho. Medir tempo, memória e número de consultas; usar agregação SQL e exportação em lotes quando os resultados indicarem necessidade. Não há evidência atual para quantificar capacidade máxima.

A auditoria financeira e os bloqueios/transações de recorrência e conciliação são bons fundamentos. Ampliar rastreabilidade para alterações sensíveis de permissões, documentos e prazos, com autor, data, motivo e contexto organizacional.

### Testes e operação

A configuração de cobertura do Vitest exclui `src/views` e `src/router`: qualquer percentual obtido com ela não representará toda a aplicação. A presença de muitos testes é positiva, mas deve ser complementada por jornadas integradas e cenários de concorrência e falha.

Não foram encontrados arquivos de CI, Playwright/Cypress ou implantação por contêiner no inventário consultado. Contêiner não é requisito; reprodução da implantação, rollback e observabilidade são. Os READMEs principais ainda precisam descrever o produto e sua operação.

Antes de dados reais, estabelecer backup de banco e arquivos com teste de restauração e política de migração incremental. A regra histórica de recriar banco descartável não deve se aplicar a ambientes que tenham dados a preservar. Documentar retenção, exportação e exclusão; a análise atual não atesta conformidade legal.

## Comparação com referências de mercado

| Referência | Capacidades destacadas na fonte oficial | Posição do Legalis e aprendizado |
| --- | --- | --- |
| Astrea | Gestão de processos, publicações, atividades/kanban, finanças, documentos, atendimento e acesso móvel. | Há sobreposição no núcleo. A oportunidade é tornar a jornada diária mais simples e medir economia de tempo. [Central de ajuda](https://astrea.aurum.com.br/pt-BR) |
| ADVBOX | Planos com workflow automático, kanban, controladoria e métricas/BI. | O financeiro do Legalis é amplo em escopo; falta demonstrar gestão coordenada de produção e resultados por equipe. [Planos e recursos](https://advbox.com.br/planos) |
| Projuris ADV | Atendimento, WhatsApp, área do cliente, workflows, documentos, financeiro, IA e API/integrações na oferta completa. | As maiores lacunas observadas são atendimento estruturado, portal e automações entre módulos. [Produto oficial](https://www.projuris.com.br/adv/) |
| Clio | CRM/intake, horas, documentos, faturamento, calendário, comunicação e portal do cliente. | Referência para continuidade da jornada cliente → caso → documento → cobrança. Não implica equivalência com integrações judiciais brasileiras. [Funcionalidades](https://www.clio.com/features/) |

Astrea também documenta recursos de IA para peças; Clio apresenta automações de entrada de clientes e acompanhamento. Isso mostra uma direção de produto, mas não torna IA o primeiro investimento necessário do Legalis. [Ferramentas Astrea](https://astrea.aurum.com.br/pt-BR/articles/13562707-9-ferramentas-do-astrea-para-trabalhar-de-forma-mais-inteligente), [workflows Clio](https://www.clio.com/features/legal-workflow-automation-software/).

A distinção competitiva mais promissora é um fluxo curto, rastreável e adaptado ao escritório: publicação recebida, revisão humana, atividade atribuída, documento preenchido e entrega registrada. O repositório já contém grande parte das peças, mas sua integração operacional ainda deve evoluir.

Não há base para dizer que o Legalis supera esses produtos em usabilidade, segurança, velocidade ou custo. Isso exigirá testes com usuários, demonstrações equivalentes e custo de operação medido.

## Prioridades propostas

| Prioridade | Entrega | Motivo |
| --- | --- | --- |
| P0 | Proteção de acesso, isolamento em jobs, restauração e validação Word | Reduzir riscos antes de uso real e fechar a frente ativa. |
| P1 | Publicação → prazo/tarefa, responsável, lembretes e histórico | Aumentar valor diário da operação jurídica. |
| P1 | CI e jornadas integradas; operação monitorada | Sustentar evolução sem regressões silenciosas. |
| P2 | Templates de tarefas e busca global | Reduzir trabalho repetido e tempo de localização. |
| P2 | Importação assistida e piloto com métricas | Facilitar adoção e descobrir gargalos reais. |
| P3 | Portal, CRM ampliado, assinatura e IA assistiva | Escolher após evidência de demanda e viabilidade. |

Integrações financeiras externas, OFX/CSV bancário, cobrança externa e emissão fiscal continuam adiadas, conforme decisão registrada. Importação de clientes/pastas é uma frente diferente. O catálogo global de qualificações permanece sem manutenção nesta proposta.

## Cronograma sugerido: 16 semanas ativas

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

## Verificações desta análise

- `npm run build`: aprovado, 2.155 módulos transformados. A primeira tentativa foi impedida pelo sandbox (`spawn EPERM`); a execução com permissão ampliada concluiu com código zero.
- `php vendor/phpunit/phpunit/phpunit --testsuite Unit`: 24 testes aprovados, 56 assertions. Não exercita a suíte Feature e não comprova integrações externas reais.
- `npm test -- tests/router/permission-guard.spec.js tests/router/auth-guard.spec.js tests/views/folders/components/FolderDocumentAutomation.spec.js`: 20 testes aprovados em três arquivos. Também exigiu execução fora do sandbox após `EPERM`.
- Não foram reexecutadas as suítes completas de integração e frontend; seus resultados históricos permanecem no `BASELINE.md` e não são apresentados como validação atual.
- Alteração desta tarefa limitada a este estudo. O cronograma vigente permanece como histórico; as datas acima são uma proposta de substituição da sequência futura, sem iniciar implementação.
