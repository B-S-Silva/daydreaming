<?php
/**
 * Script para recriar as tabelas do fórum com a estrutura correta
 */

echo "🔄 RECRIANDO TABELAS DO FÓRUM\n";
echo "=============================\n\n";

require_once 'config.php';

try {
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // 1. Remover tabelas do fórum (em ordem para respeitar foreign keys)
    echo "🗑️ REMOVENDO TABELAS ANTIGAS:\n";
    echo "=============================\n";
    
    $tabelas_remover = [
        'forum_moderacao',
        'forum_curtidas', 
        'forum_respostas',
        'forum_topicos',
        'forum_categorias'
    ];
    
    foreach ($tabelas_remover as $tabela) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS $tabela");
            echo "✅ Tabela '$tabela' removida\n";
        } catch (Exception $e) {
            echo "⚠️ Erro ao remover '$tabela': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n📋 CRIANDO TABELAS ATUALIZADAS:\n";
    echo "===============================\n";
    
    // 2. Recriar forum_categorias
    $pdo->exec("
        CREATE TABLE forum_categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT,
            cor VARCHAR(7) DEFAULT '#007bff',
            icone VARCHAR(10) DEFAULT '📝',
            ativo BOOLEAN DEFAULT TRUE,
            ordem INT DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ativo (ativo),
            INDEX idx_ordem (ordem)
        )
    ");
    echo "✅ Tabela 'forum_categorias' criada\n";

    // 3. Recriar forum_topicos
    $pdo->exec("
        CREATE TABLE forum_topicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria_id INT NOT NULL,
            titulo VARCHAR(200) NOT NULL,
            conteudo TEXT NOT NULL,
            autor_id INT NOT NULL,
            aprovado BOOLEAN DEFAULT FALSE,
            fixado BOOLEAN DEFAULT FALSE,
            fechado BOOLEAN DEFAULT FALSE,
            visualizacoes INT DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES forum_categorias(id) ON DELETE CASCADE,
            FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_categoria (categoria_id),
            INDEX idx_autor (autor_id),
            INDEX idx_aprovado (aprovado)
        )
    ");
    echo "✅ Tabela 'forum_topicos' criada\n";

    // 4. Recriar forum_respostas
    $pdo->exec("
        CREATE TABLE forum_respostas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topico_id INT NOT NULL,
            conteudo TEXT NOT NULL,
            autor_id INT NOT NULL,
            aprovado BOOLEAN DEFAULT FALSE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (topico_id) REFERENCES forum_topicos(id) ON DELETE CASCADE,
            FOREIGN KEY (autor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            INDEX idx_topico (topico_id),
            INDEX idx_autor (autor_id),
            INDEX idx_aprovado (aprovado)
        )
    ");
    echo "✅ Tabela 'forum_respostas' criada\n";

    // 5. Recriar forum_curtidas
    $pdo->exec("
        CREATE TABLE forum_curtidas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            topico_id INT NULL,
            resposta_id INT NULL,
            tipo_curtida ENUM('like', 'dislike') DEFAULT 'like',
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (topico_id) REFERENCES forum_topicos(id) ON DELETE CASCADE,
            FOREIGN KEY (resposta_id) REFERENCES forum_respostas(id) ON DELETE CASCADE,
            UNIQUE KEY unique_curtida_topico (usuario_id, topico_id),
            UNIQUE KEY unique_curtida_resposta (usuario_id, resposta_id),
            INDEX idx_usuario (usuario_id),
            INDEX idx_topico (topico_id),
            INDEX idx_resposta (resposta_id)
        )
    ");
    echo "✅ Tabela 'forum_curtidas' criada\n";

    // 6. Recriar forum_moderacao
    $pdo->exec("
        CREATE TABLE forum_moderacao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            moderador_id INT NOT NULL,
            topico_id INT NULL,
            resposta_id INT NULL,
            acao ENUM('aprovar', 'rejeitar', 'editar', 'deletar', 'fixar', 'fechar') NOT NULL,
            motivo TEXT,
            data_acao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (moderador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (topico_id) REFERENCES forum_topicos(id) ON DELETE CASCADE,
            FOREIGN KEY (resposta_id) REFERENCES forum_respostas(id) ON DELETE CASCADE,
            INDEX idx_moderador (moderador_id),
            INDEX idx_topico (topico_id),
            INDEX idx_acao (acao)
        )
    ");
    echo "✅ Tabela 'forum_moderacao' criada\n";
    
    echo "\n📂 INSERINDO CATEGORIAS PADRÃO:\n";
    echo "===============================\n";
    
    // 7. Inserir categorias padrão
    $categorias = [
        ['Geral', 'Discussões gerais sobre estudos no exterior', '#6c757d', '💬', 1],
        ['Testes Internacionais', 'TOEFL, IELTS, SAT, GRE e outros exames', '#007bff', '📝', 2],
        ['Universidades', 'Informações sobre universidades pelo mundo', '#28a745', '🎓', 3],
        ['Bolsas de Estudo', 'Oportunidades de bolsas e financiamento', '#ffc107', '💰', 4],
        ['Experiências', 'Relatos de quem estudou no exterior', '#17a2b8', '✈️', 5],
        ['Dúvidas Técnicas', 'Problemas com o sistema e suporte', '#dc3545', '🔧', 6]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO forum_categorias (nome, descricao, cor, icone, ordem) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($categorias as $categoria) {
        $stmt->execute($categoria);
        echo "✅ Categoria '{$categoria[0]}' inserida\n";
    }
    
    echo "\n🧪 TESTANDO ESTRUTURA:\n";
    echo "======================\n";
    
    // 8. Testar queries que estavam com problema
    try {
        $sql = "SELECT t.*, u.nome as autor_nome, c.nome as categoria_nome 
                FROM forum_topicos t 
                JOIN usuarios u ON t.autor_id = u.id 
                JOIN forum_categorias c ON t.categoria_id = c.id 
                ORDER BY t.data_atualizacao DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo "✅ Query principal funcionando\n";
    } catch (Exception $e) {
        echo "❌ Erro na query: " . $e->getMessage() . "\n";
    }
    
    // Verificar estrutura final
    $stmt = $pdo->query("DESCRIBE forum_topicos");
    $campos = $stmt->fetchAll();
    $campos_nomes = array_column($campos, 'Field');
    
    if (in_array('data_atualizacao', $campos_nomes)) {
        echo "✅ Campo 'data_atualizacao' presente\n";
    } else {
        echo "❌ Campo 'data_atualizacao' ausente\n";
    }
    
    if (in_array('autor_id', $campos_nomes)) {
        echo "✅ Campo 'autor_id' presente\n";
    } else {
        echo "❌ Campo 'autor_id' ausente\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 TABELAS DO FÓRUM RECRIADAS COM SUCESSO!\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "✅ ESTRUTURA ATUALIZADA:\n";
    echo "• forum_categorias (6 categorias padrão)\n";
    echo "• forum_topicos (com data_atualizacao e autor_id)\n";
    echo "• forum_respostas (com autor_id)\n";
    echo "• forum_curtidas (sistema de likes)\n";
    echo "• forum_moderacao (ferramentas de moderação)\n\n";
    
    echo "🌐 TESTE O FÓRUM:\n";
    echo "=================\n";
    echo "http://localhost:8080/forum.php\n\n";
    
    echo "🔑 CREDENCIAIS:\n";
    echo "===============\n";
    echo "Admin: admin / admin123\n";
    echo "Teste: teste / teste123\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
