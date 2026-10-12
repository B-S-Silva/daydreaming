<?php
// Verificação de segurança para fórum
require_once 'verificar_auth.php';

// Verificar se o usuário está logado e ativo
$user_data = verificarUsuarioAtivo();

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$usuario_login = $_SESSION['usuario_login'];

// Conectar ao banco de dados
$pdo = conectarBD();

// Verificar rate limiting para ações do fórum (temporariamente desabilitado para debug)
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarRateLimit('forum_action', 15, 60)) {
        header("Location: forum.php?erro=muitas_tentativas");
        exit;
    }
}
*/

// Gerar token CSRF para formulários
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processar ações do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: forum.php?erro=token_invalido");
        exit;
    }
 
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'criar_topico':
                $categoria_id = $_POST['categoria_id'];
                $titulo = trim($_POST['titulo']);
                $conteudo = trim($_POST['conteudo']);

                if (!empty($titulo) && !empty($conteudo)) {
                    // Todos os tópicos são aprovados automaticamente
                    $aprovado = 1;

                    $stmt = $pdo->prepare("INSERT INTO forum_topicos (categoria_id, autor_id, titulo, conteudo, aprovado) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$categoria_id, $usuario_id, $titulo, $conteudo, $aprovado]);

                    $sucesso = "Tópico criado e publicado com sucesso!";
                }
                break;
                
            case 'responder':
                $topico_id = $_POST['topico_id'];
                $conteudo = trim($_POST['conteudo']);

                if (!empty($conteudo)) {
                    // Todas as respostas são aprovadas automaticamente
                    $aprovado = 1;

                    $stmt = $pdo->prepare("INSERT INTO forum_respostas (topico_id, autor_id, conteudo, aprovado) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$topico_id, $usuario_id, $conteudo, $aprovado]);

                    $sucesso = "Resposta adicionada com sucesso!";
                }
                break;
                
            case 'curtir':
                $tipo = $_POST['tipo'];
                $id = $_POST['id'];
                
                if ($tipo === 'topico') {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO forum_curtidas (usuario_id, topico_id) VALUES (?, ?)");
                    $stmt->execute([$usuario_id, $id]);
                } elseif ($tipo === 'resposta') {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO forum_curtidas (usuario_id, resposta_id) VALUES (?, ?)");
                    $stmt->execute([$usuario_id, $id]);
                }
                break;

            case 'moderar':
                // Apenas admins podem moderar
                if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
                    $tipo = $_POST['tipo']; // 'topico' ou 'resposta'
                    $id = $_POST['id'];
                    $acao_mod = $_POST['acao_mod']; // 'aprovar', 'rejeitar', 'fixar', 'fechar'

                    if ($tipo === 'topico') {
                        switch ($acao_mod) {
                            case 'aprovar':
                                $stmt = $pdo->prepare("UPDATE forum_topicos SET aprovado = 1 WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Tópico aprovado com sucesso!";
                                break;
                            case 'rejeitar':
                                $stmt = $pdo->prepare("UPDATE forum_topicos SET aprovado = 0 WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Tópico rejeitado!";
                                break;
                            case 'fixar':
                                $stmt = $pdo->prepare("UPDATE forum_topicos SET fixado = NOT fixado WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Status de fixação alterado!";
                                break;
                            case 'fechar':
                                $stmt = $pdo->prepare("UPDATE forum_topicos SET fechado = NOT fechado WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Status de fechamento alterado!";
                                break;
                            case 'deletar':
                                $stmt = $pdo->prepare("DELETE FROM forum_topicos WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Tópico deletado!";
                                break;
                        }

                        // Registrar ação de moderação
                        $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, topico_id, acao) VALUES (?, ?, ?)");
                        $stmt->execute([$usuario_id, $id, $acao_mod]);

                    } elseif ($tipo === 'resposta') {
                        switch ($acao_mod) {
                            case 'aprovar':
                                $stmt = $pdo->prepare("UPDATE forum_respostas SET aprovado = 1 WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Resposta aprovada com sucesso!";
                                break;
                            case 'rejeitar':
                                $stmt = $pdo->prepare("UPDATE forum_respostas SET aprovado = 0 WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Resposta rejeitada!";
                                break;
                            case 'deletar':
                                $stmt = $pdo->prepare("DELETE FROM forum_respostas WHERE id = ?");
                                $stmt->execute([$id]);
                                $sucesso = "Resposta deletada!";
                                break;
                        }

                        // Registrar ação de moderação
                        $stmt = $pdo->prepare("INSERT INTO forum_moderacao (moderador_id, resposta_id, acao) VALUES (?, ?, ?)");
                        $stmt->execute([$usuario_id, $id, $acao_mod]);
                    }
                }
                break;
        }
    }
}

// Buscar categorias
$stmt = $pdo->query("SELECT * FROM forum_categorias WHERE ativo = 1 ORDER BY nome");
$categorias = $stmt->fetchAll();

// Buscar tópicos recentes com informações do usuário e categoria
$filtro_categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$busca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Todos os usuários veem todos os tópicos (sem necessidade de aprovação)
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
$condicao_aprovacao = "1=1";

$sql = "SELECT t.*, u.nome as autor_nome, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
               (SELECT COUNT(*) FROM forum_respostas r WHERE r.topico_id = t.id) as total_respostas,
               (SELECT COUNT(*) FROM forum_curtidas l WHERE l.topico_id = t.id) as total_likes
        FROM forum_topicos t
        JOIN usuarios u ON t.autor_id = u.id
        JOIN forum_categorias c ON t.categoria_id = c.id
        WHERE $condicao_aprovacao";

$params = [];
if ($filtro_categoria) {
    $sql .= " AND c.id = ?";
    $params[] = $filtro_categoria;
}
if ($busca) {
    $sql .= " AND (t.titulo LIKE ? OR t.conteudo LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

$sql .= " ORDER BY t.fixado DESC, t.data_atualizacao DESC LIMIT 20";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$topicos = $stmt->fetchAll();

// Buscar tópico específico se solicitado
$topico_detalhes = null;
$respostas = [];
if (isset($_GET['topico'])) {
    $topico_id = $_GET['topico'];
    
    // Incrementar visualizações
    $stmt = $pdo->prepare("UPDATE forum_topicos SET visualizacoes = visualizacoes + 1 WHERE id = ?");
    $stmt->execute([$topico_id]);
    
    // Buscar detalhes do tópico
    $stmt = $pdo->prepare("SELECT t.*, u.nome as autor_nome, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone,
                                  (SELECT COUNT(*) FROM forum_curtidas l WHERE l.topico_id = t.id) as total_likes
                           FROM forum_topicos t
                           JOIN usuarios u ON t.autor_id = u.id
                           JOIN forum_categorias c ON t.categoria_id = c.id
                           WHERE t.id = ? AND t.aprovado = 1");
    $stmt->execute([$topico_id]);
    $topico_detalhes = $stmt->fetch();
    
    if ($topico_detalhes) {
        // Buscar respostas (todas as respostas são visíveis)
        $stmt = $pdo->prepare("SELECT r.*, u.nome as autor_nome,
                                      (SELECT COUNT(*) FROM forum_curtidas l WHERE l.resposta_id = r.id) as total_likes
                               FROM forum_respostas r
                               JOIN usuarios u ON r.autor_id = u.id
                               WHERE r.topico_id = ?
                               ORDER BY r.data_criacao ASC");
        $stmt->execute([$topico_id]);
        $respostas = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fórum - Comunidade</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .forum-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, rgba(24, 123, 205, 0.1) 0%, rgba(255, 255, 255, 0.1) 100%);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .forum-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            backdrop-filter: blur(5px);
        }
        
        .forum-header h1 {
            color: var(--primary-color);
            margin: 0;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .forum-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .forum-search {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 400px;
        }
        
        .forum-search input {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: var(--color);
            font-size: 14px;
        }
        
        .forum-search input::placeholder {
            color: rgba(51, 51, 51, 0.7);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            background: var(--primary-color);
            color: white;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 123, 205, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: var(--color);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .categories-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .category-tag {
            padding: 8px 15px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: var(--color);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .category-tag:hover,
        .category-tag.active {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .topics-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .topic-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .topic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .topic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .topic-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--color);
            margin: 0;
            flex: 1;
        }
        
        .topic-category {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            color: white;
            margin-left: 15px;
        }
        
        .topic-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: rgba(51, 51, 51, 0.7);
            margin-top: 15px;
        }
        
        .topic-stats {
            display: flex;
            gap: 15px;
        }
        
        .topic-content {
            color: rgba(51, 51, 51, 0.8);
            line-height: 1.6;
            margin: 10px 0;
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
            max-width: 600px;
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
            resize: vertical;
        }
        
        .form-group textarea {
            min-height: 120px;
        }
        
        .topic-detail {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .responses {
            margin-top: 30px;
        }
        
        .response-card {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
        }
        
        .like-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .like-btn:hover {
            color: #1565c0;
            transform: scale(1.1);
        }
        
        .back-btn {
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .forum-nav {
                flex-direction: column;
                align-items: stretch;
            }
            
            .forum-search {
                max-width: none;
            }
            
            .topic-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .topic-category {
                margin-left: 0;
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>
    
    <div class="container">
        <div class="forum-container">
            <div class="forum-header">
                <h1>🌟 Fórum da Comunidade</h1>
                <p>Bem-vindo, <strong><?php echo htmlspecialchars($usuario_nome); ?></strong>! Participe das discussões e compartilhe conhecimento.</p>
                <div style="margin-top: 15px;">
                    <a href="simulador_provas.php" class="btn btn-secondary">🎯 Simulador</a>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <a href="admin_forum.php" class="btn">⚙️ Painel Admin</a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($sucesso)): ?>
                <div style="background: #10b981; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    ✅ <?php echo $sucesso; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['erro'])): ?>
                <div style="background: #dc3545; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    ❌
                    <?php
                    switch($_GET['erro']) {
                        case 'token_invalido': echo 'Token de segurança inválido. Tente novamente.'; break;
                        case 'acesso_negado': echo 'Acesso negado. Apenas administradores podem realizar esta ação.'; break;
                        default: echo 'Erro desconhecido.'; break;
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <!-- Painel de Moderação para Admins -->
                <div style="background: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                    <h4 style="color: #ffc107; margin: 0 0 10px 0;">🛡️ Painel de Moderação</h4>
                    <?php
                    // Estatísticas de moderação
                    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_topicos WHERE aprovado = 0");
                    $topicos_pendentes = $stmt->fetchColumn();

                    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_respostas WHERE aprovado = 0");
                    $respostas_pendentes = $stmt->fetchColumn();

                    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_topicos");
                    $total_topicos = $stmt->fetchColumn();

                    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_respostas");
                    $total_respostas = $stmt->fetchColumn();
                    ?>
                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                        <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                            <strong>📋 Tópicos Pendentes:</strong> <?php echo $topicos_pendentes; ?>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                            <strong>💬 Respostas Pendentes:</strong> <?php echo $respostas_pendentes; ?>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                            <strong>📊 Total Tópicos:</strong> <?php echo $total_topicos; ?>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 10px; border-radius: 5px;">
                            <strong>📊 Total Respostas:</strong> <?php echo $total_respostas; ?>
                        </div>
                    </div>
                    <?php if ($topicos_pendentes > 0 || $respostas_pendentes > 0): ?>
                        <div style="margin-top: 10px; padding: 8px; background: rgba(220, 53, 69, 0.1); border-radius: 5px; color: #dc3545;">
                            ⚠️ Há conteúdo aguardando moderação! Verifique os itens marcados com ⏳ abaixo.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!isset($_GET['topico'])): ?>
                <!-- Lista de tópicos -->
                <div class="forum-nav">
                    <div class="forum-search">
                        <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                            <input type="text" name="busca" placeholder="🔍 Buscar tópicos..." value="<?php echo htmlspecialchars($busca); ?>">
                            <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($filtro_categoria); ?>">
                            <button type="submit" class="btn">Buscar</button>
                        </form>
                    </div>
                    <button onclick="openModal('createTopicModal')" class="btn">✨ Novo Tópico</button>
                </div>
                
                <div class="categories-filter">
                    <a href="forum.php" class="category-tag <?php echo !$filtro_categoria ? 'active' : ''; ?>">🌟 Todos</a>
                    <?php foreach ($categorias as $categoria): ?>
                        <a href="forum.php?categoria=<?php echo $categoria['id']; ?>" 
                           class="category-tag <?php echo $filtro_categoria == $categoria['id'] ? 'active' : ''; ?>">
                            <?php echo $categoria['icone']; ?> <?php echo htmlspecialchars($categoria['nome']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="topics-grid">
                    <?php foreach ($topicos as $topico): ?>
                        <div class="topic-card" onclick="window.location.href='forum.php?topico=<?php echo $topico['id']; ?>'">
                            <div class="topic-header">
                                <h3 class="topic-title">
                                    <?php if ($topico['fixado']): ?>📌 <?php endif; ?>
                                    <?php if ($topico['fechado']): ?>🔒 <?php endif; ?>
                                    <?php echo htmlspecialchars($topico['titulo']); ?>
                                </h3>
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <div class="topic-category" style="background-color: <?php echo $topico['categoria_cor']; ?>">
                                        <?php echo $topico['categoria_icone']; ?> <?php echo htmlspecialchars($topico['categoria_nome']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="topic-content">
                                <?php echo nl2br(htmlspecialchars(substr($topico['conteudo'], 0, 200))); ?>
                                <?php if (strlen($topico['conteudo']) > 200): ?>..<?php endif; ?>
                            </div>
                            <div class="topic-meta">
                                <span>👤 <?php echo htmlspecialchars($topico['autor_nome']); ?></span>
                                <div class="topic-stats">
                                    <span>👁️ <?php echo $topico['visualizacoes']; ?></span>
                                    <span>💬 <?php echo $topico['total_respostas']; ?></span>
                                    <span>❤️ <?php echo $topico['total_likes']; ?></span>
                                </div>
                            </div>

                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <div class="admin-controls" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2);" onclick="event.stopPropagation();">
                                    <small style="color: rgba(255,255,255,0.7);">🛡️ Controles de Admin:</small>
                                    <div style="display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap;">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="acao" value="moderar">
                                            <input type="hidden" name="tipo" value="topico">
                                            <input type="hidden" name="id" value="<?php echo $topico['id']; ?>">
                                            <input type="hidden" name="acao_mod" value="fixar">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" class="btn-mini" style="background: #ffc107; color: #000; border: none; padding: 2px 6px; border-radius: 3px; font-size: 10px;">
                                                <?php echo $topico['fixado'] ? '📌 Desfixar' : '📌 Fixar'; ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="acao" value="moderar">
                                            <input type="hidden" name="tipo" value="topico">
                                            <input type="hidden" name="id" value="<?php echo $topico['id']; ?>">
                                            <input type="hidden" name="acao_mod" value="fechar">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" class="btn-mini" style="background: #6c757d; color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 10px;">
                                                <?php echo $topico['fechado'] ? '🔓 Abrir' : '🔒 Fechar'; ?>
                                            </button>
                                        </form>

                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja deletar este tópico?')">
                                            <input type="hidden" name="acao" value="moderar">
                                            <input type="hidden" name="tipo" value="topico">
                                            <input type="hidden" name="id" value="<?php echo $topico['id']; ?>">
                                            <input type="hidden" name="acao_mod" value="deletar">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <button type="submit" class="btn-mini" style="background: #dc3545; color: white; border: none; padding: 2px 6px; border-radius: 3px; font-size: 10px;">🗑️ Deletar</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($topicos)): ?>
                        <div style="text-align: center; padding: 40px; color: rgba(51, 51, 51, 0.6);">
                            <h3>🤔 Nenhum tópico encontrado</h3>
                            <p>Seja o primeiro a criar um tópico nesta categoria!</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Detalhes do tópico -->
                <?php if ($topico_detalhes): ?>
                    <div class="back-btn">
                        <a href="forum.php" class="btn btn-secondary">← Voltar ao Fórum</a>
                    </div>
                    
                    <div class="topic-detail">
                        <div class="topic-header">
                            <h2 class="topic-title">
                                <?php if ($topico_detalhes['fixado']): ?>📌 <?php endif; ?>
                                <?php if ($topico_detalhes['fechado']): ?>🔒 <?php endif; ?>
                                <?php echo htmlspecialchars($topico_detalhes['titulo']); ?>
                            </h2>
                            <div class="topic-category" style="background-color: <?php echo $topico_detalhes['categoria_cor']; ?>">
                                <?php echo $topico_detalhes['categoria_icone']; ?> <?php echo htmlspecialchars($topico_detalhes['categoria_nome']); ?>
                            </div>
                        </div>
                        <div class="topic-content">
                            <?php echo nl2br(htmlspecialchars($topico_detalhes['conteudo'])); ?>
                        </div>
                        <div class="topic-meta">
                            <span>👤 <?php echo htmlspecialchars($topico_detalhes['autor_nome']); ?> • 📅 <?php echo date('d/m/Y H:i', strtotime($topico_detalhes['data_criacao'])); ?></span>
                            <div class="topic-stats">
                                <span>👁️ <?php echo $topico_detalhes['visualizacoes']; ?></span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="acao" value="curtir">
                                    <input type="hidden" name="tipo" value="topico">
                                    <input type="hidden" name="id" value="<?php echo $topico_detalhes['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit" class="like-btn">❤️ <?php echo $topico_detalhes['total_likes']; ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="responses">
                        <h3>💬 Respostas (<?php echo count($respostas); ?>)</h3>
                        
                        <?php foreach ($respostas as $resposta): ?>
                            <div class="response-card">
                                <div class="topic-content">
                                    <?php echo nl2br(htmlspecialchars($resposta['conteudo'])); ?>
                                </div>
                                <div class="topic-meta">
                                    <span>👤 <?php echo htmlspecialchars($resposta['autor_nome']); ?> • 📅 <?php echo date('d/m/Y H:i', strtotime($resposta['data_criacao'])); ?></span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="acao" value="curtir">
                                        <input type="hidden" name="tipo" value="resposta">
                                        <input type="hidden" name="id" value="<?php echo $resposta['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="like-btn">❤️ <?php echo $resposta['total_likes']; ?></button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (!$topico_detalhes['fechado']): ?>
                            <div style="margin-top: 30px;">
                                <h4>✍️ Adicionar Resposta</h4>
                                <form method="POST">
                                    <input type="hidden" name="acao" value="responder">
                                    <input type="hidden" name="topico_id" value="<?php echo $topico_detalhes['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <div class="form-group">
                                        <textarea name="conteudo" placeholder="Digite sua resposta..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn">📤 Enviar Resposta</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: rgba(51, 51, 51, 0.6);">
                                🔒 Este tópico está fechado para novas respostas.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <h3>❌ Tópico não encontrado</h3>
                        <a href="forum.php" class="btn">← Voltar ao Fórum</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal para criar tópico -->
    <div id="createTopicModal" class="modal">
        <div class="modal-content">
            <h3>✨ Criar Novo Tópico</h3>
            <form method="POST">
                <input type="hidden" name="acao" value="criar_topico">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="form-group">
                    <label for="categoria_id">📂 Categoria:</label>
                    <select name="categoria_id" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>">
                                <?php echo $categoria['icone']; ?> <?php echo htmlspecialchars($categoria['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="titulo">📝 Título:</label>
                    <input type="text" name="titulo" placeholder="Digite o título do tópico" required>
                </div>
                <div class="form-group">
                    <label for="conteudo">💭 Conteúdo:</label>
                    <textarea name="conteudo" placeholder="Descreva seu tópico em detalhes..." required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeModal('createTopicModal')" class="btn btn-secondary">❌ Cancelar</button>
                    <button type="submit" class="btn">✅ Criar Tópico</button>
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
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>