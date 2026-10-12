# 🎓 DayDreaming - Sistema de Simulados para Exames Internacionais

## 📚 Sobre o Projeto

Sistema completo de preparação para exames internacionais (SAT, TOEFL, IELTS, GRE) com simulados, histórico de desempenho e sistema de badges.

## 🚀 Configuração Rápida para Colaboradores

### Pré-requisitos
- PHP 7.4+
- MySQL 5.7+
- Servidor web (Apache/Nginx) ou PHP built-in server

### 📥 Instalação

1. **Clone o repositório:**
```bash
git clone [URL_DO_REPOSITORIO]
cd DayDreaming
```

2. **Configure o banco de dados:**
```bash
php setup_database.php
```

3. **Carregue as questões do SAT:**
```bash
php seed_questoes.php
```

4. **Inicie o servidor:**
```bash
php -S localhost:8080
```

5. **Acesse o sistema:**
```
http://localhost:8080
```

### 🔑 Credenciais Padrão

**Administrador:**
- Login: `admin`
- Senha: `admin123`

**Usuário Teste:**
- Login: `teste`
- Senha: `teste123`

## ✨ Funcionalidades

### 🎯 Testes Internacionais
- **TOEFL** - Test of English as a Foreign Language
- **IELTS** - International English Language Testing System
- **SAT** - Scholastic Assessment Test
- **ACT** - American College Testing
- **GRE** - Graduate Record Examinations
- **GMAT** - Graduate Management Admission Test
- **Testes específicos por país** (DELE, DELF, TestDaF, HSK, JLPT, etc.)

### 🌍 Países e Universidades
- **América do Norte**: Estados Unidos, Canadá
- **Europa**: Reino Unido, Alemanha, França, Espanha, Itália
- **Ásia**: Japão, China, Coreia do Sul
- **Oceania**: Austrália, Nova Zelândia
- **África**: África do Sul

### 👥 Sistema de Usuários
- Cadastro e login de usuários
- Dashboard personalizado
- Sistema de administração
- Logs de acesso e atividades

### 💬 Fórum Comunitário
- Discussões sobre estudos no exterior
- Troca de experiências
- Suporte da comunidade

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 8.2+
- **Banco de Dados**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **Estilização**: CSS customizado com variáveis CSS
- **Servidor**: PHP Built-in Server (desenvolvimento)

## 📋 Pré-requisitos

- PHP 8.2 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache, Nginx) ou PHP Built-in Server

## 🚀 Execução com Docker (Recomendado)

### Pré-requisitos
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac)
- Docker Engine + Docker Compose (Linux)

### Instalação do Docker

**Windows/Mac:**
1. Baixe e instale o [Docker Desktop](https://www.docker.com/products/docker-desktop/)
2. Reinicie o computador após a instalação
3. Verifique a instalação: `docker --version`

**Linux (Ubuntu/Debian):**
```bash
sudo apt update
sudo apt install docker.io docker-compose
sudo systemctl start docker
sudo usermod -aG docker $USER
```

### Instalação Rápida

1. **Clone o repositório:**
```bash
git clone https://github.com/madpais/php-login-system.git
cd php-login-system
```

2. **Execute com Docker Compose:**
```bash
# Docker Desktop (Windows/Mac) ou versões mais novas
docker compose up -d

# Ou versão standalone do docker-compose
docker-compose up -d
```

3. **Acesse a aplicação:**
- **Sistema:** http://localhost:8080
- **phpMyAdmin:** http://localhost:8081

### Comandos Úteis

```bash
# Parar os containers
docker compose down

# Ver logs em tempo real
docker compose logs -f

# Reconstruir containers após mudanças
docker compose up -d --build

# Limpar volumes (reset completo do banco)
docker compose down -v
```

### Serviços Incluídos

- **Web Server:** Apache + PHP 8.1 (porta 8080)
- **Banco de Dados:** MySQL 8.0 (porta 3306)
- **phpMyAdmin:** Interface web para MySQL (porta 8081)

### Credenciais de Acesso

**Usuários do Sistema:**
- **Admin:** `admin` / `123456`
- **Teste:** `teste` / `123456`
- **Maria:** `maria.santos` / `123456`

**Banco de Dados:**
- **Root:** `root` / `rootpassword`
- **User:** `user` / `userpassword`

## 🛠️ Instalação Manual

### Requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache, Nginx, etc.)

### 1. Banco de Dados

Importe os arquivos SQL na seguinte ordem:

```bash
mysql -u root -p sistema_login < db_structure.sql
mysql -u root -p sistema_login < reset_database.sql
```

### 2. Configuração

O arquivo `config.php` está configurado para usar variáveis de ambiente do Docker, mas também funciona com valores padrão para desenvolvimento local.

### 3. Servidor Local

```bash
php -S localhost:8080
```

Acesse: http://localhost:8080

## Usuários de Exemplo

O sistema vem com dois usuários pré-cadastrados para testes:

1. **Administrador**
   - Usuário: admin
   - Senha: 123456
   - Email: admin@exemplo.com

2. **Usuário Teste**
   - Usuário: teste
   - Senha: 123456
   - Email: teste@exemplo.com

## Estrutura de Arquivos

- `index.php` - Redirecionamento para a página de login
- `login.php` - Página de login
- `cadastro.php` - Página de cadastro de novos usuários
- `recuperar_senha.php` - Página de recuperação de senha
- `dashboard.php` - Painel de controle após login
- `logout.php` - Script para fazer logout
- `config.php` - Configurações de conexão com o banco de dados
- `db_structure.sql` - Estrutura do banco de dados
- `public/css/style.css` - Estilos CSS principais
- `public/js/main.js` - Scripts JavaScript principais

## Segurança

Este sistema implementa várias medidas de segurança:

- Senhas armazenadas com hash usando `password_hash()` e `password_verify()`
- Proteção contra SQL Injection usando PDO com prepared statements
- Validação de entrada de dados
- Proteção contra XSS usando `htmlspecialchars()`
- Gerenciamento seguro de sessões

## Personalização

O sistema inclui funcionalidades JavaScript modernas para interatividade, validação de formulários e otimizações de performance no arquivo `public/js/main.js`.

## Melhorias Futuras

- Implementação completa do sistema de recuperação de senha com envio de e-mail
- Perfil de usuário com upload de foto
- Níveis de permissão (administrador, usuário comum, etc.)
- Autenticação de dois fatores
- Integração com redes sociais

## Licença

Este projeto está disponível como código aberto sob os termos da [Licença MIT](https://opensource.org/licenses/MIT).
