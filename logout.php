<?php
session_start();
require_once 'config.php';

// Registrar logout no banco de dados se o usuário estiver logado
if (isset($_SESSION['usuario_id'])) {
    try {
        $pdo = conectarBD();
        
        // Atualizar último logout do usuário
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_logout = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        
        $stmt = $pdo->prepare("INSERT INTO logs_sistema (usuario_id, acao, detalhes, data_hora) VALUES (?, 'logout', 'Usuário fez logout', NOW())");
        $stmt->execute([$_SESSION['usuario_id']]);
        
    } catch (PDOException $e) {
        // Em caso de erro no banco, continuar com o logout
        error_log("Erro ao registrar logout: " . $e->getMessage());
    }
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Se for necessário eliminar completamente a sessão, apague também o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir a sessão
session_destroy();

// Redirecionar para a página inicial com mensagem de sucesso
session_start();
$_SESSION['mensagem'] = "Logout realizado com sucesso!";
header("Location: index.php");
exit();
exit;