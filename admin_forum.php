<?php
// Verificação de segurança para painel administrativo
require_once 'verificar_auth.php';

// Verificar se o usuário é administrador
$user = verificarAdmin();

// Conectar ao banco de dados
$pdo = conectarBD();

// Verificar rate limiting para ações administrativas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarRateLimit('admin_action', 10, 60)) {
        registrarAcessoNaoAutorizado('admin_forum.php - Rate limit excedido');
        header("Location: admin_forum.php?erro=muitas_tentativas");
        exit;
    }
}

// Verificar token CSRF para ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        registrarAcessoNaoAutorizado('admin_forum.php - Token CSRF inválido');
        header("Location: admin_forum.php?erro=token_invalido");
        exit;
    }
}

// Gerar token CSRF para formulários
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];

// Processar ações administrativas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'aprovar_topico':
                $topico_id = $_POST['topico_id'];
                $stmt = $pdo->prepare("UPDATE forum_topicos SET aprovado = 1 WHERE id = ?");
                $stmt->execute([$topico_id]);
                
                // Registrar ação de moderação
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao) VALUES (?, 'topico', ?, 'aprovar')");
                $stmt->execute([$usuario_id, $topico_id]);
                $sucesso = "Tópico aprovado com sucesso!";
                break;
                
            case 'rejeitar_topico':
                $topico_id = $_POST['topico_id'];
                $motivo = $_POST['motivo'] ?? '';
                $stmt = $pdo->prepare("UPDATE forum_topicos SET aprovado = 0 WHERE id = ?");
                $stmt->execute([$topico_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao, motivo) VALUES (?, 'topico', ?, 'rejeitar', ?)");
                $stmt->execute([$usuario_id, $topico_id, $motivo]);
                $sucesso = "Tópico rejeitado com sucesso!";
                break;
                
            case 'fixar_topico':
                $topico_id = $_POST['topico_id'];
                $stmt = $pdo->prepare("UPDATE forum_topicos SET fixado = 1 WHERE id = ?");
                $stmt->execute([$topico_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao) VALUES (?, 'topico', ?, 'fixar')");
                $stmt->execute([$usuario_id, $topico_id]);
                $sucesso = "Tópico fixado com sucesso!";
                break;
                
            case 'desfixar_topico':
                $topico_id = $_POST['topico_id'];
                $stmt = $pdo->prepare("UPDATE forum_topicos SET fixado = 0 WHERE id = ?");
                $stmt->execute([$topico_id]);
                $sucesso = "Tópico desfixado com sucesso!";
                break;
                
            case 'fechar_topico':
                $topico_id = $_POST['topico_id'];
                $stmt = $pdo->prepare("UPDATE forum_topicos SET fechado = 1 WHERE id = ?");
                $stmt->execute([$topico_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao) VALUES (?, 'topico', ?, 'fechar')");
                $stmt->execute([$usuario_id, $topico_id]);
                $sucesso = "Tópico fechado com sucesso!";
                break;
                
            case 'abrir_topico':
                $topico_id = $_POST['topico_id'];
                $stmt = $pdo->prepare("UPDATE forum_topicos SET fechado = 0 WHERE id = ?");
                $stmt->execute([$topico_id]);
                $sucesso = "Tópico reaberto com sucesso!";
                break;
                
            case 'deletar_topico':
                $topico_id = $_POST['topico_id'];
                $motivo = $_POST['motivo'] ?? '';
                $stmt = $pdo->prepare("DELETE FROM forum_topicos WHERE id = ?");
                $stmt->execute([$topico_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao, motivo) VALUES (?, 'topico', ?, 'deletar', ?)");
                $stmt->execute([$usuario_id, $topico_id, $motivo]);
                $sucesso = "Tópico deletado com sucesso!";
                break;
                
            case 'aprovar_resposta':
                $resposta_id = $_POST['resposta_id'];
                $stmt = $pdo->prepare("UPDATE forum_respostas SET aprovado = 1 WHERE id = ?");
                $stmt->execute([$resposta_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao) VALUES (?, 'resposta', ?, 'aprovar')");
                $stmt->execute([$usuario_id, $resposta_id]);
                $sucesso = "Resposta aprovada com sucesso!";
                break;
                
            case 'deletar_resposta':
                $resposta_id = $_POST['resposta_id'];
                $motivo = $_POST['motivo'] ?? '';
                $stmt = $pdo->prepare("DELETE FROM forum_respostas WHERE id = ?");
                $stmt->execute([$resposta_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao, motivo) VALUES (?, 'resposta', ?, 'deletar', ?)");
                $stmt->execute([$usuario_id, $resposta_id, $motivo]);
                $sucesso = "Resposta deletada com sucesso!";
                break;
                
            case 'criar_categoria':
                $nome = trim($_POST['nome']);
                $descricao = trim($_POST['descricao']);
                $cor = $_POST['cor'];
                $icone = $_POST['icone'];
                
                if (!empty($nome)) {
                    $stmt = $pdo->prepare("INSERT INTO forum_categorias (nome, descricao, cor, icone) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nome, $descricao, $cor, $icone]);
                    $sucesso = "Categoria criada com sucesso!";
                }
                break;
                
            case 'promover_usuario':
                $usuario_id = $_POST['usuario_id'];
                $stmt = $pdo->prepare("UPDATE usuarios SET is_admin = 1 WHERE id = ?");
                $stmt->execute([$usuario_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao) VALUES (?, 'usuario', ?, 'promover')");
                $stmt->execute([$usuario_id, $usuario_id]);
                $sucesso = "Usuário promovido a administrador com sucesso!";
                break;
                
            case 'rebaixar_usuario':
                $usuario_id = $_POST['usuario_id'];
                $stmt = $pdo->prepare("UPDATE usuarios SET is_admin = 0 WHERE id = ?");
                $stmt->execute([$usuario_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao) VALUES (?, 'usuario', ?, 'rebaixar')");
                $stmt->execute([$usuario_id, $usuario_id]);
                $sucesso = "Usuário rebaixado para usuário comum com sucesso!";
                break;
                
            case 'banir_usuario':
                $usuario_id = $_POST['usuario_id'];
                $motivo = $_POST['motivo'] ?? '';
                $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
                $stmt->execute([$usuario_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao, motivo) VALUES (?, 'usuario', ?, 'banir', ?)");
                $stmt->execute([$_SESSION['usuario_id'], $usuario_id, $motivo]);
                header("Location: admin_forum.php?sucesso=usuario_banido");
                exit;
                
            case 'desbanir_usuario':
                $usuario_id = $_POST['usuario_id'];
                $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 1 WHERE id = ?");
                $stmt->execute([$usuario_id]);
                
                $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, tipo_conteudo, conteudo_id, acao) VALUES (?, 'usuario', ?, 'desbanir')");
                $stmt->execute([$_SESSION['usuario_id'], $usuario_id]);
                header("Location: admin_forum.php?sucesso=usuario_desbloqueado");
                exit;
        }
    }
}

// Tratar mensagens de sucesso via GET
if (isset($_GET['sucesso'])) {
    switch ($_GET['sucesso']) {
        case 'usuario_banido':
            $sucesso = "Usuário banido com sucesso!";
            break;
        case 'usuario_desbloqueado':
            $sucesso = "Usuário desbloqueado com sucesso!";
            break;
    }
}

// Buscar estatísticas gerais
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM forum_topicos");
$stats['total_topicos'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM forum_topicos WHERE aprovado = 0");
$stats['topicos_pendentes'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM forum_respostas");
$stats['total_respostas'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM forum_respostas WHERE aprovado = 0");
$stats['respostas_pendentes'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
$stats['total_usuarios'] = $stmt->fetchColumn();

// Buscar tópicos para moderação
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';
$sql = "SELECT t.*, u.nome as autor_nome, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
               (SELECT COUNT(*) FROM forum_respostas r WHERE r.topico_id = t.id) as total_respostas
        FROM forum_topicos t 
        JOIN usuarios u ON t.usuario_id = u.id 
        JOIN forum_categorias c ON t.categoria_id = c.id";

switch ($filtro) {
    case 'pendentes':
        $sql .= " WHERE t.aprovado = 0";
        break;
    case 'fixados':
        $sql .= " WHERE t.fixado = 1";
        break;
    case 'fechados':
        $sql .= " WHERE t.fechado = 1";
        break;
    default:
        break;
}

$sql .= " ORDER BY t.data_criacao DESC LIMIT 50";
$stmt = $pdo->query($sql);
$topicos = $stmt->fetchAll();

// Buscar respostas pendentes
$stmt = $pdo->query("SELECT r.*, u.nome as autor_nome, t.titulo as topico_titulo
                     FROM forum_respostas r 
                     JOIN usuarios u ON r.usuario_id = u.id 
                     JOIN forum_topicos t ON r.topico_id = t.id 
                     WHERE r.aprovado = 0 
                     ORDER BY r.data_criacao DESC LIMIT 20");
$respostas_pendentes = $stmt->fetchAll();

// Buscar categorias
$stmt = $pdo->query("SELECT * FROM forum_categorias ORDER BY nome");
$categorias = $stmt->fetchAll();

// Buscar logs de moderação recentes
$stmt = $pdo->query("SELECT m.*, u.nome as moderador_nome 
                     FROM forum_moderacao m 
                     JOIN usuarios u ON m.moderador_id = u.id 
                     ORDER BY m.data_acao DESC LIMIT 20");
$logs_moderacao = $stmt->fetchAll();

// Buscar todos os usuários
$stmt = $pdo->query("SELECT u.*, 
                            (SELECT COUNT(*) FROM forum_topicos WHERE usuario_id = u.id) as total_topicos,
                            (SELECT COUNT(*) FROM forum_respostas WHERE usuario_id = u.id) as total_respostas
                     FROM usuarios u 
                     ORDER BY u.data_criacao DESC");
$usuarios = $stmt->fetchAll();
?>

<?php define('TITLE', "Painel Administrativo"); ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Painel Administrativo do Fórum">
    <meta name="author" content="Sistema de Fórum">
    
    <title><?php echo TITLE . ' | Sistema de Fórum'; ?></title>
    <link rel="icon" type="image/png" href="dream-clouds.svg">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0/css/all.min.css">
    
    <!-- Custom styles -->
    <link rel="stylesheet" href="public/css/style.css">
    
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
    <style>
        .bg-purple {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)) !important;
        }
        
        .card-profile {
            border: none;
            border-radius: var(--border-radius-large);
        }
        
        .card-img-top {
            border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
        }
        
        .admin-container {
            background: var(--bg-white);
            border-radius: var(--border-radius-large);
            box-shadow: var(--shadow-medium);
            padding: 2rem;
        }
        
        .admin-header {
            background: var(--bg-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: var(--text-dark);
            margin: 0;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 10px 0;
        }
        
        .stat-label {
            color: rgba(51, 51, 51, 0.7);
            font-size: 14px;
        }
        
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.2);
            color: var(--color);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .tab-btn:hover,
        .tab-btn.active {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .topic-item {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .topic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .topic-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--color);
            margin: 0;
            flex: 1;
        }
        
        .topic-status {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .status-pending { background: #f59e0b; }
        .status-approved { background: #10b981; }
        .status-rejected { background: #ef4444; }
        .status-pinned { background: #8b5cf6; }
        .status-closed { background: #6b7280; }
        
        .topic-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: rgba(51, 51, 51, 0.7);
            margin: 15px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-approve { background: #10b981; color: white; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-pin { background: #8b5cf6; color: white; }
        .btn-close { background: #6b7280; color: white; }
        .btn-delete { background: #dc2626; color: white; }
        .btn-edit { background: var(--primary-color); color: white; }
        
        .btn-small:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--color);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: var(--color);
            font-size: 14px;
        }
        
        .log-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 3px solid var(--primary-color);
        }
        
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 10px;
        }
        
        .tab-button {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            color: var(--color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .tab-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .tab-button.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 15px rgba(24, 123, 205, 0.3);
        }
        
        .tab-content {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .topic-header {
                flex-direction: column;
            }
            
            .action-buttons {
                width: 100%;
                justify-content: flex-start;
            }
            
            .admin-tabs {
                flex-direction: column;
            }
            
            .tab-button {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Status de Login -->
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
            <span style="font-weight: 500;">✅ Você está logado</span>
            <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 15px; font-size: 14px;">
                👤 <?php echo htmlspecialchars($usuario_nome); ?> (Administrador)
            </span>
        </div>
        
        <div style="display: flex; align-items: center; gap: 10px;">
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
        </div>
    </div>
    
    <!-- Main Content -->
    <main role="main" class="container mt-4">
        <div class="row">
            <!-- Profile Card -->
            <div class="col-sm-3">
                <div class='card card-profile text-center box-shadow bg-white'>
                    <img alt='' class='card-img-top' src='dream-clouds.svg' style='height: 120px; object-fit: cover;'>
                    <div class='card-block p-3'>
                        <div class='mb-3'>
                            <i class="fas fa-user-shield fa-3x text-primary"></i>
                        </div>
                        <h5 class='card-title'>
                            <?php echo htmlspecialchars($usuario_nome); ?>
                            <small class="text-muted d-block">Administrador</small>
                            <small class="text-muted d-block mt-2">Painel de Controle</small>
                        </h5>
                    </div>
                </div>
            </div>
            
            <!-- Admin Content -->
            <div class="col-sm-9">
                <div class="d-flex align-items-center p-3 mb-3 text-white-50 bg-purple rounded box-shadow">
                    <i class="fas fa-cogs fa-3x mr-3 text-white"></i>
                    <div class="lh-100">
                        <h6 class="mb-0 text-white lh-100">Painel Administrativo</h6>
                        <small>Gerenciamento do Fórum</small>
                    </div>
                </div>
                
                <div class="admin-container">
            <div class="admin-header">
                <h1>⚙️ Painel Administrativo do Fórum</h1>
                <p>Bem-vindo, <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>! Gerencie o conteúdo do fórum.</p>
                <div style="margin-top: 15px;">
                    <a href="forum.php" class="btn btn-secondary">🏠 Voltar ao Fórum</a>
                    <a href="admin_questoes.php" class="btn btn-primary">📝 Gerenciar Questões</a>
                </div>
            </div>
            
            <?php if (isset($sucesso)): ?>
                <div style="background: #10b981; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    ✅ <?php echo $sucesso; ?>
                </div>
            <?php endif; ?>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_topicos']; ?></div>
                    <div class="stat-label">📝 Total de Tópicos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['topicos_pendentes']; ?></div>
                    <div class="stat-label">⏳ Tópicos Pendentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_respostas']; ?></div>
                    <div class="stat-label">💬 Total de Respostas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['respostas_pendentes']; ?></div>
                    <div class="stat-label">⏳ Respostas Pendentes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_usuarios']; ?></div>
                    <div class="stat-label">👥 Total de Usuários</div>
                </div>
            </div>
            
            <!-- Abas de navegação -->
            <div class="admin-tabs">
                <button class="tab-button active" onclick="showTab('topicos')">📝 Tópicos</button>
                <button class="tab-button" onclick="showTab('usuarios')">👥 Usuários</button>
                <button class="tab-button" onclick="showTab('categorias')">📂 Categorias</button>
                <button class="tab-button" onclick="showTab('logs')">📋 Logs</button>
            </div>
            
            <!-- Filtros de tópicos -->
            <div id="topicos-filters" class="admin-tabs" style="margin-top: 10px;">
                <a href="admin_forum.php?filtro=todos" class="tab-btn <?php echo $filtro === 'todos' ? 'active' : ''; ?>">📋 Todos os Tópicos</a>
                <a href="admin_forum.php?filtro=pendentes" class="tab-btn <?php echo $filtro === 'pendentes' ? 'active' : ''; ?>">⏳ Pendentes</a>
                <a href="admin_forum.php?filtro=fixados" class="tab-btn <?php echo $filtro === 'fixados' ? 'active' : ''; ?>">📌 Fixados</a>
                <a href="admin_forum.php?filtro=fechados" class="tab-btn <?php echo $filtro === 'fechados' ? 'active' : ''; ?>">🔒 Fechados</a>
                <button onclick="openModal('createCategoryModal')" class="tab-btn">➕ Nova Categoria</button>
            </div>
            
            <!-- Lista de tópicos -->
            <div id="topicos" class="content-section tab-content">
                <h3>📝 Gerenciar Tópicos</h3>
                
                <?php foreach ($topicos as $topico): ?>
                    <div class="topic-item">
                        <div class="topic-header">
                            <h4 class="topic-title"><?php echo htmlspecialchars($topico['titulo']); ?></h4>
                            <div class="topic-status">
                                <?php if (!$topico['aprovado']): ?>
                                    <span class="status-badge status-pending">⏳ Pendente</span>
                                <?php else: ?>
                                    <span class="status-badge status-approved">✅ Aprovado</span>
                                <?php endif; ?>
                                
                                <?php if ($topico['fixado']): ?>
                                    <span class="status-badge status-pinned">📌 Fixado</span>
                                <?php endif; ?>
                                
                                <?php if ($topico['fechado']): ?>
                                    <span class="status-badge status-closed">🔒 Fechado</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="topic-meta">
                            <span>👤 <?php echo htmlspecialchars($topico['autor_nome']); ?> • 📂 <?php echo htmlspecialchars($topico['categoria_nome']); ?> • 📅 <?php echo date('d/m/Y H:i', strtotime($topico['data_criacao'])); ?></span>
                            <span>💬 <?php echo $topico['total_respostas']; ?> respostas</span>
                        </div>
                        
                        <div class="topic-content" style="margin: 15px 0; color: rgba(51, 51, 51, 0.8);">
                            <?php echo nl2br(htmlspecialchars(substr($topico['conteudo'], 0, 200))); ?>
                            <?php if (strlen($topico['conteudo']) > 200): ?>...<?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if (!$topico['aprovado']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="aprovar_topico">
                                    <input type="hidden" name="topico_id" value="<?php echo $topico['id']; ?>">
                                    <button type="submit" class="btn-small btn-approve">✅ Aprovar</button>
                                </form>
                                <button onclick="openRejectModal(<?php echo $topico['id']; ?>, 'topico')" class="btn-small btn-reject">❌ Rejeitar</button>
                            <?php endif; ?>
                            
                            <?php if ($topico['aprovado']): ?>
                                <?php if (!$topico['fixado']): ?>
                                    <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="fixar_topico">
                                    <input type="hidden" name="topico_id" value="<?php echo $topico['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn-small btn-approve">📌 Fixar</button>
                                </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="desfixar_topico">
                                    <input type="hidden" name="topico_id" value="<?php echo $topico['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn-small btn-reject">📌 Desfixar</button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if (!$topico['fechado']): ?>
                                    <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="fechar_topico">
                                    <input type="hidden" name="topico_id" value="<?php echo $topico['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="btn-small btn-close">🔒 Fechar</button>
                                </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="acao" value="abrir_topico">
                                        <input type="hidden" name="topico_id" value="<?php echo $topico['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn-small btn-close">🔓 Abrir</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <a href="forum.php?topico=<?php echo $topico['id']; ?>" class="btn-small btn-edit">👁️ Ver</a>
                            <button onclick="openDeleteModal(<?php echo $topico['id']; ?>, 'topico')" class="btn-small btn-delete">🗑️ Deletar</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($topicos)): ?>
                    <div style="text-align: center; padding: 40px; color: rgba(51, 51, 51, 0.6);">
                        <h4>📭 Nenhum tópico encontrado</h4>
                        <p>Não há tópicos para exibir com o filtro selecionado.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Gerenciamento de Usuários -->
            <div id="usuarios" class="content-section tab-content" style="display: none;">
                <h3>👥 Gerenciar Usuários</h3>
                
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="topic-item">
                        <div class="topic-header">
                            <h4 class="topic-title">
                                👤 <?php echo htmlspecialchars($usuario['nome']); ?>
                                <?php if ($usuario['is_admin']): ?>
                                    <span class="status-badge" style="background: #10b981;">👑 Admin</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: #6b7280;">👤 Usuário</span>
                                <?php endif; ?>
                                <?php if (!$usuario['ativo']): ?>
                                    <span class="status-badge" style="background: #ef4444;">🚫 Banido</span>
                                <?php endif; ?>
                            </h4>
                        </div>
                        
                        <div class="topic-meta">
                            <span>📧 <?php echo htmlspecialchars($usuario['email']); ?></span>
                            <span>📅 Cadastro: <?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></span>
                            <span>📝 <?php echo $usuario['total_topicos']; ?> tópicos</span>
                            <span>💬 <?php echo $usuario['total_respostas']; ?> respostas</span>
                            <?php if ($usuario['ultimo_acesso']): ?>
                                <span>🕒 Último acesso: <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <?php if ($usuario['id'] != $_SESSION['usuario_id']): // Não pode modificar a si mesmo ?>
                                <?php if ($usuario['is_admin']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="acao" value="rebaixar_usuario">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn-small btn-reject" onclick="return confirm('Tem certeza que deseja rebaixar este administrador?')">⬇️ Rebaixar</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="acao" value="promover_usuario">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn-small btn-approve" onclick="return confirm('Tem certeza que deseja promover este usuário a administrador?')">⬆️ Promover</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($usuario['ativo']): ?>
                                    <button onclick="openBanModal(<?php echo $usuario['id']; ?>)" class="btn-small btn-delete">🚫 Banir</button>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="acao" value="desbanir_usuario">
                                        <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn-small btn-approve" onclick="return confirm('Tem certeza que deseja desbanir este usuário?')">✅ Desbanir</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="btn-small" style="background: #6b7280; cursor: not-allowed;">👤 Você mesmo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($usuarios)): ?>
                    <div style="text-align: center; padding: 40px; color: rgba(51, 51, 51, 0.6);">
                        <h4>👥 Nenhum usuário encontrado</h4>
                        <p>Não há usuários cadastrados no sistema.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Respostas pendentes -->
            <?php if (!empty($respostas_pendentes)): ?>
                <div class="content-section">
                    <h3>💬 Respostas Pendentes de Moderação</h3>
                    
                    <?php foreach ($respostas_pendentes as $resposta): ?>
                        <div class="topic-item">
                            <div class="topic-header">
                                <h4 class="topic-title">Resposta em: <?php echo htmlspecialchars($resposta['topico_titulo']); ?></h4>
                                <span class="status-badge status-pending">⏳ Pendente</span>
                            </div>
                            
                            <div class="topic-meta">
                                <span>👤 <?php echo htmlspecialchars($resposta['autor_nome']); ?> • 📅 <?php echo date('d/m/Y H:i', strtotime($resposta['data_criacao'])); ?></span>
                            </div>
                            
                            <div class="topic-content" style="margin: 15px 0; color: rgba(51, 51, 51, 0.8);">
                                <?php echo nl2br(htmlspecialchars($resposta['conteudo'])); ?>
                            </div>
                            
                            <div class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="aprovar_resposta">
                                    <input type="hidden" name="resposta_id" value="<?php echo $resposta['id']; ?>">
                                    <button type="submit" class="btn-small btn-approve">✅ Aprovar</button>
                                </form>
                                <button onclick="openDeleteModal(<?php echo $resposta['id']; ?>, 'resposta')" class="btn-small btn-delete">🗑️ Deletar</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Categorias -->
            <div id="categorias" class="content-section tab-content" style="display: none;">
                <h3>📂 Gerenciar Categorias</h3>
                <div style="margin-bottom: 20px;">
                    <button onclick="openModal('createCategoryModal')" class="btn">➕ Criar Nova Categoria</button>
                </div>
                
                <?php foreach ($categorias as $categoria): ?>
                    <div class="topic-item">
                        <div class="topic-header">
                            <h4 class="topic-title">
                                <?php echo $categoria['icone']; ?> <?php echo htmlspecialchars($categoria['nome']); ?>
                                <span class="status-badge" style="background: <?php echo $categoria['cor']; ?>;">Categoria</span>
                            </h4>
                        </div>
                        
                        <div class="topic-meta">
                            <span><?php echo htmlspecialchars($categoria['descricao']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Logs de moderação -->
            <div id="logs" class="content-section tab-content" style="display: none;">
                <h3>📋 Logs de Moderação Recentes</h3>
                
                <?php foreach ($logs_moderacao as $log): ?>
                    <div class="log-item">
                        <strong><?php echo htmlspecialchars($log['moderador_nome']); ?></strong> 
                        <?php echo $log['acao']; ?> um <?php echo $log['tipo_conteudo']; ?> 
                        <span style="color: rgba(51, 51, 51, 0.6);">• <?php echo date('d/m/Y H:i', strtotime($log['data_acao'])); ?></span>
                        <?php if ($log['motivo']): ?>
                            <br><small style="color: rgba(51, 51, 51, 0.7);">Motivo: <?php echo htmlspecialchars($log['motivo']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal para criar categoria -->
    <div id="createCategoryModal" class="modal">
        <div class="modal-content">
            <h3>➕ Criar Nova Categoria</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="criar_categoria">
                <div class="form-group">
                    <label for="nome">📝 Nome:</label>
                    <input type="text" name="nome" placeholder="Nome da categoria" required>
                </div>
                <div class="form-group">
                    <label for="descricao">📄 Descrição:</label>
                    <textarea name="descricao" placeholder="Descrição da categoria"></textarea>
                </div>
                <div class="form-group">
                    <label for="cor">🎨 Cor:</label>
                    <input type="color" name="cor" value="#187bcd">
                </div>
                <div class="form-group">
                    <label for="icone">🎭 Ícone (emoji):</label>
                    <input type="text" name="icone" placeholder="💬" maxlength="2">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('createCategoryModal')" class="btn btn-secondary">❌ Cancelar</button>
                    <button type="submit" class="btn">✅ Criar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para rejeitar/deletar -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <h3 id="actionModalTitle">Confirmar Ação</h3>
            <form method="POST" id="actionForm">
                <input type="hidden" name="acao" id="actionType">
                <input type="hidden" name="topico_id" id="actionTopicoId">
                <input type="hidden" name="resposta_id" id="actionRespostaId">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="motivo">📝 Motivo (opcional):</label>
                    <textarea name="motivo" placeholder="Descreva o motivo da ação..."></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('actionModal')" class="btn btn-secondary">❌ Cancelar</button>
                    <button type="submit" class="btn btn-delete" id="actionSubmit">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal para banir usuário -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <h3>🚫 Banir Usuário</h3>
            <form method="POST" id="banForm">
                <input type="hidden" name="acao" value="banir_usuario">
                <input type="hidden" name="usuario_id" id="banUserId">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="motivo">📝 Motivo do banimento:</label>
                    <textarea name="motivo" placeholder="Descreva o motivo do banimento..." required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('banModal')" class="btn btn-secondary">❌ Cancelar</button>
                    <button type="submit" class="btn btn-delete">🚫 Banir Usuário</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function openRejectModal(id, tipo) {
            document.getElementById('actionModalTitle').textContent = 'Rejeitar ' + (tipo === 'topico' ? 'Tópico' : 'Resposta');
            document.getElementById('actionType').value = 'rejeitar_' + tipo;
            document.getElementById('actionSubmit').textContent = '❌ Rejeitar';
            
            if (tipo === 'topico') {
                document.getElementById('actionTopicoId').value = id;
                document.getElementById('actionRespostaId').value = '';
            } else {
                document.getElementById('actionRespostaId').value = id;
                document.getElementById('actionTopicoId').value = '';
            }
            
            openModal('actionModal');
        }
        
        function openDeleteModal(id, tipo) {
            document.getElementById('actionModalTitle').textContent = 'Deletar ' + (tipo === 'topico' ? 'Tópico' : 'Resposta');
            document.getElementById('actionType').value = 'deletar_' + tipo;
            document.getElementById('actionSubmit').textContent = '🗑️ Deletar';
            
            if (tipo === 'topico') {
                document.getElementById('actionTopicoId').value = id;
                document.getElementById('actionRespostaId').value = '';
            } else {
                document.getElementById('actionRespostaId').value = id;
                document.getElementById('actionTopicoId').value = '';
            }
            
            openModal('actionModal');
        }
        
        function openBanModal(userId) {
            document.getElementById('banUserId').value = userId;
            openModal('banModal');
        }
        
        function showTab(tabName) {
            // Esconder todas as abas
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remover classe active de todos os botões
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Mostrar aba selecionada
            document.getElementById(tabName).style.display = 'block';
            
            // Adicionar classe active ao botão clicado
            event.target.classList.add('active');
            
            // Esconder/mostrar filtros de tópicos
            const topicosFilters = document.getElementById('topicos-filters');
            if (tabName === 'topicos') {
                topicosFilters.style.display = 'flex';
            } else {
                topicosFilters.style.display = 'none';
            }
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
    
                </div>
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>