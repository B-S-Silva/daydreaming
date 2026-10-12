<?php
require_once 'config.php';
require_once 'verificar_auth.php';
require_once 'questoes_manager.php';

// Verificar se o usuário está logado
verificarLogin();

// Conectar ao banco de dados
$pdo = conectarBD();

// Verificar se o tipo de prova foi especificado
if (!isset($_GET['tipo'])) {
    header('Location: simulador_provas.php');
    exit;
}

$tipo_prova = $_GET['tipo'];

// Definir configurações das provas
$config_provas = [
    'sat' => [
        'nome' => 'SAT',
        'duracao_minutos' => 180, // 3h
        'questoes_total' => 120,
        'cor' => '#FF9800'
    ],
    'toefl' => [
        'nome' => 'TOEFL',
        'duracao_minutos' => 180, // 3h
        'questoes_total' => 100,
        'cor' => '#4CAF50'
    ],
    'ielts' => [
        'nome' => 'IELTS',
        'duracao_minutos' => 165, // 2h45min
        'questoes_total' => 40,
        'cor' => '#2196F3'
    ],
    'gre' => [
        'nome' => 'GRE',
        'duracao_minutos' => 225, // 3h45min
        'questoes_total' => 80,
        'cor' => '#9C27B0'
    ],
    'dele' => [
        'nome' => 'DELE',
        'duracao_minutos' => 240, // 4h
        'questoes_total' => 80,
        'cor' => '#E91E63'
    ],
    'delf' => [
        'nome' => 'DELF',
        'duracao_minutos' => 150, // 2h30min
        'questoes_total' => 75,
        'cor' => '#3F51B5'
    ],
    'testdaf' => [
        'nome' => 'TestDaF',
        'duracao_minutos' => 190, // 3h10min
        'questoes_total' => 85,
        'cor' => '#795548'
    ],
    'jlpt' => [
        'nome' => 'JLPT',
        'duracao_minutos' => 170, // 2h50min
        'questoes_total' => 95,
        'cor' => '#9C27B0'
    ],
    'hsk' => [
        'nome' => 'HSK',
        'duracao_minutos' => 135, // 2h15min
        'questoes_total' => 70,
        'cor' => '#607D8B'
    ]
];

if (!isset($config_provas[$tipo_prova])) {
    header('Location: simulador_provas.php');
    exit;
}

$prova = $config_provas[$tipo_prova];

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processar início do teste
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iniciar_teste'])) {
    // Verificar token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Token CSRF inválido');
    }
    
    // Criar nova sessão de teste
    $stmt = $pdo->prepare("INSERT INTO sessoes_teste (usuario_id, tipo_prova, inicio, duracao_minutos, status) VALUES (?, ?, NOW(), ?, 'ativo')");
    $stmt->execute([$_SESSION['usuario_id'], $tipo_prova, $prova['duracao_minutos']]);
    $sessao_id = $pdo->lastInsertId();
    
    // Redirecionar para a execução do teste
    header("Location: executar_teste.php?tipo=$tipo_prova&sessao=$sessao_id&executando=1");
    exit;
}

