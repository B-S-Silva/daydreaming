<?php
/**
 * Verificação das alterações do dashboard e pontuação
 */

echo "🧪 VERIFICANDO ALTERAÇÕES DO DASHBOARD E PONTUAÇÃO\n";
echo "==================================================\n\n";

// Simular sessão de usuário
session_start();
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_nome'] = 'Admin';
$_SESSION['logado'] = true;

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // 1. Verificar se dashboard.php foi removido
    echo "🗑️ VERIFICANDO REMOÇÃO DO DASHBOARD.PHP:\n";
    echo "========================================\n";
    
    if (!file_exists('dashboard.php')) {
        echo "✅ Arquivo dashboard.php removido com sucesso\n";
    } else {
        echo "❌ Arquivo dashboard.php ainda existe\n";
    }
    
    // 2. Verificar remoção de referências ao dashboard
    echo "\n🔍 VERIFICANDO REMOÇÃO DE REFERÊNCIAS:\n";
    echo "======================================\n";
    
    $arquivos_verificar = [
        'historico_provas.php' => 'Histórico de Provas',
        'historico_testes.php' => 'Histórico de Testes',
        'forum.php' => 'Fórum',
        'admin_forum.php' => 'Admin Fórum'
    ];
    
    $referencias_removidas = 0;
    $total_arquivos = count($arquivos_verificar);
    
    foreach ($arquivos_verificar as $arquivo => $nome) {
        if (file_exists($arquivo)) {
            $conteudo = file_get_contents($arquivo);
            
            if (strpos($conteudo, 'dashboard.php') === false) {
                echo "✅ $nome - Referências ao dashboard removidas\n";
                $referencias_removidas++;
            } else {
                echo "❌ $nome - Ainda contém referências ao dashboard\n";
                
                // Mostrar onde ainda há referências
                $linhas = explode("\n", $conteudo);
                foreach ($linhas as $num => $linha) {
                    if (strpos($linha, 'dashboard.php') !== false) {
                        echo "   📍 Linha " . ($num + 1) . ": " . trim($linha) . "\n";
                    }
                }
            }
        } else {
            echo "⚠️ $nome - Arquivo não encontrado\n";
        }
    }
    
    // 3. Testar página de histórico com nova pontuação
    echo "\n🧪 TESTANDO NOVA PONTUAÇÃO NO HISTÓRICO:\n";
    echo "========================================\n";
    
    // Criar uma sessão de teste se não existir
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessoes_teste WHERE usuario_id = ? AND status = 'finalizado'");
    $stmt->execute([$_SESSION['usuario_id']]);
    $sessoes_existentes = $stmt->fetchColumn();
    
    if ($sessoes_existentes == 0) {
        echo "🔧 Criando sessão de teste...\n";
        
        $stmt = $pdo->prepare("INSERT INTO sessoes_teste (usuario_id, tipo_prova, inicio, duracao_minutos, status, pontuacao_final, acertos, questoes_respondidas, tempo_gasto) VALUES (?, ?, NOW(), ?, 'finalizado', ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], 'sat', 180, 75.0, 15, 20, 3600]);
        
        echo "✅ Sessão de teste criada\n";
    }
    
    // Testar carregamento do histórico
    ob_start();
    try {
        include 'historico_provas.php';
        $historico_output = ob_get_contents();
        ob_end_clean();
        
        if (strlen($historico_output) > 3000) {
            echo "✅ Página de histórico carrega corretamente\n";
            echo "   📏 Tamanho: " . strlen($historico_output) . " bytes\n";
            
            // Verificar se não há referências ao dashboard
            if (strpos($historico_output, 'dashboard.php') === false) {
                echo "   ✅ Nenhuma referência ao dashboard encontrada\n";
            } else {
                echo "   ❌ Ainda há referências ao dashboard\n";
            }
            
            // Verificar se a nova pontuação está sendo exibida
            if (strpos($historico_output, '/120') !== false || strpos($historico_output, '/100') !== false) {
                echo "   ✅ Nova pontuação (acertos/total) encontrada\n";
            } else {
                echo "   ❌ Nova pontuação não encontrada\n";
            }
            
            // Verificar se não há mais porcentagens na pontuação
            if (preg_match('/\d+\.\d+%/', $historico_output)) {
                echo "   ⚠️ Ainda há porcentagens na pontuação\n";
            } else {
                echo "   ✅ Porcentagens removidas da pontuação\n";
            }
            
        } else {
            echo "❌ Página de histórico muito pequena ou com erro\n";
            echo "   📏 Tamanho: " . strlen($historico_output) . " bytes\n";
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Erro ao carregar histórico: " . $e->getMessage() . "\n";
    }
    
    // 4. Verificar outros arquivos importantes
    echo "\n🔍 VERIFICANDO OUTROS ARQUIVOS:\n";
    echo "===============================\n";
    
    $outros_arquivos = [
        'resultado_teste.php' => 'Resultado do Teste',
        'revisar_prova.php' => 'Revisão da Prova'
    ];
    
    foreach ($outros_arquivos as $arquivo => $nome) {
        if (file_exists($arquivo)) {
            $conteudo = file_get_contents($arquivo);
            
            if (strpos($conteudo, 'dashboard.php') === false) {
                echo "✅ $nome - Sem referências ao dashboard\n";
            } else {
                echo "⚠️ $nome - Contém referências ao dashboard\n";
            }
        }
    }
    
    echo "\n🎯 RESUMO DAS VERIFICAÇÕES:\n";
    echo "===========================\n";
    
    $testes_ok = 0;
    $total_testes = 4;
    
    // 1. Dashboard removido
    if (!file_exists('dashboard.php')) {
        echo "✅ Dashboard.php removido\n";
        $testes_ok++;
    } else {
        echo "❌ Dashboard.php ainda existe\n";
    }
    
    // 2. Referências removidas
    if ($referencias_removidas === $total_arquivos) {
        echo "✅ Todas as referências ao dashboard removidas\n";
        $testes_ok++;
    } else {
        echo "❌ Algumas referências ao dashboard ainda existem\n";
    }
    
    // 3. Histórico carrega
    if (isset($historico_output) && strlen($historico_output) > 3000) {
        echo "✅ Página de histórico funcional\n";
        $testes_ok++;
    } else {
        echo "❌ Página de histórico com problemas\n";
    }
    
    // 4. Nova pontuação implementada
    if (isset($historico_output) && (strpos($historico_output, '/120') !== false || strpos($historico_output, '/100') !== false)) {
        echo "✅ Nova pontuação (acertos/total) implementada\n";
        $testes_ok++;
    } else {
        echo "❌ Nova pontuação não implementada\n";
    }
    
    echo "\n🎉 RESULTADO FINAL:\n";
    echo "===================\n";
    echo "Testes passaram: $testes_ok/$total_testes\n";
    
    if ($testes_ok === $total_testes) {
        echo "🎉 TODAS AS ALTERAÇÕES FORAM APLICADAS COM SUCESSO!\n\n";
        
        echo "✅ ALTERAÇÕES CONCLUÍDAS:\n";
        echo "=========================\n";
        echo "1. ✅ Arquivo dashboard.php removido\n";
        echo "2. ✅ Botões para dashboard removidos\n";
        echo "3. ✅ Pontuação alterada para acertos/total\n";
        echo "4. ✅ Páginas funcionando corretamente\n\n";
        
        echo "🌐 TESTE NO NAVEGADOR:\n";
        echo "======================\n";
        echo "http://localhost:8080/historico_provas.php\n";
        echo "- Verifique que não há botão 'Dashboard'\n";
        echo "- Verifique que a pontuação mostra 'acertos/total'\n";
        echo "- Exemplo: 15/120 em vez de 75.0%\n\n";
        
        echo "🎯 FUNCIONALIDADES ATUALIZADAS:\n";
        echo "===============================\n";
        echo "✅ Navegação simplificada sem dashboard\n";
        echo "✅ Pontuação mais clara e intuitiva\n";
        echo "✅ Foco no simulador de provas\n";
        echo "✅ Interface mais limpa\n";
        
    } else {
        echo "⚠️ ALGUMAS ALTERAÇÕES FALHARAM\n";
        echo "🔧 Verifique os detalhes acima\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
