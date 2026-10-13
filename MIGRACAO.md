# Migração PHP para TypeScript + React

## Estado atual

Fase 2 foi validada pelo responsável do projeto e o cutover para TypeScript foi autorizado. Os arquivos PHP versionados foram removidos nesta limpeza; o banco e seus schemas não foram alterados. As diferenças de comportamento conhecidas abaixo permanecem registradas. Setup do monorepo: commit `a70d363`; primeiro módulo: home e conteúdo público de exames internacionais.

| Módulo                               | Estado               | Implementação                                                                                                                                                                                                                                                         |
| ------------------------------------ | -------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Setup                                | Concluído            | npm workspaces, TypeScript strict, ESLint, Prettier, Vitest, Vite, React Router, TanStack Query e Fastify.                                                                                                                                                            |
| Home e catálogo público              | Migrado              | Home React; catálogo público em `/exames-internacionais`; endpoint `GET /api/public/international-exams`; tipos e schemas Zod compartilhados; filtros, conteúdo de 14 países, seis exames e dicas.                                                                    |
| Autenticação e usuários              | Migrado parcialmente | Login, cadastro, sessão MySQL, CSRF, logout e telas React; recuperação real e compatibilidade da sessão PHP continuam pendentes.                                                                                                                                      |
| Administração/importação de questões | Migrado parcialmente | APIs admin de estatísticas, exemplo JSON e importação multipart JSON/XML; tela React; tabela nova `questoes` no banco de desenvolvimento; testes sintéticos. Paridade PHP ao vivo pendente.                                                                           |
| Fórum e moderação                    | Migrado parcialmente | Endpoints autenticados para categorias, tópicos, respostas, curtidas, moderação, admin de usuários e auditoria; páginas React `/forum` e `/admin/forum`; 5 tabelas novas vazias; testes sintéticos. PHP ao vivo pendente.                                             |
| Simulados e respostas                | Migrado parcialmente | Catálogo, início/retomada, cronômetro, navegação, salvamento incremental e finalização em React/API; três tabelas novas vazias; testes sintéticos. Não há questões ativas no banco.                                                                                   |
| Resultados, revisão e histórico      | Migrado parcialmente | Commit `6abd4de`; APIs autenticadas, schemas compartilhados e páginas de resultado, revisão e ambos os históricos. Testes, type-check, lint e build aprovados; smoke read-only do MySQL/API/Vite aprovado. Paridade PHP ao vivo pendente.                             |
| Guias por país                       | Atualizado           | Diretório e detalhes públicos em `/guias-paises` e `/guias-paises/:id`; 28 destinos. Programas de bolsas são referências legadas e vêm acompanhados de aviso para verificar elegibilidade e dados atuais em fontes oficiais.                                          |
| Ferramentas de estudo e perfil       | Migrado parcialmente | `/ferramentas-estudo` traz GPA (conversão linear 0–10 para 0–4), quiz exploratório e prática de entrevista; `/perfil` mantém dados privados e consentimento explícito para rastreamento. API autenticada para perfil, GPA e registro de abertura de guia consentido.  |
| Ranking universitário                | Migrado              | Página `/ranking` e endpoint público com os 89 registros de `ranking.php`; filtros com ação explícita e busca incremental, ordenação por ranking/nome/pontuação, paginação numerada e detalhes com métricas/logotipo. Filtro de área de estudo não implementado porque não há `subject` nos dados. A edição/fonte do ranking não está identificada e a interface alerta para confirmar os dados em fontes oficiais. |
| Badges e notificações                | Parcial              | Header e `/notificacoes`, endpoints para listar/marcar lida, notificações de resposta/menção, catálogo legado de 20 badges e concessão por prova, fórum, GPA e países no perfil. Níveis/XP seguem pendentes.                                                     |

`apps/api/schema/expansion.sql` descreve tabelas isoladas para perfil, GPA, atividade, países explorados, badges/níveis e notificações. Esse DDL **não foi executado** e não altera tabelas existentes; antes de aplicá-lo, confirme o schema de destino e que os nomes ainda não existem. O rastreamento de países só é gravado por usuário autenticado que explicitamente ativou a opção no perfil; registra abertura de guia, não visita física. Requer que as tabelas novas estejam instaladas no banco de destino.

