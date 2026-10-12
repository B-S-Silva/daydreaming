<?php
/**
 * Script genérico para carregar questões de qualquer exame a partir de arquivo JSON
 * 
 * Uso: php carregar_questoes_json_generico.php [tipo_exame]
 * Exemplo: php carregar_questoes_json_generico.php toefl
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

// Obter tipo de exame dos argumentos da linha de comando
$tipo_exame = $argv[1] ?? null;

if (!$tipo_exame) {
    echo "❌ ERRO: Tipo de exame não especificado\n";
    echo "Uso: php carregar_questoes_json_generico.php [tipo_exame]\n";
    echo "Exemplos:\n";
    echo "  php carregar_questoes_json_generico.php toefl\n";
    echo "  php carregar_questoes_json_generico.php ielts\n";
    echo "  php carregar_questoes_json_generico.php gre\n";
    exit(1);
}

$tipo_exame = strtolower($tipo_exame);

echo "📝 CARREGANDO QUESTÕES DO " . strtoupper($tipo_exame) . " DO ARQUIVO JSON\n";
echo "================================================================\n\n";

try {
    // Conectar ao banco
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Definir caminhos dos arquivos
    $pasta_exame = "exames/" . strtoupper($tipo_exame);
    $questoes_file = "$pasta_exame/Exame_{$tipo_exame}_Test.json";
    $respostas_file = "$pasta_exame/Answers_{$tipo_exame}_Test.json";
    
    // Verificar se os arquivos existem
    echo "📄 Verificando arquivos...\n";
    
    if (!is_dir($pasta_exame)) {
        echo "❌ Pasta não encontrada: $pasta_exame\n";
        echo "Crie a pasta e coloque os arquivos JSON nela.\n";
        exit(1);
    }
    
    if (!file_exists($questoes_file)) {
        echo "❌ Arquivo de questões não encontrado: $questoes_file\n";
        echo "Coloque o arquivo JSON de questões na pasta.\n";
        exit(1);
    }
    
    if (!file_exists($respostas_file)) {
        echo "❌ Arquivo de respostas não encontrado: $respostas_file\n";
        echo "Coloque o arquivo JSON de respostas na pasta.\n";
        exit(1);
    }
    
    echo "✅ Arquivos encontrados!\n\n";
    
    // Ler arquivos JSON
    echo "📖 Lendo arquivos JSON...\n";
    $questoes_json = json_decode(file_get_contents($questoes_file), true);
    $respostas_json = json_decode(file_get_contents($respostas_file), true);
    
    if (!$questoes_json || !$respostas_json) {
        echo "❌ Erro ao decodificar arquivos JSON\n";
        exit(1);
    }
    
    echo "✅ Arquivos JSON carregados!\n\n";
    
    // Limpar questões existentes do tipo
    echo "🗑️ Removendo questões existentes do $tipo_exame...\n";
    $stmt = $pdo->prepare("DELETE FROM questoes WHERE tipo_prova = ?");
    $stmt->execute([$tipo_exame]);
    echo "✅ Questões antigas removidas!\n\n";
    
    // Preparar statement para inserção
    $stmt = $pdo->prepare("
        INSERT INTO questoes (
            tipo_prova, numero_questao, enunciado, 
            alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e,
            resposta_correta, tipo_questao, resposta_dissertativa,
            dificuldade, materia, assunto, explicacao, ativa
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    $questoes_inseridas = 0;
    $questao_numero = 1;
    
    // Processar estrutura JSON (adaptável para diferentes formatos)
    if (isset($questoes_json['sections'])) {
        // Formato SAT (com seções e módulos)
        foreach ($questoes_json['sections'] as $section_index => $section) {
            $section_name = $section['section_name'] ?? "Seção " . ($section_index + 1);
            echo "📚 Processando seção: $section_name\n";
            
            $modules = $section['modules'] ?? [$section]; // Se não há módulos, trata a seção como um módulo
            
            foreach ($modules as $module_index => $module) {
                $module_name = $module['module_name'] ?? "Módulo " . ($module_index + 1);
                echo "  📖 Processando módulo: $module_name\n";
                
                $questions = $module['questions'] ?? $module['questoes'] ?? [];
                
                foreach ($questions as $question) {
                    $questoes_inseridas += processarQuestao($question, $questao_numero, $tipo_exame, $section_name, $respostas_json, $stmt);
                    $questao_numero++;
                }
            }
        }
    } else {
        // Formato simples (lista direta de questões)
        $questions = $questoes_json['questions'] ?? $questoes_json['questoes'] ?? $questoes_json;
        
        foreach ($questions as $question) {
            $questoes_inseridas += processarQuestao($question, $questao_numero, $tipo_exame, "Geral", $respostas_json, $stmt);
            $questao_numero++;
        }
    }
    
    echo "\n📊 RESUMO DA IMPORTAÇÃO:\n";
    echo "========================\n";
    echo "✅ Total de questões inseridas: $questoes_inseridas\n";
    
    // Verificar questões inseridas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM questoes WHERE tipo_prova = ?");
    $stmt->execute([$tipo_exame]);
    $total_banco = $stmt->fetchColumn();
    echo "📊 Total no banco de dados: $total_banco\n";
    
    // Verificar distribuição por tipo
    $stmt = $pdo->prepare("
        SELECT tipo_questao, COUNT(*) as total 
        FROM questoes 
        WHERE tipo_prova = ? 
        GROUP BY tipo_questao
    ");
    $stmt->execute([$tipo_exame]);
    $distribuicao = $stmt->fetchAll();
    
    echo "\n📈 DISTRIBUIÇÃO POR TIPO:\n";
    echo "=========================\n";
    foreach ($distribuicao as $tipo) {
        $emoji = $tipo['tipo_questao'] === 'dissertativa' ? '✏️' : '🔘';
        echo "$emoji {$tipo['tipo_questao']}: {$tipo['total']} questões\n";
    }
    
    echo "\n🎉 IMPORTAÇÃO CONCLUÍDA COM SUCESSO!\n\n";
    
    echo "🌐 Agora você pode testar o " . strtoupper($tipo_exame) . " em:\n";
    echo "http://localhost:8080/simulador_provas.php\n\n";
    
    echo "🔍 Para verificar as questões importadas:\n";
    echo "SELECT * FROM questoes WHERE tipo_prova = '$tipo_exame' LIMIT 5;\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}

/**
 * Função para processar uma questão individual
 */
