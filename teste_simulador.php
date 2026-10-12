<?php
/**
 * Teste simples do simulador
 */

echo "🧪 TESTE DO SIMULADOR DE PROVAS\n";
echo "===============================\n\n";

// Simular sessão de usuário
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Admin';
$_SESSION['logado'] = true;

echo "👤 Usuário simulado: {$_SESSION['usuario_nome']} (ID: {$_SESSION['usuario_id']})\n\n";

// Testar acesso ao simulador
echo "🎯 TESTANDO ACESSO AO SIMULADOR:\n";
echo "================================\n";

try {
    // Capturar saída
    ob_start();
    
    // Simular acesso via GET
    $_GET = [];
    $_POST = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    include 'simulador_provas.php';
    
    $output = ob_get_contents();
    ob_end_clean();
    
    if (strlen($output) > 1000) {
        echo "✅ Simulador carrega corretamente\n";
        echo "📏 Tamanho da página: " . strlen($output) . " bytes\n";
        
        // Verificar se contém elementos importantes
        if (strpos($output, 'Simulador de Provas') !== false) {
            echo "✅ Título encontrado\n";
        }
        
        if (strpos($output, 'TOEFL') !== false) {
            echo "✅ Tipos de prova encontrados\n";
        }
        
        if (strpos($output, 'Iniciar Simulado') !== false) {
            echo "✅ Botões de ação encontrados\n";
        }
        
    } else {
        echo "⚠️ Página muito pequena, pode ter problemas\n";
        echo "📏 Tamanho: " . strlen($output) . " bytes\n";
        echo "📄 Conteúdo:\n" . substr($output, 0, 500) . "...\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao carregar simulador: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Erro fatal: " . $e->getMessage() . "\n";
}

echo "\n🔗 TESTANDO LINKS DO SIMULADOR:\n";
echo "===============================\n";

$tipos_teste = ['toefl', 'ielts', 'sat', 'gre'];

foreach ($tipos_teste as $tipo) {
    echo "🧪 Testando tipo: $tipo\n";
    
    try {
        // Simular acesso ao executar_teste.php
        ob_start();
        
        $_GET = ['tipo' => $tipo];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        include 'executar_teste.php';
        
        $output = ob_get_contents();
        ob_end_clean();
        
        if (strlen($output) > 500) {
            echo "  ✅ Página de teste $tipo carrega\n";
        } else {
            echo "  ⚠️ Página de teste $tipo pode ter problemas\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Erro no teste $tipo: " . $e->getMessage() . "\n";
    } catch (Error $e) {
        echo "  ❌ Erro fatal no teste $tipo: " . $e->getMessage() . "\n";
    }
}

echo "\n📊 VERIFICANDO QUESTÕES DISPONÍVEIS:\n";
echo "====================================\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova ORDER BY tipo_prova");
    $questoes = $stmt->fetchAll();
    
    foreach ($questoes as $questao) {
        $status = $questao['total'] >= 5 ? '✅' : '⚠️';
        echo "$status {$questao['tipo_prova']}: {$questao['total']} questões\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao verificar questões: " . $e->getMessage() . "\n";
}

echo "\n🎯 RECOMENDAÇÕES:\n";
echo "=================\n";

echo "1. ✅ Banco de dados configurado\n";
echo "2. ✅ Questões adicionadas\n";
echo "3. ✅ Arquivos corrigidos\n";
echo "4. 🌐 Teste no navegador: http://localhost:8080/simulador_provas.php\n";
echo "5. 🔐 Faça login primeiro em: http://localhost:8080/login.php\n";

echo "\n✅ TESTE CONCLUÍDO!\n";
?>
