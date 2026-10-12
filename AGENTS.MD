# AGENTS.MD — Base de conhecimento do projeto

## Visão geral

DayDreaming é uma aplicação PHP tradicional para cadastro e autenticação de usuários, fórum e simulados de exames internacionais. As páginas PHP na raiz também são as rotas HTTP: não há framework de roteamento nem separação formal entre apresentação, domínio e persistência.

## Executar localmente

- Requisitos: PHP com PDO e driver MySQL habilitados e um servidor MySQL compatível.
- A configuração local é `config.php`, ignorada pelo Git. Não a sobrescreva nem exponha seus valores. Use `config.exemplo.php` apenas como referência e obtenha credenciais de forma segura.
- Para desenvolvimento, a documentação principal descreve o servidor embutido: `php -S localhost:8080`; acesse `http://localhost:8080`.
- Não há `composer.json`, suíte PHPUnit/Pest ou configuração de testes identificada. Não invente dependências Composer sem confirmar necessidade.
- Antes de executar comandos de instalação, seed, reparo ou reset, inspecione seu código e confirme o alvo do banco com a pessoa responsável. Alguns scripts fazem DDL/DML ou alteram dados e não devem ser rodados como verificações inofensivas.

## Fluxos e arquivos principais

- `index.php`: página inicial; inclui `header_status.php`.
- `login.php`, `cadastro.php`, `recuperar_senha.php`, `logout.php`: sessão e fluxo de usuários.
- `verificar_auth.php`: inicialização de sessão e helpers de autenticação, autorização de admin, verificação de conta ativa e rate limiting.
- `forum.php` e `admin_forum.php`: fórum e moderação.
- `simulador_provas.php` → `executar_teste.php` → `interface_teste.php`: seleção, execução e interface do simulado.
- `salvar_resposta.php` e `processar_teste.php`: persistência de respostas e finalização.
- `resultado_teste.php`, `revisar_prova.php`, `historico_testes.php`, `historico_provas.php`: resultados, revisão e histórico.
- `admin_questoes.php` e `questoes_manager.php`: gerenciamento e importação de questões JSON/XML.
- `testes_internacionais.php`: página informativa com interação implementada em JavaScript inline.
- `Exames/SAT/Exame_SAT_Test_4.json` e `Answers_SAT_Test_4.json`: dados SAT usados pelos scripts de carga.
- `public/css/style.css`: CSS compartilhado; várias telas também têm CSS inline.

## Banco de dados e scripts operacionais

O acesso em runtime usa PDO por `conectarBD()` em `config.php`; SQL também está embutido diretamente nas páginas e nos scripts PHP. Os arquivos SQL versionados/locais (`db_structure.sql`, `forum_structure.sql`, `reset_database.sql`, `update_database.sql`) e scripts como `setup_database.php`, `criar_tabelas.php`, `inserir_dados.php`, `seed_questoes.php`, `completar_tabelas.php`, `recriar_tabelas_forum.php`, `corrigir_*.php`, `adicionar_*.php`, `limpar_questoes_extras.php` e os instaladores têm potencial de alterar schema ou dados. Não os execute sem entender o efeito e sem proteger os dados necessários.

Não altere banco, schema, migrations ou dados como parte de uma limpeza de arquivos. Preserve `config.php`, arquivos `.env` e credenciais; não os imprima nem inclua em commits.

## Convenções para mudanças

- Siga o estilo PHP existente e use `require_once` para dependências compartilhadas já estabelecidas.
- Preserve os nomes de sessão usados pelo sistema, em especial `$_SESSION['usuario_id']`, `usuario_nome`, `usuario_login` e `is_admin`, salvo alteração coordenada de todos os fluxos.
- Formulários enviam dados via `$_POST`; filtros/identificadores usam `$_GET`; redirecionamentos usam `header('Location: ...')`. Validar e escapar dados continua necessário, mesmo quando a tela gera HTML no PHP.
- Use prepared statements PDO para entrada variável nas consultas. Evite espalhar novas consultas entre views quando couber no fluxo atual.
- Não introduza Composer, framework, camada de rotas, cron, webhook ou assets sem uso demonstrado e configuração correspondente.
- Após mudanças, verifique referências às páginas/assets alterados, rode `php -l` nos PHP modificados quando PHP estiver disponível e teste manualmente os fluxos impactados com banco de desenvolvimento apropriado. Não execute testes ou setup que possam alterar um banco compartilhado.

## Pontos conhecidos para a migração TypeScript

- PHP, HTML e CSS inline estão misturados em várias páginas; cada arquivo raiz pode servir simultaneamente como rota, controlador e view.
- SQL/PDO estão distribuídos pelas páginas, autenticação e scripts de setup; falta uma fronteira única de persistência.
- Configuração, conexão e helpers usam constantes/funções globais carregadas por include; sessão e request dependem de superglobais PHP.
- Há scripts de instalação/correção/carga sobrepostos e é preciso separar os que são procedimentos operacionais dos que devem permanecer no runtime.
- As estruturas de badges/níveis ainda são consultadas por telas e aparecem no banco, mas o serviço de concessão de badges foi removido na limpeza anterior porque não havia chamadas ativas. Decida explicitamente se a gamificação deve voltar na migração; não assuma que schema existente prova que o fluxo funciona.
- PHP e Composer não estavam disponíveis na sessão de limpeza anterior; a aplicação e os scripts não foram executados ali. Faça validação real antes de concluir uma mudança funcional.

## Limpeza anterior

A limpeza agressiva anterior foi feita na branch `limpeza/agressiva`. Foram removidos testes e diagnósticos soltos, variantes órfãs da home/SAT, assets não referenciados, o serviço sem chamadas `badges_manager.php`, helpers não usados e o JavaScript genérico que não tinha markup/serviços correspondentes. Os dados JSON SAT e os arquivos de banco/configuração foram mantidos. A análise foi estática e não substitui testes de runtime.