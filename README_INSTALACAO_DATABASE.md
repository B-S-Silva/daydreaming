# 🗄️ Instalação do Banco de Dados - DayDreamming

## 📋 Visão Geral

Este documento contém instruções detalhadas para instalar e configurar o banco de dados do sistema **DayDreamming (StudyAbroad Platform)**. O sistema foi completamente recriado com uma estrutura moderna e otimizada.

## 🎯 Arquivos Criados

### 📄 Arquivos Principais
- **`database_completa.sql`** - Script SQL completo com todas as tabelas, dados e configurações
- **`instalar_database.php`** - Interface web para instalação automática
- **`README_INSTALACAO_DATABASE.md`** - Este arquivo de instruções

## 🏗️ Estrutura do Banco de Dados

### 📊 Tabelas Criadas (15 tabelas principais)

#### 🔐 Sistema de Usuários
- **`usuarios`** - Dados principais dos usuários
- **`logs_acesso`** - Log de tentativas de login
- **`logs_sistema`** - Log de ações do sistema
- **`niveis_usuario`** - Sistema de níveis e experiência
- **`historico_experiencia`** - Histórico de ganho de XP

#### 💬 Sistema de Fórum
- **`forum_categorias`** - Categorias do fórum
- **`forum_topicos`** - Tópicos criados
- **`forum_respostas`** - Respostas aos tópicos
- **`forum_curtidas`** - Sistema de curtidas
- **`forum_moderacao`** - Log de moderação

#### 🎯 Sistema de Simulador
- **`questoes`** - Banco de questões dos testes
- **`sessoes_teste`** - Sessões de testes dos usuários
- **`resultados_testes`** - Resultados detalhados
- **`respostas_usuario`** - Respostas individuais

#### 🏆 Sistema de Gamificação
- **`badges`** - Definição das conquistas
- **`usuario_badges`** - Badges conquistadas

#### ⚙️ Sistema Auxiliar
- **`configuracoes_sistema`** - Configurações gerais
- **`notificacoes`** - Notificações para usuários

## 🚀 Métodos de Instalação

### 🌐 Método 1: Interface Web (Recomendado)

1. **Acesse o instalador web:**
   ```
   http://localhost:8080/instalar_database.php
   ```

2. **Siga os passos na interface:**
   - Verifique os pré-requisitos
   - Confirme as configurações
   - Clique em "Iniciar Instalação"
   - Aguarde a conclusão

3. **Credenciais criadas:**
   - **Usuário:** `admin`
   - **Senha:** `admin123`
   - **Email:** `admin@daydreamming.com`

### 💻 Método 2: Linha de Comando

```bash
# Via MySQL CLI
mysql -u root -p < database_completa.sql

# Ou especificando o banco
mysql -u root -p db_daydreamming_project < database_completa.sql
```

### 🔧 Método 3: phpMyAdmin

1. Acesse phpMyAdmin
2. Crie o banco `db_daydreamming_project`
3. Selecione o banco criado
4. Vá em "Importar"
5. Selecione o arquivo `database_completa.sql`
6. Clique em "Executar"

## ⚙️ Configuração

### 📝 Editar config.php

Após a instalação, configure o arquivo `config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Seu usuário MySQL
define('DB_PASS', '');               // Sua senha MySQL
define('DB_NAME', 'db_daydreamming_project');
define('DB_CHARSET', 'utf8mb4');
```

## 🎮 Funcionalidades Implementadas

### 🔐 Sistema de Segurança
- ✅ Hash de senhas com `password_hash()`
- ✅ Proteção contra SQL Injection
- ✅ Sistema de bloqueio por tentativas
- ✅ Logs detalhados de acesso
- ✅ Tokens CSRF para formulários

### 🏆 Sistema de Gamificação
- ✅ **25 badges diferentes** com raridades
- ✅ Sistema de níveis com XP
- ✅ Histórico de experiência
- ✅ Ranking de usuários
- ✅ Notificações de conquistas

### 📝 Testes Internacionais
- ✅ **10 tipos de testes** suportados:
  - TOEFL, IELTS, SAT, ACT, GRE, GMAT
  - DELE, DELF, TestDaF, JLPT, HSK
- ✅ Questões de exemplo incluídas
- ✅ Sistema de pontuação detalhado
- ✅ Histórico completo de resultados

### 💬 Fórum Comunitário
- ✅ **8 categorias** pré-configuradas
- ✅ Sistema de moderação
- ✅ Curtidas e interações
- ✅ Busca avançada
- ✅ Estatísticas detalhadas

