<?php
/**
 * Script para corrigir completamente o sistema de finalização
 */

echo "🔧 CORREÇÃO COMPLETA DO SISTEMA DE FINALIZAÇÃO\n";
echo "==============================================\n\n";

// 1. Corrigir coluna 'acertou' na tabela respostas_usuario
try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Verificar se coluna 'acertou' existe
    echo "🔍 Verificando coluna 'acertou' em respostas_usuario...\n";
    
    $stmt = $pdo->query("DESCRIBE respostas_usuario");
    $colunas = $stmt->fetchAll();
    $colunas_existentes = array_column($colunas, 'Field');
    
    if (!in_array('acertou', $colunas_existentes)) {
        echo "🔧 Adicionando coluna 'acertou'...\n";
        $pdo->exec("ALTER TABLE respostas_usuario ADD COLUMN acertou BOOLEAN DEFAULT FALSE");
        echo "✅ Coluna 'acertou' adicionada\n";
    } else {
        echo "✅ Coluna 'acertou' já existe\n";
    }
    
    // 2. Corrigir processar_teste.php
    echo "\n🔧 Corrigindo processar_teste.php...\n";
    
    $processar_content = '<?php
require_once \'config.php\';
require_once \'verificar_auth.php\';

// Verificar se o usuário está logado
verificarLogin();

// Conectar ao banco de dados
$pdo = conectarBD();

