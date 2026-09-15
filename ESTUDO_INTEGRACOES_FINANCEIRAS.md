# Integrações financeiras — estudo da etapa 14

Data: 15/09/2026. Estado: estudo concluído; implementação adiada para o futuro
por decisão do usuário em 15/09/2026, sem data de retomada.

As propostas abaixo ficam preservadas como referência futura. As frentes 14A,
14B e 14C não integram a sequência ativa do projeto. Revalidar fontes e escopo
quando houver solicitação de retomada.

## Recomendação

Começar por **contas bancárias e importação assistida de extratos**, depois
homologar um provedor de cobrança e definir a emissão fiscal com os dados do
escritório. Esta sequência é uma proposta técnica, sem contratação de fornecedor.

A importação complementa a conciliação entregue na etapa 13 e permite conferir
movimentos que ainda não existem no Legalis. Não depende de credenciais bancárias.

## O que existe no projeto

- `Invoice` representa cobrança interna, inclusive parcela; não é documento fiscal.
- `Payment` e `PayablePayment` representam recebimentos e saídas, com estorno.
- `FinancialReconciliationService` compara pagamentos internos com valor e data
  informados manualmente. O fechamento considera esses pagamentos, sem verificar
  a completude de um extrato bancário.
- Existem isolamento por organização, permissões financeiras, auditoria e fila
  de integrações usada pelo DataJud. A fila pode servir de infraestrutura, mas
  eventos financeiros precisam de contratos e processamento próprios.
- Não foram encontrados cadastro de conta bancária, importador de extrato,
  adaptador de cobrança externa ou emissão de NFS-e nos modelos e rotas examinados.

## 14A — Contas bancárias e importação assistida

### Escopo proposto

1. Cadastrar contas por escritório, com nome, banco e identificação da conta,
   moeda BRL e situação ativa/inativa. Sem coleta de senha bancária.
2. Importar OFX e CSV com um modelo explícito: data, descrição, valor com sinal
   e identificador opcional. CSVs de bancos distintos exigirão mapeamento posterior;
   a primeira versão não deve prometer reconhecimento de qualquer layout.
3. Exibir prévia com conta, intervalo, entradas, saídas, duplicados e erros por
   linha. Confirmar a importação somente após validação integral.
4. Sugerir pagamentos internos pelo sentido, valor e data. Exigir confirmação
   humana; coincidências múltiplas ficam pendentes.
5. Exibir movimentos sem correspondência. O usuário poderá resolver o lançamento
   pelo fluxo financeiro existente e retornar à conciliação.

### Dados e integridade propostos

- Separar conta, lote de importação, movimento bancário e vínculo de conciliação.
  Todos pertencem à organização; referências cruzadas devem validar esse escopo.
- Preservar valor em centavos, sinal, data original, descrição e origem. Datas
  ambíguas e valores inválidos devem produzir erro, sem conversão silenciosa.
