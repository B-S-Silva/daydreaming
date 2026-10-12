<?php
require_once 'config.php';
require_once 'verificar_auth.php';

verificarLogin();
$pdo = conectarBD();

$sessao_id = $_GET['sessao'] ?? null;
if (!$sessao_id) {
    header('Location: historico_provas.php');
    exit;
}

// Buscar dados da sessão
$stmt = $pdo->prepare("
    SELECT st.*, rt.total_questoes
    FROM sessoes_teste st
    LEFT JOIN resultados_testes rt ON st.id = rt.sessao_id
    WHERE st.id = ? AND st.usuario_id = ?
");
$stmt->execute([$sessao_id, $_SESSION['usuario_id']]);
$sessao = $stmt->fetch();

if (!$sessao) {
    header('Location: historico_provas.php');
    exit;
}

// Buscar questões da prova
$stmt = $pdo->prepare("
    SELECT 
        q.id,
        q.numero_questao,
        q.enunciado,
        q.alternativa_a,
        q.alternativa_b,
        q.alternativa_c,
        q.alternativa_d,
        q.alternativa_e,
        q.resposta_correta,
        q.tipo_questao,
        q.resposta_dissertativa,
        q.materia,
        q.assunto,
        ru.resposta_usuario,
        ru.resposta_dissertativa_usuario,
        ru.esta_correta
    FROM questoes q
    LEFT JOIN respostas_usuario ru ON q.id = ru.questao_id AND ru.sessao_id = ?
    WHERE q.tipo_prova = ?
    ORDER BY q.numero_questao
");
$stmt->execute([$sessao_id, $sessao['tipo_prova']]);
$questoes = $stmt->fetchAll();

$config_provas = [
    'sat' => ['nome' => 'SAT', 'cor' => '#FF9800'],
    'toefl' => ['nome' => 'TOEFL', 'cor' => '#2196F3'],
    'ielts' => ['nome' => 'IELTS', 'cor' => '#4CAF50'],
    'gre' => ['nome' => 'GRE', 'cor' => '#9C27B0']
];

$prova = $config_provas[$sessao['tipo_prova']] ?? $config_provas['sat'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisão da Prova - <?php echo $prova['nome']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; line-height: 1.6; }
        
        .header {
            background: linear-gradient(135deg, <?php echo $prova['cor']; ?> 0%, #667eea 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .resumo-prova {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .resumo-item {
            text-align: center;
        }
        
        .resumo-valor {
            font-size: 1.5rem;
            font-weight: bold;
            color: <?php echo $prova['cor']; ?>;
        }
        
        .resumo-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .questao-card {
            background: white;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .questao-header {
            padding: 1rem;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .questao-numero {
            font-weight: bold;
            color: <?php echo $prova['cor']; ?>;
        }
        
        .questao-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-correta {
            background: #d4edda;
            color: #155724;
        }
        
        .status-incorreta {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-nao-respondida {
            background: #fff3cd;
            color: #856404;
        }
        
        .questao-conteudo {
            padding: 1.5rem;
        }
        
        .enunciado {
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            line-height: 1.7;
        }
        
        .alternativas {
            margin-bottom: 1rem;
        }
        
        .alternativa {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding: 1rem;
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .alternativa.selecionada {
            border-color: <?php echo $prova['cor']; ?>;
            background: #f8f9ff;
        }
        
        .alternativa.correta {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .alternativa.incorreta {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .letra-alternativa {
            font-weight: bold;
            margin-right: 12px;
            min-width: 20px;
        }
        
        .resposta-dissertativa {
            margin-bottom: 1rem;
        }
        
        .resposta-usuario {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid <?php echo $prova['cor']; ?>;
            margin-bottom: 1rem;
        }
        
        .resposta-correta {
            background: #d4edda;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        
        .flag-correta {
            color: #28a745;
            font-weight: bold;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .flag-incorreta {
            color: #dc3545;
            font-weight: bold;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .navegacao {
            position: fixed;
            top: 50%;
            right: 2rem;
            transform: translateY(-50%);
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .nav-questao {
            display: block;
            width: 40px;
            height: 40px;
            margin: 5px 0;
            border: none;
            border-radius: 50%;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .nav-questao.correta {
            background: #28a745;
            color: white;
        }
        
        .nav-questao.incorreta {
            background: #dc3545;
            color: white;
        }
        
        .nav-questao.nao-respondida {
            background: #ffc107;
            color: #212529;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            margin: 0.5rem;
        }
        
        .btn-primary {
            background: <?php echo $prova['cor']; ?>;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .navegacao { display: none; }
            .resumo-prova { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>

    <div class="header">
        <h1>🔍 Revisão da Prova</h1>
        <h2><?php echo $prova['nome']; ?> - <?php echo date('d/m/Y H:i', strtotime($sessao['inicio'])); ?></h2>
    </div>

    <div class="container">
        <div class="resumo-prova">
            <div class="resumo-item">
                <div class="resumo-valor"><?php echo number_format($sessao['pontuacao_final'], 1); ?>%</div>
                <div class="resumo-label">Pontuação Final</div>
            </div>
            <div class="resumo-item">
                <div class="resumo-valor"><?php echo $sessao['acertos']; ?></div>
                <div class="resumo-label">Questões Corretas</div>
            </div>
            <div class="resumo-item">
                <div class="resumo-valor"><?php echo $sessao['questoes_respondidas']; ?></div>
                <div class="resumo-label">Questões Respondidas</div>
            </div>
            <div class="resumo-item">
                <div class="resumo-valor"><?php echo gmdate('H:i:s', $sessao['tempo_gasto']); ?></div>
                <div class="resumo-label">Tempo Gasto</div>
            </div>
        </div>

        <?php foreach ($questoes as $questao): ?>
            <?php
            $respondida = !empty($questao['resposta_usuario']) || !empty($questao['resposta_dissertativa_usuario']);
            $correta = $questao['esta_correta'] == 1;
            
            if (!$respondida) {
                $status_class = 'status-nao-respondida';
                $status_text = '⚪ Não Respondida';
            } elseif ($correta) {
                $status_class = 'status-correta';
                $status_text = '✅ Correta';
            } else {
                $status_class = 'status-incorreta';
                $status_text = '❌ Incorreta';
            }
            ?>
            
            <div class="questao-card" id="questao-<?php echo $questao['numero_questao']; ?>">
                <div class="questao-header">
                    <div class="questao-numero">
                        Questão <?php echo $questao['numero_questao']; ?>
                        <?php if ($questao['assunto']): ?>
                            - <?php echo $questao['assunto']; ?>
                        <?php endif; ?>
                    </div>
                    <div class="questao-status <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </div>
                </div>
                
                <div class="questao-conteudo">
                    <div class="enunciado">
                        <?php echo nl2br(htmlspecialchars($questao['enunciado'])); ?>
                    </div>
                    
                    <?php if ($questao['tipo_questao'] === 'dissertativa'): ?>
                        <div class="resposta-dissertativa">
                            <?php if ($questao['resposta_dissertativa_usuario']): ?>
                                <h4>Sua Resposta:</h4>
                                <div class="resposta-usuario">
                                    <?php echo nl2br(htmlspecialchars($questao['resposta_dissertativa_usuario'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="resposta-usuario">
                                    <em>Questão não respondida</em>
                                </div>
                            <?php endif; ?>
                            
                            <h4>Resposta Correta:</h4>
                            <div class="resposta-correta">
                                ✅ <?php echo htmlspecialchars($questao['resposta_dissertativa'] ?: $questao['resposta_correta']); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alternativas">
                            <?php
                            $alternativas = [
                                'a' => $questao['alternativa_a'],
                                'b' => $questao['alternativa_b'],
                                'c' => $questao['alternativa_c'],
                                'd' => $questao['alternativa_d'],
                                'e' => $questao['alternativa_e']
                            ];
                            
                            foreach ($alternativas as $letra => $texto):
                                if (empty($texto)) continue;
                                
                                $classes = ['alternativa'];
                                $flag = '';
                                
                                // Verificar se foi selecionada pelo usuário
                                if ($questao['resposta_usuario'] === $letra) {
                                    $classes[] = 'selecionada';
                                }
                                
                                // Verificar se é a resposta correta
                                if ($questao['resposta_correta'] === $letra) {
                                    $classes[] = 'correta';
                                    $flag = '<div class="flag-correta">✅ Resposta Correta</div>';
                                } elseif ($questao['resposta_usuario'] === $letra && !$correta) {
                                    $classes[] = 'incorreta';
                                    $flag = '<div class="flag-incorreta">❌ Sua Resposta (Incorreta)</div>';
                                }
                            ?>
                                <div class="<?php echo implode(' ', $classes); ?>">
                                    <span class="letra-alternativa"><?php echo strtoupper($letra); ?>)</span>
                                    <div>
                                        <?php echo htmlspecialchars($texto); ?>
                                        <?php echo $flag; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="historico_provas.php" class="btn btn-secondary">
                📋 Voltar ao Histórico
            </a>
            <a href="simulador_provas.php" class="btn btn-primary">
                🔄 Nova Prova
            </a>
        </div>
    </div>

    <!-- Navegação lateral -->
    <div class="navegacao">
        <h4 style="margin-bottom: 1rem; text-align: center;">Questões</h4>
        <?php foreach ($questoes as $questao): ?>
            <?php
            $respondida = !empty($questao['resposta_usuario']) || !empty($questao['resposta_dissertativa_usuario']);
            $correta = $questao['esta_correta'] == 1;
            
            if (!$respondida) {
                $nav_class = 'nao-respondida';
            } elseif ($correta) {
                $nav_class = 'correta';
            } else {
                $nav_class = 'incorreta';
            }
            ?>
            <button class="nav-questao <?php echo $nav_class; ?>" 
                    onclick="document.getElementById('questao-<?php echo $questao['numero_questao']; ?>').scrollIntoView({behavior: 'smooth'})">
                <?php echo $questao['numero_questao']; ?>
            </button>
        <?php endforeach; ?>
    </div>
</body>
</html>
