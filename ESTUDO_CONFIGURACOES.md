# Estudo: central de configurações e cadastros de apoio

Data: 14/09/2026. Estado: proposta aprovada e implementada.

## Implementação concluída

- Central em `/settings`, acessível pela sidebar e pelo cabeçalho, agrupando
  cadastros de apoio e acessos. Os cartões respeitam as permissões do usuário.
- Categorias em `/settings/financial-categories` e centros de custo em
  `/settings/cost-centers`, com uma implementação compartilhada, busca por nome,
  filtro de situação, criação, renomeação, desativação e reativação.
- Consulta com `finance.view` ou `expenses.view`; manutenção com `finance.manage`.
  Perfis e permissões continuam em `/settings/roles`, exigindo `roles.view`.
- Gerenciamento removido da FinancePage. Os seletores compartilhados oferecem
  atalhos em nova aba; a sessão do escritório é mantida e o formulário original
  permanece preenchido. Ao recuperar foco, os seletores recarregam o catálogo.
- Nenhuma alteração adicional no backend, schema ou dados de desenvolvimento.
- Validação: 114 testes direcionados e suíte completa com 1.607 testes em
  102 arquivos aprovados; build de produção aprovado (2.144 módulos).
  Navegação, edição sem salvar, nova aba e responsividade revisadas no navegador.

O estudo abaixo registra os fundamentos da decisão.

## Parecer

É recomendável criar uma área dedicada e aproveitar o item **Configurações** já
existente na sidebar. A manutenção de categorias e centros de custo deve sair da
página operacional FinancePage. A central deve organizar páginas específicas por
assunto, com permissões próprias e uma implementação compartilhada quando as
regras forem equivalentes.

O benefício esperado é reduzir a extensão do financeiro, dar um endereço estável
aos cadastros utilizados em mais de um módulo e acomodar novas configurações sem
acrescentar um item de sidebar para cada tabela. Essa é uma avaliação de arquitetura
e experiência de uso; não substitui observação de usuários reais.

## Referências consultadas

Foram consultadas documentações oficiais de um produto jurídico e dois produtos
financeiros/ERP. A seleção usa pertinência funcional, sem afirmar uma classificação
absoluta de liderança de mercado ou uma comparação completa desses produtos.

| Produto | Prática documentada | Aplicação ao Legalis |
| --- | --- | --- |
| Clio Manage | Categorias de atividades e despesas são administradas em Activities → Manage categories, com regras de acesso e proteção para categorias de despesas já utilizadas. | Dar endereço próprio à manutenção, mantendo o uso das categorias nos lançamentos. |
| Conta Azul | Centros de custo são administrados em Financeiro → Cadastros → Centros de custo; a documentação descreve criação, edição e inativação. | Tratar cadastros como uma função específica, distinta do registro de pagamentos e recebimentos. |
| Omie | A documentação de Configurações do Aplicativo reúne categorias, departamentos/centros de custo e grupos/permissões. | Uma central abrangente é compatível com a evolução do produto. |

Fontes, consultadas em 14/09/2026:

