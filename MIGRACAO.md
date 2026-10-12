# Migração PHP para TypeScript + React

## Estado atual

Fase 2 está em andamento na branch `migracao/typescript`. O código PHP e o banco continuam preservados. Setup do monorepo: commit `a70d363`; primeiro módulo: home e conteúdo público de exames internacionais.

| Módulo                                        | Estado    | Implementação                                                                                                                                                                                      |
| --------------------------------------------- | --------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Setup                                         | Concluído | npm workspaces, TypeScript strict, ESLint, Prettier, Vitest, Vite, React Router, TanStack Query e Fastify.                                                                                         |
| Home e catálogo público                       | Migrado   | Home React; catálogo público em `/exames-internacionais`; endpoint `GET /api/public/international-exams`; tipos e schemas Zod compartilhados; filtros, conteúdo de 14 países, seis exames e dicas. |
| Autenticação e usuários                       | Pendente  | Requer contratos PHP, teste de bcrypt e desenho aprovado de sessão coexistente.                                                                                                                    |
| Administração, fórum, simuladores e histórico | Pendente  | Dependem de autenticação, introspecção read-only do MySQL e fixtures de paridade.                                                                                                                  |

O catálogo PHP é conteúdo estático e não consulta banco. Por isso este módulo não criou tabelas nem conectou Drizzle/MySQL. O endpoint fornece ao React o mesmo conteúdo que estava embutido em `testes_internacionais.php`.

## Diferenças e limites conhecidos

- O header novo informa sempre que o visitante não está logado. O PHP detecta a sessão; `/auth/me` só será migrado no módulo de autenticação.
- Para visitantes, os botões de simulador preservam o alerta e a confirmação de login. A navegação do usuário autenticado ao simulador ainda não é representada nesta etapa.
- Login, cadastro, fórum e simuladores apontam para as páginas PHP existentes durante a coexistência; esses módulos não foram migrados.
- A rota React do catálogo é `/exames-internacionais`; a URL PHP `/testes_internacionais.php` permanece ativa no sistema antigo.
- Não houve comparação automatizada com PHP: o runtime PHP não está disponível neste ambiente. Conteúdo e mensagens foram transcritos da implementação estática.

## Variáveis de ambiente

- `API_PORT` (opcional): porta da API; padrão `3001`.
- O módulo público não lê credenciais, banco, Redis ou variáveis `VITE_*`. Variáveis futuras de DB e sessão serão documentadas quando seus módulos forem iniciados.

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

Em produção, execute `npm run build`, sirva `apps/web/dist` como conteúdo estático e inicie a API com `npm run start --workspace @daydreaming/api`. Configure um proxy reverso para `/api` apontando à API, e fallback do servidor estático para `index.html` nas rotas React. Nenhuma migração ou comando DDL é necessário.

## Critérios antes de desligar o PHP

- [ ] Migrar e aceitar paridade de todos os módulos ainda pendentes, inclusive login, sessão, cadastro, admin, fórum, simulados, resultados e histórico.
- [ ] Introspectar o banco correto em modo read-only e confirmar que nenhum DDL foi executado.
- [ ] Comparar mensagens, status, redirects, respostas, regras e limites com fixtures PHP em ambiente seguro.
- [ ] Validar cookies, CSRF, CORS, rate limit, autorização, uploads, logs e secrets.
- [ ] Confirmar jobs externos, cron, webhooks e scripts operacionais; bloquear operações destrutivas públicas.
- [ ] Testar staging, observabilidade, backups, rollback e cutover reversível sem perda de dados ou sessões.
- [ ] Remover o PHP somente após aceite de paridade e ensaio de rollback.
