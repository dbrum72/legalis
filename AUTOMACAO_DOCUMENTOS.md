# Automação de documentos jurídicos — etapa 15

Iniciada em 15/09/2026 por solicitação do usuário. Escopo: produzir iniciais,
petições e outros documentos a partir de modelos textuais jurídicos e dos dados
do processo. Integrações financeiras permanecem adiadas.

## Fluxo da primeira versão

1. Cadastrar modelo do escritório com nome, tipo de peça e campos substituíveis.
2. Na pasta, escolher modelo e o cliente usado nos campos automáticos.
3. Preencher automaticamente os dados disponíveis e apresentar os faltantes.
4. Completar informações específicas da peça e revisar o texto resultante.
5. Salvar uma versão do documento na pasta, identificando modelo e responsável.

Formato escolhido pelo usuário: **importação e exportação Word (.docx)**.
A primeira versão usa modelos importados e imutáveis; editar no Word e importar
novamente cria outro registro identificado pelo seu número de versão.

## Dados existentes e limites

- Pasta: nome e número do processo; metadados DataJud podem estar ausentes.
- Cliente: nome, documento, identidade, órgão emissor, estado civil, profissão,
  endereço e contatos. Usar somente campos existentes, sem deduzir nacionalidade
  ou outras informações não cadastradas.
- Vínculo cliente/pasta: qualificação processual. Múltiplos clientes podem ter
  a mesma qualificação; não escolher automaticamente o primeiro.
- Escritório: nome. Dados de assinatura e OAB precisam de origem explicitamente
  definida; o usuário conectado não é necessariamente o advogado signatário.
- Documentos da pasta: armazenamento privado e download já implementados.
- Permissão `documents.generate` já consta nas definições de perfis.

## Regras propostas

- Catálogo de variáveis legíveis, por exemplo `{{ processo.numero }}` e
  `{{ cliente.nome }}`, com descrição da origem. A sintaxe definitiva depende
  do formato escolhido para os modelos.
- Campos específicos da peça, como fatos, pedidos e endereçamento, são preenchidos
  ou revisados pelo usuário quando não houver origem estruturada confiável.
- Modelos de inicial podem dispensar número do processo. Obrigatoriedade deve
  corresponder ao modelo, e não ser imposta a todos os documentos.
- Campos obrigatórios ausentes e variáveis desconhecidas ficam visíveis e impedem
  concluir a geração. Persistência de rascunhos fica para evolução futura.
- Substituição de dados não executa código, expressões de servidor ou instruções
  contidas no modelo. Valores inseridos são dados, sem nova interpretação como modelo.
- Alterar modelo ou cadastro não modifica documentos já gerados. Guardar versão
  do modelo e fotografia dos valores utilizados, além do texto revisado.
- Modelos e documentos são isolados por escritório. Acesso à geração exige também
  acesso à pasta; separar manutenção do catálogo da geração ao definir permissões.
- A peça permanece disponível para revisão e download. Protocolo judicial e envio
  externo não fazem parte deste fluxo.

## Validação da implementação

Cobrir substituições repetidas, acentos, campos faltantes, variáveis desconhecidas,
partes múltiplas, pasta sem número, isolamento por escritório e permissões.
Verificar preservação das versões e consistência ao salvar arquivo e registro.
Para frontend, testar o fluxo de revisão, erros e build, além da apresentação em
desktop e celular. Validar formatação e abertura dos arquivos no formato escolhido.

## Primeira versão implementada

### Blocos condicionais implementados

Use `{{#se grupo.campo}}texto opcional{{/se}}`. O trecho aparece somente se o
campo tiver conteúdo após remover espaços nas extremidades. É possível indicar
vários campos separados por espaço; todos precisam estar preenchidos. Os campos
continuam disponíveis para revisão na tela, mesmo quando o trecho é omitido.

Exemplos para a procuração analisada:

