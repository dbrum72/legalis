# Ciclo 1 — acesso, documentos e preparação operacional

Início efetivo: 16/09/2026, por solicitação de avanço do cronograma. A janela de referência continua 21/09 a 02/10/2026. Situação: em andamento; este documento não autoriza marcar o ciclo como concluído.

## Entrega de proteção de acesso

- Login: até cinco requisições por minuto para a combinação de e-mail/IP e sessenta por minuto por IP, mesmo alternando e-mails. Contam requisições válidas, inválidas e bem-sucedidas; não há bloqueio permanente da conta.
- Cadastro: até cinco requisições por minuto por IP, em orçamento separado do login. Limites implementados com o middleware nativo de throttle e os limitadores nomeados do Laravel.
- O e-mail é normalizado apenas para a chave de limitação (espaços externos e caixa); a chave de identidade usa SHA-256. Dados malformados não causam erro no limitador. Senhas não compõem chaves ou mensagens.
- Resposta HTTP 429 com `Retry-After`, `retry_after` em segundos e mensagem em português. Login e cadastro exibem contagem regressiva baseada no tempo informado pela API, bloqueiam o envio durante a espera e liberam nova tentativa ao terminar. Sem intervalo válido, usam 60 segundos. O servidor continua responsável por aplicar o limite; o contador é encerrado ao sair da tela.
- Limites por e-mail/IP evitam bloquear a conta globalmente a partir de outro endereço. Não são proteção completa contra ataque distribuído; MFA e monitoramento permanecem no ciclo 2.
- Em múltiplas instâncias, usar cache compartilhado e persistente; `array` é exclusivo para testes. Ajustar limites com base no tráfego real de escritórios que compartilham IP.
- Não confiar indiscriminadamente em `X-Forwarded-For`. O proxy de homologação/produção deve ser configurado explicitamente e seu IP efetivo verificado. O teste local confirma que cabeçalhos forjados não alteram o orçamento sem configuração de proxy confiável.

## Revisão de acesso e isolamento

| Fluxo examinado | Evidência | Situação / ação |
| --- | --- | --- |
| API com escritório | `ResolveOrganization` verifica organização e vínculo ativos; configura permissões e limpa contexto em `finally`. | Preservar e verificar pela suíte de tenancy. |
| Permissões ao trocar escritório | Testes `TenantAuthorizationTest` cobrem papéis distintos e autorização por organização. | Cobertura existente incluída na regressão completa. |
| Vínculos e documentos | Testes de documentos e automação exercitam modelo de outro escritório, permissões, partes e preservação de versões. Armazenamento local é privado. | Não identificada exposição comprovada nesta revisão; não equivale a auditoria exaustiva. |
| Escopo sem contexto | `OrganizationScope` não filtra quando não há organização atual. | Prioridade do ciclo 2: negar consultas comuns sem contexto e criar via explícita de administração. Exige revisar consumidores antes de mudar. |
| DataJud em fila | Job consulta pasta por ID; serviço persiste pela relação da pasta. Não estabelece contexto organizacional explicitamente. | Revisar contexto, organização ativa e execução sequencial entre escritórios no ciclo 2. |
| DJEN | Serviço rejeita contexto de outra organização, estabelece contexto se ausente e o limpa em `finally`. | Preservar comportamento; testar junto da futura alteração global de escopo. |
| Lembretes automáticos | `GenerateAutomaticInvoiceReminders` limpa contexto após sucesso, sem `finally` para exceção no processamento. | Corrigir restauração em sucesso/falha antes da homologação com scheduler persistente. Nenhum vazamento em endpoint foi demonstrado. |
| Recorrência financeira | Comando preserva contexto anterior e restaura em `finally`. | Referência para padronizar comandos. |

Não foi feita mudança global no escopo nesta entrega: existem comandos e testes que dependem de consultas de plataforma. A migração deve ser explícita e validada, conforme o ciclo 2.

## Homologação dos documentos Word

Há uma fixture sintética versionada: `backend/tests/fixtures/word-template.docx`. O usuário informou que existe apenas um modelo de teste em `backend/storage/app/private/organizations/1/document-templates`. Nesse diretório foram encontrados três DOCX; o mais recente, `34d5b8ab-385c-4a68-b7ac-73f22e385309.docx`, foi selecionado para validação. Os arquivos não foram contados como três modelos independentes.