As notificações seguem os tipos e mensagens de `sistema_notificacoes.php`: respostas ao autor do tópico e menções `@usuario` notificam os destinatários, exceto o próprio autor. O menu mostra as cinco mais recentes não lidas, o endpoint de listagem permite até 100 e `/notificacoes` mostra o histórico limitado a 100, como a tela PHP. As rotas usam `notificacoes_usuario`; o DDL aditivo não foi executado, então dependem de essa tabela já existir ou ser provisionada. Falhas ao registrar avisos do fórum são reportadas no log e não impedem a publicação da resposta. A tabela preexistente `badges`/`usuario_badges` é mapeada com sua relação legada `badge_id` — não criamos uma tabela incompatível nem alteramos dados. As 20 badges vêm do catálogo ativo no banco; ao abrir o perfil, a API avalia uma conquista mais alta por categoria (melhor prova, participações no fórum, melhor GPA e países consentidos), persiste apenas as novas e gera notificação. O perfil agora exibe o catálogo conquistado. A atribuição continua sob demanda no carregamento do perfil, como o PHP; bônus de XP e níveis ainda não foram conectados.

O ranking universitário foi trazido da lista estática PHP (89 entradas efetivamente definidas, apesar do comentário legado mencionar 100), mantendo nomes, posições, pontuações, imagens e oito métricas de detalhe. O filtro por área de estudo era apenas visual e também não tem campo nos registros; foi substituído por um aviso explicativo. O conteúdo original não identifica fonte nem edição e usa imagens de placeholder, então a tela informa que os números precisam ser confirmados em publicações oficiais.

Os guias carregam texto extraído das páginas correspondentes em `php-login-system-master/paises`: seções de idioma/proficiência, clima, custo de vida, bolsas, universidades, candidatura e trabalho quando presentes, além de links externos encontrados no conteúdo. A origem não possui data de atualização confiável; os textos permanecem marcados como legado e a interface orienta confirmar valores, elegibilidade, visto e regras nas fontes oficiais. O quiz mantém as 20 perguntas e opções originais e os quatro perfis com carreiras; a prática mantém as 14 perguntas e limiares originais (mais de 20 e mais de 50 caracteres não brancos). “Quem somos” recupera missão e objetivos; estatísticas antigas e conteúdo depreciativo da seção de equipe foram omitidos.

O catálogo PHP é conteúdo estático e não consulta banco. Por isso o módulo público não criou tabelas nem conectou Drizzle/MySQL. A autenticação usa Drizzle com driver `mysql2` para `usuarios`, `logs_acesso` e `logs_sistema`, e uma tabela isolada `node_sessions`; nenhuma tabela ou dado PHP foi alterado. A sessão armazena somente o hash do token aleatório e expira em uma hora por padrão.

Administração de questões está disponível em `/admin/questoes`. As rotas `GET /api/admin/questions/stats`, `GET /api/admin/questions/example?tipo_prova=...` e `POST /api/admin/questions/import` usam a sessão Node, papel administrativo do banco e CSRF para mutações. O multipart limita o arquivo a 10 MiB; apenas JSON/XML são aceitos, e XML é validado sem processamento de entidades externas. A importação processa as questões numa transação e retorna a contagem inserida/atualizada.

Com autorização explícita, a tabela nova `questoes` foi criada no banco de desenvolvimento e mapeada em `apps/api/schema/questions.sql`/Drizzle; nenhum script de carga foi executado. A tabela estava vazia após a criação. O DDL usa `ativa` e `data_criacao`; telas/scripts PHP também consultam `ativo` e `created_at`, e `questoes_manager.php` usa `ativa`. A API segue a definição criada, portanto o comportamento de estatísticas/compatibilidade com o esquema que um deploy PHP existente possa ter permanece um gate de paridade. O endpoint admin antigo testa `tipo_usuario`, campo que o fluxo de login PHP não define; a migração usa o `isAdmin` confiável da sessão Node.