// Se está executando o teste
if (isset($_GET['executando']) && isset($_GET['sessao'])) {
    $sessao_id = $_GET['sessao'];
    
    // Verificar se a sessão existe e pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM sessoes_teste WHERE id = ? AND usuario_id = ? AND status = 'ativo'");
    $stmt->execute([$sessao_id, $_SESSION['usuario_id']]);
    $sessao = $stmt->fetch();
    
    if (!$sessao) {
        header('Location: simulador_provas.php');
        exit;
    }
    
    // Verificar se o tempo não expirou
    $inicio = new DateTime($sessao['inicio']);
    $agora = new DateTime();
    $tempo_decorrido = $agora->getTimestamp() - $inicio->getTimestamp();
    $tempo_limite = $sessao['duracao_minutos'] * 60;
    
    if ($tempo_decorrido >= $tempo_limite) {
        // Tempo expirado, finalizar teste
        $stmt = $pdo->prepare("UPDATE sessoes_teste SET status = 'expirado', fim = NOW() WHERE id = ?");
        $stmt->execute([$sessao_id]);
        header('Location: resultado_teste.php?sessao=' . $sessao_id);
        exit;
    }
    
    $tempo_restante = $tempo_limite - $tempo_decorrido;
    
    // Carregar questões reais do banco de dados
    $limite_questoes = (int)$prova['questoes_total'];
    $stmt = $pdo->prepare("
        SELECT id, numero_questao, enunciado,
               alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e,
               resposta_correta, tipo_questao, resposta_dissertativa,
               dificuldade, materia, assunto, explicacao
        FROM questoes
        WHERE tipo_prova = ? AND ativa = 1
        ORDER BY numero_questao
        LIMIT $limite_questoes
    ");
    $stmt->execute([$tipo_prova]);
    $questoes_db = $stmt->fetchAll();

    // Processar questões para o formato JavaScript
    $questoes_simuladas = [];
    foreach ($questoes_db as $questao) {
        $questao_formatada = [
            'id' => $questao['id'],
            'numero' => $questao['numero_questao'],
            'enunciado' => $questao['enunciado'],
            'tipo_questao' => $questao['tipo_questao'] ?: 'multipla_escolha',
            'dificuldade' => $questao['dificuldade'],
            'materia' => $questao['materia'],
            'assunto' => $questao['assunto'],
            'explicacao' => $questao['explicacao']
        ];

        // Adicionar alternativas ou resposta dissertativa
        if ($questao['tipo_questao'] === 'dissertativa') {
            $questao_formatada['resposta_esperada'] = $questao['resposta_dissertativa'] ?: $questao['resposta_correta'];
        } else {
            $questao_formatada['alternativas'] = [];
            if (!empty($questao['alternativa_a'])) $questao_formatada['alternativas']['a'] = $questao['alternativa_a'];
            if (!empty($questao['alternativa_b'])) $questao_formatada['alternativas']['b'] = $questao['alternativa_b'];
            if (!empty($questao['alternativa_c'])) $questao_formatada['alternativas']['c'] = $questao['alternativa_c'];
            if (!empty($questao['alternativa_d'])) $questao_formatada['alternativas']['d'] = $questao['alternativa_d'];
            if (!empty($questao['alternativa_e'])) $questao_formatada['alternativas']['e'] = $questao['alternativa_e'];
            $questao_formatada['resposta_correta'] = $questao['resposta_correta'];
        }

        $questoes_simuladas[] = $questao_formatada;
    }

    // Verificar se há questões suficientes
    if (count($questoes_simuladas) == 0) {
        // Nenhuma questão encontrada - exame não disponível
        echo "<!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Exame em Preparação - DayDreaming</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
                .icon { font-size: 4rem; margin-bottom: 20px; }
                h1 { color: #333; margin-bottom: 20px; }
                p { color: #666; line-height: 1.6; margin-bottom: 30px; }
                .btn { display: inline-block; padding: 12px 24px; background: {$prova['cor']}; color: white; text-decoration: none; border-radius: 5px; transition: opacity 0.3s; }
                .btn:hover { opacity: 0.8; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='icon'>🚧</div>
                <h1>Exame {$prova['nome']} em Preparação</h1>
                <p>Este exame está sendo preparado e receberá questões reais em breve.</p>
                <p>Atualmente, apenas o <strong>SAT</strong> está disponível com questões completas do Practice Test #4.</p>
                <p>Aguarde as próximas atualizações para ter acesso a este exame!</p>
                <a href='simulador_provas.php' class='btn'>Voltar ao Simulador</a>
            </div>
        </body>
        </html>";
        exit;
    }

    // Se há poucas questões, mostrar aviso mas continuar
    if (count($questoes_simuladas) < $prova['questoes_total']) {
        $questoes_disponiveis = count($questoes_simuladas);
        echo "<script>
            alert('Atenção: Este exame tem apenas $questoes_disponiveis questões disponíveis de {$prova['questoes_total']} esperadas.\\n\\nO teste continuará com as questões disponíveis.');
        </script>";

        // Ajustar o total de questões para o disponível
        $prova['questoes_total'] = $questoes_disponiveis;
    }
    
    include 'interface_teste.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $prova['nome']; ?> - Simulador</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .teste-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .teste-header {
            text-align: center;
            background: linear-gradient(135deg, <?php echo $prova['cor']; ?>, <?php echo $prova['cor']; ?>dd);
            color: white;
            padding: 40px 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .teste-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .teste-info {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid <?php echo $prova['cor']; ?>;
        }
        
        .info-item h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .info-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: <?php echo $prova['cor']; ?>;
        }
        
        .instrucoes {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #2196F3;
            margin-bottom: 30px;
        }
        
        .instrucoes h3 {
            color: #1976D2;
            margin-bottom: 15px;
        }
        
        .instrucoes ul {
            color: #333;
            line-height: 1.6;
        }
        
        .instrucoes li {
            margin-bottom: 8px;
        }
        
        .btn-iniciar-teste {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, <?php echo $prova['cor']; ?>, <?php echo $prova['cor']; ?>dd);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-iniciar-teste:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }
        
        .aviso-importante {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .aviso-importante strong {
            color: #d63031;
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>
    
    <div class="teste-container">
        <div class="teste-header">
            <h1>📝 <?php echo $prova['nome']; ?></h1>
            <p>Prepare-se para o simulado</p>
        </div>
        
        <div class="teste-info">
            <div class="info-grid">
                <div class="info-item">
                    <h3>Duração</h3>
                    <div class="info-value"><?php echo floor($prova['duracao_minutos'] / 60); ?>h<?php echo $prova['duracao_minutos'] % 60 > 0 ? sprintf('%02dm', $prova['duracao_minutos'] % 60) : ''; ?></div>
                </div>
                <div class="info-item">
                    <h3>Questões</h3>
                    <div class="info-value"><?php echo $prova['questoes_total']; ?></div>
                </div>
                <div class="info-item">
                    <h3>Tipo</h3>
                    <div class="info-value"><?php echo $prova['nome']; ?></div>
                </div>
            </div>
            
            <div class="instrucoes">
                <h3>📋 Instruções Importantes</h3>
                <ul>
                    <li>Leia atentamente cada questão antes de responder</li>
                    <li>Você pode navegar entre as questões livremente</li>
                    <li>Suas respostas são salvas automaticamente</li>
                    <li>O cronômetro iniciará assim que você clicar em "Iniciar Teste"</li>
                    <li>Certifique-se de ter uma conexão estável com a internet</li>
                    <li>Não feche a aba do navegador durante o teste</li>
                </ul>
            </div>
            
            <div class="aviso-importante">
                <strong>⚠️ Atenção:</strong> Uma vez iniciado, o teste não pode ser pausado. 
                Certifique-se de que você tem tempo suficiente para completá-lo.
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="iniciar_teste" class="btn-iniciar-teste">
                    🚀 Iniciar Teste Agora
                </button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="simulador_provas.php" class="btn btn-secondary">
                ← Voltar para Seleção de Provas
            </a>
        </div>
    </div>
    
    <script src="public/js/main.js"></script>
</body>
</html>