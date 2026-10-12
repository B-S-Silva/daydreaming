<?php
/**
 * Script para verificar usuários e senhas no banco
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🔍 VERIFICANDO USUÁRIOS E SENHAS\n";
echo "================================\n\n";

try {
    // Conectar
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Verificar usuários existentes
    echo "👥 USUÁRIOS CADASTRADOS:\n";
    echo "========================\n";
    $stmt = $pdo->query("SELECT id, nome, usuario, email, senha, is_admin, ativo FROM usuarios ORDER BY id");
    $usuarios = $stmt->fetchAll();
    
    foreach ($usuarios as $user) {
        echo "ID: {$user['id']}\n";
        echo "Nome: {$user['nome']}\n";
        echo "Usuário: {$user['usuario']}\n";
        echo "Email: {$user['email']}\n";
        echo "Senha Hash: " . substr($user['senha'], 0, 30) . "...\n";
        echo "Admin: " . ($user['is_admin'] ? 'SIM' : 'NÃO') . "\n";
        echo "Ativo: " . ($user['ativo'] ? 'SIM' : 'NÃO') . "\n";
        echo "---\n";
    }
    
    // Testar senhas
    echo "\n🔐 TESTANDO SENHAS:\n";
    echo "===================\n";
    
    $senha_teste = 'admin123';
    echo "Testando senha: '$senha_teste'\n\n";
    
    foreach ($usuarios as $user) {
        $verifica = password_verify($senha_teste, $user['senha']);
        echo "Usuário: {$user['usuario']}\n";
        echo "Senha funciona: " . ($verifica ? '✅ SIM' : '❌ NÃO') . "\n";
        echo "---\n";
    }
    
    // Gerar nova senha hash para teste
    echo "\n🔧 GERANDO NOVA SENHA HASH:\n";
    echo "============================\n";
    $nova_senha = password_hash('admin123', PASSWORD_DEFAULT);
    echo "Nova senha hash para 'admin123':\n";
    echo "$nova_senha\n\n";
    
    // Atualizar senhas dos usuários
    echo "🔄 ATUALIZANDO SENHAS DOS USUÁRIOS:\n";
    echo "===================================\n";
    
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE usuario = ?");
    
    $usuarios_atualizar = ['admin', 'teste', 'maria.santos', 'joao.silva'];
    
    foreach ($usuarios_atualizar as $usuario) {
        $nova_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute([$nova_hash, $usuario]);
        echo "✅ Senha atualizada para usuário: $usuario\n";
    }
    
    echo "\n🔍 VERIFICAÇÃO FINAL:\n";
    echo "=====================\n";
    
    // Verificar novamente
    $stmt = $pdo->query("SELECT usuario, senha FROM usuarios ORDER BY id");
    $usuarios_final = $stmt->fetchAll();
    
    foreach ($usuarios_final as $user) {
        $verifica = password_verify('admin123', $user['senha']);
        echo "Usuário: {$user['usuario']} - Senha 'admin123': " . ($verifica ? '✅ FUNCIONA' : '❌ NÃO FUNCIONA') . "\n";
    }
    
    echo "\n🎉 ATUALIZAÇÃO CONCLUÍDA!\n\n";
    
    echo "🔑 CREDENCIAIS ATUALIZADAS:\n";
    echo "===========================\n";
    echo "Todos os usuários agora usam a senha: admin123\n\n";
    echo "Usuários disponíveis:\n";
    echo "- admin (Administrador)\n";
    echo "- teste (Usuário comum)\n";
    echo "- maria.santos (Usuário comum)\n";
    echo "- joao.silva (Usuário comum)\n\n";
    echo "🌐 Tente fazer login em: http://localhost:8080/login.php\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
