# 🚀 COMANDOS PARA COLABORADORES - DAYDREAMING

## 📋 Configuração Inicial (Obrigatória)

### 1. Clone do Repositório
```bash
git clone [URL_DO_REPOSITORIO]
cd DayDreaming
```

### 2. Configuração Automática do Banco
```bash
php setup_database.php
```

**O que este comando faz:**
- ✅ Cria o database `db_daydreamming_project`
- ✅ Cria **18 tabelas** necessárias para o sistema
- ✅ Insere **usuários padrão** com senhas hasheadas
- ✅ Cria **10 badges** do sistema de conquistas
- ✅ Configura **8 categorias** do fórum
- ✅ Insere **16 configurações** do sistema
- ✅ Prepara logs e sistema de gamificação

### 3. Carregamento de Questões
```bash
php seed_questoes.php
```

**O que este comando faz:**
- ✅ Carrega **120 questões** do SAT Practice Test #4
- ✅ Correlaciona **respostas corretas** automaticamente
- ✅ Organiza por **matérias** (Reading, Math, Writing)
- ✅ Configura **tipos de questão** (múltipla escolha/dissertativa)

### 4. Iniciar Servidor
```bash
php -S localhost:8080
```

### 5. Acessar Sistema
```
URL: http://localhost:8080
```

## 🔑 Credenciais de Acesso

### 👨‍💼 Administrador
- **Login:** `admin`
- **Senha:** `admin123`
- **Permissões:** Acesso total

### 👤 Usuário Teste
- **Login:** `teste`
- **Senha:** `teste123`
- **Permissões:** Usuário padrão

## 📊 Estrutura Completa do Banco

### 18 Tabelas Criadas Automaticamente

| # | Tabela | Descrição | Registros Iniciais |
|---|--------|-----------|-------------------|
| 1 | `usuarios` | Sistema de login e perfis | 2 usuários |
| 2 | `questoes` | Banco de questões dos exames | 120 questões SAT |
| 3 | `sessoes_teste` | Controle de testes ativos | 0 (dinâmico) |
| 4 | `respostas_usuario` | Respostas individuais | 0 (dinâmico) |
| 5 | `resultados_testes` | Resultados finalizados | 0 (dinâmico) |
| 6 | `badges` | Sistema de conquistas | 10 badges |
| 7 | `usuario_badges` | Badges conquistadas | 0 (dinâmico) |
| 8 | `forum_categorias` | Categorias do fórum | 8 categorias |
| 9 | `forum_topicos` | Tópicos do fórum | 0 (criado por usuários) |
| 10 | `forum_respostas` | Respostas do fórum | 0 (criado por usuários) |
| 11 | `forum_curtidas` | Sistema de curtidas | 0 (dinâmico) |
| 12 | `forum_moderacao` | Moderação do fórum | 0 (dinâmico) |
| 13 | `niveis_usuario` | Sistema de níveis/XP | 0 (dinâmico) |
| 14 | `configuracoes_sistema` | Configurações gerais | 16 configurações |
| 15 | `logs_sistema` | Logs de ações | 0 (dinâmico) |
| 16 | `logs_acesso` | Logs de login/logout | 0 (dinâmico) |
| 17 | `notificacoes` | Sistema de notificações | 0 (dinâmico) |
| 18 | `historico_experiencia` | Histórico de XP | 0 (dinâmico) |

## 🧪 Comandos de Verificação

### Verificar Instalação Completa
```bash
php verificar_instalacao.php
```

### Verificar Todas as Tabelas
```bash
php verificar_tabelas_completas.php
```

### Debug da Estrutura
```bash
php debug_estrutura_tabelas.php
```

## 🔧 Comandos de Manutenção

### Recriar Banco Completo
```bash
# Limpar e recriar tudo
php setup_database.php

# Recarregar questões
php seed_questoes.php
```

### Backup do Banco
```bash
# Windows (se MySQL estiver no PATH)
mysqldump -u root -p db_daydreamming_project > backup.sql

# Restaurar
mysql -u root -p db_daydreamming_project < backup.sql
```

## ✅ Checklist de Configuração

