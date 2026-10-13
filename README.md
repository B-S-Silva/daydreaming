# DayDreaming

Plataforma para preparação de exames internacionais, com autenticação, fórum, simulados, resultados e administração de questões. O runtime atual é um monorepo TypeScript com frontend React/Vite e API Fastify; o código PHP legado foi removido.

## Requisitos

- Node.js 24 ou versão LTS compatível com as dependências.
- MySQL 8 com acesso ao schema da aplicação.
- npm.

## Configuração local

Configure `.env` na raiz do repositório:

```dotenv
NODE_ENV=development
API_PORT=3001
WEB_ORIGIN=http://localhost:5173
DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=seu_schema
DATABASE_USER=seu_usuario
DATABASE_PASSWORD=sua_senha
DATABASE_CHARSET=utf8mb4
SESSION_TTL_SECONDS=3600
COOKIE_SECURE=false
```

Não versione `.env` nem inclua credenciais em logs ou commits. A aplicação usa o schema MySQL existente; não execute scripts SQL de setup/reset como parte da inicialização.

```sh
npm install
npm run dev
```

O Vite serve o frontend em `http://localhost:5173` e encaminha `/api` para o Fastify em `http://localhost:3001`.

## Validação e build

```sh
npm run typecheck
npm run lint
npm run format:check
npm test
npm run build
```

## Produção

```sh
npm run build
npm run start --workspace @daydreaming/api
```

Sirva `apps/web/dist` por um servidor estático com fallback de rotas para `index.html`. Configure um proxy reverso same-origin para encaminhar `/api` à API e defina as variáveis de ambiente de produção, incluindo `COOKIE_SECURE=true` sob HTTPS. A API encerra o startup se a conexão com MySQL falhar.

## Workspaces

- `apps/web`: aplicação React, rotas SPA e estilos.
- `apps/api`: API Fastify, autenticação/sessões, serviços e repositórios MySQL.
- `packages/shared`: tipos e schemas Zod compartilhados.

## Documentação da migração

- `ARQUITETURA.md`: inventário histórico do sistema PHP e arquitetura TypeScript alvo.
- `PLANO_MIGRACAO.md`: sequência, decisões e riscos da migração.
- `MIGRACAO.md`: módulos migrados, comportamento conhecido, configuração e cutover.

Os scripts SQL preservados são artefatos históricos/operacionais: revise o conteúdo e o schema de destino antes de executá-los.
