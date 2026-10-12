<?php
/**
 * Script para carregar questões do SAT no banco de dados
 * Execute após setup_database.php
 */

echo "📚 CARREGANDO QUESTÕES DO SAT\n";
echo "=============================\n\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Verificar se já existem questões
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes WHERE tipo_prova = 'sat'");
    $questoes_existentes = $stmt->fetchColumn();
    
    if ($questoes_existentes > 0) {
        echo "ℹ️ Já existem $questoes_existentes questões SAT no banco.\n";
        echo "🔄 Deseja recarregar? (s/n): ";
        $resposta = trim(fgets(STDIN));
        
        if (strtolower($resposta) !== 's') {
            echo "❌ Operação cancelada.\n";
            exit;
        }
        
        echo "🗑️ Removendo questões existentes...\n";
        $pdo->exec("DELETE FROM questoes WHERE tipo_prova = 'sat'");
        echo "✅ Questões removidas\n\n";
    }
    
    // Carregar questões do arquivo JSON
    echo "📄 CARREGANDO QUESTÕES DO ARQUIVO JSON...\n";
    
    $arquivo_questoes = 'exames/SAT/SAT_Test_4.json';
    $arquivo_respostas = 'exames/SAT/Answers_SAT_Test_4.json';
    
    if (!file_exists($arquivo_questoes)) {
        echo "❌ Arquivo de questões não encontrado: $arquivo_questoes\n";
        exit(1);
    }
    
    if (!file_exists($arquivo_respostas)) {
        echo "❌ Arquivo de respostas não encontrado: $arquivo_respostas\n";
        exit(1);
    }
    
    // Ler questões
    $questoes_json = json_decode(file_get_contents($arquivo_questoes), true);
    if (!$questoes_json) {
        echo "❌ Erro ao decodificar arquivo de questões\n";
        exit(1);
    }
    
    // Ler respostas
    $respostas_json = json_decode(file_get_contents($arquivo_respostas), true);
    if (!$respostas_json) {
        echo "❌ Erro ao decodificar arquivo de respostas\n";
        exit(1);
    }
    
    echo "✅ Arquivos JSON carregados\n";
    
    // Mapear respostas por número de questão
    $respostas_map = [];
    $questao_numero = 1;
    
    foreach ($respostas_json['answers'] as $modulo => $respostas) {
        foreach ($respostas as $num_questao => $resposta) {
            $respostas_map[$questao_numero] = strtolower($resposta);
            $questao_numero++;
        }
    }
    
    echo "📊 Total de respostas mapeadas: " . count($respostas_map) . "\n\n";
    
    // Processar questões
    echo "💾 INSERINDO QUESTÕES NO BANCO...\n";
    
    $stmt = $pdo->prepare("
        INSERT INTO questoes (
            numero_questao, tipo_prova, enunciado, 
            alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e,
            resposta_correta, tipo_questao, materia, assunto, dificuldade
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $questoes_inseridas = 0;
    $questao_atual = 1;
    
    foreach ($questoes_json as $modulo => $questoes_modulo) {
        echo "📚 Processando módulo: $modulo\n";
        
        foreach ($questoes_modulo as $questao) {
            // Determinar matéria baseada no módulo
            $materia = 'Reading'; // Padrão
            if (strpos(strtolower($modulo), 'math') !== false) {
                $materia = 'Math';
            } elseif (strpos(strtolower($modulo), 'writing') !== false) {
                $materia = 'Writing';
            }
            
            // Extrair enunciado
            $enunciado = $questao['question'] ?? $questao['passage'] ?? 'Questão sem enunciado';
            
            // Verificar se é múltipla escolha ou dissertativa
            $tem_alternativas = isset($questao['choices']) && is_array($questao['choices']) && count($questao['choices']) > 0;
            
            if ($tem_alternativas) {
                // Questão de múltipla escolha
                $alternativas = array_pad($questao['choices'], 5, null);
                
                $resposta_correta = $respostas_map[$questao_atual] ?? 'a';
                
                $stmt->execute([
                    $questao_atual,
                    'sat',
                    $enunciado,
                    $alternativas[0] ?? null,
                    $alternativas[1] ?? null,
                    $alternativas[2] ?? null,
                    $alternativas[3] ?? null,
                    $alternativas[4] ?? null,
                    $resposta_correta,
                    'multipla_escolha',
                    $materia,
                    $questao['topic'] ?? $modulo,
                    'medio'
                ]);
            } else {
                // Questão dissertativa
                $resposta_dissertativa = $respostas_map[$questao_atual] ?? 'Resposta modelo não disponível';
                
                $stmt->execute([
                    $questao_atual,
                    'sat',
                    $enunciado,
                    null, null, null, null, null,
                    $resposta_dissertativa,
                    'dissertativa',
                    $materia,
                    $questao['topic'] ?? $modulo,
                    'medio'
                ]);
            }
            
            $questoes_inseridas++;
            $questao_atual++;
            
            if ($questoes_inseridas % 10 == 0) {
                echo "   ✅ $questoes_inseridas questões inseridas...\n";
            }
        }
    }
    
    echo "\n🎉 QUESTÕES CARREGADAS COM SUCESSO!\n";
    echo "===================================\n";
    echo "📊 Total inserido: $questoes_inseridas questões\n";
    echo "🎓 Tipo: SAT\n";
    echo "📚 Módulos processados: " . count($questoes_json) . "\n\n";
    
    // Verificar distribuição por matéria
    echo "📊 DISTRIBUIÇÃO POR MATÉRIA:\n";
    echo "============================\n";
    
    $stmt = $pdo->query("
        SELECT materia, COUNT(*) as total, 
               SUM(CASE WHEN tipo_questao = 'multipla_escolha' THEN 1 ELSE 0 END) as multipla_escolha,
               SUM(CASE WHEN tipo_questao = 'dissertativa' THEN 1 ELSE 0 END) as dissertativa
        FROM questoes 
        WHERE tipo_prova = 'sat' 
        GROUP BY materia
    ");
    
    while ($row = $stmt->fetch()) {
        echo "📚 {$row['materia']}: {$row['total']} questões ({$row['multipla_escolha']} múltipla escolha, {$row['dissertativa']} dissertativa)\n";
    }
    
    echo "\n🔍 VERIFICAÇÃO FINAL:\n";
    echo "=====================\n";
    
    // Verificar questões com respostas
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN resposta_correta IS NOT NULL AND resposta_correta != '' THEN 1 ELSE 0 END) as com_resposta
        FROM questoes 
        WHERE tipo_prova = 'sat'
    ");
    $verificacao = $stmt->fetch();
    
    echo "✅ Total de questões: {$verificacao['total']}\n";
    echo "✅ Com resposta: {$verificacao['com_resposta']}\n";
    echo "✅ Taxa de completude: " . round(($verificacao['com_resposta'] / $verificacao['total']) * 100, 1) . "%\n\n";
    
    echo "🌐 PRÓXIMOS PASSOS:\n";
    echo "===================\n";
    echo "1. Acesse: http://localhost:8080/\n";
    echo "2. Faça login (admin/admin123 ou teste/teste123)\n";
    echo "3. Vá para o simulador de provas\n";
    echo "4. Teste o SAT com as questões carregadas\n\n";
    
    echo "🎉 SISTEMA PRONTO PARA USO!\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    
    echo "🔧 POSSÍVEIS SOLUÇÕES:\n";
    echo "======================\n";
    echo "1. Execute primeiro: php setup_database.php\n";
    echo "2. Verifique se os arquivos JSON existem na pasta exames/SAT/\n";
    echo "3. Confirme as permissões de leitura dos arquivos\n";
    echo "4. Verifique a conexão com o banco de dados\n";
}
?>
