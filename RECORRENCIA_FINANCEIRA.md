# Contas a pagar recorrentes — etapa 12

Implementação de 14/09/2026.

## Uso

Em **Financeiro → Contas a pagar → Contas recorrentes**, crie uma série com
fornecedor, descrição, valor, primeiro vencimento, periodicidade e término
opcional. Categoria e centro de custo são opcionais. O formulário mostra os
primeiros quatro vencimentos antes de salvar.

- Periodicidades: mensal, trimestral, semestral e anual.
- O primeiro vencimento deve ser hoje ou futuro, limitado a cinco anos à frente.
- O término é inclusivo. Sem término, a série continua até ser pausada.
- O dia original é preservado: 31/01 → 28 ou 29/02 → 31/03. A recorrência anual
  iniciada em 29/02 recupera esse dia nos próximos anos bissextos.
- Ao salvar, as ocorrências até 30 dias à frente são criadas na mesma transação
  da série. A rotina diária mantém essa janela. Datas usam America/Sao_Paulo.
- **Ver próximas** permite conferir e gerar antecipadamente até 90 dias à
  frente. Alterar o horizonte exige atualizar a prévia antes da geração.
- **Editar futuras** altera valor e descrição somente nas contas ainda não
  geradas. Calendário, fornecedor e classificações permanecem fixos na série;
  para modificá-los, pause a série e crie outra a partir do vencimento desejado.
- Pausar não cancela contas existentes. Retomar recupera vencimentos pendentes
  durante a pausa; eles aparecem na prévia. Cada execução processa até 120
  ocorrências por série, mantendo o cursor para a próxima execução.
- Contas geradas usam o fluxo normal de edição, pagamento e cancelamento.
  Excluir fisicamente uma ocorrência é impedido para preservar sua identidade;
  cancelar mantém o histórico e não faz a conta reaparecer na próxima geração.
- A série concluída aparece no filtro **Todas (inclui concluídas)**.

Os alertas na área de contas a pagar mostram obrigações com saldo que vencem
hoje ou nos próximos sete dias. **Consultar vencimentos** aplica esse filtro,
independentemente do filtro anterior. São avisos na aplicação, sem envio de
e-mail ou execução de pagamentos.

## Persistência, permissões e operação

As novas tabelas `payable_recurrences` e `payable_recurrence_occurrences` guardam
o modelo da série, o próximo vencimento a gerar e os vínculos com as contas.
A migration cria esse novo domínio; não altera campos das tabelas existentes.
Foi aplicada ao banco local com `php artisan migrate --force`, preservando dados.
O schema novo também é recriado no banco de testes pela suíte com RefreshDatabase.

Consulta exige `finance.view`; criação, alteração e geração exigem
`finance.manage`. Rotas e consultas são limitadas ao escritório atual. A rotina
percorre apenas escritórios ativos e restaura o contexto ao terminar.

O bloqueio transacional da série serializa gerações concorrentes. A restrição
única `(payable_recurrence_id, due_on)` impede ocorrências duplicadas; criação da
conta, registro da ocorrência e avanço do cursor são atômicos. Repetir a geração
para o mesmo horizonte não recria contas existentes, canceladas ou editadas.

Classificações e vínculos são revalidados antes da geração. Uma classificação
desativada impede novos vínculos. Falhas da rotina ficam registradas na série,
preservam o cursor, são reportadas no log e não impedem a tentativa das demais
séries. A próxima geração bem-sucedida limpa o aviso.

Comando: `php artisan finance:generate-payables`.

Agendamento registrado no Laravel: diariamente às **07:00 de America/Sao_Paulo**,
com proteção contra sobreposição e execução em vários servidores. O comando
`php artisan schedule:list` confirma o registro. A execução automática depende
de o scheduler Laravel estar ativo no ambiente de implantação, como nas demais
rotinas já existentes. Esta entrega não instala um agendador do sistema operacional.

## Validação

Backend com 415 testes e 1.837 assertions; frontend com 1.612 testes em
103 arquivos, todos aprovados. Build de produção aprovado com 2.147 módulos.
Prévia e formulário revisados em desktop e em 390×844, sem transbordamento
horizontal e sem salvar contas no banco de desenvolvimento.

## Referências de produto

- [Conta Azul: criação de lançamentos recorrentes](https://ajuda.contaazul.com/hc/pt-br/articles/7488241321229-Lan%C3%A7amentos-recorrentes-como-criar-no-Contas-a-Pagar-e-Contas-a-Receber): frequência, término e consulta dos vencimentos previstos.
- [Conta Azul: edição de despesa recorrente](https://ajuda.contaazul.com/hc/pt-br/articles/11851347774093-Conta-a-pagar-recorrente-como-editar): distinção entre alterações na ocorrência e na série.
- [Omie: despesa recorrente](https://ajuda.omie.com.br/pt-BR/articles/499171-cadastrando-uma-despesa-recorrente): repetição de compromissos como aluguel e despesas operacionais.

As referências orientam o fluxo. A janela de geração, os limites e as regras
de pausa descritos acima são decisões da implementação do Legalis.
