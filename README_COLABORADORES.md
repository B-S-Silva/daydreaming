# 🚀 DayDreaming Project - Guia para Colaboradores

## 📋 Configuração Inicial

### 1. Pré-requisitos
- **PHP 7.4+** com extensões PDO e PDO_MySQL
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Servidor web** (Apache, Nginx) ou usar servidor built-in do PHP

### 2. Instalação Rápida

```bash
# 1. Clone o repositório
git clone [URL_DO_REPOSITORIO]
cd DayDreaming_old

# 2. Configure o banco de dados
# Edite config.php com suas credenciais MySQL

# 3. Execute a configuração automática (inclui questões)
php setup_database.php

# 4. Verifique se tudo funcionou
php verificar_questoes_carregadas.php

# 5. Inicie o servidor
php -S localhost:8080 -t .
```

### 3. Acesso ao Sistema

**🌐 URLs Principais:**
- **Sistema:** http://localhost:8080/
- **Fórum:** http://localhost:8080/forum.php
- **Simulados:** http://localhost:8080/simulador_provas.php
- **Admin:** http://localhost:8080/admin_forum.php

**🔑 Credenciais:**
- **Admin:** admin / admin123
- **Teste:** teste / teste123

## 🏗️ Estrutura do Projeto

### Arquivos Principais
```
├── config.php                 # Configurações do banco
├── setup_database.php         # Configuração inicial (EXECUTE PRIMEIRO)
├── verificar_auth.php          # Sistema de autenticação
├── header_status.php           # Header padrão
├── forum.php                   # Sistema de fórum
├── admin_forum.php             # Painel de moderação
├── simulador_provas.php        # Sistema de simulados
├── login.php                   # Página de login
├── index.php                   # Página inicial
└── seed_questoes.php           # Carregamento de questões
```

### Arquivos de Debug
```
├── verificar_instalacao.php    # Verificar sistema
├── teste_criacao_topico.php    # Testar fórum
├── debug_forum_criacao.php     # Debug detalhado
└── teste_usuario_comum.php     # Teste usuário comum
```

## 🔧 Sistema de Fórum (Atualizado)

### ✅ Mudanças Importantes
- **Aprovação Automática:** Tópicos e respostas ficam visíveis imediatamente
- **Sem Moderação Prévia:** Usuários comuns não precisam aguardar aprovação
- **Moderação Reativa:** Admin age após problemas (bloqueia usuários, deleta conteúdo)
- **Questões Incluídas:** SAT carregado automaticamente do JSON (120+ questões)
- **Setup Completo:** Uma única execução configura tudo

### 🎯 Funcionalidades
- **Criação de Tópicos:** Todos os usuários
- **Respostas:** Todos os usuários
- **Sistema de Curtidas:** Funcional
- **Categorias:** Organizadas por assunto
- **Busca:** Por título e conteúdo
- **Moderação:** Fixar, fechar, deletar (apenas admin)

## 🛠️ Desenvolvimento

### Padrões de Código
- **PHP:** PSR-12 (quando possível)
- **SQL:** Prepared statements obrigatório
- **Segurança:** CSRF tokens em todos os formulários
- **Autenticação:** Verificação em todas as páginas protegidas

### Estrutura do Banco
```sql
-- Principais tabelas
usuarios                # Usuários do sistema
questoes               # Questões dos simulados
sessoes_teste          # Sessões de teste
forum_categorias       # Categorias do fórum
forum_topicos          # Tópicos (aprovado = TRUE por padrão)
forum_respostas        # Respostas (aprovado = TRUE por padrão)
forum_curtidas         # Sistema de likes
forum_moderacao        # Log de moderação
logs_acesso           # Logs de login/logout
```

### Sistema de Autenticação
```php
// Verificar se usuário está logado
require_once 'verificar_auth.php';
verificarLogin();

// Verificar se é admin
$user = verificarAdmin();

// Verificar se usuário está ativo
$user = verificarUsuarioAtivo();
```

### Rate Limiting
```php
// Verificar rate limiting
if (!verificarRateLimit('action_name', 10, 60)) {
    // Bloquear ação
}
```

## 🧪 Testes

### Testes Disponíveis
```bash
# Verificar instalação completa
php verificar_instalacao.php

# Testar criação de tópicos
php teste_criacao_topico.php

# Testar usuário comum
php teste_usuario_comum.php

# Debug detalhado do fórum
php debug_forum_criacao.php
```

### Testes Manuais
1. **Login/Logout:** Testar com admin e usuário comum
2. **Fórum:** Criar tópicos, responder, curtir
3. **Simulados:** Iniciar teste, responder questões
4. **Moderação:** Fixar, fechar, deletar (como admin)

## 🔒 Segurança

### Implementado
- **CSRF Protection:** Tokens em todos os formulários
- **SQL Injection:** Prepared statements
- **Rate Limiting:** Proteção contra spam
- **Session Security:** Verificação de sessão
- **Input Validation:** Sanitização de dados

### Boas Práticas
- Sempre usar `htmlspecialchars()` para output
- Validar todos os inputs
- Verificar permissões antes de ações
- Registrar ações importantes nos logs

## 🐛 Troubleshooting

### Problemas Comuns

**❌ Erro de conexão com banco:**
```bash
# Verificar se MySQL está rodando
sudo systemctl status mysql

# Verificar credenciais no config.php
```

**❌ Tabelas não existem:**
```bash
# Re-executar setup
php setup_database.php
```

**❌ Fórum não funciona:**
```bash
# Verificar estrutura
php verificar_instalacao.php

# Testar criação
php teste_criacao_topico.php
```

**❌ Permissões negadas:**
```sql
-- No MySQL
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

## 📞 Suporte

### Debug Tools
- **verificar_instalacao.php** - Status completo do sistema
- **teste_*.php** - Testes específicos de funcionalidades
- **Logs MySQL** - Verificar erros de banco

### Estrutura de Logs
- **logs_acesso** - Login/logout de usuários
- **logs_sistema** - Ações importantes do sistema
- **forum_moderacao** - Ações de moderação

---

## 🎯 Próximos Passos

1. **Execute:** `php setup_database.php`
2. **Teste:** Acesse http://localhost:8080/
3. **Desenvolva:** Siga os padrões estabelecidos
4. **Teste:** Use as ferramentas de debug disponíveis

**O sistema está pronto para desenvolvimento colaborativo!** 🚀
