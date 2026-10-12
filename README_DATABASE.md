# 🗄️ Script de Banco de Dados - Sistema DayDreamming

## 📋 Sobre o Script

O arquivo `script_completo_database.sql` contém todas as queries SQL necessárias para criar e configurar o banco de dados completo do sistema DayDreamming. Este script foi desenvolvido para permitir a instalação rápida e completa do sistema em qualquer ambiente.

## 🎯 O que o Script Contém

### 📊 Estrutura Completa do Banco

#### 1. Sistema de Usuários
- **`usuarios`** - Tabela principal com dados de login, perfil e permissões
- **`niveis_usuario`** - Sistema de níveis e experiência para gamificação

#### 2. Sistema de Fórum
- **`forum_categorias`** - Categorias para organização dos tópicos
- **`forum_topicos`** - Tópicos criados pelos usuários
- **`forum_respostas`** - Respostas aos tópicos do fórum

#### 3. Sistema de Simulador de Provas
- **`sessoes_teste`** - Controle de sessões ativas de testes
- **`resultados_testes`** - Resultados finais e estatísticas
- **`respostas_usuario`** - Respostas individuais de cada questão
- **`questoes`** - Banco de questões para os simulados

#### 4. Sistema de Gamificação
- **`badges`** - Definição de todas as conquistas disponíveis
- **`usuario_badges`** - Relacionamento de badges conquistadas por usuário

### 🔧 Recursos Avançados

#### Views Otimizadas
- **`vw_estatisticas_usuario`** - Estatísticas consolidadas por usuário
- **`vw_estatisticas_forum`** - Estatísticas do fórum por categoria

#### Índices para Performance
- Índices otimizados para consultas frequentes
- Chaves estrangeiras para integridade referencial
- Índices compostos para queries complexas

#### Triggers Automáticos
- Criação automática de nível inicial para novos usuários
- Manutenção automática de timestamps

## 🚀 Como Executar o Script

### Método 1: Via Linha de Comando (MySQL)
```bash
# 1. Criar o banco de dados
mysql -u root -p -e "CREATE DATABASE db_daydreamming_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Executar o script
mysql -u root -p db_daydreamming_project < script_completo_database.sql
```

### Método 2: Via phpMyAdmin
1. Acesse o phpMyAdmin
2. Crie um novo banco: `db_daydreamming_project`
3. Selecione o banco criado
4. Vá na aba "Importar"
5. Selecione o arquivo `script_completo_database.sql`
6. Clique em "Executar"

### Método 3: Via Cliente MySQL (Workbench, HeidiSQL, etc.)
1. Conecte-se ao servidor MySQL
2. Crie o banco `db_daydreamming_project`
3. Abra o arquivo `script_completo_database.sql`
4. Execute o script completo

## 📋 Dados Iniciais Incluídos

### 👤 Usuário Administrador
- **Usuário:** `admin`
- **Senha:** `admin123` ⚠️ *Altere após o primeiro login*
- **Email:** `admin@daydreamming.com`
- **Permissões:** Administrador completo

### 📂 Categorias do Fórum
1. **Geral** - Discussões gerais sobre estudar no exterior
2. **Testes Internacionais** - Dúvidas sobre TOEFL, IELTS, SAT, etc.
3. **Universidades** - Informações sobre universidades
4. **Bolsas de Estudo** - Oportunidades de financiamento
5. **Experiências** - Relatos de estudantes
6. **Dúvidas Técnicas** - Suporte do sistema

### 🏆 Badges Padrão (20 badges)
- **Especiais:** Primeiro teste, primeira participação
- **Pontuação:** Satisfatório (60%), Bom (70%), Muito Bom (80%), Excelência (90%)
- **Frequência:** Especialistas por tipo de prova, consistência, dedicação
- **Conquistas:** Maratonista, Perfeccionista, Velocista, Persistente

### ❓ Questões de Exemplo
- Uma questão exemplo para cada tipo de prova
- Estrutura completa com alternativas A-E
- Metadados como matéria e assunto
- Preparado para carregamento via JSON/XML

## 🔍 Verificação Pós-Instalação

### Comandos de Verificação
```sql
-- Verificar se todas as tabelas foram criadas
SHOW TABLES;

-- Verificar estrutura da tabela usuarios
DESCRIBE usuarios;

-- Verificar dados iniciais
SELECT COUNT(*) as total_badges FROM badges;
SELECT COUNT(*) as total_categorias FROM forum_categorias;
SELECT COUNT(*) as total_questoes FROM questoes;

-- Verificar usuário admin
SELECT id, nome, usuario, email, is_admin FROM usuarios WHERE usuario = 'admin';
```

