# Conciliação manual e fechamento gerencial — etapa 13

Implementação de 14/09/2026.

## Fluxo de trabalho

Em **Financeiro → Análise do período → Conciliação e fechamento**, selecione
o mês do pagamento e consulte recebimentos e saídas efetivamente registrados.
A página abre no mês anterior e separa pendentes, divergentes, conciliados e
cancelados. Os totais são sempre do mês inteiro, independentemente da paginação
ou do filtro de situação. Cancelados não compõem os totais.

Ao conferir, informe valor e data observados no extrato, a referência do
documento/linha e uma observação. Coincidência de valor e data marca o registro
como conciliado. Qualquer diferença registra divergência e impede o fechamento.
O valor zero permite registrar ausência da movimentação no extrato, com explicação.
A conferência não altera valores pagos, saldos de cobranças ou contas a pagar.

Uma leitura incorreta do extrato pode ser corrigida revisando a conferência, com
motivo obrigatório e preservação do antes/depois. Se o erro estiver no pagamento,
use o fluxo já existente de estorno e novo registro no Financeiro. Não existe
ajuste automático de saldo para ocultar uma diferença.

O fechamento exige um mês já encerrado e todos os pagamentos ativos conciliados.
O usuário revisa os totais e confirma com uma observação. É possível fechar um
mês sem movimentos após essa confirmação explícita. O mês corrente não pode ser
fechado antes de terminar.

O fechamento preserva uma fotografia dos registros e conferências. Alterações
posteriores em pagamentos ou conferências são sinalizadas como **Revisão
necessária** ao consultar o mês, exibindo os totais atuais e os do fechamento.
A correção financeira continua disponível. Reabra o mês com motivo, revise as
pendências e feche novamente; os fechamentos anteriores permanecem na auditoria.

## Escopo e regras

- Conferência manual de `payments` e `payable_payments`, no escritório atual.
  Não importa extratos nem executa movimentações bancárias.
- A referência do extrato é informada pelo usuário. Esta fase não possui
  cadastro de contas bancárias, validação automática de saldo do banco ou
  detecção de movimentos do extrato que ainda não existem no Legalis.
- O mês segue `paid_at`, com a convenção de armazenamento atual do financeiro.
  Timestamps de auditoria são exibidos em UTC. A disponibilidade para fechar um
  mês considera o calendário de America/Sao_Paulo.
- Fechamento gerencial com sinalização de alterações, conforme a alternativa
  prevista no cronograma. Não é bloqueio contábil/fiscal de lançamentos.
- A comparação inclui identidade, data, valor, método, referência e cancelamento
  do pagamento, além da conferência. Mudanças no nome do fornecedor/cliente não
  alteram a conferência de caixa.
- A conciliação registra somente o que foi efetivamente comparado pelo usuário;
  movimentos bancários ainda não cadastrados devem ser tratados no financeiro.

## Segurança e consistência

Consulta exige `finance.view`; conferir, fechar e reabrir exigem `finance.manage`.
As consultas e alterações são limitadas ao escritório ativo, inclusive a auditoria.
Não há endpoints de exclusão de conferências, fechamentos ou eventos de auditoria.

Uma assinatura dos dados do pagamento evita salvar conferência sobre uma versão
alterada. Uma assinatura do período impede fechamento com prévia desatualizada.
Conferências e fechamentos usam transações e bloqueio da organização para
serializar operações simultâneas desse fluxo. Pagamentos seguem disponíveis;
uma mudança concorrente ao fechamento será identificada na consulta subsequente.

Observadores registram criação e alteração/estorno de pagamentos, dentro da
transação usada pelos fluxos financeiros existentes. Conferências e fechamentos
registram usuário, data, motivo e antes/depois. Não são fabricados eventos para
pagamentos antigos: a auditoria começa com a implantação. A trilha é mantida
pela aplicação, sem alegação de inviolabilidade contra alteração direta no banco.

As tabelas novas são `financial_reconciliations`, `financial_closings` e
`financial_audit_events`. A migration foi aplicada ao banco local com
`php artisan migrate --force`, preservando os dados existentes. O banco de testes
recria o schema pela suíte com RefreshDatabase.

Rotas:

- `GET /api/finance/reconciliation?month=AAAA-MM`: totais, situação do mês,
  pagamentos e auditoria, com páginas de 25 itens e filtro de situação.
- `PUT /api/finance/reconciliation/{incoming|outgoing}/{id}`: registra ou revisa
  uma conferência com referência, nota e assinatura da versão consultada.
- `POST /api/finance/closing`: confirma o mês, assinatura e nota.
- `POST /api/finance/closing/reopen`: reabre o mês com motivo.

## Referências de produto

Validação: suíte completa de backend com 422 testes e 1.901 assertions; frontend
com 1.618 testes em 104 arquivos. Após a revisão visual, mais 48 testes de tela e
navegação aprovados. Build final aprovado com 2.150 módulos. Navegação e confirmação
mensal revisadas em desktop e 390×844, sem salvar fechamentos no ambiente local.

- [Conta Azul: campos e processos de conciliação](https://ajuda.contaazul.com/hc/pt-br/articles/32748375541005-Concilia%C3%A7%C3%A3o-banc%C3%A1ria-entendendo-os-campos-telas-e-processos): comparação de data e valor entre movimentação bancária e lançamento.
- [Conta Azul: diferenças na conciliação](https://ajuda.contaazul.com/hc/pt-br/articles/32789633592589-Concilia%C3%A7%C3%A3o-Erro-Saldo-com-diferen%C3%A7a-de-R): tratamento de diferenças e revisão de dados do pagamento.
- [Omie: fechamento do período](https://ajuda.omie.com.br/pt-BR/articles/1923558-fechamento-do-periodo-painel-do-contador): referência para controle de períodos e integridade das informações.

As referências orientam a sequência conferir → resolver divergências → fechar.
O Legalis utiliza nesta etapa conferência manual e sinalização de alterações,
sem reproduzir o bloqueio ou as integrações desses produtos.
