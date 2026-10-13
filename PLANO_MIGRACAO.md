# Plano de migração PHP para TypeScript + React

## Estado e limites

Fase 1 aprovada; Fase 2 em execução na branch `migracao/typescript`. O setup do monorepo está no commit `a70d363`; o módulo público está no commit `4ee7d75`; autenticação usa sessão server-side no MySQL de desenvolvimento. O fórum foi commitado em `181c633`, simulados/respostas em `4477147`; resultados, revisão e histórico estão implementados nesta etapa, aguardando gates e commit. Tabelas criadas para as features TS não receberam seed; tabelas PHP existentes foram preservadas.

O `LIMPEZA.md` referenciado no pedido não está no checkout: ele foi excluído na solicitação anterior para remover todos os Markdown exceto `README.md`; a base de conhecimento atual é `AGENTS.MD`.

## Bloqueios conhecidos a resolver antes de declarar paridade

1. **Schema não confiável por scripts:** arquivos PHP/SQL declaram colunas diferentes para fórum, questões, sessões e logs. Autenticação/logs, questões, fórum e as tabelas novas de sessão/resposta/resultado foram mapeados; as tabelas novas de resultado seguem sem dados de teste. Badges permanecem fora da migração. Nenhum `setup_database.php`, `reset_database.sql`, `drizzle-kit push` ou script de correção deve ser executado.
2. **Sessão/ID inconsistente:** login define `usuario_id`; `historico_testes.php` consulta `$_SESSION['user_id']`. O endpoint TS usa o ID autenticado da sessão Node (`usuario_id`) para carregar o histórico, corrigindo essa incompatibilidade legada. `admin_questoes.php` exige `tipo_usuario === 'admin'`, enquanto autenticação grava `is_admin`; a API usa o papel confiável `isAdmin` da sessão Node, não um campo enviado pelo cliente.
3. **Pontuação de dissertativa divergente:** `salvar_resposta.php` aceita igualdade ou resposta esperada contida; `processar_teste.php` só aceita igualdade normalizada. Total geral é calculado pelas respostas, mas `total_questoes` persiste constante 120. Fechamento também pode salvar respostas novamente; transação e duplicidade precisam de fixture.
4. **Recuperação de senha não é fluxo real:** token é gerado e descartado, sem email e sem endpoint de redefinição. A paridade atual é preservar a tela/mensagem simulada, não prometer funcionalidade nova.
5. **Sessão PHP não pode ser lida automaticamente pelo Node:** cookie e store Node já foram definidos; coexistência e cutover coordenado continuam pendentes para não desconectar usuários inadvertidamente.
6. **Ambiente sem validação executável prévia:** PHP e Composer não estavam disponíveis na inspeção de limpeza; falta suíte automatizada. Criar ambiente e baseline isolados é um gate inicial.
7. **Scripts de manutenção na raiz:** podem ser diretamente executáveis pelo web server e alterar schema/dados. Não expor a API SPA nem rodá-los automaticamente.

## Sequência macro e gates

### Fase 1 — concluída e aprovada: análise/projeto

- `ARQUITETURA.md`: mapa das telas, endpoints, regras, schema observado e arquitetura alvo.
- `PLANO_MIGRACAO.md`: sequência, gates, riscos e critérios abaixo.
- A arquitetura foi validada antes do início da implementação.

### Fase 2 — em execução: setup e módulos verticais

1. Branch `migracao/typescript` criada; PHP permanece intacto.
2. Setup npm workspaces, TypeScript strict, ESLint, Prettier e Vitest concluído e commitado em `a70d363`.
3. O catálogo público é embutido no PHP e não acessa tabelas; o módulo usa schema Zod compartilhado e endpoint estático, sem introspecção nem alteração do banco (`4ee7d75`).
4. Módulo de autenticação implementado: schemas compartilhados, rotas Fastify, Drizzle repositories com driver MySQL para `usuarios`/logs, bcrypt, cookies HttpOnly, sessão/CSRF/flash em `node_sessions`, telas de login/cadastro e testes. As sessões PHP não são compartilhadas; recuperação de senha permanece sem envio/persistência de token.
5. Não usar armazenamento em memória como substituto de persistência e não executar setup/seed, `drizzle-kit push` ou DDL sem autorização. O usuário autorizou criar o banco dedicado e a tabela `node_sessions`; nenhum schema/dado PHP existente foi alterado. O módulo de resultados reutiliza apenas as tabelas já criadas; esta etapa não executa DDL nem grava dados.