- OFX: usar identidade da conta e `FITID` para detectar repetição, com restrição
  única por organização/conta/identificador. A especificação define identidade de
  transação no contexto da conta. [Especificação OFX 2.3](https://financialdataexchange.org/common/Uploaded%20files/OFX%20files/OFX%20Banking%20Specification%20v2.3.pdf).
- Identificador repetido com conteúdo diferente é conflito a revisar, não atualização
  automática. CSV sem identificador: hash do arquivo evita reenvio idêntico; mesma
  data/valor/descrição apenas sinaliza suspeita, pois pode haver pagamentos legítimos
  iguais. Não eliminar essas linhas automaticamente entre arquivos diferentes.
- Primeira versão: vínculo de um movimento com um pagamento. Tarifas, liquidações
  agrupadas, transferências e diferenças de valor ficam pendentes para tratamento
  explícito; não ajustar a cobrança para forçar coincidência.
- Revalidar versão, saldo e cancelamento do pagamento na confirmação, em transação.
  Impedir que dois usuários vinculem simultaneamente o mesmo movimento/pagamento.
- Importar extrato não gera receita, despesa nem baixa automática. Extrato e
  pagamento são evidências do mesmo movimento, não duas entradas de caixa.
- Limitar tamanho e quantidade de linhas; rejeitar XML com entidades externas e
  referências remotas. Guardar arquivos em armazenamento privado e autorizar acesso.
  Se houver exportação de descrições para CSV, neutralizar fórmulas de planilha.

### Fechamento e experiência

Cadastro em Configurações; importação e pendências em Conciliação e fechamento.
Reutilizar componentes e semântica de botões existentes. Consulta com `finance.view`;
importação, vínculos e manutenção com `finance.manage`.

Preservar o significado do fechamento manual existente. Uma futura indicação de
"extrato conferido" precisa verificar movimentos sem vínculo, conta e cobertura
do intervalo. Não atribuir completude bancária a fechamentos antigos. Alterar ou
desvincular evidências deve deixar auditoria e sinalizar revisão do fechamento.

### Critérios de aceite

- Reimportação idêntica e importações concorrentes não duplicam movimentos.
- Arquivo inválido não deixa lote parcialmente confirmado.
- OFX com formatos suportados, encoding e datas com fuso; CSV com vírgula decimal,
  campos entre aspas, duplicados legítimos e arquivo acima do limite têm testes.
- Contas, lotes e pagamentos de outro escritório são inacessíveis.
- Baixa/estorno concorrente invalida a prévia; vínculo não altera totais de caixa.
- Pendências bancárias ficam visíveis e não são tratadas como conciliadas.
- Testes de API e interface, build e revisão desktop/mobile antes da entrega.

## 14B — Provedor de cobrança

Comparação inicial de duas alternativas, sem ranking de preço ou contratação:

| Alternativa | Evidência de capacidade | Ponto de avaliação para o Legalis |
| --- | --- | --- |
| Asaas | Cobranças por boleto, Pix e cartão; referência externa e acompanhamento por webhook | Candidato à primeira prova em sandbox por proximidade com o modelo de cobrança existente |
| Efí | API Pix com notificações e homologação; documentação de configuração mTLS | Alternativa quando o foco operacional for Pix e houver infraestrutura para certificados |

O guia do Asaas orienta persistir IDs, tratar eventos repetidos e consultar operações
inconclusivas antes de repetir criação. Também recomenda coordenar notificações,
relevante porque o Legalis já envia lembretes. [Guia de cobranças Asaas](https://docs.asaas.com/docs/guia-de-cobrancas).

A documentação da Efí descreve autenticação mútua e configuração do servidor para
webhooks Pix. Essa exigência deve entrar na homologação da infraestrutura.
[Webhooks Efí](https://dev.efipay.com.br/docs/api-pix/webhooks/).

### Contrato técnico proposto

- Conexão por organização, credenciais cifradas, ambientes separados e IDs externos
  vinculados à conexão. Nunca resolver escritório apenas por campo recebido no evento.
- Uma cobrança externa por parcela interna na primeira versão, evitando reproduzir
  simultaneamente o parcelamento no provedor e no Legalis.
- Operação de emissão persistida antes da chamada; respostas inconclusivas ficam em
  consulta/revisão, sem recriação cega. Não presumir suporte a chave de idempotência
  na API escolhida sem validar o endpoint.
- Autenticar webhook conforme o provedor, persistir evento de forma durável e
  processar em fila com deduplicação. Validar cobrança, moeda, valor e transições;
  eventos fora de ordem não devem regredir uma liquidação.
- Separar confirmação, recebimento, tarifa, valor líquido e devolução. A regra de
  baixa deve ser definida por meio de pagamento durante a homologação.
- Reutilizar serviços de pagamento e auditoria. Estorno local não deve solicitar
  devolução externa implicitamente. Reembolso parcial exige modelagem específica.
- Selecionar um responsável pelos lembretes para evitar mensagens duplicadas.

Antes de escolher: levantar provedor já utilizado, meios de pagamento, volume mensal,
ticket médio, tarifas negociadas, prazo de recebimento e necessidade de cada
escritório manter sua própria conta. Preços e condições não foram cotados.

## 14C — Emissão fiscal

Existe documentação oficial de integração do Emissor Público Nacional, com manuais
e esquemas de produção. A existência da API não define o enquadramento de um
escritório específico. [Documentação atual da NFS-e](https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica/documentacao-atual).

Levantar município, CNPJ/CPF do prestador, regime tributário, inscrição municipal,
serviço, retenções, credenciamento/certificado e emissor usado hoje. Com essas
informações, validar com a contabilidade se convém integração nacional direta ou
provedor fiscal com cobertura apropriada. Não inferir obrigação fiscal pelo pagamento.

O domínio fiscal deverá guardar documento, status, chave, XML, representação e
eventos de cancelamento/substituição separadamente da cobrança. Cancelar cobrança
ou estornar pagamento não equivale a cancelar documento fiscal.

## Resultado deste ciclo

Estudo e sequência de implementação registrados. Não houve alteração de código,
schema, credenciais ou dados financeiros. Não foram executadas transações externas.
Na retomada futura, a primeira entrega sugerida é **14A — contas bancárias e
importação assistida**, com layouts delimitados acima. Escolha comercial e
enquadramento fiscal permanecem em aberto.
