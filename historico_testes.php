<?php
require_once 'config.php';
require_once 'verificar_auth.php';
require_once 'badges_manager.php';

// Verificar se o usuário está logado
verificarLogin();

// Conectar ao banco de dados
$pdo = conectarBD();

// Buscar histórico de testes do usuário
$stmt = $pdo->prepare("
    SELECT rt.*, st.inicio, st.fim
    FROM resultados_testes rt
    INNER JOIN sessoes_teste st ON rt.sessao_id = st.id
    WHERE rt.usuario_id = ?
    ORDER BY rt.data_realizacao DESC
    LIMIT 50
");
$stmt->execute([$_SESSION['user_id']]);
$historico = $stmt->fetchAll();

// Buscar estatísticas gerais
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_testes,
        AVG(pontuacao) as media_pontuacao,
        MAX(pontuacao) as melhor_pontuacao,
        MIN(pontuacao) as pior_pontuacao,
        tipo_prova,
        COUNT(*) as testes_por_tipo
    FROM resultados_testes 
    WHERE usuario_id = ?
    GROUP BY tipo_prova
");
$stmt->execute([$_SESSION['user_id']]);
$estatisticas_por_tipo = $stmt->fetchAll();

// Estatísticas gerais
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_testes,
        AVG(pontuacao) as media_geral,
        MAX(pontuacao) as melhor_pontuacao,
        SUM(CASE WHEN pontuacao >= 70 THEN 1 ELSE 0 END) as testes_aprovados
    FROM resultados_testes 
    WHERE usuario_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats_gerais = $stmt->fetch();

// Buscar badges do usuário
$stmt = $pdo->prepare("
    SELECT b.nome, b.descricao, b.icone, ub.data_conquista
    FROM badges b 
    INNER JOIN usuario_badges ub ON b.id = ub.badge_id 
    WHERE ub.usuario_id = ?
    ORDER BY ub.data_conquista DESC
");
$stmt->execute([$_SESSION['user_id']]);
$badges = $stmt->fetchAll();

// Configurações das provas
$config_provas = [
    'toefl' => ['nome' => 'TOEFL', 'cor' => '#4CAF50', 'icone' => '🇺🇸'],
    'ielts' => ['nome' => 'IELTS', 'cor' => '#2196F3', 'icone' => '🇬🇧'],
    'sat' => ['nome' => 'SAT', 'cor' => '#FF9800', 'icone' => '🎓'],
    'dele' => ['nome' => 'DELE', 'cor' => '#E91E63', 'icone' => '🇪🇸'],
    'delf' => ['nome' => 'DELF', 'cor' => '#3F51B5', 'icone' => '🇫🇷'],
    'testdaf' => ['nome' => 'TestDaF', 'cor' => '#795548', 'icone' => '🇩🇪'],
    'jlpt' => ['nome' => 'JLPT', 'cor' => '#9C27B0', 'icone' => '🇯🇵'],
    'hsk' => ['nome' => 'HSK', 'cor' => '#F44336', 'icone' => '🇨🇳']
];

function obterNivelDesempenho($pontuacao) {
    if ($pontuacao >= 90) return ['nivel' => 'Excelente', 'cor' => '#4CAF50', 'icone' => '🏆'];
    if ($pontuacao >= 80) return ['nivel' => 'Muito Bom', 'cor' => '#2196F3', 'icone' => '🥇'];
    if ($pontuacao >= 70) return ['nivel' => 'Bom', 'cor' => '#FF9800', 'icone' => '🥈'];
    if ($pontuacao >= 60) return ['nivel' => 'Satisfatório', 'cor' => '#FFC107', 'icone' => '🥉'];
    return ['nivel' => 'Precisa Melhorar', 'cor' => '#F44336', 'icone' => '📚'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Testes - <?php echo NOME_APP; ?></title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        .historico-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .historico-header {
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .historico-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .estatisticas-gerais {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .stat-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .secoes-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .historico-testes {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .badges-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .teste-item {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .teste-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .teste-icone {
            font-size: 2rem;
            margin-right: 20px;
            width: 60px;
            text-align: center;
        }
        
        .teste-info {
            flex: 1;
        }
        
        .teste-nome {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .teste-data {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .teste-detalhes {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .teste-pontuacao {
            text-align: right;
            min-width: 80px;
        }
        
        .pontuacao-valor {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .nivel-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        .badge-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border-radius: 10px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .badge-icone {
            font-size: 1.5rem;
            margin-right: 15px;
        }
        
        .badge-info {
            flex: 1;
        }
        
        .badge-nome {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .badge-data {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .estatisticas-tipo {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .tipo-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .tipo-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: white;
        }
        
        .filtros {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filtro-grupo {
            display: flex;
            flex-direction: column;
        }
        
        .filtro-grupo label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .filtro-grupo select,
        .filtro-grupo input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .btn-filtrar {
            padding: 8px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .sem-testes {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .sem-testes img {
            width: 100px;
            opacity: 0.5;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .secoes-container {
                grid-template-columns: 1fr;
            }
            
            .teste-item {
                flex-direction: column;
                text-align: center;
            }
            
            .teste-icone {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .teste-pontuacao {
                text-align: center;
                margin-top: 10px;
            }
            
            .estatisticas-gerais {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>
    
    <div class="historico-container">
        <div class="historico-header">
            <h1>📊 Histórico de Testes</h1>
            <p>Acompanhe seu progresso e evolução</p>
        </div>
        
        <!-- Estatísticas Gerais -->
        <div class="estatisticas-gerais">
            <div class="stat-card">
                <h3>Total de Testes</h3>
                <div class="stat-value"><?php echo $stats_gerais['total_testes'] ?? 0; ?></div>
                <div class="stat-label">realizados</div>
            </div>
            
            <div class="stat-card">
                <h3>Média Geral</h3>
                <div class="stat-value"><?php echo number_format($stats_gerais['media_geral'] ?? 0, 1); ?>%</div>
                <div class="stat-label">pontuação média</div>
            </div>
            
            <div class="stat-card">
                <h3>Melhor Resultado</h3>
                <div class="stat-value"><?php echo number_format($stats_gerais['melhor_pontuacao'] ?? 0, 1); ?>%</div>
                <div class="stat-label">maior pontuação</div>
            </div>
            
            <div class="stat-card">
                <h3>Taxa de Aprovação</h3>
                <div class="stat-value"><?php echo $stats_gerais['total_testes'] > 0 ? number_format(($stats_gerais['testes_aprovados'] / $stats_gerais['total_testes']) * 100, 1) : 0; ?>%</div>
                <div class="stat-label">≥ 70% de acerto</div>
            </div>
        </div>
        
        <!-- Estatísticas por Tipo -->
        <?php if (!empty($estatisticas_por_tipo)): ?>
        <div class="estatisticas-tipo">
            <h2 style="text-align: center; margin-bottom: 25px;">📈 Desempenho por Tipo de Prova</h2>
            <div class="tipo-stats">
                <?php foreach ($estatisticas_por_tipo as $stat): ?>
                    <?php $prova = $config_provas[$stat['tipo_prova']]; ?>
                    <div class="tipo-card" style="background: <?php echo $prova['cor']; ?>">
                        <div style="font-size: 2rem; margin-bottom: 10px;"><?php echo $prova['icone']; ?></div>
                        <h3><?php echo $prova['nome']; ?></h3>
                        <div style="font-size: 1.5rem; font-weight: bold; margin: 10px 0;">
                            <?php echo number_format($stat['media_pontuacao'], 1); ?>%
                        </div>
                        <div style="opacity: 0.9;"><?php echo $stat['testes_por_tipo']; ?> testes realizados</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="secoes-container">
            <!-- Histórico de Testes -->
            <div class="historico-testes">
                <h2 style="margin-bottom: 20px;">📝 Últimos Testes</h2>
                
                <!-- Filtros -->
                <div class="filtros">
                    <div class="filtros-grid">
                        <div class="filtro-grupo">
                            <label>Tipo de Prova:</label>
                            <select id="filtro-tipo">
                                <option value="">Todos os tipos</option>
                                <option value="toefl">TOEFL</option>
                            <option value="ielts">IELTS</option>
                            <option value="sat">SAT</option>
                            <option value="dele">DELE</option>
                            <option value="delf">DELF</option>
                            <option value="testdaf">TestDaF</option>
                            <option value="jlpt">JLPT</option>
                            <option value="hsk">HSK</option>
                            </select>
                        </div>
                        
                        <div class="filtro-grupo">
                            <label>Período:</label>
                            <select id="filtro-periodo">
                                <option value="">Todos os períodos</option>
                                <option value="7">Últimos 7 dias</option>
                                <option value="30">Últimos 30 dias</option>
                                <option value="90">Últimos 3 meses</option>
                            </select>
                        </div>
                        
                        <div class="filtro-grupo">
                            <button class="btn-filtrar" onclick="aplicarFiltros()">🔍 Filtrar</button>
                        </div>
                    </div>
                </div>
                
                <div id="lista-testes">
                    <?php if (empty($historico)): ?>
                        <div class="sem-testes">
                            <div style="font-size: 4rem; margin-bottom: 20px;">📝</div>
                            <h3>Nenhum teste realizado ainda</h3>
                            <p>Comece fazendo seu primeiro simulado!</p>
                            <a href="simulador_provas.php" class="btn btn-primary" style="margin-top: 15px;">Fazer Teste</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($historico as $teste): ?>
                            <?php 
                            $prova = $config_provas[$teste['tipo_prova']];
                            $desempenho = obterNivelDesempenho($teste['pontuacao']);
                            ?>
                            <div class="teste-item" onclick="window.location.href='resultado_teste.php?sessao=<?php echo $teste['sessao_id']; ?>'">
                                <div class="teste-icone"><?php echo $prova['icone']; ?></div>
                                <div class="teste-info">
                                    <div class="teste-nome"><?php echo $prova['nome']; ?></div>
                                    <div class="teste-data"><?php echo date('d/m/Y H:i', strtotime($teste['data_realizacao'])); ?></div>
                                    <div class="teste-detalhes">
                                        <span><?php echo $teste['acertos']; ?>/<?php echo $teste['total_questoes']; ?> acertos</span>
                                        <span><?php echo gmdate('H:i:s', $teste['tempo_gasto']); ?></span>
                                    </div>
                                </div>
                                <div class="teste-pontuacao">
                                    <div class="pontuacao-valor" style="color: <?php echo $desempenho['cor']; ?>">
                                        <?php echo number_format($teste['pontuacao'], 1); ?>%
                                    </div>
                                    <div class="nivel-badge" style="background: <?php echo $desempenho['cor']; ?>">
                                        <?php echo $desempenho['icone']; ?> <?php echo $desempenho['nivel']; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Badges -->
            <div class="badges-section">
                <h2 style="margin-bottom: 20px;">🏆 Suas Conquistas</h2>
                
                <?php if (empty($badges)): ?>
                    <div style="text-align: center; padding: 20px; color: #666;">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🏆</div>
                        <p>Nenhuma badge conquistada ainda.</p>
                        <p style="font-size: 0.9rem;">Faça testes para ganhar badges!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($badges as $badge): ?>
                        <div class="badge-item">
                            <div class="badge-icone"><?php echo $badge['icone']; ?></div>
                            <div class="badge-info">
                                <div class="badge-nome"><?php echo htmlspecialchars($badge['nome']); ?></div>
                                <div class="badge-data"><?php echo date('d/m/Y', strtotime($badge['data_conquista'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="simulador_provas.php" class="btn btn-primary">
                🎯 Fazer Novo Teste
            </a>
        </div>
    </div>
    
    <script src="public/js/main.js"></script>
    <script>
        function aplicarFiltros() {
            const tipo = document.getElementById('filtro-tipo').value;
            const periodo = document.getElementById('filtro-periodo').value;
            
            // Implementar filtros via JavaScript ou recarregar página com parâmetros
            let url = 'historico_testes.php?';
            const params = [];
            
            if (tipo) params.push('tipo=' + tipo);
            if (periodo) params.push('periodo=' + periodo);
            
            if (params.length > 0) {
                url += params.join('&');
            }
            
            window.location.href = url;
        }
        
        // Aplicar filtros da URL se existirem
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.get('tipo')) {
                document.getElementById('filtro-tipo').value = urlParams.get('tipo');
            }
            
            if (urlParams.get('periodo')) {
                document.getElementById('filtro-periodo').value = urlParams.get('periodo');
            }
        });
    </script>
</body>
</html>