**Gate do módulo de autenticação:** typecheck, lint, testes, formatação, build, audit e smoke de DB/API precisam passar antes do commit. A paridade com PHP continua parcial: não há runtime PHP disponível para comparar redirects/erros ao vivo, nem fixture de hash de conta real; testes usam bcrypt sintético. Não expor o banco para produção: a credencial de desenvolvimento deve ser substituída e seus privilégios restringidos.

O rate limiter atual guarda contadores no processo e serve somente a execução de instância única; antes de escalar horizontalmente, configurar um store compartilhado. A sessão em si é compartilhada no MySQL.

### Fase 3 — módulos verticais

Para **cada** feature, seguir sem pular etapas: (a) rever tabelas/índices reais em escopo; (b) atualizar contratos/tipos e schemas Zod compartilhados; (c) repository + service + routes Fastify; (d) tela React e estados loading/error/empty; (e) testes de regras, request/response e UI; (f) type-check, lint e suíte da feature; (g) documentar paridade e fazer **um commit claro por módulo**. Não começar módulo seguinte se o gate falhar.

Ordem sugerida — do mais isolado ao mais acoplado, com autenticação antecipada por ser dependência das telas protegidas:

1. **Shell e conteúdo público** (`index.php`, `header_status.php`, `testes_internacionais.php`): React Router/layout; catálogo de exames estático tipado; `GET /api/auth/me` alimenta o header. Sem DB para conteúdo embutido.
2. **Usuários e autenticação** (`login.php`, `cadastro.php`, `recuperar_senha.php`, `logout.php`, `verificar_auth.php`): APIs de login/register/me/logout, cookie e sessão em MySQL, validações compartilhadas e telas login/cadastro implementadas. Restam comparação com PHP executável, smoke com hashes reais em cópia sanitizada e cutover de sessões. Recuperação fica sem envio/token até especificação de email.
3. **Admin de questões/importação** (`admin_questoes.php`, `questoes_manager.php`, scripts JSON/XML de carga): migrado parcialmente nesta branch; APIs de estatísticas, exemplo e importação multipart JSON/XML, autorização admin, tela React e testes estão implementados. O schema novo `questoes` foi criado somente após autorização explícita, está no banco de desenvolvimento e não recebeu seed. As divergências PHP entre `ativa`/`ativo` e `data_criacao`/`created_at` são documentadas; a implementação segue o DDL efetivamente criado e ainda requer comparação com o runtime PHP.
4. **Fórum** (`forum.php`, `admin_forum.php`): APIs e telas React para categorias, busca/listagem/detalhe, criação/resposta/curtida, moderação/admin/banimento/auditoria foram implementadas parcialmente. As cinco tabelas estavam ausentes; após autorização expressa, foram criadas apenas as tabelas novas, sem seed nem mudanças nas tabelas PHP. Falta paridade ao vivo com PHP, revisar diferenças de sessão/rate limit/mensagens e validar o conteúdo real após carga de categorias.
5. **Simulado e resposta** (`simulador_provas.php`, `executar_teste.php`, `interface_teste.php`, `salvar_resposta.php`, `processar_teste.php`): catálogo, sessão, questão/timer, salvamento incremental e finalização foram migrados para `/simulador` e a API (`4477147`). As tabelas `sessoes_teste`, `respostas_usuario` e `resultados_testes` foram criadas após autorização explícita, sem seed. Mantém a divergência de dissertativa (substring no autosave, igualdade ao finalizar) e `total_questoes=120`. Testes usam questões sintéticas; o banco não tem questões ativas para teste integrado real.
6. **Resultado, revisão e histórico** (`resultado_teste.php`, `revisar_prova.php`, `historico_testes.php`, `historico_provas.php`): implementados em `/resultados/:id`, `/resultados/:id/revisao`, `/historico/testes` e `/historico/provas`, com endpoints autenticados para detalhe, revisão, estatísticas e listagens. O histórico TS usa o ID confiável da sessão Node em vez da chave PHP incompatível `user_id`. Filtros de tipo/período continuam sem filtrar, como no PHP; badges ficam fora. Gates e comparação ao vivo com PHP ainda pendentes.
7. **Corte e hardening:** teste E2E dos caminhos público/usuário/admin; comparar comportamento lado a lado; direcionar rotas por feature/reverse proxy; desativar scripts operacionais acessíveis publicamente ou movê-los para operação restrita; só remover PHP após paridade aceita, backup e rollback ensaiados.