O fórum está migrado parcialmente em `/forum` e `/admin/forum`. A API oferece `GET /api/forum/categories`, `GET /api/forum/topics?categoria=&busca=`, `GET /api/forum/topics/:id`, `POST /api/forum/topics`, `POST /api/forum/replies`, `POST /api/forum/likes`, `GET /api/admin/forum/dashboard`, `POST /api/admin/forum/categories`, `POST /api/admin/forum/moderation` e `POST /api/admin/forum/users/action`. As mutações exigem sessão ativa e CSRF; rotas administrativas verificam o papel no banco. Busca/listagem mantêm o limite PHP de 20 tópicos e dashboard limita em 50; tópicos/respostas novos são aprovados automaticamente; detalhe do tópico incrementa visualizações; curtidas são idempotentes.

Por autorização explícita, foram criadas apenas as novas tabelas `forum_categorias`, `forum_topicos`, `forum_respostas`, `forum_curtidas` e `forum_moderacao` no banco de desenvolvimento; a introspecção posterior confirmou as colunas e nenhuma linha inicial foi adicionada. Não foram alteradas tabelas PHP existentes. O DDL registra os aliases de autor `autor_id` e `usuario_id` para compatibilizar os dois usos nos arquivos PHP; a API preenche ambos. O runtime `forum.php` usa `forum_curtidas`, enquanto `forum_structure.sql` declara `forum_likes`; o schema novo segue o runtime e não cria alias/tabela duplicada. Moderação suporta tanto os campos legados específicos quanto os campos genéricos do painel PHP. Como o banco foi deixado vazio, categorias precisam ser criadas no painel admin antes de usuários criarem tópicos.

O módulo de simulados usa `GET /api/simulator/dashboard`, `POST /api/simulator/sessions`, `GET /api/simulator/sessions/:id`, `POST /api/simulator/answers` e `POST /api/simulator/finish`; as telas estão em `/simulador`, `/simulador/:tipo` e `/simulador/sessoes/:id`. As tabelas `sessoes_teste`, `respostas_usuario` e `resultados_testes` foram criadas anteriormente no banco de desenvolvimento, que não recebeu fixtures nesta validação. A contagem nesse banco continua sem questões ativas. Para o teste autenticado, foi criado o schema separado `daydreaming_parity_staging`, com cópia da estrutura das 13 tabelas, 18 chaves estrangeiras e fixtures sintéticas; nenhum dado do schema de desenvolvimento foi copiado.

O cálculo incremental de dissertativas preserva a aceitação de resposta esperada contida no texto; o cálculo final do PHP compara apenas igualdade após normalização, e essa divergência foi mantida. Também foi preservado `total_questoes = 120` ao persistir resultado, inclusive para outras provas. Os cartões e a execução PHP têm contagens divergentes para DELE, DELF, TestDaF, JLPT e HSK; a API preserva ambos os valores usados em cada tela. A concessão de badges aparece no perfil e é recalculada ali, não no momento de finalizar o simulado.

O módulo de resultados usa `GET /api/results/:id`, `GET /api/results/:id/review`, `GET /api/results/history/tests` e `GET /api/results/history/sessions`. As páginas correspondentes são `/resultados/:id`, `/resultados/:id/revisao`, `/historico/testes` e `/historico/provas`. Sessões e resultados são sempre limitados ao usuário autenticado; revisão retorna questões do tipo da prova e respostas da sessão. Resultado sem linha em `resultados_testes` usa `pontuacao_final` da sessão e zera os demais campos, conforme `resultado_teste.php`. Os resumos de histórico mantêm as métricas distintas dos dois PHP: média de `resultados_testes.pontuacao` para histórico de testes e média `acertos / denominador fixo por prova` para histórico de sessões. A implementação do módulo não alterou schema ou dados do banco de desenvolvimento; o staging separado usado para paridade está descrito abaixo.

