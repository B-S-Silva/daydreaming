# Limpeza agressiva

## Estado do repositório

- O projeto não estava em um repositório Git. Foi inicializado e o estado versionável anterior à limpeza foi registrado no commit `3d38644`.
- A limpeza foi feita na branch `limpeza/agressiva`.
- O snapshot inicial respeitou o `.gitignore`: `config.php` e os arquivos SQL já eram ignorados e continuam fora do Git. Eles foram mantidos no disco e não foram alterados.
- Nenhum `.env` foi encontrado. Arquivos de configuração e documentação com credenciais foram preservados sem edição.
- Documentos preservados por conterem credenciais: `README.md`, `README_COLABORADORES.md`, `README_DATABASE.md`, `README_INSTALACAO_DATABASE.md`, `COMANDOS_COLABORADORES.md`, `SETUP_COLABORADORES.md`, `INICIO_RAPIDO.md` e `INSTRUCOES_INSTALACAO.md`.

## Remoções

### Código morto

- Removido `configurar_idioma.php`, um diagnóstico de locale sem referências.
- Removido `badges_manager.php` e suas inclusões em `historico_testes.php` e `simulador_provas.php`. As telas consultam as badges diretamente e nenhuma rota chamava o serviço ou as funções auxiliares removidas.
- Removidos de `questoes_manager.php` o método `buscarQuestoesTeste` e os wrappers globais sem chamadas no código ativo.
- Removidas as referências a `public/js/main.js` nas páginas. O script só continha componentes genéricos sem markup correspondente, preloads de imagens inexistentes, registro de um `sw.js` ausente e código de exportação para testes que não existem.

### Páginas duplicadas ou órfãs

- `index_new.php`: versão alternativa da página inicial, sem referências no fluxo ativo.
- `sat1.php`: simulador estático legado, sem links a partir das rotas ativas.
- `absolute/path/to/sat1.php`: arquivo residual no caminho acidental `absolute/path/to`.

### Testes soltos, diagnósticos e lixo

Removidos os scripts sem framework de testes ou chamadas pelo sistema:

`diagnostico_simulador.php`, `forum_debug.php`, `teste_colaborador.php`, `teste_conexao.php`, `teste_criacao_topico.php`, `teste_finalizacao_completo.php`, `teste_final_colaborador.php`, `teste_final_forum.php`, `teste_final_index_new.php`, `teste_forum_correcoes.php`, `teste_forum_corrigido.php`, `teste_idioma.php`, `teste_login.php`, `teste_simulador.php`, `teste_usuario_comum.php`, `verificacao_final_alteracoes.php`, `verificar_alteracoes_dashboard.php`, `verificar_estado_final.php`, `verificar_estrutura_questoes.php`, `verificar_header_implementado.php`, `verificar_instalacao.php`, `verificar_links_corrigidos.php`, `verificar_questoes_carregadas.php`, `verificar_respostas_sat.php`, `verificar_servidor.php`, `verificar_sistema_colaborador.php`, `verificar_tabelas_completas.php`, `verificar_tabela_forum.php` e `verificar_usuarios.php`.

Também foram removidos `configurar_powershell_pt.ps1`, o guia obsoleto `INSTRUCOES_SIMULADOR.md` e `.github/copilot-instructions.md`, que descrevia uma estrutura desatualizada. O arquivo local ignorado `.vscode/settings.json` também foi removido; por não estar versionado, ele não está incluído nas contagens abaixo.

### Assets sem uso

- `imagens/`: 47 imagens sem referência após a remoção da página alternativa.
- `Exames/SAT/imagens_questões/`: 9 imagens sem referência no código ou nos JSON de questões.
- `Exames/SAT/sat-practice-test-4-answers-digital.pdf` e `Exames/SAT/sat-practice-test-4-digital.pdf`: PDFs sem consumo pela aplicação.
- `public/js/main.js`: JavaScript genérico sem funcionalidade necessária no fluxo atual.