Ordem dos módulos pode ser ajustada após introspecção se dependências ou integrações reais mudarem. Não alterar tabelas/dados PHP existentes. Tabelas novas autorizadas e criadas neste estágio: `node_sessions`, `questoes`, as cinco tabelas do fórum e `sessoes_teste`, `respostas_usuario`, `resultados_testes` no banco de desenvolvimento. Fórum e tabelas de simulados foram deixadas vazias.

### Fluxo fixo dentro de cada módulo

```text
schema real/fixtures
  -> Zod schemas + tipos em packages/shared
  -> repositories Drizzle + services + routes Fastify
  -> React feature (Router + Query + RHF)
  -> testes de unidade, contrato/paridade e UI
  -> type-check + ESLint + Vitest + revisão de diff
  -> commit único do módulo; PHP permanece
```

## Plano de autenticação e cutover

- Não converter a sessão para JWT como atalho: login PHP usa sessão server-side e logout a destrói. A decisão aprovada é sessão opaca persistida em `node_sessions`, com hash SHA-256 do token, cookie HTTP-only e TTL.
- `SESSION_TTL_SECONDS`, `COOKIE_SECURE`, origem web, domínio/path/nome do cookie, `SameSite=Strict`, CSRF e expiração devem ser configurados/validados por ambiente e HTTPS em produção.
- Sessão nova Node não autentica PHP antigo por default. Opções a avaliar na Fase 2: migrar/cortar em conjunto login + todas as páginas protegidas; ou construir uma ponte de compatibilidade curta e com testes de segurança. Não parsear arquivos serializados de sessão PHP informalmente.
- Login consulta `usuarios` no banco dedicado e aceita hashes bcrypt compatíveis. Preserva `is_admin` e `ativo` de fonte servidor, registra tentativas de login e atualiza `ultimo_acesso`. Testes cobrem usuário ativo/inativo, sessão, CSRF e logout; teste com hash real segue pendente em ambiente isolado.
- Recuperação real (token persistido, expiração, envio, rate limit) muda o comportamento atual e requer confirmação funcional e mail provider. Até então reproduzir que a recuperação atual é simulada, sem alegar envio.

## Dados, tipos e comportamento

- Drizzle introspection somente após conectar à cópia correta; rejeitar scripts conflitantes como única fonte do modelo. Nenhuma migration/push ou seed em banco existente.
- Testar queries com prepared params; preservar FKs/transações existentes. Ler `DECIMAL` como string ou integer minor units (pontuação em centésimos) e evitar arredondamento binário. Converter apenas na apresentação.
- Datas: inventariar `TIMESTAMP` vs `DATETIME`, timezone de conexão e horário salvo; preservar `America/Sao_Paulo` antes de serializar ISO. Testar virada de dia/horário de verão e filtros de período com fixtures se aplicável.
- IDs, flags MySQL (`0`, `1`, `NULL`), strings vazias e campos opcionais devem ter transformação explícita em boundary. Proibir `any`; se lib externa exigir tipo incerto, adapter narrow/parse validado em comentário e schema.
- Paridade de endpoints JSON: snapshot de status, chaves, mensagens, defaults e resultados para `/salvar_resposta.php` e `/processar_teste.php`. Paridade UI/form: erros de validação, redirects pós-login/cadastro, regra de conta ativa, mensagem de fórum e filtros.
- Dados SAT: preservar os dois JSON atuais; não depender de PDFs/imagens removidos pela limpeza anterior e confirmar que os JSON possuem os dados exigidos pelo importador.

