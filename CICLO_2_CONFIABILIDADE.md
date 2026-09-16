# Ciclo 2 — confiabilidade e operação

Iniciado em 16/09/2026 por solicitação de continuidade. A janela de planejamento permanece 05/10 a 16/10/2026. O inventário do ciclo 1 foi realizado; sua meta de dez modelos permanece pendente porque há somente um modelo de teste disponível. Avançar nas entregas técnicas independentes não equivale a encerrar a homologação documental.

## Isolamento nas tarefas de segundo plano

`CurrentOrganization::run` estabelece um contexto durante uma operação e restaura o anterior em `finally`. Funciona quando o chamador não tinha escritório, quando já tinha um e em operações aninhadas. O método é destinado a operações internas confiáveis, não concede permissões a requisições HTTP e não substitui o middleware de acesso.

- **DataJud:** a busca inicial pelo ID persistido no job é explicitamente administrativa, removendo somente `OrganizationScope`. A sincronização e a trilha de execução ocorrem sob o escritório da pasta; sucesso e exceção restauram o contexto anterior. Jobs automáticos deixam de executar quando a organização está inativa ou o monitoramento da pasta foi desligado. A consulta manual autorizada continua funcionando com monitoramento desligado; apenas o controller define essa exceção, não um parâmetro fornecido pelo cliente.
- **DJEN:** mesma distinção entre localizar a inscrição do job e executar no seu escritório. Organizações e inscrições inativas são ignoradas; o serviço existente continua responsável pela importação e deduplicação.
- **Geração de lembretes:** regras são enumeradas explicitamente entre escritórios ativos; cada consulta de cobranças e geração de lembrete recebe seu próprio contexto. Exceção no processamento deixa de reter o escritório no processo.
- **Envio de lembretes:** a cobrança é carregada somente depois de entrar no escritório do lembrete. Uma referência a cobrança de outro escritório é recusada e registrada como falha. Erro de envio preserva a restauração e não impede processar o próximo lembrete. Lembretes de organizações inativas permanecem agendados sem envio.

As consultas administrativas removem apenas o escopo organizacional, preservando outros eventuais escopos. O escopo global ainda permite consulta sem contexto em outros consumidores: sua alteração para negar por padrão exige a migração completa dos comandos, serviços e testes. Não declarar esse item global concluído com esta entrega.

Não houve alteração de schema, execução de comandos de cobrança reais, envio externo de mensagens ou sincronização com provedores reais. Os testes usam banco de testes, clientes simulados e mailer falso.

## Integração contínua

Workflow preparado em `.github/workflows/ci.yml` para push, pull request e execução manual:

- Backend em Ubuntu 24.04, PHP 8.3 e serviço MySQL 8.4 descartável, com banco e usuário exclusivos do job. Credenciais no YAML são apenas desse serviço efêmero. Variáveis do job prevalecem sobre os valores locais de `phpunit.xml`, sem alterar esse arquivo.
- `composer install` usa o lockfile; `composer test -- --compact` executa a suíte completa. Cache, sessão e e-mail usam drivers de teste; filas são síncronas.
- Frontend em Node 24, dependências de `package-lock.json` com `npm ci`, suíte Vitest com dois workers e build Vite. A URL de API é local e não requer servidor real para os testes simulados.
- Permissão do token limitada a leitura; credenciais de checkout não persistidas; execuções anteriores do mesmo ref são canceladas. Nenhum deploy, scheduler ou worker de produção é executado.

O arquivo foi preparado localmente. A primeira execução no GitHub, instalação limpa em Linux e configuração dos checks obrigatórios na proteção de branches ainda precisam ser verificadas após envio ao repositório. Não foi realizado push nesta tarefa.

Referências de configuração consultadas: [checkout](https://github.com/actions/checkout), [setup-node](https://github.com/actions/setup-node) e [setup-php](https://github.com/shivammathur/setup-php).

## Validação

- 18 testes direcionados aprovados, 92 assertions: contexto aninhado, sucesso/falha de jobs, organizações inativas, monitoramento desligado, separação entre cobranças e restauração após falhas de consulta/e-mail.
- Formatação dos arquivos PHP alterados verificada pelo Pint.
- A regressão completa encontrou um caso de sincronização manual DataJud afetado pela exigência de monitoramento automático (455 de 456 aprovados, 2.175 assertions, 735,004 segundos). Corrigida a distinção entre consulta manual e execução automática.
- Revalidação final dos fluxos afetados: **23 testes aprovados, 111 assertions, 30,827 segundos**, abrangendo DataJud manual/agendado, DJEN, contexto e lembretes. A suíte completa não foi repetida após essa correção localizada; não apresentar os 456 casos como aprovados em uma execução final única.
- Frontend completo: 1.634 testes em 106 arquivos aprovados em 329,81 segundos, com dois workers.
- YAML do workflow validado com o parser local; instalação limpa e execução no GitHub ainda pendentes.
- O frontend de produto não foi alterado nesta entrega. Os resultados do complemento de contagem regressiva são históricos: 26 testes direcionados e build aprovados; a suíte completa acima já inclui esse complemento.

## Próximos passos do ciclo

1. Confirmar a primeira execução do CI no GitHub e tornar os checks obrigatórios antes de merge.
2. Implementar recuperação de senha com expiração, limite de tentativas e revogação de sessões.
3. Implementar MFA administrativo mínimo com recuperação de acesso.
4. Completar a migração das consultas sem contexto para operações administrativas explícitas e negar acesso implícito fora de contexto.
5. Demonstrar restauração de banco e arquivos em ambiente isolado; configurar monitoramento real de filas e scheduler.

O ciclo 2 permanece em andamento; nem o workflow local nem os testes com mocks certificam operação em produção.