- [Clio — Activity Categories](https://help.clio.com/hc/en-us/articles/9289744400667-Activity-Categories)
- [Conta Azul — Centro de custo: o que é e como cadastrar](https://ajuda.contaazul.com/hc/pt-br/articles/7493213331725)
- [Omie — Principais Configurações](https://ajuda.omie.com.br/pt-BR/articles/9762562-principais-configuracoes-da-omie)

Os caminhos variam entre os produtos. A conclusão derivada das referências é a
separação entre manutenção dos cadastros e operação diária. A escolha de uma
central Configurações para o Legalis considera também sua estrutura atual.

## Diagnóstico do código local

- `frontend/src/config/menu.js`: Configurações aponta para `role-permissions`
  e exige `roles.view`.
- `frontend/src/router/index.js`: existe `/settings/roles`, sem página inicial
  de configurações.
- `frontend/src/components/navigation/SideBarNav/index.vue`: visibilidade usa
  uma única permissão por item.
- `frontend/src/router/guards/permission.js`: considera a primeira permissão
  encontrada na cadeia de rotas. Um requisito administrativo no pai poderia
  bloquear indevidamente um usuário autorizado apenas aos cadastros financeiros.
- `FinancialOrganization.vue` está inserido em FinancePage e já concentra a
  manutenção de categorias e centros de custo.
- API e tabela `financial_classifications` já atendem aos dois cadastros e ao
  isolamento por escritório. A mudança de navegação não exige migration.
- O ícone Configurações do HeaderBar atualmente não tem navegação associada;
  pode apontar para a mesma central, com as mesmas regras de acesso.

## Organização proposta

| Entrada na central | Destino proposto | Conteúdo |
| --- | --- | --- |
| Categorias financeiras | `/settings/financial-categories` | Lista, busca, filtro por situação, criação, renomeação, desativação e reativação. |
| Centros de custo | `/settings/cost-centers` | As mesmas operações, com linguagem e registros próprios. |
| Perfis e permissões | `/settings/roles` | Tela existente, mantendo sua responsabilidade e endereço. |

A sidebar teria uma única entrada **Configurações**, apontando para `/settings`.
A central exibiria os dois primeiros itens em um grupo **Cadastros de apoio** e
perfis em **Acessos**. Grupos futuros só devem aparecer quando houver funcionalidade
implementada, evitando cartões vazios e promessas de recursos.

“Configurações” comporta os cadastros e os acessos já existentes. “Tabelas de apoio”
é uma expressão mais técnica e restrita; “Cadastros de apoio” funciona melhor como
nome de grupo interno para o usuário do escritório.

Categorias e centros devem ter páginas distintas, reutilizando componentes de
listagem e formulário. O tipo é determinado pela página: o usuário não precisa
selecionar “categoria” ou “centro de custo” toda vez que cadastra um item.

## Fluxo de uso

1. O administrador abre Configurações e escolhe o cadastro desejado.
2. Consulta ou busca registros, com filtro Ativos/Inativos/Todos.
3. Cria ou mantém registros, recebendo confirmação ou erro junto à ação.
4. No financeiro e nas pastas, os formulários continuam apresentando os seletores.
5. Um atalho contextual “Gerenciar categorias” leva à página correspondente,
   apenas para quem pode gerenciar. Deve preservar o formulário em andamento
   ou alertar antes de uma saída que descarte dados.

A manutenção teria uma única implementação. A FinancePage manteria somente o
atalho contextual; não haveria uma segunda lista editável dos mesmos cadastros.

## Permissões e preservação de dados

- A central aparece quando o usuário pode acessar pelo menos uma de suas áreas.
  A visibilidade de cada cartão e a autorização da rota/API continuam independentes.
- Preservar inicialmente o contrato atual: consulta do catálogo com `finance.view`
  ou `expenses.view`; manutenção com `finance.manage`; perfis com suas permissões
  específicas. Não exigir `roles.view` para acessar o cadastro financeiro.
- Exibir o escritório ativo e limpar dados/formulários ao mudar de organização.
- Preservar desativação e reativação, vínculos existentes e restrições de tipo.
  Explicar que renomear altera o nome exibido nos registros vinculados.
- Não transformar a central em um editor genérico de tabelas do banco. Outros
  cadastros, especialmente os globais compartilhados entre escritórios, precisam
  de análise de domínio e autorização antes de ganhar manutenção pela interface.

## Implementação e validação sugeridas

Executar esta reorganização antes da etapa 12, como complemento de usabilidade
da etapa 11. Criar a central e as duas páginas, adaptar menu/guardas, reaproveitar
API/componentes e retirar o gerenciador embutido no financeiro. Preservar a rota
existente de perfis. Não há necessidade de recriar o banco.

Verificar acesso direto por URL, usuários com permissões financeiras sem acesso
a perfis, consulta sem edição, troca de escritório, retorno de navegação, manutenção
dos filtros e formulários, links contextuais e responsividade. Executar testes
de frontend proporcionais às alterações e build de produção.

Diretriz para próximos estudos: consultar documentação oficial de aplicações de
referência do domínio, registrar as fontes e adaptar os padrões às necessidades
do Legalis, distinguindo prática documentada de recomendação própria.
