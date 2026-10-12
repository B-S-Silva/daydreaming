# 🚀 Guia de Configuração para Colaboradores

## 📋 Pré-requisitos

Antes de começar, certifique-se de ter instalado:

- **PHP 7.4+** (recomendado: PHP 8.0+)
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Servidor web** (Apache, Nginx) ou usar o servidor built-in do PHP

### 🔍 Verificar Pré-requisitos

```bash
# Verificar versão do PHP
php --version

# Verificar extensões necessárias
php -m | grep -E "(pdo|pdo_mysql|json|mbstring)"

# Verificar MySQL
mysql --version
```

## 📥 Configuração Inicial

### 1. Clone do Repositório

```bash
git clone [URL_DO_REPOSITORIO]
cd DayDreaming
```

### 2. Configuração do Banco de Dados

#### Opção A: Configuração Automática (Recomendada)

```bash
# Execute o script de configuração automática
php setup_database.php
```

Este script irá:
- ✅ Criar o database `db_daydreamming_project`
- ✅ Criar todas as 12 tabelas necessárias
- ✅ Inserir usuários padrão com senhas hasheadas
- ✅ Criar badges do sistema
- ✅ Configurar categorias do fórum
- ✅ Inserir configurações do sistema

#### Opção B: Configuração Manual

Se preferir configurar manualmente:

```sql
-- 1. Criar database
CREATE DATABASE db_daydreamming_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Usar o database
USE db_daydreamming_project;

-- 3. Executar o script SQL completo
SOURCE script_completo_database.sql;
```

### 3. Carregar Questões do SAT

```bash
# Carregar questões do arquivo JSON
php seed_questoes.php
```

Este script irá:
- ✅ Carregar ~120 questões do SAT Practice Test #4
- ✅ Correlacionar respostas automaticamente
- ✅ Organizar por matérias (Reading, Math, Writing)
- ✅ Configurar tipos de questão (múltipla escolha/dissertativa)

### 4. Iniciar o Servidor

```bash
# Servidor built-in do PHP (desenvolvimento)
php -S localhost:8080

# Ou configurar Apache/Nginx apontando para a pasta do projeto
```

### 5. Acessar o Sistema

```
URL: http://localhost:8080
```

## 🔑 Credenciais Padrão

### 👨‍💼 Administrador
- **Login:** `admin`
- **Senha:** `admin123`
- **Permissões:** Acesso total ao sistema

### 👤 Usuário Teste
- **Login:** `teste`
- **Senha:** `teste123`
- **Permissões:** Usuário padrão

## 📊 Estrutura do Banco de Dados

### Tabelas Principais

| Tabela | Descrição | Registros Iniciais |
|--------|-----------|-------------------|
| `usuarios` | Dados de login e perfil | 2 usuários |
| `questoes` | Banco de questões dos exames | ~120 questões SAT |
| `sessoes_teste` | Controle de testes ativos | 0 (criadas dinamicamente) |
| `respostas_usuario` | Respostas individuais | 0 (criadas dinamicamente) |
| `resultados_testes` | Resultados finalizados | 0 (criados dinamicamente) |
| `badges` | Sistema de conquistas | 7 badges padrão |
| `usuario_badges` | Badges conquistadas | 0 (conquistadas dinamicamente) |
| `forum_categorias` | Categorias do fórum | 6 categorias |
| `forum_topicos` | Tópicos do fórum | 0 (criados pelos usuários) |
| `forum_respostas` | Respostas do fórum | 0 (criadas pelos usuários) |
| `niveis_usuario` | Sistema de níveis/XP | 0 (criados automaticamente) |
| `configuracoes_sistema` | Configurações gerais | 10 configurações |

## 🧪 Testando o Sistema

### Fluxo Completo de Teste

