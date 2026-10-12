<?php
/**
 * Script para carregar questões do SAT do arquivo JSON
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "📝 CARREGANDO QUESTÕES DO SAT DO ARQUIVO JSON\n";
echo "============================================\n\n";

try {
    // Conectar ao banco
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Ler arquivo de questões
    $questoes_file = 'exames/SAT/Exame_SAT_Test_4.json';
    $respostas_file = 'exames/SAT/Answers_SAT_Test_4.json';
    
    if (!file_exists($questoes_file)) {
        throw new Exception("Arquivo de questões não encontrado: $questoes_file");
    }
    
    if (!file_exists($respostas_file)) {
        throw new Exception("Arquivo de respostas não encontrado: $respostas_file");
    }
    
    echo "📄 Lendo arquivos JSON...\n";
    $questoes_json = json_decode(file_get_contents($questoes_file), true);
    $respostas_json = json_decode(file_get_contents($respostas_file), true);
    
    if (!$questoes_json || !$respostas_json) {
        throw new Exception("Erro ao decodificar arquivos JSON");
    }
    
    echo "✅ Arquivos JSON carregados com sucesso!\n\n";
    
    // Limpar questões SAT existentes
    echo "🗑️ Removendo questões SAT existentes...\n";
    $stmt = $pdo->prepare("DELETE FROM questoes WHERE tipo_prova = 'sat'");
    $stmt->execute();
    echo "✅ Questões antigas removidas!\n\n";
    
    // Preparar statement para inserção
    $stmt = $pdo->prepare("
        INSERT INTO questoes (
            tipo_prova, numero_questao, enunciado, 
            alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e,
            resposta_correta, dificuldade, materia, assunto, explicacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $questoes_inseridas = 0;
    $questao_numero = 1;
    
    // Processar cada seção
    foreach ($questoes_json['sections'] as $section) {
        $section_name = $section['section_name'];
        echo "📚 Processando seção: $section_name\n";
        
        // Processar cada módulo
        foreach ($section['modules'] as $module_index => $module) {
            $module_name = $module['module_name'];
            echo "  📖 Processando módulo: $module_name\n";
            
            // Determinar chave das respostas
            $answer_key = '';
            if (strpos($section_name, 'Reading') !== false) {
                $answer_key = 'reading_writing_module' . ($module_index + 1);
            } elseif (strpos($section_name, 'Math') !== false) {
                $answer_key = 'math_module' . ($module_index + 1);
            }
            
            // Processar cada questão
            foreach ($module['questions'] as $question) {
                $question_num = $question['question_number'];
                $question_text = $question['question_text'];
                $options = $question['options'];
                
                // Extrair alternativas
                $alt_a = $alt_b = $alt_c = $alt_d = $alt_e = '';
                
                foreach ($options as $option) {
                    if (strpos($option, 'A)') === 0) {
                        $alt_a = trim(substr($option, 2));
                    } elseif (strpos($option, 'B)') === 0) {
                        $alt_b = trim(substr($option, 2));
                    } elseif (strpos($option, 'C)') === 0) {
                        $alt_c = trim(substr($option, 2));
                    } elseif (strpos($option, 'D)') === 0) {
                        $alt_d = trim(substr($option, 2));
                    } elseif (strpos($option, 'E)') === 0) {
                        $alt_e = trim(substr($option, 2));
                    }
                }
                
                // Obter resposta correta
                $resposta_correta = '';
                if (isset($respostas_json['answers'][$answer_key][$question_num])) {
                    $resposta_correta = strtolower($respostas_json['answers'][$answer_key][$question_num]);
                }
                
                // Determinar dificuldade baseada no módulo
                $dificuldade = 'medio';
                if (strpos($module_name, 'Module 1') !== false) {
                    $dificuldade = 'facil';
                } elseif (strpos($module_name, 'Module 2') !== false) {
                    $dificuldade = 'medio';
                } elseif (strpos($module_name, 'Module 3') !== false) {
                    $dificuldade = 'dificil';
                }
                
                // Determinar matéria
                $materia = 'Reading and Writing';
                if (strpos($section_name, 'Math') !== false) {
                    $materia = 'Mathematics';
                }
                
                // Inserir questão
                $stmt->execute([
                    'sat',                          // tipo_prova
                    $questao_numero,               // numero_questao
                    $question_text,                // enunciado
                    $alt_a,                        // alternativa_a
                    $alt_b,                        // alternativa_b
                    $alt_c,                        // alternativa_c
                    $alt_d,                        // alternativa_d
                    $alt_e ?: null,                // alternativa_e
                    $resposta_correta,             // resposta_correta
                    $dificuldade,                  // dificuldade
                    $materia,                      // materia
                    $section_name,                 // assunto
                    "Questão do SAT Practice Test #4" // explicacao
                ]);
                
                $questoes_inseridas++;
                $questao_numero++;
                
                echo "    ✅ Questão $questao_numero inserida (Resposta: " . strtoupper($resposta_correta) . ")\n";
            }
        }
    }
    
    echo "\n📊 RESUMO DA IMPORTAÇÃO:\n";
    echo "========================\n";
    echo "✅ Total de questões inseridas: $questoes_inseridas\n";
    
    // Verificar questões inseridas
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes WHERE tipo_prova = 'sat'");
    $total_banco = $stmt->fetchColumn();
    echo "📊 Total no banco de dados: $total_banco\n";
    
    // Verificar distribuição por dificuldade
    echo "\n📈 DISTRIBUIÇÃO POR DIFICULDADE:\n";
    echo "=================================\n";
    $stmt = $pdo->query("SELECT dificuldade, COUNT(*) as total FROM questoes WHERE tipo_prova = 'sat' GROUP BY dificuldade");
    $distribuicao = $stmt->fetchAll();
    
    foreach ($distribuicao as $nivel) {
        echo "📝 {$nivel['dificuldade']}: {$nivel['total']} questões\n";
    }
    
    // Verificar distribuição por matéria
    echo "\n📚 DISTRIBUIÇÃO POR MATÉRIA:\n";
    echo "============================\n";
    $stmt = $pdo->query("SELECT materia, COUNT(*) as total FROM questoes WHERE tipo_prova = 'sat' GROUP BY materia");
    $materias = $stmt->fetchAll();
    
    foreach ($materias as $materia) {
        echo "📖 {$materia['materia']}: {$materia['total']} questões\n";
    }
    
    echo "\n🎉 IMPORTAÇÃO CONCLUÍDA COM SUCESSO!\n\n";
    
    echo "🌐 Agora você pode testar o SAT em:\n";
    echo "http://localhost:8080/simulador_provas.php\n\n";
    
    echo "🔍 Para verificar as questões importadas:\n";
    echo "SELECT * FROM questoes WHERE tipo_prova = 'sat' LIMIT 5;\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
