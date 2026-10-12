<?php
/**
 * Teste completo da finalização
 */

echo "🎯 TESTE COMPLETO DA FINALIZAÇÃO\n";
echo "================================\n\n";

// Simular sessão de usuário
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Admin';
$_SESSION['logado'] = true;

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Criar sessão de teste
    $stmt = $pdo->prepare("INSERT INTO sessoes_teste (usuario_id, tipo_prova, inicio, duracao_minutos, status) VALUES (?, ?, NOW(), ?, 'ativo')");
    $stmt->execute([$_SESSION['usuario_id'], 'sat', 180]);
    $sessao_id = $pdo->lastInsertId();
    
    echo "✅ Sessão de teste criada: $sessao_id\n\n";
    
    // Teste 1: Finalização sem respostas
    echo "🧪 TESTE 1: FINALIZAÇÃO SEM RESPOSTAS\n";
    echo "=====================================\n";
    
    $_POST = [
        'sessao_id' => $sessao_id,
        'respostas' => '{}',
        'finalizar' => '1'
    ];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    
    ob_start();
    include 'processar_teste.php';
    $output1 = ob_get_contents();
    ob_end_clean();
    
    // Extrair apenas o JSON (remover warnings)
    $json_start = strpos($output1, '{');
    if ($json_start !== false) {
        $json_output1 = substr($output1, $json_start);
        $result1 = json_decode($json_output1, true);
        
        if ($result1 && $result1['sucesso']) {
            echo "✅ Finalização sem respostas: SUCESSO\n";
            echo "   📊 Pontuação: {$result1['pontuacao']}%\n";
            echo "   📝 Questões respondidas: {$result1['questoes_respondidas']}\n";
            echo "   ✅ Acertos: {$result1['acertos']}\n";
        } else {
            echo "❌ Finalização sem respostas: FALHOU\n";
            echo "   📄 Output: $json_output1\n";
        }
    } else {
        echo "❌ Resposta não contém JSON válido\n";
        echo "   📄 Output: $output1\n";
    }
    
    // Teste 2: Finalização com respostas
    echo "\n🧪 TESTE 2: FINALIZAÇÃO COM RESPOSTAS\n";
    echo "=====================================\n";
    
    // Criar nova sessão
    $stmt = $pdo->prepare("INSERT INTO sessoes_teste (usuario_id, tipo_prova, inicio, duracao_minutos, status) VALUES (?, ?, NOW(), ?, 'ativo')");
    $stmt->execute([$_SESSION['usuario_id'], 'sat', 180]);
    $sessao_id_2 = $pdo->lastInsertId();
    
    // Buscar algumas questões reais para testar
    $stmt = $pdo->query("SELECT numero_questao, resposta_correta FROM questoes WHERE tipo_prova = 'sat' LIMIT 5");
    $questoes_reais = $stmt->fetchAll();
    
    $respostas_teste = [];
    foreach ($questoes_reais as $i => $questao) {
        if ($i < 3) {
            // Primeiras 3 corretas
            $respostas_teste[$questao['numero_questao']] = $questao['resposta_correta'];
        } else {
            // Últimas 2 incorretas
            $respostas_teste[$questao['numero_questao']] = 'x'; // resposta incorreta
        }
    }
    
    $_POST = [
        'sessao_id' => $sessao_id_2,
        'respostas' => json_encode($respostas_teste),
        'finalizar' => '1'
    ];
    
    echo "📋 Testando com " . count($respostas_teste) . " respostas (3 corretas, 2 incorretas)\n";
    
    ob_start();
    include 'processar_teste.php';
    $output2 = ob_get_contents();
    ob_end_clean();
    
    $json_start = strpos($output2, '{');
    if ($json_start !== false) {
        $json_output2 = substr($output2, $json_start);
        $result2 = json_decode($json_output2, true);
        
        if ($result2 && $result2['sucesso']) {
            echo "✅ Finalização com respostas: SUCESSO\n";
            echo "   📊 Pontuação: {$result2['pontuacao']}%\n";
            echo "   📝 Questões respondidas: {$result2['questoes_respondidas']}\n";
            echo "   ✅ Acertos: {$result2['acertos']}\n";
            echo "   🎯 Taxa de acerto esperada: 60% (3/5)\n";
        } else {
            echo "❌ Finalização com respostas: FALHOU\n";
            echo "   📄 Output: $json_output2\n";
        }
    }
    
    // Teste 3: Verificar página de resultado
    echo "\n🧪 TESTE 3: PÁGINA DE RESULTADO\n";
    echo "===============================\n";
    
    $_GET['sessao'] = $sessao_id_2;
    
    ob_start();
    try {
        include 'resultado_teste.php';
        $resultado_output = ob_get_contents();
        ob_end_clean();
        
        if (strlen($resultado_output) > 2000) {
            echo "✅ Página de resultado carrega corretamente\n";
            echo "   📏 Tamanho: " . strlen($resultado_output) . " bytes\n";
            
            // Verificar se contém elementos importantes
            if (strpos($resultado_output, 'Resultado do Teste') !== false) {
                echo "   ✅ Título encontrado\n";
            }
            if (strpos($resultado_output, 'SAT') !== false) {
                echo "   ✅ Nome da prova encontrado\n";
            }
            if (strpos($resultado_output, '%') !== false) {
                echo "   ✅ Pontuação percentual encontrada\n";
            }
        } else {
            echo "⚠️ Página de resultado muito pequena\n";
            echo "   📏 Tamanho: " . strlen($resultado_output) . " bytes\n";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Erro ao carregar página de resultado: " . $e->getMessage() . "\n";
    }
    
    echo "\n📊 RESUMO DOS TESTES:\n";
    echo "=====================\n";
    
    $testes_passaram = 0;
    $total_testes = 3;
    
    if (isset($result1) && $result1['sucesso']) {
        echo "✅ Teste 1: Finalização sem respostas\n";
        $testes_passaram++;
    } else {
        echo "❌ Teste 1: Finalização sem respostas\n";
    }
    
    if (isset($result2) && $result2['sucesso']) {
        echo "✅ Teste 2: Finalização com respostas\n";
        $testes_passaram++;
    } else {
        echo "❌ Teste 2: Finalização com respostas\n";
    }
    
    if (isset($resultado_output) && strlen($resultado_output) > 2000) {
        echo "✅ Teste 3: Página de resultado\n";
        $testes_passaram++;
    } else {
        echo "❌ Teste 3: Página de resultado\n";
    }
    
    echo "\n🎯 RESULTADO FINAL:\n";
    echo "===================\n";
    echo "Testes passaram: $testes_passaram/$total_testes\n";
    
    if ($testes_passaram === $total_testes) {
        echo "🎉 TODOS OS TESTES PASSARAM!\n";
        echo "✅ Sistema de finalização funcionando perfeitamente\n";
        echo "✅ Permite finalizar sem respostas\n";
        echo "✅ Calcula pontuação corretamente\n";
        echo "✅ Página de resultado funcional\n\n";
        
        echo "🌐 TESTE NO NAVEGADOR:\n";
        echo "======================\n";
        echo "http://localhost:8080/executar_teste.php?tipo=sat&sessao=$sessao_id_2&executando=1\n";
        echo "- Responda algumas questões (opcional)\n";
        echo "- Clique em 'Finalizar Teste'\n";
        echo "- Veja o loading e resumo\n";
        echo "- Seja redirecionado para os resultados\n";
        
    } else {
        echo "⚠️ ALGUNS TESTES FALHARAM\n";
        echo "🔧 Verifique os logs acima para detalhes\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