function processarQuestao($question, $numero, $tipo_exame, $secao, $respostas_json, $stmt) {
    // Extrair dados da questão
    $question_text = $question['question_text'] ?? $question['enunciado'] ?? '';
    $options = $question['options'] ?? $question['alternativas'] ?? [];
    
    // Determinar se é dissertativa (sem alternativas ou com campo de resposta livre)
    $eh_dissertativa = empty($options) || isset($question['resposta_livre']) || isset($question['answer_type']) && $question['answer_type'] === 'text';
    
    // Extrair alternativas
    $alt_a = $alt_b = $alt_c = $alt_d = $alt_e = '';
    
    if (!$eh_dissertativa && is_array($options)) {
        foreach ($options as $i => $option) {
            $letra = chr(65 + $i); // A, B, C, D, E
            if ($letra === 'A') $alt_a = is_string($option) ? $option : ($option['text'] ?? '');
            if ($letra === 'B') $alt_b = is_string($option) ? $option : ($option['text'] ?? '');
            if ($letra === 'C') $alt_c = is_string($option) ? $option : ($option['text'] ?? '');
            if ($letra === 'D') $alt_d = is_string($option) ? $option : ($option['text'] ?? '');
            if ($letra === 'E') $alt_e = is_string($option) ? $option : ($option['text'] ?? '');
        }
    }
    
    // Obter resposta correta
    $resposta_correta = $question['correct_answer'] ?? $question['resposta_correta'] ?? '';
    
    // Determinar dificuldade
    $dificuldade = $question['difficulty'] ?? $question['dificuldade'] ?? 'medio';
    
    // Determinar matéria
    $materia = $question['subject'] ?? $question['materia'] ?? $secao;
    
    // Inserir questão
    $tipo_questao = $eh_dissertativa ? 'dissertativa' : 'multipla_escolha';
    $resposta_dissertativa = $eh_dissertativa ? $resposta_correta : null;
    $resposta_multipla = $eh_dissertativa ? null : $resposta_correta;
    
    $stmt->execute([
        $tipo_exame,                    // tipo_prova
        $numero,                        // numero_questao
        $question_text,                 // enunciado
        $alt_a,                         // alternativa_a
        $alt_b,                         // alternativa_b
        $alt_c,                         // alternativa_c
        $alt_d,                         // alternativa_d
        $alt_e ?: null,                 // alternativa_e
        $resposta_multipla,             // resposta_correta
        $tipo_questao,                  // tipo_questao
        $resposta_dissertativa,         // resposta_dissertativa
        $dificuldade,                   // dificuldade
        $materia,                       // materia
        $secao,                         // assunto
        "Questão do " . strtoupper($tipo_exame) // explicacao
    ]);
    
    echo "    ✅ Questão $numero inserida ($tipo_questao)\n";
    return 1;
}
?>
