<?php
require_once 'config.php';

echo "🔐 TESTE DE LOGIN - SISTEMA DAYDREAMING\n";
echo "=======================================\n\n";

function testarLogin($usuario, $senha) {
    try {
        $conn = conectarBD();
        
        echo "🧪 Testando login: $usuario / $senha\n";
        
        // Buscar usuário
        $stmt = $conn->prepare("SELECT id, usuario, senha, nome, is_admin, ativo FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $usuario_dados = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario_dados) {
            echo "❌ Usuário não encontrado!\n\n";
            return false;
        }
        
        echo "✅ Usuário encontrado: {$usuario_dados['nome']}\n";
        echo "📊 Status ativo: " . ($usuario_dados['ativo'] ? 'Sim' : 'Não') . "\n";
        echo "👑 É admin: " . ($usuario_dados['is_admin'] ? 'Sim' : 'Não') . "\n";
        
        // Verificar senha
        if (password_verify($senha, $usuario_dados['senha'])) {
            echo "✅ Senha CORRETA!\n";
            
            if ($usuario_dados['ativo']) {
                echo "🎉 LOGIN SUCESSO! Usuário pode acessar o sistema.\n";
                return true;
            } else {
                echo "🚫 Usuário está BANIDO!\n";
                return false;
            }
        } else {
            echo "❌ Senha INCORRETA!\n";
            return false;
        }
        
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n";
        return false;
    }
    
    echo "\n";
}

// Testar as credenciais
echo "📋 TESTANDO CREDENCIAIS ATUALIZADAS:\n";
echo "====================================\n\n";

$credenciais = [
    ['admin', 'admin123'],
    ['teste', 'teste123'],
    ['admin', 'senha_errada'], // Teste com senha errada
    ['usuario_inexistente', 'qualquer_senha'] // Teste com usuário inexistente
];

foreach ($credenciais as $cred) {
    testarLogin($cred[0], $cred[1]);
    echo str_repeat('-', 50) . "\n\n";
}

echo "📝 CREDENCIAIS VÁLIDAS PARA USO:\n";
echo "================================\n";
echo "👨‍💼 ADMINISTRADOR:\n";
echo "   🔑 Usuário: admin\n";
echo "   🔐 Senha: admin123\n";
echo "   🌐 URL: http://localhost:8080/login.php\n\n";

echo "👤 USUÁRIO COMUM:\n";
echo "   🔑 Usuário: teste\n";
echo "   🔐 Senha: teste123\n";
echo "   🌐 URL: http://localhost:8080/login.php\n\n";

echo "🚀 PRÓXIMOS PASSOS:\n";
echo "==================\n";
echo "1. Acesse: http://localhost:8080/login.php\n";
echo "2. Use uma das credenciais acima\n";
echo "3. Após login, você será redirecionado para o dashboard\n";
echo "4. Como admin, você pode acessar: http://localhost:8080/admin_forum.php\n\n";

echo "✅ Teste de login concluído!\n";
?>
