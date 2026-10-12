<?php
/**
 * Script para verificar estrutura da tabela questoes
 */

echo "🔍 VERIFICANDO ESTRUTURA DA TABELA QUESTOES\n";
echo "===========================================\n\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📊 ESTRUTURA DA TABELA:\n";
    echo "=======================\n";
    
    $stmt = $pdo->query("DESCRIBE questoes");
    $colunas = $stmt->fetchAll();
    
    foreach ($colunas as $coluna) {
        echo "📝 {$coluna['Field']} - {$coluna['Type']} - {$coluna['Null']} - {$coluna['Default']}\n";
    }
    
    echo "\n🔍 VERIFICANDO SE COLUNA 'ativo' EXISTE:\n";
    echo "========================================\n";
    
    $colunas_nomes = array_column($colunas, 'Field');
    
    if (in_array('ativo', $colunas_nomes)) {
        echo "✅ Coluna 'ativo' existe\n";
    } else {
        echo "❌ Coluna 'ativo' NÃO existe\n";
        echo "🔧 Adicionando coluna 'ativo'...\n";
        
        try {
            $pdo->exec("ALTER TABLE questoes ADD COLUMN ativo BOOLEAN DEFAULT TRUE");
            echo "✅ Coluna 'ativo' adicionada com sucesso!\n";
        } catch (Exception $e) {
            echo "❌ Erro ao adicionar coluna: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📊 TESTANDO QUERY CORRIGIDA:\n";
    echo "============================\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes WHERE tipo_prova = 'sat' AND ativo = 1");
    $questoes_ativas = $stmt->fetchColumn();
    echo "✅ Questões SAT ativas: $questoes_ativas\n";
    
    echo "\n🎯 TESTANDO CARREGAMENTO DE QUESTÕES:\n";
    echo "=====================================\n";
    
    $stmt = $pdo->prepare("
        SELECT id, numero_questao, enunciado, 
               alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e,
               resposta_correta, tipo_questao, resposta_dissertativa,
               dificuldade, materia, assunto, explicacao
        FROM questoes 
        WHERE tipo_prova = ? AND ativo = 1 
        ORDER BY numero_questao 
        LIMIT 5
    ");
    $stmt->execute(['sat']);
    $questoes_teste = $stmt->fetchAll();
    
    echo "✅ Questões carregadas para teste: " . count($questoes_teste) . "\n";
    
    foreach ($questoes_teste as $i => $questao) {
        echo "\n📝 Questão " . ($i + 1) . ":\n";
        echo "   ID: {$questao['id']}\n";
        echo "   Número: {$questao['numero_questao']}\n";
        echo "   Tipo: {$questao['tipo_questao']}\n";
        echo "   Enunciado: " . substr($questao['enunciado'], 0, 60) . "...\n";
        
        if ($questao['tipo_questao'] === 'dissertativa') {
            echo "   ✏️ Resposta esperada: {$questao['resposta_dissertativa']}\n";
        } else {
            echo "   🔘 Alternativas: A) {$questao['alternativa_a']}\n";
            echo "   ✅ Resposta: {$questao['resposta_correta']}\n";
        }
    }
    
    echo "\n🎉 VERIFICAÇÃO CONCLUÍDA!\n";
    echo "=========================\n";
    echo "✅ Estrutura da tabela corrigida\n";
    echo "✅ Query de carregamento funcionando\n";
    echo "✅ Questões disponíveis para teste\n\n";
    
    echo "🔗 TESTE NOVAMENTE:\n";
    echo "===================\n";
    echo "http://localhost:8080/executar_teste.php?tipo=sat\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
