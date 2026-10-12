<?php
require_once 'config.php';
require_once 'verificar_auth.php';

// Verificar se o usuário está logado
verificarLogin();

// Conectar ao banco de dados
$pdo = conectarBD();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

if (!isset($_POST['sessao_id']) || !isset($_POST['finalizar'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Dados incompletos']);
    exit;
}

$sessao_id = $_POST['sessao_id'];
$respostas = json_decode($_POST['respostas'] ?? '{}', true);

try {
    // Verificar se a sessão existe e pertence ao usuário
    $stmt = $pdo->prepare("SELECT * FROM sessoes_teste WHERE id = ? AND usuario_id = ? AND status = 'ativo'");
    $stmt->execute([$sessao_id, $_SESSION['usuario_id']]);
    $sessao = $stmt->fetch();
    
    if (!$sessao) {
        http_response_code(404);
        echo json_encode(['erro' => 'Sessão não encontrada']);
        exit;
    }
    
    // Calcular estatísticas
    $questoes_respondidas = count($respostas);
    $acertos = 0;
    $tipo_prova = $sessao['tipo_prova'];
    
    // Buscar respostas corretas
    $stmt = $pdo->prepare("SELECT id, numero_questao, resposta_correta, tipo_questao, resposta_dissertativa FROM questoes WHERE tipo_prova = ?");
    $stmt->execute([$tipo_prova]);
    $questoes_corretas = $stmt->fetchAll();

    // Organizar questões por número
    $questoes_map = [];
    foreach ($questoes_corretas as $questao) {
        $questoes_map[$questao['numero_questao']] = $questao;
    }

    // Calcular acertos e salvar respostas
    foreach ($respostas as $questao_num => $resposta_usuario) {
        if (isset($questoes_map[$questao_num])) {
            $questao = $questoes_map[$questao_num];

            // Determinar resposta correta baseada no tipo
            if ($questao['tipo_questao'] === 'dissertativa') {
                $resposta_correta = $questao['resposta_dissertativa'] ?: $questao['resposta_correta'];
            } else {
                $resposta_correta = $questao['resposta_correta'];
            }

            // Comparar respostas
            $esta_correta = strtolower(trim($resposta_usuario)) === strtolower(trim($resposta_correta));
            if ($esta_correta) {
                $acertos++;
            }

            // Salvar resposta do usuário
            $stmt_resposta = $pdo->prepare("
                INSERT INTO respostas_usuario
                (sessao_id, questao_id, questao_numero, resposta_usuario, resposta_dissertativa_usuario, resposta_correta, esta_correta, data_resposta)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                resposta_usuario = VALUES(resposta_usuario),
                resposta_dissertativa_usuario = VALUES(resposta_dissertativa_usuario),
                esta_correta = VALUES(esta_correta),
                data_resposta = NOW()
            ");

            if ($questao['tipo_questao'] === 'dissertativa') {
                $stmt_resposta->execute([
                    $sessao_id, $questao['id'], $questao_num,
                    null, $resposta_usuario, $resposta_correta, $esta_correta
                ]);
            } else {
                $stmt_resposta->execute([
                    $sessao_id, $questao['id'], $questao_num,
                    $resposta_usuario, null, $resposta_correta, $esta_correta
                ]);
            }
        }
    }
    
    // Calcular pontuação
    $total_questoes = 120; // SAT padrão
    $pontuacao = $questoes_respondidas > 0 ? ($acertos / $questoes_respondidas) * 100 : 0;
    
    // Calcular tempo gasto
    $inicio = new DateTime($sessao['inicio']);
    $fim = new DateTime();
    $tempo_gasto = $fim->getTimestamp() - $inicio->getTimestamp();
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    // Atualizar sessão
    $stmt = $pdo->prepare("UPDATE sessoes_teste SET status = 'finalizado', fim = NOW(), pontuacao_final = ?, acertos = ?, questoes_respondidas = ?, tempo_gasto = ? WHERE id = ?");
    $stmt->execute([$pontuacao, $acertos, $questoes_respondidas, $tempo_gasto, $sessao_id]);
    
    // Salvar resultado detalhado
    $stmt = $pdo->prepare("INSERT INTO resultados_testes (usuario_id, sessao_id, tipo_prova, pontuacao, acertos, total_questoes, questoes_respondidas, tempo_gasto, data_realizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['usuario_id'], $sessao_id, $tipo_prova, $pontuacao, $acertos, $total_questoes, $questoes_respondidas, $tempo_gasto]);
    
    // Confirmar transação
    $pdo->commit();
    
    echo json_encode([
        'sucesso' => true,
        'pontuacao' => $pontuacao,
        'acertos' => $acertos,
        'questoes_respondidas' => $questoes_respondidas,
        'tempo_gasto' => $tempo_gasto
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    error_log("Erro ao processar teste: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
}
?>