- [ ] **PHP 7.4+** instalado
- [ ] **MySQL** rodando
- [ ] **Repositório** clonado
- [ ] **`php setup_database.php`** executado ✅
- [ ] **`php seed_questoes.php`** executado ✅
- [ ] **Servidor** iniciado (`php -S localhost:8080`)
- [ ] **Login** realizado (admin/admin123)
- [ ] **Teste SAT** executado com sucesso
- [ ] **Histórico** funcionando
- [ ] **Revisão** funcionando

## 🎯 Teste Completo do Sistema

### Fluxo de Teste Obrigatório
1. **Acesse:** http://localhost:8080
2. **Login:** admin / admin123
3. **Vá para:** Simulador de Provas
4. **Escolha:** SAT (120 questões)
5. **Execute:** Responda 5-10 questões
6. **Finalize:** Clique "Finalizar Teste"
7. **Verifique:** Pontuação calculada corretamente
8. **Histórico:** Acesse histórico de provas
9. **Revisão:** Clique "Revisar" para ver gabarito
10. **Header:** Verifique header em todas as páginas

### Verificações Importantes
- ✅ **Pontuação:** (acertos ÷ 120) × 100 para SAT
- ✅ **Cronômetro:** Funcionando durante teste
- ✅ **Navegação:** Entre questões
- ✅ **Salvamento:** Automático das respostas
- ✅ **Responsivo:** Interface adaptável

## 🐛 Solução de Problemas

### Erro: "Connection refused"
```bash
# Verificar MySQL
net start mysql
# ou
sudo service mysql start
```

### Erro: "Table doesn't exist"
```bash
# Executar setup novamente
php setup_database.php
```

### Erro: "No questions found"
```bash
# Carregar questões
php seed_questoes.php
```

### Erro: "Access denied"
```bash
# Verificar credenciais no config.php
# Padrão: host=localhost, user=root, password=''
```

## 📁 Arquivos Importantes

### Scripts de Configuração
- `setup_database.php` - **OBRIGATÓRIO** - Configuração completa
- `seed_questoes.php` - **OBRIGATÓRIO** - Carregamento de questões
- `verificar_instalacao.php` - Diagnóstico geral
- `verificar_tabelas_completas.php` - Verificação detalhada

### Documentação
- `README.md` - Documentação principal
- `SETUP_COLABORADORES.md` - Guia detalhado
- `COMANDOS_COLABORADORES.md` - Este arquivo

### Sistema Principal
- `config.php` - Configuração do banco
- `index.php` - Página inicial
- `login.php` - Sistema de login
- `simulador_provas.php` - Lista de simulados
- `executar_teste.php` - Execução de testes
- `historico_provas.php` - Histórico de resultados

## 🎉 Resultado Final

Após executar os comandos obrigatórios, você terá:

### ✅ Sistema Completo
- **18 tabelas** criadas automaticamente
- **120 questões SAT** carregadas
- **Usuários padrão** com senhas seguras
- **Sistema de badges** configurado
- **Fórum** com categorias
- **Logs e auditoria** funcionando
- **Gamificação** (níveis/XP) ativa

### ✅ Funcionalidades Testadas
- Login/logout seguro
- Simulados funcionais
- Cálculo correto de pontuação
- Histórico completo
- Revisão detalhada
- Interface responsiva
- Header em todas as páginas

### ✅ Pronto para Desenvolvimento
- Estrutura de banco completa
- Dados de teste disponíveis
- Sistema funcional
- Documentação atualizada
- Scripts de manutenção

---

## 🚀 COMANDOS ESSENCIAIS RESUMIDOS

```bash
# 1. Clone
git clone [repositorio]
cd DayDreaming

# 2. Configurar banco (OBRIGATÓRIO)
php setup_database.php

# 3. Carregar questões (OBRIGATÓRIO)
php seed_questoes.php

# 4. Iniciar servidor
php -S localhost:8080

# 5. Acessar
# http://localhost:8080
# Login: admin / admin123
```

**🎯 Com estes 4 comandos, qualquer colaborador terá o sistema completo funcionando!**
