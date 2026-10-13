# Migração PHP para TypeScript + React

## Estado atual

Fase 2 está em andamento na branch `migracao/typescript`. O código PHP e o banco continuam preservados. Setup do monorepo: commit `a70d363`; primeiro módulo: home e conteúdo público de exames internacionais.

| Módulo                               | Estado               | Implementação                                                                                                                                                                                                             |
| ------------------------------------ | -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Setup                                | Concluído            | npm workspaces, TypeScript strict, ESLint, Prettier, Vitest, Vite, React Router, TanStack Query e Fastify.                                                                                                                |
| Home e catálogo público              | Migrado              | Home React; catálogo público em `/exames-internacionais`; endpoint `GET /api/public/international-exams`; tipos e schemas Zod compartilhados; filtros, conteúdo de 14 países, seis exames e dicas.                        |
| Autenticação e usuários              | Migrado parcialmente | Login, cadastro, sessão MySQL, CSRF, logout e telas React; recuperação real e compatibilidade da sessão PHP continuam pendentes.                                                                                          |
| Administração/importação de questões | Migrado parcialmente | APIs admin de estatísticas, exemplo JSON e importação multipart JSON/XML; tela React; tabela nova `questoes` no banco de desenvolvimento; testes sintéticos. Paridade PHP ao vivo pendente.                               |
| Fórum e moderação                    | Migrado parcialmente | Endpoints autenticados para categorias, tópicos, respostas, curtidas, moderação, admin de usuários e auditoria; páginas React `/forum` e `/admin/forum`; 5 tabelas novas vazias; testes sintéticos. PHP ao vivo pendente. |
| Simulados e respostas                | Migrado parcialmente | Catálogo, início/retomada, cronômetro, navegação, salvamento incremental e finalização em React/API; três tabelas novas vazias; testes sintéticos. Não há questões ativas no banco.                                       |
| Resultados, revisão e histórico      | Migrado parcialmente | APIs autenticadas, schemas compartilhados e páginas de resultado, revisão e ambos os históricos; testes de contrato e UI. Sem DDL/seed; paridade PHP ao vivo pendente.                                                    |

O catálogo PHP é conteúdo estático e não consulta banco. Por isso o módulo público não criou tabelas nem conectou Drizzle/MySQL. A autenticação usa Drizzle com driver `mysql2` para `usuarios`, `logs_acesso` e `logs_sistema`, e uma tabela isolada `node_sessions`; nenhuma tabela ou dado PHP foi alterado. A sessão armazena somente o hash do token aleatório e expira em uma hora por padrão.

Administração de questões está disponível em `/admin/questoes`. As rotas `GET /api/admin/questions/stats`, `GET /api/admin/questions/example?tipo_prova=...` e `POST /api/admin/questions/import` usam a sessão Node, papel administrativo do banco e CSRF para mutações. O multipart limita o arquivo a 10 MiB; apenas JSON/XML são aceitos, e XML é validado sem processamento de entidades externas. A importação processa as questões numa transação e retorna a contagem inserida/atualizada.

Com autorização explícita, a tabela nova `questoes` foi criada no banco de desenvolvimento e mapeada em `apps/api/schema/questions.sql`/Drizzle; nenhum script de carga foi executado. A tabela estava vazia após a criação. O DDL usa `ativa` e `data_criacao`; telas/scripts PHP também consultam `ativo` e `created_at`, e `questoes_manager.php` usa `ativa`. A API segue a definição criada, portanto o comportamento de estatísticas/compatibilidade com o esquema que um deploy PHP existente possa ter permanece um gate de paridade. O endpoint admin antigo testa `tipo_usuario`, campo que o fluxo de login PHP não define; a migração usa o `isAdmin` confiável da sessão Node.

O fórum está migrado parcialmente em `/forum` e `/admin/forum`. A API oferece `GET /api/forum/categories`, `GET /api/forum/topics?categoria=&busca=`, `GET /api/forum/topics/:id`, `POST /api/forum/topics`, `POST /api/forum/replies`, `POST /api/forum/likes`, `GET /api/admin/forum/dashboard`, `POST /api/admin/forum/categories`, `POST /api/admin/forum/moderation` e `POST /api/admin/forum/users/action`. As mutações exigem sessão ativa e CSRF; rotas administrativas verificam o papel no banco. Busca/listagem mantêm o limite PHP de 20 tópicos e dashboard limita em 50; tópicos/respostas novos são aprovados automaticamente; detalhe do tópico incrementa visualizações; curtidas são idempotentes.

