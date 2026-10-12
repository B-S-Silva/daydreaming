<?php
require_once 'config.php';
require_once 'verificar_auth.php';
require_once 'badges_manager.php';
require_once 'questoes_manager.php';

// Verificar se o usuário está logado
verificarLogin();

// Conectar ao banco de dados
$pdo = conectarBD();

// Obter dados do usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Gerar token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Buscar estatísticas do usuário
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total_testes,
        AVG(CASE
            WHEN st.tipo_prova = 'sat' THEN (st.acertos / 120) * 100
            WHEN st.tipo_prova = 'toefl' THEN (st.acertos / 100) * 100
            WHEN st.tipo_prova = 'ielts' THEN (st.acertos / 40) * 100
            WHEN st.tipo_prova = 'gre' THEN (st.acertos / 80) * 100
            ELSE (st.acertos / 120) * 100
        END) as media_pontuacao
    FROM sessoes_teste st
    WHERE st.usuario_id = ? AND st.status = 'finalizado'
");
$stmt->execute([$_SESSION['usuario_id']]);
$estatisticas = $stmt->fetch();

// Buscar badges do usuário
$stmt = $pdo->prepare("SELECT b.nome, b.descricao, b.icone FROM badges b INNER JOIN usuario_badges ub ON b.id = ub.badge_id WHERE ub.usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$badges = $stmt->fetchAll();

// Definir tipos de provas disponíveis
$tipos_provas = [
    'toefl' => [
        'nome' => 'TOEFL',
        'descricao' => 'Test of English as a Foreign Language',
        'icone' => '🇺🇸',
        'cor' => '#4CAF50',
        'duracao' => '3h00min',
        'questoes' => 100
    ],
    'ielts' => [
        'nome' => 'IELTS',
        'descricao' => 'International English Language Testing System',
        'icone' => '🇬🇧',
        'cor' => '#2196F3',
        'duracao' => '2h45min',
        'questoes' => 40
    ],
    'sat' => [
        'nome' => 'SAT',
        'descricao' => 'Scholastic Assessment Test',
        'icone' => '🎓',
        'cor' => '#FF9800',
        'duracao' => '3h00min',
        'questoes' => 120
    ],
    'gre' => [
        'nome' => 'GRE',
        'descricao' => 'Graduate Record Examinations',
        'icone' => '🎯',
        'cor' => '#9C27B0',
        'duracao' => '3h45min',
        'questoes' => 80
    ],
    'dele' => [
        'nome' => 'DELE',
        'descricao' => 'Diplomas de Español como Lengua Extranjera',
        'icone' => '🇪🇸',
        'cor' => '#E91E63',
        'duracao' => '4h00min',
        'questoes' => 50
    ],
    'delf' => [
        'nome' => 'DELF',
        'descricao' => 'Diplôme d\'Études en Langue Française',
        'icone' => '🇫🇷',
        'cor' => '#3F51B5',
        'duracao' => '2h30min',
        'questoes' => 45
    ],
    'testdaf' => [
        'nome' => 'TestDaF',
        'descricao' => 'Test Deutsch als Fremdsprache',
        'icone' => '🇩🇪',
        'cor' => '#795548',
        'duracao' => '3h10min',
        'questoes' => 35
    ],
    'jlpt' => [
        'nome' => 'JLPT',
        'descricao' => 'Japanese Language Proficiency Test',
        'icone' => '🇯🇵',
        'cor' => '#9C27B0',
        'duracao' => '2h50min',
        'questoes' => 60
    ],
    'hsk' => [
        'nome' => 'HSK',
        'descricao' => 'Hanyu Shuiping Kaoshi (Chinese Proficiency Test)',
        'icone' => '🇨🇳',
        'cor' => '#F44336',
        'duracao' => '2h30min',
        'questoes' => 100
    ]
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de Provas - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#667eea">
    <style>
        .simulador-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-simulador {
            text-align: center;
            margin-bottom: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header-simulador h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .header-simulador p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .estatisticas-usuario {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .stat-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .badges-section {
            margin-bottom: 40px;
        }
        
        .badges-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        
        .badge {
            background: linear-gradient(45deg, #FFD700, #FFA500);
            color: #333;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(255, 215, 0, 0.3);
        }
        
        .provas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .prova-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .prova-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .prova-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--cor-prova);
        }
        
        .prova-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .prova-icone {
            font-size: 3rem;
            margin-right: 15px;
        }
        
        .prova-info h3 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.5rem;
        }
        
        .prova-info p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .prova-detalhes {
            margin-bottom: 25px;
        }
        
        .detalhe-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detalhe-label {
            color: #666;
            font-weight: 500;
        }
        
        .detalhe-valor {
            color: #333;
            font-weight: 600;
        }
        
        .btn-iniciar {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--cor-prova), var(--cor-prova));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-iniciar:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }
        
        .nivel-usuario {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .nivel-badge {
            display: inline-block;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .header-simulador h1 {
                font-size: 2rem;
            }
            
            .provas-grid {
                grid-template-columns: 1fr;
            }
            
            .estatisticas-usuario {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>
    
    <div class="simulador-container">
        <div class="header-simulador">
            <h1>🎯 Simulador de Provas</h1>
            <p>Teste seus conhecimentos e acompanhe seu progresso</p>
        </div>
        
        <!-- Nível do Usuário -->
        <div class="nivel-usuario" id="ancora">
            <div class="nivel-badge">Nível Iniciante</div>
            <p>Continue fazendo testes para avançar de nível!</p>
        </div>
        
        <!-- Estatísticas do Usuário -->
        <div class="estatisticas-usuario">
            <div class="stat-card">
                <h3>Testes Realizados</h3>
                <div class="stat-value"><?php echo $estatisticas['total_testes'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Média de Pontuação</h3>
                <div class="stat-value"><?php echo number_format($estatisticas['media_pontuacao'] ?? 0, 1); ?>%</div>
            </div>
            <div class="stat-card">
                <h3>Badges Conquistadas</h3>
                <div class="stat-value"><?php echo count($badges); ?></div>
            </div>
        </div>
        
        <!-- Badges do Usuário -->
        <?php if (!empty($badges)): ?>
        <div class="badges-section">
            <h2 style="text-align: center; margin-bottom: 20px;">🏆 Suas Conquistas</h2>
            <div class="badges-container">
                <?php foreach ($badges as $badge): ?>
                    <div class="badge" title="<?php echo htmlspecialchars($badge['descricao']); ?>">
                        <?php echo $badge['icone']; ?> <?php echo htmlspecialchars($badge['nome']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Grid de Provas -->
        <div class="provas-grid">
            <?php foreach ($tipos_provas as $tipo => $prova): ?>
                <div class="prova-card" style="--cor-prova: <?php echo $prova['cor']; ?>">
                    <div class="prova-header">
                        <div class="prova-icone"><?php echo $prova['icone']; ?></div>
                        <div class="prova-info">
                            <h3><?php echo $prova['nome']; ?></h3>
                            <p><?php echo $prova['descricao']; ?></p>
                        </div>
                    </div>
                    
                    <div class="prova-detalhes">
                        <div class="detalhe-item">
                            <span class="detalhe-label">Duração:</span>
                            <span class="detalhe-valor"><?php echo $prova['duracao']; ?></span>
                        </div>
                        <div class="detalhe-item">
                            <span class="detalhe-label">Questões:</span>
                            <span class="detalhe-valor"><?php echo $prova['questoes']; ?></span>
                        </div>
                        <div class="detalhe-item">
                            <span class="detalhe-label">Dificuldade:</span>
                            <span class="detalhe-valor">Média</span>
                        </div>
                    </div>
                    
                    <a href="executar_teste.php?tipo=<?php echo $tipo; ?>" 
                       class="btn-iniciar" 
                       style="background: <?php echo $prova['cor']; ?>">
                        Iniciar Simulado
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Botão para ver histórico -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="historico_testes.php" class="btn btn-secondary">
                📊 Ver Histórico de Testes
            </a>
        </div>
    </div>
    
    <script src="public/js/main.js"></script>
</body>
</html>