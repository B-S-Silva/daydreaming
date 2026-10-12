<?php
require_once 'config.php';
require_once 'verificar_auth.php';

verificarLogin();
$pdo = conectarBD();

$sessao_id = $_GET['sessao'] ?? null;
if (!$sessao_id) {
    header('Location: simulador_provas.php');
    exit;
}

// Buscar dados da sessão e resultado
$stmt = $pdo->prepare("
    SELECT st.*, rt.pontuacao, rt.acertos, rt.total_questoes, rt.questoes_respondidas, rt.tempo_gasto
    FROM sessoes_teste st
    LEFT JOIN resultados_testes rt ON st.id = rt.sessao_id
    WHERE st.id = ? AND st.usuario_id = ?
");
$stmt->execute([$sessao_id, $_SESSION['usuario_id']]);
$resultado = $stmt->fetch();

if (!$resultado) {
    header('Location: simulador_provas.php');
    exit;
}

$config_provas = [
    'sat' => ['nome' => 'SAT', 'cor' => '#FF9800'],
    'toefl' => ['nome' => 'TOEFL', 'cor' => '#2196F3'],
    'ielts' => ['nome' => 'IELTS', 'cor' => '#4CAF50'],
    'gre' => ['nome' => 'GRE', 'cor' => '#9C27B0']
];

$prova = $config_provas[$resultado['tipo_prova']] ?? $config_provas['sat'];
$pontuacao = $resultado['pontuacao'] ?? $resultado['pontuacao_final'] ?? 0;
$acertos = $resultado['acertos'] ?? 0;
$questoes_respondidas = $resultado['questoes_respondidas'] ?? 0;
$tempo_gasto = $resultado['tempo_gasto'] ?? 0;

// Formatação
$tempo_formatado = gmdate("H:i:s", $tempo_gasto);
$porcentagem = number_format($pontuacao, 1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado do Teste - <?php echo $prova['nome']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .pontuacao { font-size: 3rem; font-weight: bold; color: <?php echo $prova['cor']; ?>; margin: 20px 0; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .stat-value { font-size: 2rem; font-weight: bold; color: <?php echo $prova['cor']; ?>; }
        .stat-label { color: #666; margin-top: 5px; }
        .actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
        .btn { padding: 12px 24px; border: none; border-radius: 5px; text-decoration: none; font-weight: 600; cursor: pointer; }
        .btn-primary { background: <?php echo $prova['cor']; ?>; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <?php include 'header_status.php'; ?>

    <div class="container">
        <div class="header">
            <h1>🎉 Resultado do Teste</h1>
            <h2><?php echo $prova['nome']; ?></h2>
            <div class="pontuacao"><?php echo $porcentagem; ?>%</div>
        </div>
        
        <div class="stats">
            <div class="stat">
                <div class="stat-value"><?php echo $acertos; ?></div>
                <div class="stat-label">Questões Acertadas</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $questoes_respondidas; ?></div>
                <div class="stat-label">Questões Respondidas</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?php echo $tempo_formatado; ?></div>
                <div class="stat-label">Tempo Gasto</div>
            </div>
        </div>
        
        <div class="actions">
            <a href="revisar_prova.php?sessao=<?php echo $sessao_id; ?>" class="btn btn-primary">🔍 Revisar Prova</a>
            <a href="historico_provas.php" class="btn btn-secondary">📋 Histórico</a>
            <a href="simulador_provas.php" class="btn btn-primary">🔄 Novo Teste</a>
        </div>
    </div>
</body>
</html>