Por autorização explícita, foram criadas apenas as novas tabelas `forum_categorias`, `forum_topicos`, `forum_respostas`, `forum_curtidas` e `forum_moderacao` no banco de desenvolvimento; a introspecção posterior confirmou as colunas e nenhuma linha inicial foi adicionada. Não foram alteradas tabelas PHP existentes. O DDL registra os aliases de autor `autor_id` e `usuario_id` para compatibilizar os dois usos nos arquivos PHP; a API preenche ambos. O runtime `forum.php` usa `forum_curtidas`, enquanto `forum_structure.sql` declara `forum_likes`; o schema novo segue o runtime e não cria alias/tabela duplicada. Moderação suporta tanto os campos legados específicos quanto os campos genéricos do painel PHP. Como o banco foi deixado vazio, categorias precisam ser criadas no painel admin antes de usuários criarem tópicos.

O módulo de simulados usa `GET /api/simulator/dashboard`, `POST /api/simulator/sessions`, `GET /api/simulator/sessions/:id`, `POST /api/simulator/answers` e `POST /api/simulator/finish`; as telas estão em `/simulador`, `/simulador/:tipo` e `/simulador/sessoes/:id`. Por autorização explícita, foram criadas somente as tabelas novas `sessoes_teste`, `respostas_usuario` e `resultados_testes`, com relações para `usuarios` e `questoes`. Nenhuma linha de teste/seed foi inserida; a verificação de contagem encontrou zero sessões, respostas e resultados, e nenhuma questão ativa em `questoes`. A tela de teste exibirá o estado de exame em preparação até questões serem importadas.

O cálculo incremental de dissertativas preserva a aceitação de resposta esperada contida no texto; o cálculo final do PHP compara apenas igualdade após normalização, e essa divergência foi mantida. Também foi preservado `total_questoes = 120` ao persistir resultado, inclusive para outras provas. Os cartões e a execução PHP têm contagens divergentes para DELE, DELF, TestDaF, JLPT e HSK; a API preserva ambos os valores usados em cada tela. A tela antiga consultava badges, mas as tabelas não existem e o serviço de concessão foi removido; gamificação ficou explicitamente fora deste módulo.

O módulo de resultados usa `GET /api/results/:id`, `GET /api/results/:id/review`, `GET /api/results/history/tests` e `GET /api/results/history/sessions`. As páginas correspondentes são `/resultados/:id`, `/resultados/:id/revisao`, `/historico/testes` e `/historico/provas`. Sessões e resultados são sempre limitados ao usuário autenticado; revisão retorna questões do tipo da prova e respostas da sessão. Resultado sem linha em `resultados_testes` usa `pontuacao_final` da sessão e zera os demais campos, conforme `resultado_teste.php`. Os resumos de histórico mantêm as métricas distintas dos dois PHP: média de `resultados_testes.pontuacao` para histórico de testes e média `acertos / denominador fixo por prova` para histórico de sessões. Nenhuma tabela ou dado foi alterado nesta etapa.

O PHP consulta `$_SESSION['user_id']` em `historico_testes.php`, embora o login grave `usuario_id`; o endpoint TS usa o ID verificado da sessão Node e corrige essa inconsistência para que o histórico funcione. Os filtros de tipo/período são preservados na URL e na interface, mas continuam sem efeito, como no PHP. A seção de badges não foi reintroduzida. Tipos de prova desconhecidos seguem com identificação visual SAT nas telas de resultado/revisão; a apresentação de GRE passou a usar a configuração válida do simulador em vez do índice PHP ausente.

As mensagens de erro/redirect das páginas PHP foram convertidas em estados HTTP 404/401 e links no SPA. Formatação de datas é feita no fuso do navegador (o PHP usa o fuso do servidor), e não houve execução PHP para comparação ao vivo. O banco não tem resultados nem questões ativas, portanto as telas foram validadas com respostas sintéticas, sem smoke integrado de dados.

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
- Não houve comparação automatizada com PHP: o runtime PHP não está disponível neste ambiente. Regras e mensagens de autenticação foram transcritas da implementação estática e cobertas com testes sintéticos; validação de hashes de contas reais deve ocorrer em ambiente isolado.
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

## Próxima etapa

Executar os gates finais do módulo de resultados e criar seu commit isolado. Em seguida, validar paridade e fluxos integrados em ambiente PHP controlado, preparar hardening/cutover e manter o PHP intacto até aceite explícito.

## Critérios antes de desligar o PHP

- [ ] Migrar e aceitar paridade de todos os módulos ainda pendentes, inclusive admin, fórum, simulados, resultados e histórico.
- [ ] Introspectar os schemas restantes e confirmar que tabelas/dados PHP não foram alterados.
- [ ] Comparar mensagens, status, redirects, respostas, regras e limites com fixtures PHP em ambiente seguro.
- [ ] Validar cookies, CSRF, CORS, rate limit, autorização, uploads, logs e secrets.
- [ ] Confirmar jobs externos, cron, webhooks e scripts operacionais; bloquear operações destrutivas públicas.
- [ ] Testar staging, observabilidade, backups, rollback e cutover reversível sem perda de dados ou sessões.
- [ ] Remover o PHP somente após aceite de paridade e ensaio de rollback.
