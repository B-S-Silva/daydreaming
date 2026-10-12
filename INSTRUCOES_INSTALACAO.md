# 📋 Instruções de Instalação - Sistema DayDreamming

## 🎯 Visão Geral

Este guia contém todas as instruções necessárias para configurar o sistema DayDreamming (StudyAbroad Platform) em um novo ambiente. O sistema inclui:

- ✅ Sistema de usuários e autenticação
- ✅ Fórum de discussões
- ✅ Simulador de provas internacionais
- ✅ Sistema de badges e gamificação
- ✅ Painel administrativo

## 🛠️ Pré-requisitos

### Software Necessário:
- **PHP 7.4+** (recomendado PHP 8.0+)
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Servidor Web** (Apache, Nginx, ou PHP built-in server)
- **Composer** (opcional, para dependências futuras)

### Extensões PHP Necessárias:
- `pdo_mysql`
- `mbstring`
- `openssl`
- `json`
- `session`

## 📦 Instalação Passo a Passo

### 1. Preparar o Ambiente

#### 1.1 Criar o Banco de Dados
```sql
-- Conecte-se ao MySQL como root ou usuário com privilégios
CREATE DATABASE db_daydreamming_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário específico (opcional, mas recomendado)
CREATE USER 'daydreamming_user'@'localhost' IDENTIFIED BY 'sua_senha_segura';
GRANT ALL PRIVILEGES ON db_daydreamming_project.* TO 'daydreamming_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 1.2 Executar o Script de Criação
```bash
# Método 1: Via linha de comando
mysql -u root -p db_daydreamming_project < script_completo_database.sql

# Método 2: Via phpMyAdmin
# 1. Acesse phpMyAdmin
# 2. Selecione o banco 'db_daydreamming_project'
# 3. Vá em 'Importar'
# 4. Selecione o arquivo 'script_completo_database.sql'
# 5. Clique em 'Executar'
```

### 2. Configurar o Sistema

#### 2.1 Configurar Credenciais do Banco
Edite o arquivo `config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'daydreamming_user'); // ou 'root'
define('DB_PASS', 'sua_senha_segura');
define('DB_NAME', 'db_daydreamming_project');
define('DB_CHARSET', 'utf8mb4');
```

#### 2.2 Configurar URLs (se necessário)
```php
// URL Configuration
define('SITE_URL', 'http://seu-dominio.com'); // ou http://localhost:8080
```

### 3. Configurar Permissões

#### 3.1 Permissões de Arquivos (Linux/Mac)
```bash
# Dar permissões adequadas
chmod 755 .
chmod 644 *.php
chmod 644 *.sql
chmod 644 *.md

# Se houver diretórios de upload/cache
mkdir -p uploads cache logs
chmod 777 uploads cache logs
```

#### 3.2 Configurar .htaccess (Apache)
O arquivo `.htaccess` já está configurado para:
- Proteger arquivos sensíveis
- Bloquear acesso a arquivos .sql
- Configurações de segurança

### 4. Testar a Instalação

#### 4.1 Iniciar o Servidor
```bash
# Servidor PHP built-in (desenvolvimento)
php -S localhost:8080

# Ou configure no Apache/Nginx
```

#### 4.2 Acessar o Sistema
1. Abra o navegador em `http://localhost:8080`
2. Faça login com as credenciais padrão:
   - **Usuário:** `admin`
   - **Senha:** `admin123`
   - **Email:** `admin@daydreamming.com`

#### 4.3 Verificar Funcionalidades
- ✅ Login/Logout
- ✅ Cadastro de usuários
- ✅ Fórum (criar tópicos/respostas)
- ✅ Simulador de provas
- ✅ Painel administrativo

## 🔧 Configurações Adicionais

### Configurar Email (Recuperação de Senha)
Edite `config.php` para configurar SMTP:

```php
// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@gmail.com');
define('SMTP_PASS', 'sua-senha-app');
define('SMTP_FROM', 'noreply@daydreamming.com');
```

### Configurar Uploads
```php
// Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
```

## 🗃️ Estrutura do Banco de Dados

### Tabelas Principais:

#### Sistema de Usuários
- `usuarios` - Dados dos usuários
- `niveis_usuario` - Sistema de níveis

#### Sistema de Fórum
- `forum_categorias` - Categorias do fórum
- `forum_topicos` - Tópicos criados
- `forum_respostas` - Respostas aos tópicos

#### Sistema de Simulador
- `sessoes_teste` - Sessões de teste
- `resultados_testes` - Resultados dos testes
- `respostas_usuario` - Respostas individuais
- `questoes` - Banco de questões

#### Sistema de Gamificação
- `badges` - Definição das conquistas
- `usuario_badges` - Badges conquistadas

## 🔐 Segurança

### Primeiros Passos de Segurança:

1. **Alterar senha do admin:**
   ```sql
   UPDATE usuarios SET senha = PASSWORD('nova_senha_segura') WHERE usuario = 'admin';
   ```

2. **Configurar HTTPS em produção**

3. **Configurar backup automático:**
   ```bash
   # Exemplo de script de backup
   mysqldump -u usuario -p db_daydreamming_project > backup_$(date +%Y%m%d).sql
   ```

4. **Configurar logs de erro:**
   ```php
   // Em config.php para produção
   define('DEBUG', false);
   ini_set('log_errors', 1);
   ini_set('error_log', '/path/to/error.log');
   ```

## 🚀 Funcionalidades do Sistema

### Para Usuários:
- 📝 Cadastro e login
- 💬 Participação no fórum
- 📊 Simulados de provas internacionais
- 🏆 Sistema de badges e níveis
- 📈 Histórico de desempenho

### Para Administradores:
- 👥 Gerenciamento de usuários
- 🛡️ Moderação do fórum
- ❓ Gerenciamento de questões
- 📊 Relatórios e estatísticas
- 🏅 Gerenciamento de badges

## 🔧 Manutenção

### Limpeza Periódica:
```sql
-- Remover sessões antigas (executar mensalmente)
DELETE FROM sessoes_teste 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
AND status = 'expirado';

-- Limpar logs antigos
DELETE FROM logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Backup Recomendado:
```bash
#!/bin/bash
# Script de backup diário
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u usuario -p db_daydreamming_project > backup_$DATE.sql
gzip backup_$DATE.sql

# Manter apenas últimos 30 backups
find . -name "backup_*.sql.gz" -mtime +30 -delete
```

## 🆘 Solução de Problemas

### Problemas Comuns:

#### 1. Erro de Conexão com Banco
```
Solução:
- Verificar credenciais em config.php
- Confirmar se MySQL está rodando
- Testar conexão manual
```

#### 2. Páginas em Branco
```
Solução:
- Ativar exibição de erros: ini_set('display_errors', 1)
- Verificar logs de erro do PHP
- Confirmar extensões PHP necessárias
```

#### 3. Problemas com Sessões
```
Solução:
- Verificar permissões da pasta de sessões
- Configurar session.save_path no php.ini
- Limpar cookies do navegador
```

#### 4. Simulador Não Funciona
```
Solução:
- Executar setup_simulador.php
- Verificar se tabelas foram criadas
- Confirmar dados de exemplo nas questões
```

## 📞 Suporte

Para suporte adicional:
- 📧 Email: admin@daydreamming.com
- 📱 Telefone: +55 11 99999-9999
- 🌐 Site: http://localhost:8080

## 📄 Licença

Este sistema foi desenvolvido para fins educacionais. Consulte a documentação para mais informações sobre licenciamento.

---

**✅ Sistema DayDreamming - Sua plataforma completa para estudar no exterior!**