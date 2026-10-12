<?php
require_once 'config.php';

echo "🔐 ATUALIZANDO SENHAS DOS USUÁRIOS\n";
echo "==================================\n\n";

try {
    $conn = conectarBD();
    
    // Verificar usuários atuais
    echo "📋 Verificando usuários existentes...\n";
    $stmt = $conn->query("SELECT id, nome, usuario, email FROM usuarios");
    $usuarios = $stmt->fetchAll();
    
    foreach ($usuarios as $user) {
        echo "- ID: {$user['id']}, Usuário: {$user['usuario']}, Nome: {$user['nome']}\n";
    }
    
    echo "\n🔄 Gerando novos hashes de senha...\n";
    
    // Senhas que vamos usar
    $senhas = [
        'admin' => 'admin123',
        'teste' => 'teste123'
    ];
    
    foreach ($senhas as $usuario => $senha_texto) {
        // Gerar hash seguro da senha
        $hash_senha = password_hash($senha_texto, PASSWORD_DEFAULT);
        
        echo "👤 Usuário: $usuario\n";
        echo "🔑 Senha: $senha_texto\n";
        echo "🔒 Hash: $hash_senha\n";
        
        // Atualizar no banco
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ? WHERE usuario = ?");
        $resultado = $stmt->execute([$hash_senha, $usuario]);
        
        if ($resultado) {
            echo "✅ Senha atualizada com sucesso!\n";
        } else {
            echo "❌ Erro ao atualizar senha!\n";
        }
        echo "\n";
    }
    
    // Verificar se as senhas foram atualizadas
    echo "🧪 TESTANDO LOGIN COM AS NOVAS SENHAS...\n";
    echo "========================================\n";
    
    foreach ($senhas as $usuario => $senha_texto) {
        echo "Testando login: $usuario / $senha_texto\n";
        
        // Buscar usuário
        $stmt = $conn->prepare("SELECT id, nome, usuario, senha, is_admin FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user_data = $stmt->fetch();
        
        if ($user_data) {
            // Verificar senha
            if (password_verify($senha_texto, $user_data['senha'])) {
                echo "✅ Login SUCESSO! Senha correta.\n";
                echo "   - ID: {$user_data['id']}\n";
                echo "   - Nome: {$user_data['nome']}\n";
                echo "   - Admin: " . ($user_data['is_admin'] ? 'Sim' : 'Não') . "\n";
            } else {
                echo "❌ Login FALHOU! Senha incorreta.\n";
            }
        } else {
            echo "❌ Usuário não encontrado!\n";
        }
        echo "\n";
    }
    
    echo "📝 CREDENCIAIS PARA TESTE:\n";
    echo "==========================\n";
    echo "👨‍💼 ADMINISTRADOR:\n";
    echo "   Usuário: admin\n";
    echo "   Senha: admin123\n";
    echo "   URL: http://localhost:8080/login.php\n\n";
    
    echo "👤 USUÁRIO COMUM:\n";
    echo "   Usuário: teste\n";
    echo "   Senha: teste123\n";
    echo "   URL: http://localhost:8080/login.php\n\n";
    
    echo "✅ Atualização de senhas concluída!\n";
    echo "Agora você pode fazer login com as credenciais acima.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