```text
{{#se cliente.identidade cliente.orgao_emissor}}, e no Registro Geral sob o nº {{ cliente.identidade }}/{{ cliente.orgao_emissor }}{{/se}}
{{#se processo.numero}} e especialmente para efetuar sua defesa em Ação {{ processo.numero }}{{/se}}
```

Inclua dentro do bloco a pontuação e os espaços que devem desaparecer. O RG é
omitido se faltar identidade ou órgão emissor. A menção à ação depende do número
do processo, inclusive do valor revisado pelo usuário para a cópia.

Cada bloco deve abrir e fechar no mesmo parágrafo, sem aninhamento. Marcadores
divididos entre trechos de formatação do Word são aceitos. O recurso controla
texto; não remove imagens, tabelas ou o parágrafo inteiro. Campos fora de blocos,
ou dentro de blocos visíveis, continuam obrigatórios. Nenhum poder específico
é incluído ou retirado automaticamente sem marcação explícita no modelo.

Para ativar as regras em modelos existentes, edite uma cópia no Word e utilize
**Substituir modelo** em Settings. Não há migração de banco neste incremento.

### Cadastro e preparação

Em **Configurações → Modelos de documentos** (`/settings/document-templates`):

1. Abra **Como preparar o modelo** para consultar os marcadores disponíveis,
   sem precisar abrir uma pasta.
2. No Word, insira marcadores completos no mesmo parágrafo. Exemplos:
   `{{ cliente.nome }}`, `{{ cliente.documento }}`, `{{ pasta.nome }}`,
   `{{ processo.numero }}`, `{{ escritorio.nome }}` e `{{ data.hoje }}`.
3. Para dados específicos da peça, use `{{ campo.fatos }}`, `{{ campo.pedidos }}`,
   `{{ campo.enderecamento }}` ou outro nome em minúsculas, sem acentos ou espaços.
4. Abra **Importar modelo .docx**, informe o nome e selecione o arquivo.
5. Consulte a lista com busca, identificação da versão e data. **Baixar original**
   permite editar no Word e importar novamente como outra versão.

### Geração e exportação

Em **Pastas → detalhes da pasta → Documentos → Gerar a partir de modelo Word**:

1. Escolha o modelo e, quando ele usar campos `cliente.*`, o cliente vinculado
   à pasta. A qualificação é exibida junto ao nome, sem seleção automática.
2. Clique em **Preencher e visualizar**, complete ou corrija os valores e
   atualize a prévia. Essas correções não alteram o cadastro original.
3. Clique em **Gerar Word e salvar na pasta**. Na lista de documentos, use
   **Baixar** para abrir e revisar a cópia no Word.

Usuários com permissão de manutenção dispõem do atalho **Gerenciar modelos
(nova aba)**. Ao retornar, a lista é atualizada sem apagar seleção, valores ou
prévia. Falhas nessa atualização preservam o formulário e oferecem nova tentativa.
A importação e o download do modelo original ficam somente em Configurações.

Marcadores fora de blocos ou dentro de blocos visíveis precisam ser preenchidos.
Para iniciais sem número, omita o marcador ou envolva o trecho em um bloco
condicionado a `processo.numero`. Campos `campo.*` são livres;
variáveis desconhecidas nos grupos predefinidos são rejeitadas na importação.

A prévia é textual, não uma reprodução da diagramação. Corpo, tabelas, cabeçalhos,
rodapés e notas Word suportadas passam pela substituição. Marcadores divididos
em trechos de formatação do mesmo parágrafo são reconhecidos; o valor usa a
formatação do início do marcador. O restante do pacote é preservado. Textos
maiores podem mudar a paginação, que deve ser conferida no Word.

### Limites deliberados

- Uma parte selecionada para `cliente.*`; outras partes e signatários usam campos
  livres nesta versão. Não há repetição automática de partes nem blocos entre parágrafos.
- Sem editor de texto rico no navegador, edição de documento salvo ou retomada
  de rascunho no servidor. O texto fixo é alterado no Word; os valores são revisados
  na tela antes da geração. Rascunhos do formulário se perdem ao sair da página.
