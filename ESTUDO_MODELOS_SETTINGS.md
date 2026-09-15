# Modelos Word em Configurações

Estudo de 15/09/2026. Recomendação: **migrar a manutenção dos modelos para
Configurações e manter a geração do documento na pasta**.

Atualização: migração aprovada pelo usuário e implementada em 15/09/2026,
preservando as permissões atuais. Guia atualizado em `AUTOMACAO_DOCUMENTOS.md`.
As seções abaixo registram o estudo que orientou a implementação.

## Fundamentação no código atual

`DocumentTemplate` pertence à organização, sem vínculo com pasta. Importação,
listagem e download do original já usam `/api/document-templates`, enquanto
contexto, prévia e geração usam `/api/folders/{folder}/document-generation`.
A separação existe no backend e pode ser refletida na navegação.

Hoje `FolderDocumentAutomation.vue` reúne preparação do modelo, importação,
download do original e geração da cópia. Isso exige abrir uma pasta para
cadastrar um recurso compartilhado por todo o escritório e mistura duas tarefas
com frequências diferentes: preparar modelos e produzir documentos.

A central de Configurações já reúne cadastros de apoio. Uma página de modelos
segue o mesmo padrão adotado para categorias financeiras e centros de custo.
Esta conclusão é uma avaliação da arquitetura local; não é resultado de teste
de usabilidade com usuários.

## Distribuição recomendada

| Configurações → Modelos de documentos | Pastas → Documentos |
| --- | --- |
| Lista dos modelos do escritório, com busca | Seleção do modelo disponível |
| Importar arquivo `.docx` e nomear o modelo | Seleção do cliente da pasta |
| Consultar marcadores e preparar modelos no Word | Preenchimento e ajuste dos valores para a cópia |
| Baixar o modelo original para edição externa | Prévia e indicação de campos faltantes |
| Identificar cada importação e sua data | Gerar `.docx`, salvar na pasta e baixar o resultado |

Endereço sugerido: `/settings/document-templates`, em **Cadastros de apoio**.
Não é necessário criar outro item na sidebar.

"Apenas exportação" deve conservar os passos de seleção, preenchimento e revisão
necessários à geração. Um botão de download isolado não informa quais dados ou
qual modelo serão utilizados. A lista e as funções de anexos existentes da pasta
continuam com sua finalidade; a mudança proposta atinge a manutenção de modelos.

## Descoberta e continuidade do trabalho

A pasta pode oferecer um link discreto **Gerenciar modelos**, para usuários
autorizados, abrindo a página de Configurações em nova aba. O formulário de
importação terá apenas um endereço.

Ao retornar à pasta, atualizar a lista dos modelos sem limpar o modelo escolhido,
os valores preenchidos ou a prévia. Se não houver modelos, explicar que o cadastro
fica em Configurações; oferecer o link somente a quem pode utilizá-lo.

## Ajustes técnicos necessários

1. Extrair o formulário de importação, as instruções e o download do original
   para uma página dedicada. Reutilizar os componentes do design system.
2. Adicionar cartão, rota e regras de acesso em `config/settings.js`, router e
   menu. O HeaderBar já consome `settingsPermissions`; alinhar todos os pontos.
3. Expor o catálogo de marcadores por endpoint independente de pasta. Hoje
   `catalog()` é privado e seus dados chegam ao frontend pela consulta de contexto
   de uma pasta, junto às partes. A tela administrativa não precisa consultar partes.
4. Manter listagem de modelos disponível ao gerador, bem como contexto, prévia,
   assinatura, geração e download do resultado nos contratos atuais.
5. Preservar troca de organização, descarte de respostas atrasadas, bloqueio de
   envio duplicado e feedback de erro. Atualização ao recuperar foco não deve
   sobrescrever dados que o usuário já está revisando.

Não é necessário mover arquivos, recriar modelos, mudar hashes, alterar o schema
das tabelas documentais ou regenerar documentos existentes. O esforço concentra-se
na interface, navegação, catálogo de marcadores e testes.

## Permissões

Hoje a consulta dos modelos exige `documents.generate` e a importação exige
também `folders.update`. A entrada de Configurações aceita apenas `roles.view`,
`finance.view` ou `expenses.view`. Simplesmente mover o formulário bloquearia
usuários que podem trabalhar com documentos, mas não têm essas permissões.

Para uma migração restrita à organização das telas, recomendo **preservar os
direitos atuais**: permitir acesso ao catálogo por `documents.generate` e exibir
importação apenas com a combinação atual. Isso não concede acesso aos demais
cartões de Configurações, que mantêm suas próprias regras.

Como evolução independente, uma permissão `document-templates.manage` representaria
melhor a responsabilidade de manter modelos do escritório. Sua adoção exige definir
quem a receberá e atualizar os perfis existentes; não deve ser concedida amplamente
como efeito colateral da mudança de endereço. Geração e manutenção podem então
ser concedidas separadamente.

## Limites e critérios de aceite

- Usuário autorizado importa um modelo sem abrir qualquer pasta.
- O modelo aparece na geração em outras pastas do mesmo escritório.
- Usuário gerador sem manutenção usa modelos, mas não os importa.
- Usuários sem permissões financeiras alcançam o catálogo quando autorizados,
  sem receber acesso a cadastros financeiros ou perfis.
- Voltar de Configurações atualiza a lista sem apagar o preenchimento da pasta.
- Modelos e dados de outro escritório não aparecem após troca de organização.
- Documentos anteriores e downloads continuam funcionando sem mudanças.
- Testes de central, sidebar, cabeçalho, rotas, importação, geração e permissões;
  build e revisão em desktop/celular após implementar.

Busca e apresentação das importações cabem na nova página. Exclusão, renomeação,
desativação e agrupamento formal de versões são evoluções separadas: não existem
no fluxo atual e não são requisitos para esta migração de interface.

## Parecer

**Recomendável.** A mudança torna explícito que o modelo é um recurso do escritório
e que o documento produzido pertence à pasta. Também reduz a quantidade de controles
na operação diária. O custo é um deslocamento para Configurações ao cadastrar
modelos, mitigado pelo atalho e pela atualização da lista ao retornar.

Condição para uma boa implementação: preservar a revisão antes da exportação e
tratar acesso à central e catálogo de marcadores como parte da migração.
