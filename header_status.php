<?php
// Componente de header para mostrar status de login
if (session_status() == PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

require_once 'config.php';

// Verificar se o usuário está logado
$usuario_logado = isset($_SESSION['usuario_id']);
$usuario_nome = '';
$usuario_login = '';

if ($usuario_logado) {
    $usuario_nome = $_SESSION['usuario_nome'] ?? '';
    $usuario_login = $_SESSION['usuario_login'] ?? '';
}
?>

<div id="login-status-header" style="
    background: linear-gradient(135deg, #187bcb 0%, #6c5ce7 100%);
    color: white;
    padding: 10px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
">
    <div style="display: flex; align-items: center; gap: 15px;">
        <?php if ($usuario_logado): ?>
            <span style="font-weight: 500;">✅ Você está logado</span>
            <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 15px; font-size: 14px;">
                👤 <?php echo htmlspecialchars($usuario_nome); ?> (<?php echo htmlspecialchars($usuario_login); ?>)
            </span>
        <?php else: ?>
            <span style="font-weight: 500;">❌ Você não está logado</span>
        <?php endif; ?>

        <a href="index.php" style="
            background: rgba(255,255,255,0.15);
            color: white;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 18px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.25);
        " onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.15)'">
            🏠 Página Inicial
        </a>
    </div>
    
    <div style="display: flex; align-items: center; gap: 10px;">
        <?php if ($usuario_logado): ?>
            <a href="logout.php" style="
                background: rgba(255,255,255,0.2);
                color: white;
                text-decoration: none;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.3s ease;
                border: 1px solid rgba(255,255,255,0.3);
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                🚪 Deslogar
            </a>
        <?php else: ?>
            <a href="login.php" style="
                background: rgba(255,255,255,0.2);
                color: white;
                text-decoration: none;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: 500;
                transition: all 0.3s ease;
                border: 1px solid rgba(255,255,255,0.3);
            " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                🔑 Fazer Login
            </a>
        <?php endif; ?>
    </div>
</div>

<style>
#login-status-header a:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    #login-status-header {
        flex-direction: column;
        gap: 12px;
        text-align: center;
        padding: 15px 10px;
        font-size: 14px;
    }
    
    #login-status-header > div {
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }

    #login-status-header > div:first-child {
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    #login-status-header span {
        font-size: 13px;
        line-height: 1.4;
        word-wrap: break-word;
    }
    
    #login-status-header a {
        font-size: 13px !important;
        padding: 10px 20px !important;
        width: fit-content;
        margin: 0 auto;
        display: block;
    }
}

@media (max-width: 480px) {
    #login-status-header {
        padding: 12px 8px;
        font-size: 13px;
    }
    
    #login-status-header span {
        font-size: 12px;
    }
    
    #login-status-header a {
        font-size: 12px !important;
        padding: 8px 16px !important;
    }
}
</style>