Os JSON `Exames/SAT/Exame_SAT_Test_4.json` e `Exames/SAT/Answers_SAT_Test_4.json` foram mantidos porque são lidos pelos scripts de carga. `dream-clouds.svg`, `manifest.json` e `public/css/style.css` também foram mantidos por terem referências ativas.

### Dependências

Não existe `composer.json`, `composer.lock` ou diretório `vendor`; portanto, não foi identificada dependência Composer a remover e não houve commit de dependências.

## Totais

Pelo histórico Git desde o snapshot inicial: **96 arquivos versionados removidos** e **8.677 linhas de texto removidas**. O total não inclui o arquivo local ignorado `.vscode/settings.json`, que não tinha histórico Git para calcular suas linhas.

## Restaurações após quebra

Nenhum arquivo foi restaurado. Não foi possível executar a aplicação para detectar quebras: PHP e Composer não estão instalados neste ambiente. `composer install --no-interaction` foi tentado e falhou porque o comando `composer` não está disponível; além disso, não há manifesto Composer. Não havia configuração de PHPUnit/Pest nem suíte automatizada. Os scripts manuais de teste e diagnóstico listados acima foram removidos e não foram executados, inclusive porque alguns acessam o banco.

## Estrutura final

```text
/
├── index.php, login.php, cadastro.php, recuperar_senha.php, logout.php
├── forum.php, admin_forum.php, admin_questoes.php
├── simulador_provas.php, executar_teste.php, interface_teste.php
├── processar_teste.php, salvar_resposta.php, resultado_teste.php
├── historico_testes.php, historico_provas.php, revisar_prova.php
├── testes_internacionais.php
├── header_status.php, verificar_auth.php, questoes_manager.php
├── config.php [local, ignorado pelo Git; mantido sem alteração]
├── config.exemplo.php, .gitignore, .htaccess, manifest.json
├── dream-clouds.svg
├── Exames/
│   └── SAT/
│       ├── Answers_SAT_Test_4.json
│       └── Exame_SAT_Test_4.json
├── public/
│   └── css/
│       └── style.css
├── arquivos SQL e scripts de banco/setup/migração [mantidos]
└── documentação com credenciais [mantida sem alteração]
```

## Pontos que ainda dificultam a migração para TypeScript

- SQL e acesso PDO estão espalhados pelas páginas e scripts de setup, misturando persistência e regras de negócio.
- PHP, HTML e CSS inline aparecem juntos em várias telas; rotas e respostas também dependem diretamente de `$_GET`, `$_POST` e `header()`.
- Sessão, autenticação e configuração usam estado global (`$_SESSION`, constantes e funções carregadas por `require_once`).
- A estrutura é baseada em arquivos PHP acessados diretamente e includes relativos, sem uma camada explícita de roteamento/API.
- Há múltiplos scripts de criação, correção e carga de banco com responsabilidades sobrepostas. Foram mantidos por estarem no escopo protegido de banco/migrações.
- `config.php` é ignorado pelo Git; a configuração efetiva de cada ambiente não está representada em um manifesto versionado seguro.
- O esquema ainda contém tabelas de badges e níveis, mas o serviço de concessão removido não era chamado. A interface de histórico e simulador continua lendo badges diretamente; essa discrepância deve ser decidida na migração.
- As páginas ainda dependem de CSS e bibliotecas externas por CDN, além de chamadas diretas ao MySQL, o que exige separar configuração, armazenamento e apresentação no novo backend.

## Commits da limpeza

- `85e1f85` — testes soltos e diagnósticos.
- `3b5ad76` — páginas duplicadas e órfãs.
- `b99678f` — diagnóstico de locale sem referências.
- `26a4994` — imagens e PDFs sem uso.
- `7ed6f90` — serviço e helpers sem chamadas.
- `119808a` — documentação obsoleta.
- `6529ba3` — referências ao JavaScript sem uso.
- `3d98a2d` — asset JavaScript sem funcionalidade necessária.
