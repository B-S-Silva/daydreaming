<?php
echo "Testando conexao com banco...\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=db_daydreamming_project;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Conexao OK!\n";
    
    // Verificar usuarios
    $stmt = $pdo->query("SELECT usuario, senha FROM usuarios");
    $users = $stmt->fetchAll();
    
    echo "Usuarios encontrados:\n";
    foreach ($users as $user) {
        echo "- " . $user['usuario'] . " (hash: " . substr($user['senha'], 0, 20) . "...)\n";
    }
    
    // Atualizar senhas
    echo "\nAtualizando senhas...\n";
    
    $admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $teste_hash = password_hash('teste123', PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE usuario = 'admin'");
    $stmt->execute([$admin_hash]);
    echo "Admin atualizado\n";
    
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE usuario = 'teste'");
    $stmt->execute([$teste_hash]);
    echo "Teste atualizado\n";
    
    echo "\nCredenciais:\n";
    echo "admin / admin123\n";
    echo "teste / teste123\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
?>