### Resultados Esperados
- **Tabelas criadas:** 11 tabelas principais
- **Views criadas:** 2 views otimizadas
- **Badges inseridas:** 20 badges padrão
- **Categorias:** 6 categorias do fórum
- **Questões:** 8 questões de exemplo
- **Usuário admin:** 1 usuário administrador

## ⚙️ Configurações Importantes

### Charset e Collation
- **Charset:** `utf8mb4` (suporte completo a Unicode)
- **Collation:** `utf8mb4_unicode_ci` (ordenação internacional)

### Engines de Tabela
- **Engine:** InnoDB (padrão MySQL)
- **Suporte a:** Transações, chaves estrangeiras, índices

### Configurações de Segurança
- Senhas criptografadas com `password_hash()`
- Chaves estrangeiras com `ON DELETE CASCADE`
- Campos obrigatórios definidos como `NOT NULL`
- Índices únicos para evitar duplicatas

## 🔧 Personalização

### Modificar Dados Iniciais
Para personalizar os dados iniciais, edite as seções `INSERT INTO` no final do script:

```sql
-- Exemplo: Adicionar nova categoria
INSERT INTO forum_categorias (nome, descricao, cor, icone, ativo, ordem) VALUES 
('Nova Categoria', 'Descrição da categoria', '#ff5722', '🆕', TRUE, 7);

-- Exemplo: Adicionar nova badge
INSERT INTO badges (codigo, nome, descricao, icone, tipo, condicao_valor) VALUES
('nova_badge', 'Nova Conquista', 'Descrição da conquista', '🎉', 'especial', 1);
```

### Adicionar Campos Personalizados
```sql
-- Exemplo: Adicionar campo telefone na tabela usuarios
ALTER TABLE usuarios ADD COLUMN telefone VARCHAR(20) NULL AFTER email;

-- Exemplo: Adicionar campo imagem na tabela forum_topicos
ALTER TABLE forum_topicos ADD COLUMN imagem VARCHAR(255) NULL AFTER conteudo;
```

## 📊 Estatísticas do Script

- **Linhas de código:** ~400 linhas
- **Tabelas criadas:** 11 tabelas
- **Índices criados:** 25+ índices
- **Views criadas:** 2 views
- **Triggers criados:** 1 trigger
- **Dados iniciais:** 35+ registros
- **Tempo de execução:** ~2-5 segundos

## 🚨 Avisos Importantes

### ⚠️ Segurança
1. **Altere a senha do admin** imediatamente após a instalação
2. **Configure HTTPS** em ambiente de produção
3. **Faça backup** antes de executar em banco existente
4. **Teste em ambiente** de desenvolvimento primeiro

### 🔄 Compatibilidade
- **MySQL:** 5.7+ (recomendado 8.0+)
- **MariaDB:** 10.3+ (recomendado 10.5+)
- **PHP:** 7.4+ (recomendado 8.0+)

### 📝 Observações
- O script usa `IF NOT EXISTS` para evitar erros em re-execuções
- Dados duplicados são tratados com `ON DUPLICATE KEY UPDATE`
- Todas as tabelas usam `AUTO_INCREMENT` para chaves primárias
- Timestamps são automaticamente gerenciados

## 🆘 Solução de Problemas

### Erro: "Table already exists"
**Solução:** O script usa `IF NOT EXISTS`, este erro não deveria ocorrer. Verifique se está executando a versão correta.

### Erro: "Foreign key constraint fails"
**Solução:** Execute o script em ordem. As tabelas são criadas na sequência correta de dependências.

### Erro: "Access denied"
**Solução:** Verifique se o usuário MySQL tem privilégios para criar tabelas e inserir dados.

### Erro: "Unknown charset"
**Solução:** Verifique se o MySQL suporta `utf8mb4`. Em versões antigas, use `utf8`.

## 📞 Suporte

Para dúvidas sobre o script ou problemas na instalação:
- 📧 **Email:** admin@daydreamming.com
- 📱 **Telefone:** +55 11 99999-9999
- 📖 **Documentação:** Consulte `INSTRUCOES_INSTALACAO.md`

---

**✅ Script testado e validado para MySQL 8.0+ e MariaDB 10.5+**

*Última atualização: Janeiro 2025*