O PHP consulta `$_SESSION['user_id']` em `historico_testes.php`, embora o login grave `usuario_id`; o endpoint TS usa o ID verificado da sessão Node e corrige essa inconsistência para que o histórico funcione. Os filtros de tipo/período são preservados na URL e na interface, mas continuam sem efeito, como no PHP. Tipos de prova desconhecidos seguem com identificação visual SAT nas telas de resultado/revisão; a apresentação de GRE passou a usar a configuração válida do simulador em vez do índice PHP ausente.

As mensagens de erro/redirect das páginas PHP foram convertidas em estados HTTP 404/401 e links no SPA. Formatação de datas é feita no fuso do navegador (o PHP usa o fuso do servidor), e não houve comparação pareada PHP↔TS. Validação automatizada: 13 arquivos e 56 testes passaram, além de type-check, lint, Prettier e build. Smoke anterior confirmou API `/health` (200), SPA Vite (200), proxy Vite→API (200), conexão MySQL e metadados esperados; as contagens read-only foram zero em `sessoes_teste`, `respostas_usuario`, `resultados_testes` e `questoes`.

Com PHP 8.2.12 e `pdo_mysql`, `php -l` passou nos principais pontos de entrada, endpoints e helper de autenticação. Os smoke checks sem autenticação também passaram: páginas públicas retornaram 200, validações de POST inválido ocorreram antes do banco e rotas protegidas redirecionaram para `login.php` (302). A tentativa de conexão PHP pela configuração local continuou falhando, então, sem alterar `config.php` ou `.env`, foi usada uma cópia temporária das páginas PHP com conexão por variáveis de ambiente e fuso `America/Sao_Paulo`, apontada exclusivamente ao schema isolado.

Nesse staging, o login PHP aceitou a senha bcrypt sintética e redirecionou (302); o login Node aceitou o mesmo usuário e senha (200). Resultado, revisão e os dois históricos PHP retornaram 200; as APIs Node correspondentes e o dashboard também retornaram 200. A finalização do simulado foi executada em sessões separadas nos dois runtimes: ambos retornaram pontuação 100, um acerto e uma questão respondida, com 62 segundos de tempo gasto. Foram exercitados writes apenas no schema de staging; os arquivos/configuração locais originais e os dados do schema de desenvolvimento permaneceram intocados.

Login, cadastro e recuperação têm rate limits por IP (respectivamente 5, 3 e 3 requisições por minuto). O armazenamento do limiter é local ao processo; não execute múltiplas instâncias sem configurar um store compartilhado.

## Diferenças e limites conhecidos

- O header novo consulta `/api/auth/me`; a sessão Node não autentica as páginas PHP. O corte das páginas protegidas exige migração coordenada.
- `admin_questoes.php` ainda não foi executado ao vivo para comparar mensagens, estatísticas e XML; a validação atual é estática e baseada em testes sintéticos. JSON inválido, validação de campos e XML inválido podem gerar mensagens diferentes do PHP, que frequentemente converte exceções de carga em um erro genérico.
- A página PHP oferece oito tipos de prova; o schema da API aceita também GRE/GMAT, presentes nos schemas legados. O formulário mantém os oito tipos mostrados na tela PHP.
- A atualização de uma questão não altera `ativa`; questões XML têm o número normalizado de string para inteiro. Alternativas seguem nullable no schema novo para suportar dissertativas, mas o importador continua exigindo A–E como `questoes_manager.php`.
- Após login administrativo, o SPA ainda direciona à home; o painel migrado é acessível pelo link “Admin do Fórum” no cabeçalho.
- A navegação do simulador está disponível em React; visitantes recebem o erro de autenticação da API e um link para login, não o alerta JavaScript do PHP.
- As rotas PHP de fórum continuam intactas durante a coexistência e não compartilham cookie/sessão com Node. Não houve comparação automatizada com PHP; formulários, status, filtros e mensagens ainda precisam de validação ao vivo.
- O PHP dá rate limit apenas a POSTs de `admin_forum.php` com estado de sessão; o API rate limiter atual é por IP e memória do processo. A escrita de curtidas/moderação/admin usa contratos JSON em vez de redirects e mensagens de flash do PHP.
- A API impede que um administrador altere a própria conta (PHP permitia essas ações); categoria inválida/cor é rejeitada no schema em vez de depender apenas do banco. A ordenação das categorias e do painel admin foi alinhada às consultas PHP.
- A API não envia gabaritos ao navegador, ao contrário de `executar_teste.php`; enunciados e alternativas são renderizados como texto escapado em React, enquanto o PHP interpreta HTML embutido. O autosave exige CSRF no Node; `salvar_resposta.php` não faz essa checagem.
- A tela migrada mostra o resumo logo após finalizar e oferece o link para o resultado persistido, revisão e histórico. Reabrir uma sessão já finalizada dentro do runner não foi alterado; a base não tem questões ativas para validar uma prova completa com dados reais.
- O painel novo mantém os principais filtros/ações mas apresenta categorias vazias (sem seed) até o admin criá-las. A documentação e os SQL PHP divergem sobre `forum_likes`/`forum_curtidas`, colunas de autor e logs; a aplicação implementada segue `forum_curtidas` e o esquema novo compatível.
- A rota React do catálogo é `/exames-internacionais`; a URL PHP `/testes_internacionais.php` permanece ativa no sistema antigo.
- A comparação pareada autenticada cobre somente login, leitura de resultado/revisão/históricos e finalização do simulado com fixtures sintéticas no staging; não demonstra paridade completa. Fórum, administração/importação de questões, validações de borda, mensagens e erros ainda precisam de testes pareados. A conexão PHP pela configuração legada segue sem validação; o harness temporário não alterou `config.php` nem `.env`.
- Recuperação de senha continua sem envio de email nem persistência de token; a API retorna indisponibilidade explicitamente e a tela não alega que um email foi enviado.