header(\'Content-Type: application/json\');

if ($_SERVER[\'REQUEST_METHOD\'] !== \'POST\') {
    http_response_code(405);
    echo json_encode([\'erro\' => \'Método não permitido\']);
    exit;
}

if (!isset($_POST[\'sessao_id\']) || !isset($_POST[\'finalizar\'])) {
    http_response_code(400);
    echo json_encode([\'erro\' => \'Dados incompletos\']);
    exit;
}

$sessao_id = $_POST[\'sessao_id\'];
$respostas = json_decode($_POST[\'respostas\'] ?? \'{}\', true);

try {
    // Verificar se a sessão existe e pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM sessoes_teste WHERE id = ? AND usuario_id = ? AND status = \'ativo\'");
    $stmt->execute([$sessao_id, $_SESSION[\'usuario_id\']]);
    $sessao = $stmt->fetch();
    
    if (!$sessao) {
        http_response_code(404);
        echo json_encode([\'erro\' => \'Sessão não encontrada\']);
        exit;
    }
    
    // Calcular estatísticas
    $questoes_respondidas = count($respostas);
    $acertos = 0;
    $tipo_prova = $sessao[\'tipo_prova\'];
    
    // Buscar respostas corretas
    $stmt = $pdo->prepare("SELECT numero_questao, resposta_correta, tipo_questao, resposta_dissertativa FROM questoes WHERE tipo_prova = ?");
    $stmt->execute([$tipo_prova]);
    $questoes_corretas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Calcular acertos
    foreach ($respostas as $questao_num => $resposta_usuario) {
        if (isset($questoes_corretas[$questao_num])) {
            $resposta_correta = $questoes_corretas[$questao_num];
            if (strtolower(trim($resposta_usuario)) === strtolower(trim($resposta_correta))) {
                $acertos++;
            }
        }
    }
    
    // Calcular pontuação
    $total_questoes = 120; // SAT padrão
    $pontuacao = $questoes_respondidas > 0 ? ($acertos / $questoes_respondidas) * 100 : 0;
    
    // Calcular tempo gasto
    $inicio = new DateTime($sessao[\'inicio\']);
    $fim = new DateTime();
    $tempo_gasto = $fim->getTimestamp() - $inicio->getTimestamp();
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Atualizar sessão
    $stmt = $pdo->prepare("UPDATE sessoes_teste SET status = \'finalizado\', fim = NOW(), pontuacao_final = ?, acertos = ?, questoes_respondidas = ?, tempo_gasto = ? WHERE id = ?");
    $stmt->execute([$pontuacao, $acertos, $questoes_respondidas, $tempo_gasto, $sessao_id]);
    
    // Salvar resultado detalhado
    $stmt = $pdo->prepare("INSERT INTO resultados_testes (usuario_id, sessao_id, tipo_prova, pontuacao, acertos, total_questoes, questoes_respondidas, tempo_gasto, data_realizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION[\'usuario_id\'], $sessao_id, $tipo_prova, $pontuacao, $acertos, $total_questoes, $questoes_respondidas, $tempo_gasto]);
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode([
        \'sucesso\' => true,
        \'pontuacao\' => $pontuacao,
        \'acertos\' => $acertos,
        \'questoes_respondidas\' => $questoes_respondidas,
        \'tempo_gasto\' => $tempo_gasto
    ]);
    
} catch (Exception $e) {
    $pdo->rollback();
    error_log("Erro ao processar teste: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([\'erro\' => \'Erro interno do servidor\']);
}
?>';
    
    file_put_contents('processar_teste.php', $processar_content);
    echo "✅ processar_teste.php reescrito\n";
    
    // 3. Criar resultado_teste.php simplificado
    echo "\n🔧 Criando resultado_teste.php simplificado...\n";
    
    $resultado_content = '<?php
require_once \'config.php\';
require_once \'verificar_auth.php\';

verificarLogin();
$pdo = conectarBD();

$sessao_id = $_GET[\'sessao\'] ?? null;
if (!$sessao_id) {
    header(\'Location: simulador_provas.php\');
    exit;
}

// Buscar dados da sessão e resultado
$stmt = $pdo->prepare("
    SELECT st.*, rt.pontuacao, rt.acertos, rt.total_questoes, rt.questoes_respondidas, rt.tempo_gasto
    FROM sessoes_teste st
    LEFT JOIN resultados_testes rt ON st.id = rt.sessao_id
    WHERE st.id = ? AND st.usuario_id = ?
");
$stmt->execute([$sessao_id, $_SESSION[\'usuario_id\']]);
$resultado = $stmt->fetch();

if (!$resultado) {
    header(\'Location: simulador_provas.php\');
    exit;
}

$config_provas = [
    \'sat\' => [\'nome\' => \'SAT\', \'cor\' => \'#FF9800\'],
    \'toefl\' => [\'nome\' => \'TOEFL\', \'cor\' => \'#2196F3\'],
    \'ielts\' => [\'nome\' => \'IELTS\', \'cor\' => \'#4CAF50\'],
    \'gre\' => [\'nome\' => \'GRE\', \'cor\' => \'#9C27B0\']
];

$prova = $config_provas[$resultado[\'tipo_prova\']] ?? $config_provas[\'sat\'];
$pontuacao = $resultado[\'pontuacao\'] ?? $resultado[\'pontuacao_final\'] ?? 0;
$acertos = $resultado[\'acertos\'] ?? 0;
$questoes_respondidas = $resultado[\'questoes_respondidas\'] ?? 0;
$tempo_gasto = $resultado[\'tempo_gasto\'] ?? 0;

// Formatação
$tempo_formatado = gmdate("H:i:s", $tempo_gasto);
$porcentagem = number_format($pontuacao, 1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado do Teste - <?php echo $prova[\'nome\']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .pontuacao { font-size: 3rem; font-weight: bold; color: <?php echo $prova[\'cor\']; ?>; margin: 20px 0; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 30px 0; }
        .stat { text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        .stat-value { font-size: 2rem; font-weight: bold; color: <?php echo $prova[\'cor\']; ?>; }
        .stat-label { color: #666; margin-top: 5px; }
        .actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
        .btn { padding: 12px 24px; border: none; border-radius: 5px; text-decoration: none; font-weight: 600; cursor: pointer; }
        .btn-primary { background: <?php echo $prova[\'cor\']; ?>; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Resultado do Teste</h1>
            <h2><?php echo $prova[\'nome\']; ?></h2>
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
            <a href="simulador_provas.php" class="btn btn-primary">🔄 Novo Teste</a>
            <a href="dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
        </div>
    </div>
</body>
</html>';
    
    file_put_contents('resultado_teste.php', $resultado_content);
    echo "✅ resultado_teste.php reescrito\n";
    
    echo "\n🎉 CORREÇÃO COMPLETA FINALIZADA!\n";
    echo "=================================\n";
    echo "✅ Estrutura do banco corrigida\n";
    echo "✅ processar_teste.php reescrito\n";
    echo "✅ resultado_teste.php simplificado\n";
    echo "✅ Sistema pronto para finalização\n\n";
    
    echo "🧪 TESTE AGORA:\n";
    echo "===============\n";
    echo "1. http://localhost:8080/executar_teste.php?tipo=sat\n";
    echo "2. Responda algumas questões\n";
    echo "3. Clique em \'Finalizar Teste\'\n";
    echo "4. Veja os resultados\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>