O renderizador da skill de documentos foi executado sobre a fixture e reportou `LibreOffice soffice.exe was not found on PATH`. A instalação padrão também não foi encontrada. A revisão visual foi realizada pela alternativa nativa: Microsoft Word 16.0, abertura somente para leitura com macros desabilitadas, exportação PDF e renderização PNG pelo Poppler. Foram inspecionadas todas as quatro páginas resultantes (duas do original e uma de cada saída).

Resultado do modelo disponível: aprovados os dois cenários testados, preenchimento completo e ausência conjunta de RG/órgão emissor/número do processo. Nome, data, timbre, fundo e assinatura foram preservados visualmente; os trechos condicionais desapareceram no segundo cenário, sem marcadores remanescentes ou texto cortado. O modelo original ocupa duas páginas, sendo a segunda apenas timbrada; ambas as saídas ocupam uma página com os valores sintéticos utilizados. Essa redução é compatível com a remoção dos marcadores e não representa perda de texto observada. Isso não certifica outros comprimentos ou layouts.

O SHA-256 original foi conferido após a revisão e permaneceu `74096f6a66da5796bb41f9c129f44dc0d183bfd7cdc4fb2a16c2d4a3f90e465a`. As saídas de QA têm hashes `6c891701f6517b69994b92ae0e7226369c9ac2a4e2d71c88725391cb877d2265` (preenchido) e `14676466eebbf30cf0536088729a845ac1700d5e06cb46569031e2c0497efb69` (opcionais ausentes). Os arquivos e imagens ficaram no diretório ignorado `backend/storage/framework/testing/word-cycle1`, sem substituir modelo ou cadastrar documentos no banco.

Matriz proposta para selecionar dez modelos reais com o escritório. A amostra atual cobre parcialmente as características dos casos 1, 2 e 3 com um único modelo; a homologação de dez modelos distintos continua **pendente**:

| Caso | Característica a representar | Conferência obrigatória |
| --- | --- | --- |
| 1 | Modelo curto com cabeçalho e rodapé | Logo, margens e estilos preservados. |
| 2 | Qualificação com RG e órgão emissor | Omissão integral quando faltar campo; pontuação correta. |
| 3 | Documento anterior à distribuição | Número do processo ausente sem texto residual. |
| 4 | Modelo longo com várias páginas | Quebras, numeração e assinaturas sem cortes. |
| 5 | Tabela com marcadores | Largura de colunas, bordas e conteúdo extenso. |
| 6 | Marcador dividido entre formatações | Substituição completa e estilo coerente. |
| 7 | Texto livre com várias linhas | Quebras e espaçamento preservados. |
| 8 | Nomes longos, acentos e caracteres especiais | Sem truncamento ou corrupção de texto. |
| 9 | Condicionais múltiplas no mesmo parágrafo | Combinações preenchidas/vazias; sem marcadores remanescentes. |
| 10 | Modelo substituído e posteriormente arquivado | Nova geração bloqueada após arquivo; documento antigo inalterado. |

Para cada modelo registrar: identificação anonimizada, versão/hash original, dados fictícios de preenchimento, versão/hash gerada, aplicativo/versão usados para abrir, quantidade de páginas, imagens da revisão e responsável pela aprovação. Comparar original e saída. Não copiar dados sensíveis para artefatos de teste versionados.

Dependência para fechar a meta original de dez modelos: ampliar o conjunto disponível ou revisar explicitamente esse critério com o usuário. Dois cenários do mesmo modelo não contam como dois modelos. Condicionais já existem; não implementar novamente nem antecipar múltiplas partes do ciclo 6.

## Definição do ambiente de homologação

Especificação definida abaixo; ambiente remoto ainda não provisionado. Não há endereço ou infraestrutura de homologação confirmados neste trabalho.