## Qualidade e aceite por módulo

- `tsc --noEmit` em strict, ESLint sem erros, Prettier check e Vitest para cada commit de módulo.
- Testes de serviço cobrindo inputs válidos/limítrofes/inválidos e coerção PHP vs TS (incluindo `"0"`, vazio, `null`, número/string e case de resposta).
- `Fastify.inject` para status, headers, cookie/CSRF, schema de response, auth/role e queries; não é necessário expor o servidor.
- Teste de paridade com resultado PHP capturado num banco isolado e registros sintéticos, sem credenciais ou PII. Se PHP não puder ser iniciado, declarar o módulo bloqueado para comparação em vez de inventar golden output.
- Testes React cobrindo loading/error/empty/success, formulário e navegação essenciais. Critério de qualidade atende aos mesmos casos reais; não apenas snapshot de markup.
- Revisão de security: CORS restrito, cookies, CSRF, rate limit, upload multipart, XML XXE, SQL paramétrico, autenticação admin e logs sem senhas/tokens.

## Riscos e mitigação

| Risco                                            | Impacto                                      | Mitigação / gate                                                                                                           |
| ------------------------------------------------ | -------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| Schema instalado diverge de setup/SQL            | API falha ou dados incorretos                | Introspecção read-only e fixture antes de modelos; zero DDL.                                                               |
| Perda de sessão no coexistente PHP/Node          | Logout inesperado ou bypass                  | Cutover coordenado ou ponte explícita; browser tests de cookie, expiração, logout, papel e banimento.                      |
| Hash de senha tem algoritmo/variante diferente   | usuários não conseguem autenticar            | Amostra somente de formato/metadados sem vazar hashes; bcrypt verifier + teste de hashes sintéticos; manter column.        |
| Histórico usa chave de sessão errada             | PHP pode retornar vazio mesmo com resultados | API TS usa o ID autenticado correto; diferença está documentada e o PHP não foi alterado.                                  |
| Regras de questão/sessão contraditórias          | Scores e resultados incompatíveis            | Casos de paridade, documentar bug vs intended behavior, decisão antes do corte.                                            |
| FLOAT/DECIMAL ou timezone muda score/histórico   | regressão silenciosa                         | Decimal string/centésimos; fixture timezone; compare rows sintéticas.                                                      |
| Upload JSON/XML inseguro ou limites diferentes   | DoS, XXE, import parcial                     | parser seguro, streaming/limites configurados, validação estrita, transação e teste malicioso.                             |
| Fórum tem coluna autor/admin variando            | erro em join/moderação                       | Schema real e flows CRUD/moderação cobertos.                                                                               |
| Scripts operacionais na raiz web                 | alteração destrutiva sem intenção            | Bloquear por web server antes do cutover, exigir CLI/restrição; inventário e revisão por script.                           |
| Recuperação simula email                         | UI promete ação que não ocorreu              | Documentar comportamento atual; não implementar envio/alterar mensagens sem provider/especificação.                        |
| CDNs indisponíveis / UI depende de JS inline     | visual/interação muda                        | Inventariar assets usados, servir dependências controladas e testes visuais/funcionais por tela.                           |
| Sem PHP/Composer e testes de baseline            | comparativo não confiável                    | Instalar runtime em ambiente controlado e capturar contratos antes da migração; declarar gates bloqueados se indisponível. |
| App SPA consome endpoints PHP com redirects HTML | interceptors e UX incompatíveis              | API nova em prefixo separado; aliases/reverse proxy e contrato explícito durante migração.                                 |

