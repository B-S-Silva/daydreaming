<?php
/**
 * Script para adicionar mais questões ao banco de dados
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "📝 ADICIONANDO MAIS QUESTÕES AO BANCO\n";
echo "=====================================\n\n";

try {
    // Conectar
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Questões adicionais para cada tipo de teste
    $questoes_adicionais = [
        // TOEFL - questões 3-10
        ['toefl', 3, 'What is the main idea of the passage?', 'The importance of education', 'The benefits of technology', 'The challenges of modern life', 'The role of government', null, 'b', 'medio', 'Reading', 'Main Idea', 'Technology benefits are the main focus.'],
        ['toefl', 4, 'Choose the correct form: "She _______ to the store yesterday."', 'go', 'goes', 'went', 'going', null, 'c', 'facil', 'Grammar', 'Past Tense', 'Past tense of "go" is "went".'],
        ['toefl', 5, 'The word "comprehensive" most nearly means:', 'simple', 'complete', 'difficult', 'expensive', null, 'b', 'medio', 'Vocabulary', 'Academic Words', 'Comprehensive means complete or thorough.'],
        ['toefl', 6, 'According to the lecture, what causes climate change?', 'Solar radiation', 'Human activities', 'Natural cycles', 'Ocean currents', null, 'b', 'medio', 'Listening', 'Science', 'Human activities are the main cause.'],
        ['toefl', 7, 'Complete the sentence: "If I _______ more time, I would travel."', 'have', 'had', 'will have', 'having', null, 'b', 'medio', 'Grammar', 'Conditionals', 'Second conditional uses "had".'],
        ['toefl', 8, 'What is the author\'s purpose in this passage?', 'To entertain', 'To inform', 'To persuade', 'To criticize', null, 'b', 'medio', 'Reading', 'Purpose', 'The passage aims to inform readers.'],
        ['toefl', 9, 'The professor mentions three factors. What are they?', 'Time, money, effort', 'Speed, accuracy, efficiency', 'Quality, quantity, cost', 'Planning, execution, evaluation', null, 'a', 'dificil', 'Listening', 'Details', 'Time, money, and effort are mentioned.'],
        ['toefl', 10, 'Choose the best transition: "The study was thorough. _______, the results were inconclusive."', 'Therefore', 'However', 'Furthermore', 'Similarly', null, 'b', 'medio', 'Writing', 'Transitions', 'However shows contrast.'],
        
        // IELTS - questões 3-10
        ['ielts', 3, 'The graph shows that unemployment rates _______ between 2010 and 2020.', 'increased', 'decreased', 'remained stable', 'fluctuated', null, 'a', 'medio', 'Writing', 'Data Description', 'The graph shows an increase.'],
        ['ielts', 4, 'What does the speaker say about renewable energy?', 'It is too expensive', 'It is the future', 'It is unreliable', 'It is complicated', null, 'b', 'medio', 'Listening', 'Opinion', 'Speaker believes it is the future.'],
        ['ielts', 5, 'Complete: "The number of students _______ significantly this year."', 'have increased', 'has increased', 'are increasing', 'were increased', null, 'b', 'facil', 'Grammar', 'Subject-Verb Agreement', 'Singular subject needs singular verb.'],
        ['ielts', 6, 'According to the passage, what is the main benefit of exercise?', 'Weight loss', 'Better health', 'Stress relief', 'Social interaction', null, 'b', 'medio', 'Reading', 'Main Idea', 'Better health is the main benefit.'],
        ['ielts', 7, 'The word "substantial" in paragraph 2 means:', 'small', 'significant', 'temporary', 'unusual', null, 'b', 'medio', 'Vocabulary', 'Context Clues', 'Substantial means significant or considerable.'],
        ['ielts', 8, 'What is the writer\'s opinion about social media?', 'It is harmful', 'It is beneficial', 'It has both pros and cons', 'It is unnecessary', null, 'c', 'medio', 'Reading', 'Opinion', 'Writer presents both sides.'],
        ['ielts', 9, 'Choose the correct preposition: "She is interested _______ learning French."', 'in', 'on', 'at', 'for', null, 'a', 'facil', 'Grammar', 'Prepositions', 'Interested takes preposition "in".'],
        ['ielts', 10, 'The speaker suggests that students should:', 'Study more', 'Take breaks', 'Use technology', 'Work in groups', null, 'b', 'medio', 'Listening', 'Advice', 'Speaker recommends taking breaks.'],
        
        // SAT - questões 3-10
        ['sat', 3, 'If 2x + 5 = 15, what is the value of x?', '5', '10', '7.5', '2.5', null, 'a', 'facil', 'Mathematics', 'Linear Equations', '2x = 10, so x = 5.'],
        ['sat', 4, 'The area of a rectangle with length 8 and width 6 is:', '14', '28', '48', '56', null, 'c', 'facil', 'Mathematics', 'Geometry', 'Area = length × width = 8 × 6 = 48.'],
        ['sat', 5, 'Which word best describes the tone of the passage?', 'Optimistic', 'Pessimistic', 'Neutral', 'Sarcastic', null, 'a', 'medio', 'Reading', 'Tone', 'The passage has a positive outlook.'],
        ['sat', 6, 'What is 25% of 80?', '15', '20', '25', '30', null, 'b', 'facil', 'Mathematics', 'Percentages', '25% of 80 = 0.25 × 80 = 20.'],
        ['sat', 7, 'The author uses the example of _______ to illustrate the main point.', 'historical events', 'scientific discoveries', 'personal experiences', 'statistical data', null, 'b', 'medio', 'Reading', 'Examples', 'Scientific discoveries are used as examples.'],
        ['sat', 8, 'If y = 3x + 2, what is y when x = 4?', '10', '12', '14', '16', null, 'c', 'facil', 'Mathematics', 'Functions', 'y = 3(4) + 2 = 12 + 2 = 14.'],
        ['sat', 9, 'The passage suggests that technology has:', 'Improved communication', 'Reduced privacy', 'Increased costs', 'Simplified life', null, 'a', 'medio', 'Reading', 'Inference', 'Technology improved communication.'],
        ['sat', 10, 'What is the slope of the line passing through (2,3) and (4,7)?', '1', '2', '3', '4', null, 'b', 'medio', 'Mathematics', 'Coordinate Geometry', 'Slope = (7-3)/(4-2) = 4/2 = 2.'],
        
        // GRE - questões 3-10
        ['gre', 3, 'The politician\'s speech was so _______ that even his critics were impressed.', 'mundane', 'eloquent', 'verbose', 'contentious', null, 'b', 'medio', 'Vocabulary', 'Advanced Words', 'Eloquent means well-spoken and impressive.'],
        ['gre', 4, 'If the average of three numbers is 15, what is their sum?', '30', '45', '50', '60', null, 'b', 'facil', 'Mathematics', 'Statistics', 'Sum = Average × Count = 15 × 3 = 45.'],
        ['gre', 5, 'The author\'s argument is _______ because it lacks supporting evidence.', 'compelling', 'tenuous', 'comprehensive', 'innovative', null, 'b', 'medio', 'Vocabulary', 'Critical Reasoning', 'Tenuous means weak or lacking substance.'],
        ['gre', 6, 'What is 2^5?', '10', '25', '32', '64', null, 'c', 'facil', 'Mathematics', 'Exponents', '2^5 = 2×2×2×2×2 = 32.'],
        ['gre', 7, 'The passage implies that scientific progress is:', 'Inevitable', 'Unpredictable', 'Gradual', 'Revolutionary', null, 'c', 'medio', 'Reading', 'Inference', 'Progress is described as gradual.'],
        ['gre', 8, 'Choose the word that is most opposite to "ephemeral":', 'temporary', 'permanent', 'brief', 'fleeting', null, 'b', 'medio', 'Vocabulary', 'Antonyms', 'Ephemeral means temporary; permanent is opposite.'],
        ['gre', 9, 'If x > 0 and x^2 = 16, what is x?', '2', '4', '8', '16', null, 'b', 'facil', 'Mathematics', 'Algebra', 'x^2 = 16, so x = 4 (since x > 0).'],
        ['gre', 10, 'The researcher\'s methodology was _______ by peer reviewers.', 'praised', 'questioned', 'ignored', 'replicated', null, 'b', 'medio', 'Reading', 'Context', 'Methodology was questioned by reviewers.']
    ];
    
    // Inserir questões
    $stmt = $pdo->prepare("
        INSERT INTO questoes (tipo_prova, numero_questao, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e, resposta_correta, dificuldade, materia, assunto, explicacao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE enunciado = VALUES(enunciado)
    ");
    
    $questoes_inseridas = 0;
    foreach ($questoes_adicionais as $questao) {
        $stmt->execute($questao);
        $questoes_inseridas++;
        echo "✅ Questão {$questao[0]} #{$questao[1]} inserida\n";
    }
    
    echo "\n📊 VERIFICANDO TOTAL DE QUESTÕES:\n";
    echo "==================================\n";
    
    $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova ORDER BY tipo_prova");
    $totais = $stmt->fetchAll();
    
    foreach ($totais as $total) {
        echo "📝 {$total['tipo_prova']}: {$total['total']} questões\n";
    }
    
    echo "\n🎉 QUESTÕES ADICIONADAS COM SUCESSO!\n";
    echo "Total de questões inseridas: $questoes_inseridas\n\n";
    
    echo "🌐 Agora você pode testar o simulador em:\n";
    echo "http://localhost:8080/simulador_provas.php\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
