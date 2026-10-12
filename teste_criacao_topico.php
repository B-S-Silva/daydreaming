<?php
/**
 * Teste específico para criação de tópicos
 */

session_start();

// Simular usuário logado
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Admin';
$_SESSION['usuario_login'] = 'admin';
$_SESSION['logado'] = true;
$_SESSION['is_admin'] = true;

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo "<h1>🧪 Teste de Criação de Tópicos</h1>";

// Conectar ao banco
try {
    require_once 'config.php';
    $pdo = conectarBD();
    echo "<p>✅ Conectado ao banco de dados</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro de conexão: " . $e->getMessage() . "</p>";
    exit;
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>📨 Processando Criação de Tópico</h2>";
    
    echo "<h3>🔍 Dados Recebidos:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>🔐 Verificação CSRF:</h3>";
    echo "Token na sessão: " . $_SESSION['csrf_token'] . "<br>";
    echo "Token no POST: " . ($_POST['csrf_token'] ?? 'NÃO ENVIADO') . "<br>";
    echo "Tokens coincidem: " . (($_POST['csrf_token'] ?? '') === $_SESSION['csrf_token'] ? 'SIM' : 'NÃO') . "<br>";
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo "<p style='color: red;'>❌ Token CSRF inválido!</p>";
    } else {
        echo "<p style='color: green;'>✅ Token CSRF válido</p>";
        
        if (isset($_POST['acao']) && $_POST['acao'] === 'criar_topico') {
            $categoria_id = $_POST['categoria_id'] ?? '';
            $titulo = trim($_POST['titulo'] ?? '');
            $conteudo = trim($_POST['conteudo'] ?? '');
            
            echo "<h3>📝 Dados do Tópico:</h3>";
            echo "Categoria ID: $categoria_id<br>";
            echo "Título: $titulo<br>";
            echo "Conteúdo: " . substr($conteudo, 0, 100) . "...<br>";
            
            if (!empty($titulo) && !empty($conteudo) && !empty($categoria_id)) {
                try {
                    // Verificar se a categoria existe
                    $stmt = $pdo->prepare("SELECT id FROM forum_categorias WHERE id = ? AND ativo = 1");
                    $stmt->execute([$categoria_id]);
                    if (!$stmt->fetch()) {
                        echo "<p style='color: red;'>❌ Categoria inválida!</p>";
                    } else {
                        echo "<p style='color: green;'>✅ Categoria válida</p>";
                        
                        // Admins têm tópicos aprovados automaticamente
                        $aprovado = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? 1 : 0;
                        echo "Aprovado automaticamente: " . ($aprovado ? 'SIM' : 'NÃO') . "<br>";
                        
                        $stmt = $pdo->prepare("INSERT INTO forum_topicos (categoria_id, autor_id, titulo, conteudo, aprovado) VALUES (?, ?, ?, ?, ?)");
                        $result = $stmt->execute([$categoria_id, $_SESSION['usuario_id'], $titulo, $conteudo, $aprovado]);
                        
                        if ($result) {
                            $topico_id = $pdo->lastInsertId();
                            echo "<p style='color: green; font-weight: bold;'>🎉 TÓPICO CRIADO COM SUCESSO!</p>";
                            echo "ID do tópico: $topico_id<br>";
                            echo "Status: " . ($aprovado ? 'Aprovado e visível' : 'Aguardando aprovação') . "<br>";
                            
                            // Verificar se foi inserido
                            $stmt = $pdo->prepare("SELECT * FROM forum_topicos WHERE id = ?");
                            $stmt->execute([$topico_id]);
                            $topico_criado = $stmt->fetch();
                            
                            if ($topico_criado) {
                                echo "<h3>✅ Confirmação no Banco:</h3>";
                                echo "<pre>";
                                print_r($topico_criado);
                                echo "</pre>";
                            }
                            
                        } else {
                            echo "<p style='color: red;'>❌ Erro ao inserir no banco</p>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<p style='color: red;'>❌ Dados incompletos!</p>";
                echo "Título vazio: " . (empty($titulo) ? 'SIM' : 'NÃO') . "<br>";
                echo "Conteúdo vazio: " . (empty($conteudo) ? 'SIM' : 'NÃO') . "<br>";
                echo "Categoria vazia: " . (empty($categoria_id) ? 'SIM' : 'NÃO') . "<br>";
            }
        }
    }
}

// Buscar categorias
$stmt = $pdo->query("SELECT * FROM forum_categorias WHERE ativo = 1 ORDER BY ordem");
$categorias = $stmt->fetchAll();

echo "<h2>📋 Formulário de Teste</h2>";
?>

<form method="POST" style="max-width: 600px; background: #f8f9fa; padding: 20px; border-radius: 10px;">
    <input type="hidden" name="acao" value="criar_topico">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <div style="margin-bottom: 15px;">
        <label><strong>📂 Categoria:</strong></label><br>
        <select name="categoria_id" required style="width: 100%; padding: 8px;">
            <option value="">Selecione uma categoria</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo $categoria['id']; ?>">
                    <?php echo $categoria['icone']; ?> <?php echo htmlspecialchars($categoria['nome']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>📝 Título:</strong></label><br>
        <input type="text" name="titulo" placeholder="Digite o título do tópico" required style="width: 100%; padding: 8px;" value="Teste de Tópico - <?php echo date('H:i:s'); ?>">
    </div>
    
    <div style="margin-bottom: 15px;">
        <label><strong>💭 Conteúdo:</strong></label><br>
        <textarea name="conteudo" placeholder="Descreva seu tópico em detalhes..." required style="width: 100%; padding: 8px; height: 120px;">Este é um tópico de teste criado em <?php echo date('d/m/Y H:i:s'); ?> para verificar se a funcionalidade de criação está funcionando corretamente.</textarea>
    </div>
    
    <button type="submit" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        ✅ Criar Tópico de Teste
    </button>
</form>

<h2>📊 Últimos Tópicos no Banco</h2>
<?php
try {
    $stmt = $pdo->query("SELECT t.*, u.nome as autor_nome, c.nome as categoria_nome 
                         FROM forum_topicos t 
                         JOIN usuarios u ON t.autor_id = u.id 
                         JOIN forum_categorias c ON t.categoria_id = c.id 
                         ORDER BY t.data_criacao DESC LIMIT 5");
    $topicos = $stmt->fetchAll();
    
    if (count($topicos) > 0) {
        echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Título</th><th>Autor</th><th>Categoria</th><th>Aprovado</th><th>Data</th></tr>";
        foreach ($topicos as $topico) {
            $aprovado = $topico['aprovado'] ? '✅ Sim' : '⏳ Não';
            $cor = $topico['aprovado'] ? '#d4edda' : '#fff3cd';
            echo "<tr style='background: $cor;'>";
            echo "<td>{$topico['id']}</td>";
            echo "<td>" . htmlspecialchars($topico['titulo']) . "</td>";
            echo "<td>{$topico['autor_nome']}</td>";
            echo "<td>{$topico['categoria_nome']}</td>";
            echo "<td>$aprovado</td>";
            echo "<td>{$topico['data_criacao']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum tópico encontrado.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar tópicos: " . $e->getMessage() . "</p>";
}
?>

<h2>🔗 Links Úteis</h2>
<a href="forum.php">← Voltar ao Fórum</a><br>
<a href="admin_forum.php">🛡️ Painel de Administração</a><br>
<a href="debug_forum_criacao.php">🔍 Debug Detalhado</a>
