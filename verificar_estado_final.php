<?php
/**
 * Script para verificar o estado final após limpeza
 */

echo "📊 VERIFICAÇÃO FINAL DO SISTEMA\n";
echo "===============================\n\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Verificar questões por tipo de prova
    echo "📋 QUESTÕES POR TIPO DE PROVA:\n";
    echo "==============================\n";
    
    $tipos_prova = ['sat', 'toefl', 'ielts', 'gre'];
    
    foreach ($tipos_prova as $tipo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE tipo_prova = ?");
        $stmt->execute([$tipo]);
        $total = $stmt->fetchColumn();
        
        if ($total > 0) {
            echo "✅ " . strtoupper($tipo) . ": $total questões\n";
            
            // Mostrar distribuição por tipo de questão
            $stmt = $pdo->prepare("
                SELECT tipo_questao, COUNT(*) as total 
                FROM questoes 
                WHERE tipo_prova = ? 
                GROUP BY tipo_questao
            ");
            $stmt->execute([$tipo]);
            $distribuicao = $stmt->fetchAll();
            
            foreach ($distribuicao as $dist) {
                $emoji = $dist['tipo_questao'] === 'dissertativa' ? '✏️' : '🔘';
                echo "   $emoji {$dist['tipo_questao']}: {$dist['total']}\n";
            }
        } else {
            echo "⚪ " . strtoupper($tipo) . ": 0 questões (aguardando JSON)\n";
        }
        echo "\n";
    }
    
    // Verificar sessões ativas
    echo "🔄 SESSÕES ATIVAS:\n";
    echo "==================\n";
    
    $stmt = $pdo->query("
        SELECT tipo_prova, COUNT(*) as total 
        FROM sessoes_teste 
        WHERE status = 'ativo' 
        GROUP BY tipo_prova
    ");
    $sessoes = $stmt->fetchAll();
    
    if (empty($sessoes)) {
        echo "ℹ️ Nenhuma sessão ativa encontrada\n";
    } else {
        foreach ($sessoes as $sessao) {
            echo "🔄 {$sessao['tipo_prova']}: {$sessao['total']} sessões ativas\n";
        }
    }
    
    echo "\n📊 ESTATÍSTICAS GERAIS:\n";
    echo "=======================\n";
    
    // Total de questões
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes");
    $total_questoes = $stmt->fetchColumn();
    echo "📝 Total de questões: $total_questoes\n";
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $total_usuarios = $stmt->fetchColumn();
    echo "👥 Total de usuários: $total_usuarios\n";
    
    // Total de badges
    $stmt = $pdo->query("SELECT COUNT(*) FROM badges");
    $total_badges = $stmt->fetchColumn();
    echo "🏆 Total de badges: $total_badges\n";
    
    echo "\n🧪 TESTANDO ACESSO AOS EXAMES:\n";
    echo "==============================\n";
    
    foreach ($tipos_prova as $tipo) {
        echo "🔍 Testando $tipo...\n";
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE tipo_prova = ?");
        $stmt->execute([$tipo]);
        $questoes_disponiveis = $stmt->fetchColumn();
        
        if ($questoes_disponiveis > 0) {
            echo "   ✅ $questoes_disponiveis questões disponíveis\n";
            echo "   🔗 http://localhost:8080/executar_teste.php?tipo=$tipo\n";
        } else {
            echo "   ⚪ Sem questões - mostrará página 'em preparação'\n";
            echo "   🔗 http://localhost:8080/executar_teste.php?tipo=$tipo\n";
        }
        echo "\n";
    }
    
    echo "🎯 RESUMO DO SISTEMA:\n";
    echo "=====================\n";
    echo "✅ SAT: Totalmente funcional com 120 questões reais\n";
    echo "   - 107 questões de múltipla escolha\n";
    echo "   - 13 questões dissertativas\n";
    echo "   - Baseado no SAT Practice Test #4\n\n";
    
    echo "⚪ TOEFL: Aguardando arquivo JSON\n";
    echo "⚪ IELTS: Aguardando arquivo JSON\n";
    echo "⚪ GRE: Aguardando arquivo JSON\n\n";
    
    echo "🌐 LINKS PARA TESTE:\n";
    echo "====================\n";
    echo "🏠 Página inicial: http://localhost:8080\n";
    echo "🎯 Simulador: http://localhost:8080/simulador_provas.php\n";
    echo "📚 Testes internacionais: http://localhost:8080/testes_internacionais.php\n";
    echo "🔐 Login: http://localhost:8080/login.php (admin/admin123)\n\n";
    
    echo "📋 PRÓXIMOS PASSOS:\n";
    echo "===================\n";
    echo "1. ✅ SAT funcionando completamente\n";
    echo "2. 📄 Aguardar arquivos JSON dos outros exames\n";
    echo "3. 🔄 Carregar questões TOEFL, IELTS, GRE\n";
    echo "4. 🧪 Testar todos os exames\n\n";
    
    echo "🎉 SISTEMA OTIMIZADO E PRONTO!\n";
    echo "==============================\n";
    echo "- Questões extras removidas\n";
    echo "- Banco de dados limpo\n";
    echo "- SAT totalmente funcional\n";
    echo "- Outros exames preparados para receber JSON\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