- Sem exclusão/substituição de modelos nesta versão. Reimportações ficam listadas
  separadamente; documentos gerados não acompanham alterações posteriores.
- Entrada até 10 MB, pacote descompactado até 30 MB, até 1.000 entradas ZIP e
  até 100 campos distintos. Arquivos antigos `.doc`, macros, objetos incorporados,
  revisões pendentes, campos dinâmicos Word e imagens/vínculos externos não são
  suportados. Hyperlinks comuns são preservados.
- Apenas formato WordprocessingML padrão; Strict Open XML e estruturas alternativas
  são rejeitados. Marcadores em partes não suportadas, como comentários, geram erro.
- A assinatura de prévia impede salvar conteúdo diferente do revisado; os valores
  enviados explicitamente pelo formulário são a fotografia escolhida pelo usuário.

### Implementação e acesso

- `document_templates`: organização, autor, nome, arquivo privado, hash e campos.
- `document_generations`: vínculo com o documento da pasta, hash do modelo e
  fotografia dos valores usados. O responsável fica no documento gerado.
- `DocxTemplateService` usa ZIP e DOM nativos do PHP, sem executar modelos ou
  fazer consultas externas. Valores inseridos são texto e não são reinterpretados.
- Consulta de modelos exige `documents.generate`; importação exige também
  `folders.update`. Contexto e prévia exigem `folders.view`; geração exige
  também `folders.update`. Download do resultado usa a proteção existente da pasta.
- Novas tabelas aplicadas por `migrate --force`, preservando dados locais.

Rotas: `/api/document-templates`, `/api/document-templates/fields`,
`/api/document-templates/{template}/download` e
`/api/folders/{folder}/document-generation` com `/context` e `/preview`.

### Substituição e retirada de modelos

Em **Configurações → Modelos de documentos**, use **Substituir modelo** para
enviar o arquivo atualizado. O novo modelo recebe um ID próprio; o anterior é
arquivado somente após validação e armazenamento bem-sucedidos, em uma transação.
Se houver erro, o anterior continua disponível. Os vínculos históricos não são
transferidos para o substituto.

- **Excluir modelo** remove definitivamente modelos ativos sem gerações registradas,
  após confirmação na tela.
- **Arquivar modelo** retira modelos utilizados das novas gerações, preservando
  o arquivo original, o registro e os documentos já gerados.
- **Mostrar arquivados** permite consultar e baixar os originais preservados.
- Prévias de modelos arquivados não podem gerar novos documentos: é necessário
  selecionar um modelo disponível e revisar novamente.
- A substituição preserva o anterior mesmo quando ele ainda não foi utilizado.

API: `DELETE /api/document-templates/{template}` decide entre excluir e arquivar;
`POST /api/document-templates/{template}/replace` recebe `name` e `file`.
Ambas exigem `documents.generate` e `folders.update`. A listagem retorna somente
ativos por padrão; `include_archived=1` inclui arquivados. O isolamento por
escritório também se aplica às novas operações.

### Testes do fluxo

Testes de geração, download, isolamento, autorização, campos faltantes, prévia
desatualizada, caracteres especiais, quebras de linha e marcadores divididos.
Fixture Word completo cobre tabelas, cabeçalho e rodapé, além de pacotes mínimos.
Os resultados consolidados da execução ficam na baseline e no cronograma.

A tentativa de renderizar o documento de demonstração com `render_docx.py`
foi impedida pela ausência de LibreOffice (`soffice.exe`) neste Windows.
Portanto, a aparência e paginação do arquivo exportado **não foram certificadas
por renderização visual** neste ciclo. Validação XML não substitui essa revisão.

Referência de estrutura:
[Microsoft — WordprocessingML](https://learn.microsoft.com/en-us/office/open-xml/word/how-to-open-and-add-text-to-a-word-processing-document).