1. **Acesse:** http://localhost:8080
2. **Faça login:** admin / admin123
3. **Vá para:** Simulador de Provas
4. **Escolha:** SAT (120 questões disponíveis)
5. **Execute:** Responda algumas questões
6. **Finalize:** Clique em "Finalizar Teste"
7. **Veja resultados:** Pontuação calculada corretamente
8. **Acesse histórico:** Ver todas as provas realizadas
9. **Revise:** Clique em "Revisar" para ver gabarito

### Verificações Importantes

- ✅ **Pontuação:** Calculada como (acertos ÷ total questões exame) × 100
- ✅ **Cronômetro:** Funcionando durante o teste
- ✅ **Navegação:** Entre questões funcional
- ✅ **Salvamento:** Respostas salvas automaticamente
- ✅ **Header:** Status de login em todas as páginas
- ✅ **Responsivo:** Interface adaptável a diferentes telas

## 🔧 Comandos de Manutenção

### Verificar Instalação

```bash
# Verificar se tudo está funcionando
php verificar_instalacao.php
```

### Recriar Banco (se necessário)

```bash
# Limpar e recriar tudo
php setup_database.php

# Recarregar questões
php seed_questoes.php
```

### Backup do Banco

```bash
# Fazer backup
mysqldump -u root -p db_daydreamming_project > backup_$(date +%Y%m%d).sql

# Restaurar backup
mysql -u root -p db_daydreamming_project < backup_20241221.sql
```

## 🐛 Solução de Problemas

### Erro: "Connection refused"

```bash
# Verificar se MySQL está rodando
sudo service mysql start
# ou
brew services start mysql
```

### Erro: "Access denied"

```bash
# Verificar credenciais no config.php
# Padrão: user='root', password=''
```

### Erro: "Table doesn't exist"

```bash
# Executar novamente o setup
php setup_database.php
```

### Erro: "No questions found"

```bash
# Carregar questões
php seed_questoes.php

# Verificar arquivos JSON
ls -la exames/SAT/
```

### Erro: "Headers already sent"

```bash
# Verificar se não há espaços/quebras antes de <?php
# Verificar encoding dos arquivos (UTF-8 sem BOM)
```

## 📁 Arquivos Importantes

### Scripts de Configuração
- `setup_database.php` - Configuração completa do banco
- `seed_questoes.php` - Carregamento de questões
- `verificar_instalacao.php` - Diagnóstico do sistema

### Configuração
- `config.php` - Configurações do banco de dados
- `.gitignore` - Arquivos ignorados pelo Git

### Sistema Principal
- `index.php` - Página inicial
- `login.php` - Sistema de autenticação
- `simulador_provas.php` - Lista de simulados
- `executar_teste.php` - Execução de testes
- `historico_provas.php` - Histórico de resultados

## 🎯 Próximos Passos

Após a configuração bem-sucedida:

1. **Explore o sistema** com as credenciais padrão
2. **Teste todas as funcionalidades** principais
3. **Verifique a responsividade** em diferentes dispositivos
4. **Comece o desenvolvimento** das suas funcionalidades
5. **Mantenha o banco atualizado** com os scripts fornecidos

## 📞 Suporte

### Problemas Comuns
- Consulte este documento primeiro
- Execute `php verificar_instalacao.php`
- Verifique os logs de erro do PHP

### Contato
- 📧 **Email:** [email-do-projeto]
- 💬 **Slack:** [canal-do-projeto]
- 🐛 **Issues:** [link-para-issues]

---

## ✅ Checklist de Configuração

- [ ] PHP 7.4+ instalado
- [ ] MySQL rodando
- [ ] Repositório clonado
- [ ] `php setup_database.php` executado com sucesso
- [ ] `php seed_questoes.php` executado com sucesso
- [ ] Servidor iniciado (`php -S localhost:8080`)
- [ ] Login realizado (admin/admin123)
- [ ] Teste SAT executado com sucesso
- [ ] Histórico e revisão funcionando

**🎉 Parabéns! Seu ambiente está pronto para desenvolvimento!**
