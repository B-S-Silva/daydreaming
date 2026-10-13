# Migração PHP para TypeScript + React

## Estado atual

Fase 2 está em andamento na branch `migracao/typescript`. O código PHP e o banco continuam preservados. Setup do monorepo: commit `a70d363`; primeiro módulo: home e conteúdo público de exames internacionais.

| Módulo                               | Estado               | Implementação                                                                                                                                                                                      |
| ------------------------------------ | -------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Setup                                | Concluído            | npm workspaces, TypeScript strict, ESLint, Prettier, Vitest, Vite, React Router, TanStack Query e Fastify.                                                                                         |
| Home e catálogo público              | Migrado              | Home React; catálogo público em `/exames-internacionais`; endpoint `GET /api/public/international-exams`; tipos e schemas Zod compartilhados; filtros, conteúdo de 14 países, seis exames e dicas. |
| Autenticação e usuários              | Migrado parcialmente | Login, cadastro, sessão MySQL, CSRF, logout e telas React; recuperação real e compatibilidade da sessão PHP continuam pendentes.                                                                   |
| Administração/importação de questões | Migrado parcialmente | APIs admin de estatísticas, exemplo JSON e importação multipart JSON/XML; tela React; tabela nova `questoes` no banco de desenvolvimento; testes sintéticos. Paridade PHP ao vivo pendente.        |
| Fórum, simuladores e histórico       | Pendente             | Dependem de introspecção read-only do MySQL e fixtures de paridade.                                                                                                                                |

O catálogo PHP é conteúdo estático e não consulta banco. Por isso o módulo público não criou tabelas nem conectou Drizzle/MySQL. A autenticação usa Drizzle com driver `mysql2` para `usuarios`, `logs_acesso` e `logs_sistema`, e uma tabela isolada `node_sessions`; nenhuma tabela ou dado PHP foi alterado. A sessão armazena somente o hash do token aleatório e expira em uma hora por padrão.

Administração de questões está disponível em `/admin/questoes`. As rotas `GET /api/admin/questions/stats`, `GET /api/admin/questions/example?tipo_prova=...` e `POST /api/admin/questions/import` usam a sessão Node, papel administrativo do banco e CSRF para mutações. O multipart limita o arquivo a 10 MiB; apenas JSON/XML são aceitos, e XML é validado sem processamento de entidades externas. A importação processa as questões numa transação e retorna a contagem inserida/atualizada.

Com autorização explícita, a tabela nova `questoes` foi criada no banco de desenvolvimento e mapeada em `apps/api/schema/questions.sql`/Drizzle; nenhum script de carga foi executado. A tabela estava vazia após a criação. O DDL usa `ativa` e `data_criacao`; telas/scripts PHP também consultam `ativo` e `created_at`, e `questoes_manager.php` usa `ativa`. A API segue a definição criada, portanto o comportamento de estatísticas/compatibilidade com o esquema que um deploy PHP existente possa ter permanece um gate de paridade. O endpoint admin antigo testa `tipo_usuario`, campo que o fluxo de login PHP não define; a migração usa o `isAdmin` confiável da sessão Node.

Login, cadastro e recuperação têm rate limits por IP (respectivamente 5, 3 e 3 requisições por minuto). O armazenamento do limiter é local ao processo; não execute múltiplas instâncias sem configurar um store compartilhado.

## Diferenças e limites conhecidos

- O header novo consulta `/api/auth/me`; a sessão Node não autentica as páginas PHP. O corte das páginas protegidas exige migração coordenada.
- `admin_questoes.php` ainda não foi executado ao vivo para comparar mensagens, estatísticas e XML; a validação atual é estática e baseada em testes sintéticos. JSON inválido, validação de campos e XML inválido podem gerar mensagens diferentes do PHP, que frequentemente converte exceções de carga em um erro genérico.
- A página PHP oferece oito tipos de prova; o schema da API aceita também GRE/GMAT, presentes nos schemas legados. O formulário mantém os oito tipos mostrados na tela PHP.
- A atualização de uma questão não altera `ativa`; questões XML têm o número normalizado de string para inteiro. Alternativas seguem nullable no schema novo para suportar dissertativas, mas o importador continua exigindo A–E como `questoes_manager.php`.
- Após login administrativo, o SPA ainda direciona à home, não ao `admin_forum.php` do PHP, porque o módulo admin e a ponte de sessão não foram migrados.
- Para visitantes, os botões de simulador preservam o alerta e a confirmação de login. A navegação do usuário autenticado ao simulador ainda não é representada nesta etapa.
- Fórum e simuladores continuam apontando para as páginas PHP existentes durante a coexistência; esses módulos não foram migrados.
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

Concluir gates locais do módulo de questões, revisar o diff e fazer um commit isolado. A próxima fronteira do plano é o fórum, iniciada por introspecção read-only das tabelas reais. PHP permanece intacto.

## Critérios antes de desligar o PHP

- [ ] Migrar e aceitar paridade de todos os módulos ainda pendentes, inclusive admin, fórum, simulados, resultados e histórico.
- [ ] Introspectar os schemas restantes e confirmar que tabelas/dados PHP não foram alterados.
- [ ] Comparar mensagens, status, redirects, respostas, regras e limites com fixtures PHP em ambiente seguro.
- [ ] Validar cookies, CSRF, CORS, rate limit, autorização, uploads, logs e secrets.
- [ ] Confirmar jobs externos, cron, webhooks e scripts operacionais; bloquear operações destrutivas públicas.
- [ ] Testar staging, observabilidade, backups, rollback e cutover reversível sem perda de dados ou sessões.
- [ ] Remover o PHP somente após aceite de paridade e ensaio de rollback.