- Instância isolada da produção, com HTTPS, domínio próprio, `APP_ENV=staging` e `APP_DEBUG=false`; API servida a partir de `backend/public` e frontend compilado.
- Banco MySQL exclusivo, usuário restrito, chaves próprias e dados sintéticos de dois escritórios. Nenhuma credencial de produção ou conta padrão de seeder com senha conhecida.
- Cache compartilhado entre API e workers; armazenamento privado persistente para arquivos; nenhum compartilhamento de banco, cache ou documentos com produção.
- Mailpit ou destinatários de teste bloqueados por configuração. Sincronizações externas desativadas inicialmente; executar ensaios controlados antes de habilitar scheduler. Não enviar lembretes reais durante homologação.
- Worker supervisionado para `integrations,default` e scheduler supervisionado; conferir última execução, retries e erros. Worker deve reiniciar após implantação.
- Configurar origem CORS e frontend URL para o domínio escolhido: hoje CORS enumera origens locais. Definir proxy confiável sem permitir cabeçalho arbitrário determinar IP.
- Instalar renderizador DOCX e fontes representativas, registrar versões; comparar também no editor usado pelo escritório.
- Validar `/up`, autenticação, troca de escritório, acesso negado, geração/download, falha da fila e limites de acesso. Health check HTTP sozinho não certifica banco, filas e e-mail.
- Banco e arquivos devem entrar no mesmo plano de backup/restauração; ensaio e alertas serão implementados no ciclo 2. Nunca executar `migrate:fresh` sobre dados de homologação/produção a preservar.

## Desempenho inicial

Ambiente observado: Windows, PHP CLI 8.3.10 e Node 26.5.1. O build transformou 2.155 módulos em 3,08 s nesta execução. O logo da sidebar tem aproximadamente 699 kB e o chunk FinancePage cerca de 98 kB sem gzip. Esses tamanhos ajudam a priorizar medição; não comprovam lentidão percebida.

Microbenchmark reproduzível: executar `php scripts/benchmark-document-template.php` em `backend`. Não escreve documentos nem consulta o banco; mede somente processamento XML da fixture após bootstrap. Cinco aquecimentos, cinquenta amostras, concorrência um; outros testes estavam em execução no computador.

| Métrica observada | Resultado |
| --- | --- |
| Fixture | 38.117 bytes; SHA-256 `1b6ab5815bd83ca7bb34b5ac795d60849ffa60d42a83568b412909ccc3a1306e` |
| Mediana | 47,581 ms |
| p95 | 96,189 ms |
| Máximo | 107,420 ms |
| Pico de memória do processo, incluindo framework | 22 MiB |

Não extrapolar para latência HTTP, abertura no Word, arquivos grandes ou capacidade de produção. As consultas financeiras continuam agregando em memória; a medição de API permanece pendente de massa isolada.

Protocolo de carga definido para homologação: dois escritórios sintéticos; por escritório, 500 clientes, 1.000 pastas e 10.000 registros financeiros distribuídos em doze meses. Executar primeiro com um usuário e depois dez usuários concorrentes; cinco aquecimentos e cem consultas medidas por cenário. Medir p50/p95, erros, memória e consultas SQL de dashboard, pastas e caixa mensal/anual; medir exportação separadamente. Meta inicial de p95 até dois segundos para consultas usuais, sem incluir fonte externa. Massa não criada no banco local nesta entrega.

## Backlog de continuidade

1. Ampliar a amostra até a meta de dez modelos, ou revisar explicitamente essa meta; o modelo disponível foi validado em dois cenários, mas a condição original de conclusão do ciclo 1 continua pendente.
2. Confirmar domínio/host de homologação e provisionar conforme especificação, sem reutilizar dados reais.
3. Entrega inicial do ciclo 2 realizada: restauração de contexto nos lembretes e jobs DataJud/DJEN, com testes de falha e troca de escritório. A migração global das consultas sem contexto permanece pendente; acompanhar em `CICLO_2_CONFIABILIDADE.md`.
4. Implantar CI, recuperação de senha, MFA administrativo, restauração e monitoramento previstos no ciclo 2.
5. Executar carga isolada antes de otimizar consultas e dividir páginas; não tratar o microbenchmark como teste de capacidade.

## Validação desta entrega

- Testes novos de limitação: seis aprovados, 103 assertions.
- Testes direcionados das telas de login/cadastro: 25 aprovados, incluindo mensagens HTTP 429 e possibilidade de nova tentativa.
- Build de produção aprovado. Execução do Vite/Vitest exigiu permissão fora do sandbox após `spawn EPERM`.
- Backend completo: 446 testes aprovados, 2.117 assertions, 567,753 segundos.
- Frontend completo: 1.633 testes em 106 arquivos aprovados, 192,99 segundos, com dois workers.
- Word: original e duas saídas sintéticas renderizados e inspecionados; original preservado por hash. Validação feita no Word instalado, não no LibreOffice indisponível.
