<?php
/**
 * Script para inserir dados iniciais
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "📝 INSERINDO DADOS INICIAIS\n";
echo "===========================\n\n";

try {
    // Conectar
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Inserir usuário administrador
    echo "👤 Inserindo usuário administrador...\n";
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, usuario, email, senha, is_admin, ativo) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE nome = VALUES(nome), is_admin = VALUES(is_admin)
    ");
    $stmt->execute([
        'Administrador do Sistema',
        'admin',
        'admin@daydreamming.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // admin123
        true,
        true
    ]);
    echo "✅ Usuário admin criado!\n";
    
    // Inserir usuários de teste
    echo "👥 Inserindo usuários de teste...\n";
    $usuarios_teste = [
        ['Usuário Teste', 'teste', 'teste@daydreamming.com'],
        ['Maria Santos', 'maria.santos', 'maria@exemplo.com'],
        ['João Silva', 'joao.silva', 'joao@exemplo.com']
    ];
    
    foreach ($usuarios_teste as $user) {
        $stmt->execute([
            $user[0], $user[1], $user[2],
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // admin123
            false, true
        ]);
    }
    echo "✅ Usuários de teste criados!\n";
    
    // Inserir categorias do fórum
    echo "💬 Inserindo categorias do fórum...\n";
    $categorias = [
        ['💬 Discussões Gerais', 'Conversas gerais sobre estudar no exterior', '#007bff', '💬', 1],
        ['📝 Testes Internacionais', 'Dúvidas sobre TOEFL, IELTS, SAT, GRE, GMAT', '#28a745', '📝', 2],
        ['🎓 Universidades', 'Informações sobre universidades e admissões', '#17a2b8', '🎓', 3],
        ['💰 Bolsas de Estudo', 'Oportunidades de bolsas e financiamento', '#ffc107', '💰', 4],
        ['✈️ Experiências', 'Relatos de quem estudou no exterior', '#6f42c1', '✈️', 5],
        ['🔧 Suporte Técnico', 'Problemas técnicos e sugestões', '#dc3545', '🔧', 6]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO forum_categorias (nome, descricao, cor, icone, ordem) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE descricao = VALUES(descricao)
    ");
    
    foreach ($categorias as $cat) {
        $stmt->execute($cat);
    }
    echo "✅ Categorias do fórum criadas!\n";
    
    // Inserir badges básicas
    echo "🏆 Inserindo badges...\n";
    $badges = [
        ['primeiro_teste', 'Primeiro Passo', 'Completou seu primeiro teste', '🎯', 'especial', 'teste', 1, 'comum', 100],
        ['satisfatorio', 'Satisfatório', 'Pontuação 60-69%', '🥉', 'pontuacao', 'teste', 60, 'comum', 30],
        ['bom', 'Bom Desempenho', 'Pontuação 70-79%', '🥈', 'pontuacao', 'teste', 70, 'comum', 50],
        ['muito_bom', 'Muito Bom', 'Pontuação 80-89%', '🥇', 'pontuacao', 'teste', 80, 'raro', 75],
        ['excelencia', 'Excelência', 'Pontuação 90%+', '🏆', 'pontuacao', 'teste', 90, 'epico', 100],
        ['perfeccionista', 'Perfeccionista', '100% de acertos', '💯', 'pontuacao', 'teste', 100, 'lendario', 200],
        ['consistente', 'Consistente', '5 resultados acima de 70%', '📈', 'frequencia', 'teste', 5, 'raro', 100],
        ['dedicado', 'Dedicado', '10 resultados acima de 70%', '💪', 'frequencia', 'teste', 10, 'epico', 150],
        ['velocista', 'Velocista', 'Teste em tempo recorde', '⚡', 'tempo', 'teste', 1, 'raro', 75],
        ['primeiro_post', 'Primeira Participação', 'Primeiro tópico no fórum', '📝', 'especial', 'forum', 1, 'comum', 50]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO badges (codigo, nome, descricao, icone, tipo, categoria, condicao_valor, raridade, experiencia_bonus) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE nome = VALUES(nome)
    ");
    
    foreach ($badges as $badge) {
        $stmt->execute($badge);
    }
    echo "✅ Badges criadas!\n";
    
    // Inserir questões de exemplo
    echo "❓ Inserindo questões de exemplo...\n";
    $questoes = [
        // TOEFL
        ['toefl', 1, 'Choose the word that best completes: "The lecture was so _______ that students fell asleep."', 'engaging', 'tedious', 'fascinating', 'inspiring', null, 'b', 'medio', 'Vocabulary', 'Academic English', 'Tedious means boring.'],
        ['toefl', 2, 'Which sentence is correct?', 'If I was you, I would study.', 'If I were you, I would study.', 'If I am you, I would study.', 'If I will be you, I would study.', null, 'b', 'medio', 'Grammar', 'Conditionals', 'Subjunctive mood requires "were".'],
        
        // IELTS
        ['ielts', 1, 'What is the main advantage of renewable energy?', 'Cheaper than fossil fuels', 'Reduces environmental impact', 'Creates more jobs', 'More reliable', null, 'b', 'medio', 'Reading', 'Environment', 'Environmental protection is key.'],
        ['ielts', 2, 'Complete: "The research _______ that climate change affects migration."', 'suggests', 'suggest', 'suggesting', 'suggested', null, 'a', 'facil', 'Grammar', 'Subject-Verb', 'Singular subject needs singular verb.'],
        
        // SAT
        ['sat', 1, 'If 3x + 7 = 22, what is x?', '3', '5', '7', '15', null, 'b', 'facil', 'Mathematics', 'Algebra', '3x = 15, so x = 5.'],
        ['sat', 2, 'What describes the author\'s tone?', 'Optimistic', 'Skeptical', 'Neutral', 'Passionate', null, 'b', 'medio', 'Reading', 'Analysis', 'Author questions claims.'],
        
        // GRE
        ['gre', 1, '"Ubiquitous" most nearly means:', 'rare', 'expensive', 'everywhere', 'dangerous', null, 'c', 'medio', 'Vocabulary', 'Advanced', 'Ubiquitous means everywhere.'],
        ['gre', 2, 'If average of 5 numbers is 12, what is their sum?', '60', '17', '7', '24', null, 'a', 'facil', 'Mathematics', 'Statistics', 'Sum = Average × Count = 60.']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO questoes (tipo_prova, numero_questao, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e, resposta_correta, dificuldade, materia, assunto, explicacao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE enunciado = VALUES(enunciado)
    ");
    
    foreach ($questoes as $questao) {
        $stmt->execute($questao);
    }
    echo "✅ Questões de exemplo criadas!\n";
    
    // Criar níveis para usuários
    echo "📈 Criando níveis para usuários...\n";
    $stmt = $pdo->prepare("
        INSERT INTO niveis_usuario (usuario_id, nivel_atual, experiencia_total, experiencia_nivel, experiencia_necessaria)
        SELECT id, 1, 0, 0, 100 FROM usuarios
        ON DUPLICATE KEY UPDATE nivel_atual = VALUES(nivel_atual)
    ");
    $stmt->execute();
    echo "✅ Níveis criados!\n";
    
    // Verificar dados inseridos
    echo "\n🔍 Verificando dados inseridos...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $usuarios = $stmt->fetchColumn();
    echo "👥 Usuários: $usuarios\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_categorias");
    $categorias = $stmt->fetchColumn();
    echo "💬 Categorias: $categorias\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM badges");
    $badges = $stmt->fetchColumn();
    echo "🏆 Badges: $badges\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes");
    $questoes = $stmt->fetchColumn();
    echo "❓ Questões: $questoes\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM niveis_usuario");
    $niveis = $stmt->fetchColumn();
    echo "📈 Níveis: $niveis\n";
    
    echo "\n🎉 DADOS INICIAIS INSERIDOS COM SUCESSO!\n\n";
    
    echo "🔑 CREDENCIAIS DE ACESSO:\n";
    echo "   Usuário: admin\n";
    echo "   Senha: admin123\n";
    echo "   Email: admin@daydreamming.com\n\n";
    
    echo "⚠️ IMPORTANTE: Altere a senha após o primeiro login!\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
