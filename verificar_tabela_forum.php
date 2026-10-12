<?php
// Verificação simples da tabela forum_topicos
require_once 'config.php';

try {
    $pdo = conectarBD();
    
    echo "<h1>Verificação da Tabela forum_topicos</h1>";
    
    // Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'forum_topicos'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Tabela forum_topicos existe</p>";
        
        // Mostrar estrutura
        echo "<h2>Estrutura da Tabela:</h2>";
        $stmt = $pdo->query("DESCRIBE forum_topicos");
        $campos = $stmt->fetchAll();
        
        echo "<table border='1'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($campos as $campo) {
            echo "<tr>";
            echo "<td>{$campo['Field']}</td>";
            echo "<td>{$campo['Type']}</td>";
            echo "<td>{$campo['Null']}</td>";
            echo "<td>{$campo['Key']}</td>";
            echo "<td>{$campo['Default']}</td>";
            echo "<td>{$campo['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar se data_atualizacao existe
        $campos_nomes = array_column($campos, 'Field');
        if (in_array('data_atualizacao', $campos_nomes)) {
            echo "<p>✅ Campo data_atualizacao existe</p>";
        } else {
            echo "<p>❌ Campo data_atualizacao NÃO existe</p>";
            echo "<p><strong>SOLUÇÃO:</strong> Execute DROP TABLE forum_topicos; e depois php setup_database.php</p>";
        }
        
        if (in_array('autor_id', $campos_nomes)) {
            echo "<p>✅ Campo autor_id existe</p>";
        } else {
            echo "<p>❌ Campo autor_id NÃO existe</p>";
            echo "<p><strong>SOLUÇÃO:</strong> Execute DROP TABLE forum_topicos; e depois php setup_database.php</p>";
        }
        
        // Testar query problemática
        echo "<h2>Teste da Query:</h2>";
        try {
            $sql = "SELECT t.*, u.nome as autor_nome 
                    FROM forum_topicos t 
                    JOIN usuarios u ON t.autor_id = u.id 
                    ORDER BY t.data_atualizacao DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            echo "<p>✅ Query funciona corretamente</p>";
        } catch (Exception $e) {
            echo "<p>❌ Erro na query: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p>❌ Tabela forum_topicos NÃO existe</p>";
        echo "<p><strong>SOLUÇÃO:</strong> Execute php setup_database.php</p>";
    }
    
    echo "<h2>Ações:</h2>";
    echo "<a href='setup_database.php'>Executar Setup Database</a><br>";
    echo "<a href='forum.php'>Testar Fórum</a><br>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>