## 📊 Dados Iniciais Incluídos

### 👥 Usuários de Teste
- **admin** - Administrador principal
- **teste** - Usuário de teste
- **maria.santos** - Usuário exemplo
- **joao.silva** - Usuário exemplo

### 🏆 Badges Pré-configuradas
- **Marcos:** Primeiro Passo, Primeira Participação
- **Pontuação:** Satisfatório, Bom, Muito Bom, Excelência, Perfeccionista
- **Frequência:** Consistente, Dedicado, Maratonista, Persistente
- **Especialização:** Por tipo de teste (TOEFL, IELTS, etc.)
- **Sociais:** Participativo, Popular, Influenciador

### 📚 Questões de Exemplo
- **2 questões por tipo de teste** (20 questões total)
- Questões em múltiplos idiomas
- Explicações detalhadas incluídas
- Diferentes níveis de dificuldade

## 🔧 Recursos Avançados

### 📈 Views Otimizadas
- **`vw_estatisticas_usuario`** - Estatísticas completas por usuário
- **`vw_estatisticas_forum`** - Estatísticas do fórum por categoria
- **`vw_ranking_usuarios`** - Ranking geral dos usuários

### ⚡ Triggers Automáticos
- Criação automática de nível para novos usuários
- Atualização de estatísticas do fórum
- Registro de histórico de experiência
- Controle de visualizações

### 🛠️ Procedimentos Armazenados
- **`sp_atualizar_nivel_usuario`** - Recalcula nível baseado em XP
- **`sp_limpeza_dados_antigos`** - Remove dados antigos automaticamente

### 🚀 Índices de Performance
- Índices compostos para consultas frequentes
- Otimização para ranking e estatísticas
- Suporte a busca por texto completo

## 🔍 Verificação da Instalação

### ✅ Comandos de Verificação

```sql
-- Verificar tabelas criadas
SHOW TABLES;

-- Verificar usuários
SELECT id, nome, usuario, is_admin, ativo FROM usuarios;

-- Verificar badges
SELECT codigo, nome, icone, raridade FROM badges ORDER BY raridade;

-- Verificar categorias do fórum
SELECT nome, icone, cor FROM forum_categorias ORDER BY ordem;

-- Verificar questões
SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova;
```

### 📊 Estatísticas Esperadas
- **Tabelas:** 15 tabelas principais
- **Usuários:** 4 usuários iniciais
- **Badges:** 25 badges configuradas
- **Categorias:** 8 categorias do fórum
- **Questões:** 20 questões de exemplo
- **Configurações:** 16 configurações do sistema

## 🆘 Solução de Problemas

### ❌ Problemas Comuns

#### 1. Erro de Conexão
```
Solução:
- Verificar se MySQL está rodando
- Confirmar credenciais em config.php
- Testar conexão manual
```

#### 2. Erro de Charset
```
Solução:
- Usar UTF8MB4 no MySQL
- Configurar charset no PHP
- Verificar configuração do servidor
```

#### 3. Erro de Permissões
```
Solução:
- Verificar permissões do usuário MySQL
- Confirmar privilégios de CREATE DATABASE
- Testar com usuário root
```

#### 4. Timeout na Instalação
```
Solução:
- Aumentar max_execution_time no PHP
- Executar via linha de comando
- Dividir o script em partes menores
```

## 🔄 Manutenção

### 🧹 Limpeza Regular
```sql
-- Executar mensalmente
CALL sp_limpeza_dados_antigos();
```

### 💾 Backup Recomendado
```bash
# Backup completo
mysqldump -u usuario -p db_daydreamming_project > backup_$(date +%Y%m%d).sql

# Backup apenas dados
mysqldump -u usuario -p --no-create-info db_daydreamming_project > dados_$(date +%Y%m%d).sql
```

### 📈 Monitoramento
- Verificar logs de erro regularmente
- Monitorar crescimento das tabelas
- Acompanhar performance das consultas
- Validar integridade dos dados

## 📞 Suporte

Para problemas na instalação:

1. **Verifique os logs** de erro do PHP e MySQL
2. **Consulte este documento** para soluções comuns
3. **Teste com dados mínimos** primeiro
4. **Verifique permissões** de arquivo e banco

---

**✅ Sistema DayDreamming - Database v2.0**  
**Criado em:** 2025-01-21  
**Charset:** UTF8MB4  
**Engine:** InnoDB  
**Compatibilidade:** MySQL 5.7+ / MariaDB 10.3+
