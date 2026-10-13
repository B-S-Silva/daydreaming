# Base de conhecimento do projeto

## Visão geral

DayDreaming é um monorepo TypeScript com uma SPA React/Vite, API Fastify e pacote compartilhado. O PHP versionado foi removido após a validação e autorização de cutover pelo responsável do projeto. O banco MySQL existente continua sendo usado; schemas e dados não devem ser alterados por tarefas de limpeza.

## Executar localmente

- Requisitos: Node.js compatível com Vite, npm e MySQL 8.
- Configure as variáveis de conexão e runtime em `.env`; não imprima, sobrescreva ou versione credenciais.
- Execute `npm install` e `npm run dev`; Vite fica em `http://localhost:5173`, API em `http://localhost:3001`.
- Comandos de validação: `npm run typecheck`, `npm run lint`, `npm run format:check`, `npm test` e `npm run build`.
- Não há necessidade de Composer/PHP no runtime migrado.

## Workspaces e fluxos

- `apps/web`: UI React e React Router. Features estão em `src/features/` (auth, fórum, simulados, resultados, questões e conteúdo público).
- `apps/api`: Fastify organizado por módulos, com rotas, serviços, repositórios Drizzle/MySQL e schemas.
- `packages/shared`: contratos Zod e tipos partilhados entre frontend e API.
- A autenticação usa cookie HttpOnly, sessão persistida em MySQL (`node_sessions`) e CSRF para mutações.
- A SPA chama endpoints `/api/*`; o proxy de desenvolvimento para a API está em `apps/web/vite.config.ts`.

## Banco de dados e segurança operacional

- O schema MySQL de runtime é configurado pelas variáveis `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD` e `DATABASE_CHARSET`.
- Schemas Drizzle estão em `apps/api/src/db/`; DDL de referência está em `apps/api/schema/`.
- Scripts SQL antigos na raiz podem executar DDL, reset ou seed. Inspecione-os e confirme o alvo antes de rodar; não os use como testes inofensivos.
- Não altere schema, dados ou migrations durante limpezas de código. Fixtures de paridade ficam em `daydreaming_parity_staging`, isoladas dos dados de desenvolvimento.
- `config.php` é um arquivo local ignorado pelo Git e não é usado pelo runtime TypeScript. Preserve-o sem ler ou incluir em commits.

## Convenções de desenvolvimento

- Siga a organização por feature e as fronteiras rota → serviço → repositório na API.
- Compartilhe contratos de request/response no pacote `@daydreaming/shared` com Zod e mantenha TypeScript strict; evite `any`.
- Use queries parametrizadas pelos repositórios Drizzle e valide entradas nos limites HTTP.
- Preserve os formatos de resposta e mensagens legadas intencionalmente aceitos; documente diferenças de comportamento.
- Para mudanças, rode o menor conjunto de testes aplicável e os type-check/lint/testes quando a superfície compartilhada ou o fluxo exigir.
- Não faça commits, pushes, alterações no banco ou remoções fora do escopo sem autorização explícita.

## Estado do cutover

O responsável validou a migração e autorizou remover os arquivos PHP versionados. A autenticação PHP e Node não compartilham sessões; usuários precisam autenticar-se novamente no runtime novo. Diferenças de comportamento e itens intencionalmente fora do escopo estão documentados em `MIGRACAO.md`. O arquivo local ignorado `config.php`, `.env`, banco e scripts SQL preservados não fazem parte da remoção.
