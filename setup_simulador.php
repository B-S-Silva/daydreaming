<?php
/**
 * Script para configurar as tabelas do simulador de provas
 * Execute este arquivo uma vez para criar as tabelas necessárias
 */

require_once 'config.php';

try {
    // Conectar ao banco de dados
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    echo "<h2>Configurando Sistema de Simulador de Provas...</h2>";
    
    // Ler e executar o script SQL
    $sql = file_get_contents('simulador_database.sql');
    
    // Dividir o SQL em comandos individuais
    $commands = explode(';', $sql);
    
    $executed = 0;
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^\s*--/', $command)) {
            try {
                $pdo->exec($command);
                $executed++;
            } catch (PDOException $e) {
                echo "<p style='color: orange;'>Aviso: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Configuração concluída! $executed comandos executados.</p>";
    
    // Verificar se as tabelas foram criadas
    $stmt = $pdo->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Filtrar tabelas relacionadas ao simulador
    $tables = array_filter($all_tables, function($table) {
        return strpos($table, 'teste') !== false || 
               strpos($table, 'badge') !== false || 
               strpos($table, 'questoes') !== false ||
               strpos($table, 'sessoes') !== false ||
               strpos($table, 'respostas') !== false ||
               strpos($table, 'niveis') !== false;
    });
    
    echo "<h3>Tabelas criadas:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>O simulador de provas está pronto para uso!</strong></p>";
    echo "<p><a href='simulador_provas.php'>Acessar Simulador</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro na configuração: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Verifique se:</p>";
    echo "<ul>";
    echo "<li>O MySQL está rodando</li>";
    echo "<li>O banco de dados 'db_daydreamming_project' existe</li>";
    echo "<li>As credenciais em config.php estão corretas</li>";
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Simulador</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #333; }
        p { margin: 10px 0; }
        ul { margin: 10px 0 10px 20px; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
</body>
</html>