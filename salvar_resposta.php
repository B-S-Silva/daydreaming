<?php
/**
 * Script para salvar respostas via AJAX
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once 'config.php';
require_once 'verificar_auth.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

try {
    // Conectar ao banco
    $pdo = conectarBD();
    
    // Obter dados da requisição
    $sessao_id = $_POST['sessao_id'] ?? null;
    $questao_numero = $_POST['questao_numero'] ?? null;
    $resposta = $_POST['resposta'] ?? null;
    $tipo_resposta = $_POST['tipo_resposta'] ?? 'multipla_escolha';
    
    // Validar dados obrigatórios
    if (!$sessao_id || !$questao_numero || !$resposta) {
        echo json_encode(['success' => false, 'error' => 'Dados obrigatórios não fornecidos']);
        exit;
    }
    
    // Verificar se a sessão pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT id, usuario_id, tipo_prova, status 
        FROM sessoes_teste 
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([$sessao_id, $_SESSION['usuario_id']]);
    $sessao = $stmt->fetch();
    
    if (!$sessao) {
        echo json_encode(['success' => false, 'error' => 'Sessão não encontrada ou não autorizada']);
        exit;
    }
    
    if ($sessao['status'] !== 'ativo') {
        echo json_encode(['success' => false, 'error' => 'Sessão não está ativa']);
        exit;
    }
    
    // Obter dados da questão
    $stmt = $pdo->prepare("
        SELECT id, resposta_correta, tipo_questao, resposta_dissertativa
        FROM questoes 
        WHERE tipo_prova = ? AND numero_questao = ?
    ");
    $stmt->execute([$sessao['tipo_prova'], $questao_numero]);
    $questao = $stmt->fetch();
    
    if (!$questao) {
        echo json_encode(['success' => false, 'error' => 'Questão não encontrada']);
        exit;
    }
    
    // Determinar se a resposta está correta
    $esta_correta = false;
    
    if ($questao['tipo_questao'] === 'dissertativa') {
        // Para questões dissertativas, comparar com a resposta esperada
        $resposta_esperada = $questao['resposta_dissertativa'] ?: $questao['resposta_correta'];
        
        // Normalizar respostas para comparação (remover espaços, converter para minúsculas)
        $resposta_normalizada = trim(strtolower($resposta));
        $esperada_normalizada = trim(strtolower($resposta_esperada));
        
        // Verificar se a resposta está correta (pode ser exata ou conter a resposta esperada)
        $esta_correta = ($resposta_normalizada === $esperada_normalizada) || 
                       (strpos($resposta_normalizada, $esperada_normalizada) !== false);
        
    } else {
        // Para questões de múltipla escolha
        $esta_correta = (strtolower($resposta) === strtolower($questao['resposta_correta']));
    }
    
    // Verificar se já existe uma resposta para esta questão
    $stmt = $pdo->prepare("
        SELECT id FROM respostas_usuario 
        WHERE sessao_id = ? AND questao_numero = ?
    ");
    $stmt->execute([$sessao_id, $questao_numero]);
    $resposta_existente = $stmt->fetch();
    
    if ($resposta_existente) {
        // Atualizar resposta existente
        if ($tipo_resposta === 'dissertativa') {
            $stmt = $pdo->prepare("
                UPDATE respostas_usuario 
                SET resposta_dissertativa_usuario = ?, 
                    resposta_usuario = NULL,
                    esta_correta = ?,
                    data_resposta = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$resposta, $esta_correta, $resposta_existente['id']]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE respostas_usuario 
                SET resposta_usuario = ?, 
                    resposta_dissertativa_usuario = NULL,
                    esta_correta = ?,
                    data_resposta = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$resposta, $esta_correta, $resposta_existente['id']]);
        }
        
    } else {
        // Inserir nova resposta
        if ($tipo_resposta === 'dissertativa') {
            $stmt = $pdo->prepare("
                INSERT INTO respostas_usuario 
                (sessao_id, questao_id, questao_numero, resposta_dissertativa_usuario, resposta_correta, esta_correta, data_resposta)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $sessao_id, 
                $questao['id'], 
                $questao_numero, 
                $resposta, 
                $questao['resposta_correta'], 
                $esta_correta
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO respostas_usuario 
                (sessao_id, questao_id, questao_numero, resposta_usuario, resposta_correta, esta_correta, data_resposta)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $sessao_id, 
                $questao['id'], 
                $questao_numero, 
                $resposta, 
                $questao['resposta_correta'], 
                $esta_correta
            ]);
        }
    }
    
    // Atualizar contador de questões respondidas na sessão
    $stmt = $pdo->prepare("
        UPDATE sessoes_teste 
        SET questoes_respondidas = (
            SELECT COUNT(*) FROM respostas_usuario 
            WHERE sessao_id = ? AND (resposta_usuario IS NOT NULL OR resposta_dissertativa_usuario IS NOT NULL)
        )
        WHERE id = ?
    ");
    $stmt->execute([$sessao_id, $sessao_id]);
    
    // Log da ação
    $stmt = $pdo->prepare("
        INSERT INTO logs_sistema (usuario_id, acao, tabela_afetada, registro_id, detalhes)
        VALUES (?, 'resposta_salva', 'respostas_usuario', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['usuario_id'],
        $sessao_id,
        json_encode([
            'questao_numero' => $questao_numero,
            'tipo_resposta' => $tipo_resposta,
            'esta_correta' => $esta_correta,
            'resposta_length' => strlen($resposta)
        ])
    ]);
    
    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'esta_correta' => $esta_correta,
        'tipo_resposta' => $tipo_resposta,
        'questao_numero' => $questao_numero,
        'message' => 'Resposta salva com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao salvar resposta: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Erro interno do servidor',
        'details' => $e->getMessage()
    ]);
}
?>
