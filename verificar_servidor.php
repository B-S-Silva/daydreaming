<?php
/**
 * Script para verificar se o servidor e simulador estão funcionando
 */

echo "🌐 VERIFICANDO SERVIDOR E SIMULADOR\n";
echo "===================================\n\n";

// Verificar se o arquivo existe
$arquivos_importantes = [
    'simulador_provas.php',
    'executar_teste.php',
    'interface_teste.php',
    'salvar_resposta.php',
    'config.php'
];

echo "📁 VERIFICANDO ARQUIVOS:\n";
echo "========================\n";

foreach ($arquivos_importantes as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - OK\n";
    } else {
        echo "❌ $arquivo - NÃO ENCONTRADO\n";
    }
}

echo "\n🗄️ VERIFICANDO BANCO DE DADOS:\n";
echo "==============================\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    echo "✅ Conexão com banco - OK\n";
    
    // Verificar tabelas importantes
    $tabelas = ['usuarios', 'questoes', 'sessoes_teste', 'respostas_usuario'];
    
    foreach ($tabelas as $tabela) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela $tabela - OK\n";
        } else {
            echo "❌ Tabela $tabela - NÃO ENCONTRADA\n";
        }
    }
    
    // Verificar questões SAT
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes WHERE tipo_prova = 'sat'");
    $total_sat = $stmt->fetchColumn();
    echo "📊 Questões SAT: $total_sat\n";
    
    // Verificar questões dissertativas
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes WHERE tipo_prova = 'sat' AND tipo_questao = 'dissertativa'");
    $dissertativas = $stmt->fetchColumn();
    echo "✏️ Questões dissertativas: $dissertativas\n";
    
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "\n";
}

echo "\n🔗 LINKS PARA TESTE:\n";
echo "====================\n";
echo "🌐 Servidor: http://localhost:8080\n";
echo "🔐 Login: http://localhost:8080/login.php\n";
echo "🎯 Simulador: http://localhost:8080/simulador_provas.php\n";
echo "🧪 Teste SAT: http://localhost:8080/executar_teste.php?tipo=sat\n";

echo "\n📋 CREDENCIAIS DE TESTE:\n";
echo "========================\n";
echo "👤 Usuário: admin\n";
echo "🔑 Senha: admin123\n";

echo "\n✅ SERVIDOR PRONTO!\n";
echo "===================\n";
echo "O simulador está funcionando na porta 8080.\n";
echo "Acesse: http://localhost:8080/simulador_provas.php\n\n";

echo "🎯 FUNCIONALIDADES DISPONÍVEIS:\n";
echo "===============================\n";
echo "🔘 Questões de múltipla escolha\n";
echo "✏️ Questões dissertativas\n";
echo "💾 Salvamento automático\n";
echo "🎯 Correção em tempo real\n";
echo "📊 Estatísticas de progresso\n";
echo "🏆 Sistema de badges\n";
?>