## Variáveis de ambiente

- `API_PORT` (opcional): porta da API; padrão `3001`.
- `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`, `DATABASE_CHARSET`: conexão da API ao schema de desenvolvimento. Usuário/senha são obrigatórios; não grave valores reais no repositório.
- `SESSION_TTL_SECONDS` (padrão `3600`), `COOKIE_SECURE` (padrão `true` em produção) e `WEB_ORIGIN` (padrão `http://localhost:5173`).
- A credencial MySQL de desenvolvimento usada no provisionamento tem privilégios amplos e deve ser substituída por uma credencial de menor privilégio antes de qualquer uso em produção.

## Como executar

Requer Node.js/npm compatíveis com Vite.

```sh
npm install
npm run dev
```

O ambiente local inicia a API Fastify, Vite e watcher do pacote compartilhado. Vite encaminha `/api` para `http://localhost:3001`.

```sh
npm run typecheck
npm run lint
npm run format:check
npm test
npm run build
```

Em produção, execute `npm run build`, sirva `apps/web/dist` como conteúdo estático e inicie a API com `npm run start --workspace @daydreaming/api`. Configure um proxy reverso para `/api` apontando à API, e fallback do servidor estático para `index.html` nas rotas React. Configure as variáveis de MySQL e sessão descritas acima; a API falha ao iniciar se não conseguir conectar ao banco.

## Estado do cutover

O responsável confirmou a validação da migração e autorizou o corte. A comparação executada nesta sessão cobriu login, resultado/revisão/históricos e finalização do simulado com fixtures sintéticas; o staging `daydreaming_parity_staging` permanece isolado no servidor MySQL e nenhum dado do schema de desenvolvimento foi copiado. As diferenças aceitas continuam descritas neste documento. A configuração de hardening operacional (proxy, cookies/HTTPS, rate-limit multi-instância, backups e rollback) continua sendo responsabilidade do deploy.

## Checklist pós-cutover

- [x] Validação da migração e autorização do cutover confirmadas pelo responsável do projeto.
- [x] Remover os arquivos PHP versionados e as referências executáveis restantes.
- [ ] Introspectar os schemas restantes e confirmar que tabelas/dados PHP não foram alterados.
- [ ] Validar cookies, CSRF, CORS, rate limit, autorização, uploads, logs e secrets.
- [ ] Confirmar jobs externos, cron, webhooks e scripts operacionais do ambiente de deploy.
- [ ] Testar staging, observabilidade, backups, rollback e cutover reversível sem perda de dados ou sessões.
