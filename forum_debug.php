<?php
// Debug simples do fórum
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Admin';
$_SESSION['usuario_login'] = 'admin';
$_SESSION['logado'] = true;

echo "<h1>Debug do Fórum</h1>";

// Verificar arquivos
echo "<h2>Arquivos:</h2>";
echo file_exists('forum.php') ? "✅ forum.php<br>" : "❌ forum.php<br>";
echo file_exists('verificar_auth.php') ? "✅ verificar_auth.php<br>" : "❌ verificar_auth.php<br>";
echo file_exists('config.php') ? "✅ config.php<br>" : "❌ config.php<br>";

// Testar conexão
echo "<h2>Banco de Dados:</h2>";
try {
    require_once 'config.php';
    $pdo = conectarBD();
    echo "✅ Conexão OK<br>";
    
    // Verificar tabelas
    $tabelas = ['logs_acesso', 'forum_categorias', 'forum_topicos', 'forum_respostas'];
    foreach ($tabelas as $tabela) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $tabela");
            $count = $stmt->fetchColumn();
            echo "✅ $tabela: $count registros<br>";
        } catch (Exception $e) {
            echo "❌ $tabela: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

// Testar forum.php
echo "<h2>Teste do Forum:</h2>";
try {
    ob_start();
    include 'forum.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    echo "Tamanho da saída: " . strlen($output) . " bytes<br>";
    if (strlen($output) > 1000) {
        echo "✅ Forum carrega corretamente<br>";
        echo "<a href='forum.php' target='_blank'>Abrir Fórum</a>";
    } else {
        echo "❌ Problema no forum<br>";
        echo "Conteúdo: " . htmlspecialchars(substr($output, 0, 500));
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "Linha: " . $e->getLine() . "<br>";
}

echo "<h2>Soluções:</h2>";
echo "<a href='setup_database.php'>Executar Setup Database</a><br>";
echo "<a href='verificar_instalacao.php'>Verificar Instalação</a><br>";
?>
