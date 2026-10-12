<?php
// Arquivo de verificação de autenticação centralizada
// Este arquivo deve ser incluído em páginas que requerem autenticação

// Verificar se a sessão já foi iniciada
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once 'config.php';

/**
 * Verifica se o usuário está logado
 * @param string $redirect_url URL para redirecionamento se não estiver logado
 */
function verificarLogin($redirect_url = 'login.php') {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: $redirect_url");
        exit;
    }
}

/**
 * Verifica se o usuário é administrador
 * @param string $redirect_url URL para redirecionamento se não for admin
 */
function verificarAdmin($redirect_url = 'forum.php?erro=acesso_negado') {
    // Primeiro verificar se está logado
    verificarLogin();
    
    try {
        $pdo = conectarBD();
        
        // Verificar se o usuário é administrador
        $stmt = $pdo->prepare("SELECT is_admin, nome, ativo FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Usuário não encontrado, limpar sessão
            session_destroy();
            header("Location: login.php?erro=usuario_nao_encontrado");
            exit;
        }
        
        // Verificar se o usuário está ativo
        if (!$user['ativo']) {
            session_destroy();
            header("Location: login.php?erro=usuario_banido");
            exit;
        }
        
        if (!$user['is_admin']) {
            header("Location: $redirect_url");
            exit;
        }
        
        return $user;
        
    } catch (Exception $e) {
        // Em caso de erro, redirecionar para login
        session_destroy();
        header("Location: login.php?erro=erro_sistema");
        exit;
    }
}

/**
 * Verifica se o usuário está ativo (não banido)
 */
function verificarUsuarioAtivo() {
    // Primeiro verificar se está logado
    verificarLogin();
    
    try {
        $pdo = conectarBD();
        
        // Verificar se o usuário está ativo
        $stmt = $pdo->prepare("SELECT ativo, nome FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Usuário não encontrado, limpar sessão
            session_destroy();
            header("Location: login.php?erro=usuario_nao_encontrado");
            exit;
        }
        
        if (!$user['ativo']) {
            session_destroy();
            header("Location: login.php?erro=usuario_banido");
            exit;
        }
        
        return $user;
        
    } catch (Exception $e) {
        // Em caso de erro, redirecionar para login
        session_destroy();
        header("Location: login.php?erro=erro_sistema");
        exit;
    }
}

/**
 * Registra tentativa de acesso não autorizado
 * @param string $pagina Página que foi acessada
 * @param string $ip IP do usuário
 */
function registrarAcessoNaoAutorizado($pagina, $ip = null) {
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    try {
        $pdo = conectarBD();
        
        $stmt = $pdo->prepare("INSERT INTO logs_acesso (usuario_id, tipo_evento, sucesso, ip_address, user_agent) VALUES (?, 'tentativa_login', FALSE, ?, ?)");
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt->execute([$usuario_id, $ip, $user_agent]);
        
    } catch (Exception $e) {
        // Falha silenciosa no log - não deve interromper o fluxo
        error_log("Erro ao registrar acesso não autorizado: " . $e->getMessage());
    }
}

/**
 * Função para verificar rate limiting (proteção contra ataques)
 * @param string $action Ação sendo realizada
 * @param int $max_attempts Máximo de tentativas permitidas
 * @param int $time_window Janela de tempo em segundos
 */
function verificarRateLimit($action, $max_attempts = 5, $time_window = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Limpar tentativas antigas
    $now = time();
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });
    
    // Verificar se excedeu o limite
    if (count($_SESSION[$key]) >= $max_attempts) {
        return false;
    }
    
    // Registrar nova tentativa
    $_SESSION[$key][] = $now;
    return true;
}
?>