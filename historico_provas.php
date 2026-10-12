<?php
require_once 'config.php';
require_once 'verificar_auth.php';

verificarLogin();
$pdo = conectarBD();

// Buscar histórico de provas do usuário
$stmt = $pdo->prepare("
    SELECT 
        st.id,
        st.tipo_prova,
        st.inicio,
        st.fim,
        st.status,
        st.pontuacao_final,
        st.acertos,
        st.questoes_respondidas,
        st.tempo_gasto,
        st.duracao_minutos,
        rt.total_questoes
    FROM sessoes_teste st
    LEFT JOIN resultados_testes rt ON st.id = rt.sessao_id
    WHERE st.usuario_id = ? AND st.status = 'finalizado'
    ORDER BY st.inicio DESC
");
$stmt->execute([$_SESSION['usuario_id']]);
$historico = $stmt->fetchAll();

$config_provas = [
    'sat' => ['nome' => 'SAT', 'cor' => '#FF9800'],
    'toefl' => ['nome' => 'TOEFL', 'cor' => '#2196F3'],
    'ielts' => ['nome' => 'IELTS', 'cor' => '#4CAF50'],
    'gre' => ['nome' => 'GRE', 'cor' => '#9C27B0']
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Provas - DayDreaming</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .provas-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #667eea;
            color: white;
            padding: 1rem;
            font-weight: 600;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .prova-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .pontuacao {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .pontuacao.excelente { color: #4CAF50; }
        .pontuacao.bom { color: #FF9800; }
        .pontuacao.regular { color: #f44336; }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            table { font-size: 0.9rem; }
            th, td { padding: 0.75rem 0.5rem; }
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>

    <div class="header">
        <h1>📊 Histórico de Provas</h1>
        <p>Acompanhe seu progresso e desempenho</p>
    </div>

    <div class="container">
        <?php if (!empty($historico)): ?>
            <?php
            // Calcular estatísticas
            $total_provas = count($historico);
            $total_questoes = array_sum(array_column($historico, 'questoes_respondidas'));
            $total_acertos = array_sum(array_column($historico, 'acertos'));

            // Calcular média de pontuação correta (acertos / total de questões do exame)
            $soma_porcentagens = 0;
            foreach ($historico as $prova_hist) {
                $total_questoes_exame = [
                    'sat' => 120, 'toefl' => 100, 'ielts' => 40, 'gre' => 80
                ];
                $total_questoes_prova = $total_questoes_exame[$prova_hist['tipo_prova']] ?? 120;
                $soma_porcentagens += ($prova_hist['acertos'] / $total_questoes_prova) * 100;
            }
            $media_pontuacao = $total_provas > 0 ? $soma_porcentagens / $total_provas : 0;
            $tempo_total = array_sum(array_column($historico, 'tempo_gasto'));
            ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_provas; ?></div>
                    <div class="stat-label">Provas Realizadas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_questoes; ?></div>
                    <div class="stat-label">Questões Respondidas</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo gmdate('H:i', $tempo_total); ?></div>
                    <div class="stat-label">Tempo Total de Estudo</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo number_format($media_pontuacao, 1); ?>%</div>
                    <div class="stat-label">Média de Pontuação</div>
                </div>
            </div>

            <div class="provas-table">
                <div class="table-header">
                    📋 Histórico Detalhado de Provas
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Prova</th>
                            <th>Data/Hora</th>
                            <th>Questões</th>
                            <th>Acertos</th>
                            <th>Pontuação</th>
                            <th>Tempo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $prova): ?>
                            <?php
                            $config = $config_provas[$prova['tipo_prova']] ?? $config_provas['sat'];

                            // Calcular total de questões do exame baseado no tipo
                            $total_questoes_exame = [
                                'sat' => 120,
                                'toefl' => 100,
                                'ielts' => 40,
                                'gre' => 80
                            ];
                            $total_questoes = $total_questoes_exame[$prova['tipo_prova']] ?? 120;

                            // Calcular porcentagem correta: acertos / total de questões do exame
                            $porcentagem = ($prova['acertos'] / $total_questoes) * 100;
                            $classe_pontuacao = $porcentagem >= 80 ? 'excelente' : ($porcentagem >= 60 ? 'bom' : 'regular');
                            $tempo_formatado = gmdate('H:i:s', $prova['tempo_gasto']);
                            ?>
                            <tr>
                                <td>
                                    <span class="prova-badge" style="background: <?php echo $config['cor']; ?>">
                                        <?php echo $config['nome']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($prova['inicio'])); ?></td>
                                <td>
                                    <strong><?php echo $prova['questoes_respondidas']; ?></strong>
                                    <?php if ($prova['total_questoes']): ?>
                                        / <?php echo $prova['total_questoes']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo $prova['acertos']; ?></strong></td>
                                <td>
                                    <span class="pontuacao <?php echo $classe_pontuacao; ?>">
                                        <?php echo number_format($porcentagem, 1); ?>%
                                    </span>
                                </td>
                                <td><?php echo $tempo_formatado; ?></td>
                                <td>
                                    <a href="revisar_prova.php?sessao=<?php echo $prova['id']; ?>" 
                                       class="btn btn-primary">
                                        🔍 Revisar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="simulador_provas.php" class="btn btn-primary">
                    🔄 Nova Prova
                </a>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
                <h2>Nenhuma prova realizada ainda</h2>
                <p>Comece fazendo seu primeiro simulado!</p>
                <br>
                <a href="simulador_provas.php" class="btn btn-primary">
                    🚀 Fazer Primeira Prova
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
