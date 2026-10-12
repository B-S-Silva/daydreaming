<?php
/**
 * Script para adicionar suporte a questões dissertativas
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🔧 ADICIONANDO SUPORTE A QUESTÕES DISSERTATIVAS\n";
echo "===============================================\n\n";

try {
    // Conectar ao banco
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // 1. Adicionar campo tipo_questao na tabela questoes
    echo "🔧 Adicionando campo tipo_questao...\n";
    try {
        $pdo->exec("ALTER TABLE questoes ADD COLUMN tipo_questao ENUM('multipla_escolha', 'dissertativa') DEFAULT 'multipla_escolha' AFTER resposta_correta");
        echo "✅ Campo tipo_questao adicionado!\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ Campo tipo_questao já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Adicionar campo resposta_dissertativa na tabela questoes
    echo "🔧 Adicionando campo resposta_dissertativa...\n";
    try {
        $pdo->exec("ALTER TABLE questoes ADD COLUMN resposta_dissertativa TEXT NULL AFTER tipo_questao");
        echo "✅ Campo resposta_dissertativa adicionado!\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ Campo resposta_dissertativa já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 3. Modificar tabela respostas_usuario para suportar respostas dissertativas
    echo "🔧 Adicionando campo resposta_dissertativa_usuario...\n";
    try {
        $pdo->exec("ALTER TABLE respostas_usuario ADD COLUMN resposta_dissertativa_usuario TEXT NULL AFTER resposta_usuario");
        echo "✅ Campo resposta_dissertativa_usuario adicionado!\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ Campo resposta_dissertativa_usuario já existe\n";
        } else {
            throw $e;
        }
    }
    
    // 4. Atualizar questões SAT que não têm alternativas
    echo "\n📝 Identificando questões dissertativas...\n";
    $stmt = $pdo->query("
        SELECT id, numero_questao, enunciado, resposta_correta 
        FROM questoes 
        WHERE tipo_prova = 'sat' 
        AND (alternativa_a = '' OR alternativa_a IS NULL)
        AND (alternativa_b = '' OR alternativa_b IS NULL)
        ORDER BY numero_questao
    ");
    $questoes_dissertativas = $stmt->fetchAll();
    
    echo "🔍 Encontradas " . count($questoes_dissertativas) . " questões dissertativas\n\n";
    
    // 5. Atualizar cada questão dissertativa
    $stmt_update = $pdo->prepare("
        UPDATE questoes 
        SET tipo_questao = 'dissertativa', 
            resposta_dissertativa = ? 
        WHERE id = ?
    ");
    
    foreach ($questoes_dissertativas as $questao) {
        // A resposta correta já está no campo resposta_correta
        $resposta_esperada = $questao['resposta_correta'];
        
        $stmt_update->execute([$resposta_esperada, $questao['id']]);
        
        echo "✅ Questão {$questao['numero_questao']} atualizada como dissertativa\n";
        echo "   📝 Resposta esperada: $resposta_esperada\n";
        echo "   📄 Enunciado: " . substr($questao['enunciado'], 0, 80) . "...\n\n";
    }
    
    // 6. Verificar estatísticas atualizadas
    echo "📊 ESTATÍSTICAS ATUALIZADAS:\n";
    echo "============================\n";
    
    $stmt = $pdo->query("SELECT tipo_questao, COUNT(*) as total FROM questoes WHERE tipo_prova = 'sat' GROUP BY tipo_questao");
    $tipos = $stmt->fetchAll();
    
    foreach ($tipos as $tipo) {
        $emoji = $tipo['tipo_questao'] == 'multipla_escolha' ? '🔘' : '✏️';
        echo "$emoji {$tipo['tipo_questao']}: {$tipo['total']} questões\n";
    }
    
    // 7. Mostrar exemplos de questões dissertativas
    echo "\n✏️ EXEMPLOS DE QUESTÕES DISSERTATIVAS:\n";
    echo "=====================================\n";
    $stmt = $pdo->query("
        SELECT numero_questao, LEFT(enunciado, 100) as enunciado_resumo, resposta_dissertativa 
        FROM questoes 
        WHERE tipo_prova = 'sat' AND tipo_questao = 'dissertativa' 
        ORDER BY numero_questao 
        LIMIT 3
    ");
    $exemplos = $stmt->fetchAll();
    
    foreach ($exemplos as $exemplo) {
        echo "📝 Questão {$exemplo['numero_questao']}:\n";
        echo "   ❓ {$exemplo['enunciado_resumo']}...\n";
        echo "   ✅ Resposta: {$exemplo['resposta_dissertativa']}\n\n";
    }
    
    echo "🎉 MODIFICAÇÕES CONCLUÍDAS COM SUCESSO!\n\n";
    
    echo "🔧 PRÓXIMOS PASSOS:\n";
    echo "===================\n";
    echo "1. ✅ Estrutura do banco atualizada\n";
    echo "2. ✅ Questões dissertativas identificadas\n";
    echo "3. 🔄 Atualizar interface do simulador\n";
    echo "4. 🔄 Atualizar lógica de correção\n";
    echo "5. 🔄 Testar funcionalidade\n\n";
    
    echo "🌐 O simulador será atualizado para suportar:\n";
    echo "- 🔘 Questões de múltipla escolha (clique)\n";
    echo "- ✏️ Questões dissertativas (campo de texto)\n";
    echo "- 🎯 Correção automática para ambos os tipos\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
