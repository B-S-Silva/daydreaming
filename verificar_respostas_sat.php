<?php
/**
 * Script para verificar e corrigir respostas do SAT
 */

echo "🔍 VERIFICANDO RESPOSTAS DO SAT\n";
echo "===============================\n\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Verificar respostas atuais no banco
    echo "📊 VERIFICANDO RESPOSTAS NO BANCO:\n";
    echo "==================================\n";
    
    $stmt = $pdo->query("
        SELECT numero_questao, resposta_correta, tipo_questao, resposta_dissertativa 
        FROM questoes 
        WHERE tipo_prova = 'sat' 
        ORDER BY numero_questao 
        LIMIT 10
    ");
    $questoes_banco = $stmt->fetchAll();
    
    foreach ($questoes_banco as $questao) {
        $resposta = $questao['tipo_questao'] === 'dissertativa' 
            ? $questao['resposta_dissertativa'] 
            : $questao['resposta_correta'];
        echo "📝 Questão {$questao['numero_questao']}: $resposta ({$questao['tipo_questao']})\n";
    }
    
    // Ler arquivo de respostas
    echo "\n📄 CARREGANDO ARQUIVO DE RESPOSTAS:\n";
    echo "===================================\n";
    
    $respostas_file = 'exames/SAT/Answers_SAT_Test_4.json';
    if (!file_exists($respostas_file)) {
        echo "❌ Arquivo de respostas não encontrado: $respostas_file\n";
        exit(1);
    }
    
    $respostas_json = json_decode(file_get_contents($respostas_file), true);
    if (!$respostas_json) {
        echo "❌ Erro ao decodificar arquivo de respostas\n";
        exit(1);
    }
    
    echo "✅ Arquivo de respostas carregado\n";
    
    // Mapear respostas por número de questão
    $respostas_corretas = [];
    $questao_numero = 1;
    
    foreach ($respostas_json['answers'] as $modulo => $respostas) {
        echo "📚 Processando módulo: $modulo\n";
        foreach ($respostas as $num_questao => $resposta) {
            $respostas_corretas[$questao_numero] = strtolower($resposta);
            $questao_numero++;
        }
    }
    
    echo "✅ Total de respostas mapeadas: " . count($respostas_corretas) . "\n\n";
    
    // Verificar discrepâncias
    echo "🔍 VERIFICANDO DISCREPÂNCIAS:\n";
    echo "=============================\n";
    
    $stmt = $pdo->query("SELECT numero_questao, resposta_correta, tipo_questao FROM questoes WHERE tipo_prova = 'sat' ORDER BY numero_questao");
    $questoes_todas = $stmt->fetchAll();
    
    $discrepancias = 0;
    $atualizacoes = [];
    
    foreach ($questoes_todas as $questao) {
        $num = $questao['numero_questao'];
        $resposta_banco = strtolower($questao['resposta_correta']);
        $resposta_json = $respostas_corretas[$num] ?? null;
        
        if ($questao['tipo_questao'] === 'multipla_escolha' && $resposta_json) {
            if ($resposta_banco !== $resposta_json) {
                echo "⚠️ Questão $num: Banco='$resposta_banco' vs JSON='$resposta_json'\n";
                $atualizacoes[] = [$num, $resposta_json];
                $discrepancias++;
            }
        }
    }
    
    if ($discrepancias > 0) {
        echo "\n🔧 CORRIGINDO DISCREPÂNCIAS:\n";
        echo "============================\n";
        
        $stmt = $pdo->prepare("UPDATE questoes SET resposta_correta = ? WHERE numero_questao = ? AND tipo_prova = 'sat'");
        
        foreach ($atualizacoes as $atualizacao) {
            $stmt->execute([$atualizacao[1], $atualizacao[0]]);
            echo "✅ Questão {$atualizacao[0]} atualizada para '{$atualizacao[1]}'\n";
        }
        
        echo "\n✅ $discrepancias respostas corrigidas\n";
    } else {
        echo "✅ Todas as respostas estão corretas no banco\n";
    }
    
    echo "\n📊 ESTATÍSTICAS FINAIS:\n";
    echo "=======================\n";
    
    $stmt = $pdo->query("
        SELECT 
            tipo_questao,
            COUNT(*) as total,
            COUNT(CASE WHEN resposta_correta IS NOT NULL AND resposta_correta != '' THEN 1 END) as com_resposta
        FROM questoes 
        WHERE tipo_prova = 'sat' 
        GROUP BY tipo_questao
    ");
    $stats = $stmt->fetchAll();
    
    foreach ($stats as $stat) {
        echo "📝 {$stat['tipo_questao']}: {$stat['com_resposta']}/{$stat['total']} com resposta\n";
    }
    
    echo "\n🎉 VERIFICAÇÃO CONCLUÍDA!\n";
    echo "=========================\n";
    echo "✅ Respostas do arquivo JSON correlacionadas\n";
    echo "✅ Banco de dados atualizado\n";
    echo "✅ Sistema pronto para correção automática\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
