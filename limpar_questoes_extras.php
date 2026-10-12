<?php
/**
 * Script para limpar questões extras e preparar para arquivos JSON específicos
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🧹 LIMPANDO QUESTÕES EXTRAS E PREPARANDO PARA JSON\n";
echo "==================================================\n\n";

try {
    // Conectar ao banco
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // 1. Verificar quantas questões SAT existem no JSON original
    echo "📄 VERIFICANDO ARQUIVO JSON ORIGINAL:\n";
    echo "====================================\n";
    
    $questoes_file = 'exames/SAT/Exame_SAT_Test_4.json';
    if (file_exists($questoes_file)) {
        $questoes_json = json_decode(file_get_contents($questoes_file), true);
        
        $total_questoes_json = 0;
        foreach ($questoes_json['sections'] as $section) {
            foreach ($section['modules'] as $module) {
                $total_questoes_json += count($module['questions']);
            }
        }
        
        echo "✅ Questões no arquivo JSON: $total_questoes_json\n";
    } else {
        echo "❌ Arquivo JSON não encontrado!\n";
        $total_questoes_json = 0;
    }
    
    // 2. Verificar questões atuais no banco
    echo "\n📊 QUESTÕES ATUAIS NO BANCO:\n";
    echo "============================\n";
    
    $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova ORDER BY tipo_prova");
    $questoes_atuais = $stmt->fetchAll();
    
    foreach ($questoes_atuais as $tipo) {
        echo "📝 {$tipo['tipo_prova']}: {$tipo['total']} questões\n";
    }
    
    // 3. Remover questões extras do SAT (manter apenas as do JSON)
    if ($total_questoes_json > 0) {
        echo "\n🔧 REMOVENDO QUESTÕES SAT EXTRAS:\n";
        echo "=================================\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM questoes WHERE tipo_prova = 'sat'");
        $total_sat_banco = $stmt->fetchColumn();
        
        echo "📊 SAT no banco: $total_sat_banco\n";
        echo "📄 SAT no JSON: $total_questoes_json\n";
        
        if ($total_sat_banco > $total_questoes_json) {
            $extras = $total_sat_banco - $total_questoes_json;
            echo "⚠️ Encontradas $extras questões extras do SAT\n";
            
            // Remover questões extras (manter apenas as primeiras do JSON)
            $stmt = $pdo->prepare("
                DELETE FROM questoes 
                WHERE tipo_prova = 'sat' 
                AND numero_questao > ?
            ");
            $stmt->execute([$total_questoes_json]);
            
            echo "✅ Questões SAT extras removidas\n";
        } else {
            echo "✅ Quantidade de questões SAT está correta\n";
        }
    }
    
    // 4. Remover todas as questões dos outros exames
    echo "\n🗑️ REMOVENDO QUESTÕES DOS OUTROS EXAMES:\n";
    echo "========================================\n";
    
    $outros_exames = ['toefl', 'ielts', 'gre'];
    
    foreach ($outros_exames as $exame) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE tipo_prova = ?");
        $stmt->execute([$exame]);
        $total_antes = $stmt->fetchColumn();
        
        if ($total_antes > 0) {
            $stmt = $pdo->prepare("DELETE FROM questoes WHERE tipo_prova = ?");
            $stmt->execute([$exame]);
            
            echo "🗑️ $exame: $total_antes questões removidas\n";
        } else {
            echo "ℹ️ $exame: Nenhuma questão encontrada\n";
        }
    }
    
    // 5. Limpar respostas órfãs (de questões que não existem mais)
    echo "\n🧹 LIMPANDO RESPOSTAS ÓRFÃS:\n";
    echo "============================\n";
    
    $stmt = $pdo->query("
        DELETE ru FROM respostas_usuario ru
        LEFT JOIN questoes q ON ru.questao_id = q.id
        WHERE q.id IS NULL
    ");
    $orfas_removidas = $stmt->rowCount();
    echo "🗑️ Respostas órfãs removidas: $orfas_removidas\n";
    
    // 6. Limpar sessões de teste dos exames removidos
    echo "\n🧹 LIMPANDO SESSÕES DOS EXAMES REMOVIDOS:\n";
    echo "=========================================\n";
    
    foreach ($outros_exames as $exame) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessoes_teste WHERE tipo_prova = ?");
        $stmt->execute([$exame]);
        $sessoes_antes = $stmt->fetchColumn();
        
        if ($sessoes_antes > 0) {
            // Primeiro remover respostas das sessões
            $stmt = $pdo->prepare("
                DELETE ru FROM respostas_usuario ru
                INNER JOIN sessoes_teste st ON ru.sessao_id = st.id
                WHERE st.tipo_prova = ?
            ");
            $stmt->execute([$exame]);
            
            // Depois remover as sessões
            $stmt = $pdo->prepare("DELETE FROM sessoes_teste WHERE tipo_prova = ?");
            $stmt->execute([$exame]);
            
            echo "🗑️ $exame: $sessoes_antes sessões removidas\n";
        }
    }
    
    // 7. Verificar estado final
    echo "\n📊 ESTADO FINAL DO BANCO:\n";
    echo "=========================\n";
    
    $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova ORDER BY tipo_prova");
    $questoes_finais = $stmt->fetchAll();
    
    if (empty($questoes_finais)) {
        echo "ℹ️ Nenhuma questão encontrada no banco\n";
    } else {
        foreach ($questoes_finais as $tipo) {
            echo "📝 {$tipo['tipo_prova']}: {$tipo['total']} questões\n";
        }
    }
    
    // Verificar questões SAT por tipo
    $stmt = $pdo->query("
        SELECT tipo_questao, COUNT(*) as total 
        FROM questoes 
        WHERE tipo_prova = 'sat' 
        GROUP BY tipo_questao
    ");
    $sat_tipos = $stmt->fetchAll();
    
    echo "\n📋 QUESTÕES SAT POR TIPO:\n";
    echo "=========================\n";
    foreach ($sat_tipos as $tipo) {
        $emoji = $tipo['tipo_questao'] === 'dissertativa' ? '✏️' : '🔘';
        echo "$emoji {$tipo['tipo_questao']}: {$tipo['total']} questões\n";
    }
    
    // 8. Verificar espaço liberado
    echo "\n💾 OTIMIZANDO TABELAS:\n";
    echo "======================\n";
    
    $pdo->exec("OPTIMIZE TABLE questoes");
    $pdo->exec("OPTIMIZE TABLE respostas_usuario");
    $pdo->exec("OPTIMIZE TABLE sessoes_teste");
    
    echo "✅ Tabelas otimizadas\n";
    
    echo "\n🎉 LIMPEZA CONCLUÍDA COM SUCESSO!\n";
    echo "=================================\n";
    echo "✅ Questões SAT extras removidas\n";
    echo "✅ Questões TOEFL, IELTS, GRE removidas\n";
    echo "✅ Respostas órfãs limpas\n";
    echo "✅ Sessões antigas removidas\n";
    echo "✅ Banco otimizado\n\n";
    
    echo "📋 PRÓXIMOS PASSOS:\n";
    echo "===================\n";
    echo "1. ✅ SAT: Questões do JSON carregadas e limpas\n";
    echo "2. 🔄 TOEFL: Aguardando arquivo JSON\n";
    echo "3. 🔄 IELTS: Aguardando arquivo JSON\n";
    echo "4. 🔄 GRE: Aguardando arquivo JSON\n\n";
    
    echo "🌐 TESTE O SAT:\n";
    echo "===============\n";
    echo "http://localhost:8080/simulador_provas.php\n";
    echo "- Apenas o SAT terá questões funcionais\n";
    echo "- Outros exames mostrarão mensagem de 'em breve'\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
