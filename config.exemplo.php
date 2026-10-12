<?php
/**
 * Arquivo de configuração de exemplo para colaboradores
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para config.php
 * 2. Altere as configurações abaixo conforme seu ambiente
 * 3. Execute: php setup_database.php
 */

// ⚠️ IMPORTANTE: Renomeie este arquivo para config.php

// Configurações do banco de dados
define('DB_HOST', 'localhost');          // Host do MySQL
define('DB_USER', 'root');               // Usuário do MySQL
define('DB_PASS', '');                   // Senha do MySQL (deixe vazio se não tiver)
define('DB_NAME', 'db_daydreamming_project'); // Nome do banco de dados
define('DB_CHARSET', 'utf8mb4');         // Charset do banco

// Configurações de segurança
define('CSRF_TOKEN_LENGTH', 32);         // Tamanho do token CSRF
define('SESSION_TIMEOUT', 3600);         // Timeout da sessão (1 hora)

// Configurações de rate limiting
define('RATE_LIMIT_LOGIN', 5);           // Máximo de tentativas de login
define('RATE_LIMIT_FORUM', 15);          // Máximo de ações no fórum por minuto
define('RATE_LIMIT_WINDOW', 300);        // Janela de tempo (5 minutos)

// Configurações do sistema
define('SITE_NAME', 'DayDreaming Project');
define('SITE_URL', 'http://localhost:8080');
define('ADMIN_EMAIL', 'admin@daydreaming.local');

// Configurações de debug (apenas para desenvolvimento)
define('DEBUG_MODE', true);              // Ativar modo debug
define('SHOW_ERRORS', true);             // Mostrar erros PHP
define('LOG_QUERIES', false);            // Log de queries SQL

// Configurações de upload (para futuras implementações)
define('UPLOAD_MAX_SIZE', 5242880);      // 5MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

/**
 * Função para conectar ao banco de dados
 * @return PDO Conexão com o banco
 */
function conectarBD() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Configurar timezone
        $pdo->exec("SET time_zone = '+00:00'");
        
        return $pdo;
        
    } catch (PDOException $e) {
        // Em produção, não mostrar detalhes do erro
        if (DEBUG_MODE) {
            die("Erro de conexão: " . $e->getMessage());
        } else {
            die("Erro de conexão com o banco de dados.");
        }
    }
}

/**
 * Função para conectar sem especificar banco (para criação)
 * @return PDO Conexão sem banco específico
 */
function conectarSemBanco() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        
        return new PDO($dsn, DB_USER, DB_PASS, $options);
        
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Erro de conexão: " . $e->getMessage());
        } else {
            die("Erro de conexão com o servidor MySQL.");
        }
    }
}

/**
 * Função para log de erros (para futuras implementações)
 * @param string $message Mensagem de erro
 * @param string $level Nível do erro
 */
function logError($message, $level = 'ERROR') {
    if (DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message\n";
        
        // Em desenvolvimento, mostrar no erro_log
        error_log($logMessage);
        
        // Em produção, salvar em arquivo específico
        // file_put_contents('logs/error.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Função para sanitizar input
 * @param string $input Input do usuário
 * @return string Input sanitizado
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Função para validar email
 * @param string $email Email para validar
 * @return bool True se válido
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Configurações de sessão segura
 */
function configurarSessao() {
    // Configurações de segurança da sessão
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Mudar para 1 em HTTPS
    ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
    
    // Nome da sessão personalizado
    session_name('DAYDREAMING_SESSION');
}

// Configurar sessão se ainda não foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    configurarSessao();
}

// Configurações de erro para desenvolvimento
if (DEBUG_MODE && SHOW_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone padrão
date_default_timezone_set('America/Sao_Paulo');

/**
 * INSTRUÇÕES PARA COLABORADORES:
 * ==============================
 * 
 * 1. CONFIGURAÇÃO BÁSICA:
 *    - Altere DB_HOST, DB_USER, DB_PASS conforme seu MySQL
 *    - Mantenha DB_NAME como 'db_daydreamming_project'
 *    - Execute: php setup_database.php
 * 
 * 2. DESENVOLVIMENTO:
 *    - Mantenha DEBUG_MODE = true durante desenvolvimento
 *    - Use SHOW_ERRORS = true para ver erros PHP
 *    - LOG_QUERIES = true para debug de SQL (cuidado com performance)
 * 
 * 3. PRODUÇÃO:
 *    - Altere DEBUG_MODE = false
 *    - Altere SHOW_ERRORS = false
 *    - Configure senha forte para DB_PASS
 *    - Configure HTTPS e altere cookie_secure = 1
 * 
 * 4. SEGURANÇA:
 *    - Nunca commite este arquivo com senhas reais
 *    - Use .gitignore para config.php
 *    - Mantenha config.exemplo.php atualizado
 * 
 * 5. TROUBLESHOOTING:
 *    - Erro de conexão: Verifique MySQL rodando
 *    - Erro de permissão: GRANT ALL PRIVILEGES
 *    - Erro de charset: Verifique utf8mb4 no MySQL
 */
?>