## Variáveis de ambiente novas previstas

Definir schema Zod de configuração na inicialização e falhar rápido se obrigatória faltar. Nenhum valor real vai em Git.

- API/runtime: `NODE_ENV`, `API_PORT`, `WEB_ORIGIN`.
- MySQL existente: `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD`, `DATABASE_CHARSET`, `DATABASE_TIME_ZONE`.
- Sessões: `SESSION_TTL_SECONDS`, `COOKIE_SECURE`; MySQL `node_sessions` armazena sessão server-side. Rate-limit atual é local ao processo e exige store compartilhado se houver múltiplas instâncias.
- Upload: `UPLOAD_MAX_BYTES` (definir após comparar configuração atual; não copiar limite do `config.exemplo.php` como fato).
- Vite: `VITE_API_BASE_URL` (não colocar credenciais ou segredos no prefixo VITE).
- SMTP não incluído enquanto recuperação de senha continuar simulada. Se houver decisão de implementar, adicionar configuração do provider/SMTP com secret store e testes específicos como mudança funcional separada.

## Como executar o estado atual

Use Node.js compatível com o Vite e npm workspaces. `npm install` instala as dependências; `npm run dev` inicia Vite, API e watcher do pacote shared. Verificações: `npm run typecheck`, `npm run lint`, `npm run format:check`, `npm test` e `npm run build`. Para produção, `npm run build`, sirva `apps/web/dist` por um servidor estático e inicie a API com `npm run start --workspace @daydreaming/api`; configure o proxy reverso para `/api` e defina `API_PORT` se diferente de 3001. Configure `DATABASE_USER` e `DATABASE_PASSWORD` no ambiente da API; `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_CHARSET`, `WEB_ORIGIN`, `SESSION_TTL_SECONDS` e `COOKIE_SECURE` possuem defaults para desenvolvimento. A API falha ao iniciar se a configuração obrigatória ou a conexão MySQL estiver ausente.

## Checklist para desligar PHP com segurança

- [ ] Schema real/queries reconciliados e o app usa o MySQL existente sem DDL nem mudança de dados.
- [ ] Login, cadastro, sessão, cookie, logout, banimento/admin e fluxo de recuperação aprovado em semântica; hashes existentes testados.
- [ ] Paridade aprovada por módulo para request/response/mensagens/redirects, pontuação/tempo, fórum/moderação, importação e histórico.
- [ ] Vitest/type-check/ESLint, browser e testes de integração/E2E passam em ambiente de staging com cópia de dados sanitizada.
- [ ] Uploads e limite, CORS, CSRF, rate limit, secrets, HTTPS/cookies, observabilidade e acesso admin revisados.
- [ ] Nenhum job/integração cron/webhook/email oculto foi descoberto em deploy; cron/infra inspecionados fora do repositório.
- [ ] Rotas PHP e scripts potencialmente destrutivos não estão acessíveis publicamente; runbooks e rollback documentados.
- [ ] Observabilidade e comparação de tráfego cobrem período de aceite; backups e restore foram ensaiados.
- [ ] Regras, discrepâncias aceitas e mensagens finais assinadas pelo responsável de produto.
- [ ] Cutover pode ser revertido para PHP sem perda de sessões/dados; janela e responsável definidos.
- [ ] Só após aceite e rollback ensaiado: parar PHP, verificar logs/erros e monitorar o novo runtime.

## Próxima fronteira de migração

Resultados, revisão e histórico estão implementados e aguardam validação automatizada final e commit isolado. Em seguida, o plano segue para hardening, testes E2E/paridade em ambiente PHP controlado e preparação do cutover; não há outra feature de negócio definida antes desses gates. `questoes` continua sem questões ativas para E2E integrado. O PHP permanece intacto e sessões PHP não são compartilhadas com Node.
