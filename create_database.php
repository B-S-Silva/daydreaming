<?php
// Script temporário para criar o banco de dados
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
];

try {
    echo "Conectando ao MySQL...\n";
    $pdo = new PDO("mysql:host={$config['host']};charset={$config['charset']}", $config['user'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Lendo arquivo SQL...\n";
    $sql = file_get_contents('db_structure.sql');
    
    echo "Executando comandos SQL...\n";
    $commands = explode(';', $sql);
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^(--|\/\*|\*)/', $command)) {
            try {
                $pdo->exec($command);
                echo "✓ Comando executado com sucesso\n";
            } catch (PDOException $e) {
                echo "⚠ Aviso: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✅ Banco de dados criado com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
