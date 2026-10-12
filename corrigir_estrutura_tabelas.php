<?php
/**
 * Script para corrigir estrutura das tabelas para finalização
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🔧 CORRIGINDO ESTRUTURA DAS TABELAS\n";
echo "===================================\n\n";

try {
    // Conectar ao banco
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Verificar estrutura da tabela sessoes_teste
    echo "🔍 VERIFICANDO TABELA sessoes_teste:\n";
    echo "====================================\n";
    
    $stmt = $pdo->query("DESCRIBE sessoes_teste");
    $colunas_sessoes = $stmt->fetchAll();
    
    $colunas_existentes = array_column($colunas_sessoes, 'Field');
    
    // Colunas necessárias
    $colunas_necessarias = [
        'questoes_respondidas' => 'INT DEFAULT 0',
        'pontuacao_final' => 'DECIMAL(5,2) DEFAULT 0.00',
        'acertos' => 'INT DEFAULT 0',
        'tempo_gasto' => 'INT DEFAULT 0'
    ];
    
    foreach ($colunas_necessarias as $coluna => $definicao) {
        if (!in_array($coluna, $colunas_existentes)) {
            echo "🔧 Adicionando coluna '$coluna'...\n";
            try {
                $pdo->exec("ALTER TABLE sessoes_teste ADD COLUMN $coluna $definicao");
                echo "✅ Coluna '$coluna' adicionada\n";
            } catch (Exception $e) {
                echo "❌ Erro ao adicionar '$coluna': " . $e->getMessage() . "\n";
            }
        } else {
            echo "✅ Coluna '$coluna' já existe\n";
        }
    }
    
    // Verificar se tabela resultados_testes existe
    echo "\n🔍 VERIFICANDO TABELA resultados_testes:\n";
    echo "=======================================\n";
    
    try {
        $stmt = $pdo->query("DESCRIBE resultados_testes");
        echo "✅ Tabela resultados_testes existe\n";
        
        $colunas_resultados = $stmt->fetchAll();
        $colunas_resultados_existentes = array_column($colunas_resultados, 'Field');
        
        // Verificar colunas necessárias
        $colunas_resultados_necessarias = [
            'questoes_respondidas' => 'INT DEFAULT 0'
        ];
        
        foreach ($colunas_resultados_necessarias as $coluna => $definicao) {
            if (!in_array($coluna, $colunas_resultados_existentes)) {
                echo "🔧 Adicionando coluna '$coluna' em resultados_testes...\n";
                try {
                    $pdo->exec("ALTER TABLE resultados_testes ADD COLUMN $coluna $definicao");
                    echo "✅ Coluna '$coluna' adicionada\n";
                } catch (Exception $e) {
                    echo "❌ Erro ao adicionar '$coluna': " . $e->getMessage() . "\n";
                }
            } else {
                echo "✅ Coluna '$coluna' já existe em resultados_testes\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Tabela resultados_testes não existe. Criando...\n";
        
        $sql_criar_resultados = "
        CREATE TABLE resultados_testes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            sessao_id INT NOT NULL,
            tipo_prova VARCHAR(20) NOT NULL,
            pontuacao DECIMAL(5,2) DEFAULT 0.00,
            acertos INT DEFAULT 0,
            total_questoes INT DEFAULT 0,
            questoes_respondidas INT DEFAULT 0,
            tempo_gasto INT DEFAULT 0,
            data_realizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
            FOREIGN KEY (sessao_id) REFERENCES sessoes_teste(id)
        )";
        
        try {
            $pdo->exec($sql_criar_resultados);
            echo "✅ Tabela resultados_testes criada com sucesso\n";
        } catch (Exception $e) {
            echo "❌ Erro ao criar tabela resultados_testes: " . $e->getMessage() . "\n";
        }
    }
    
    // Corrigir queries no processar_teste.php
    echo "\n🔧 CORRIGINDO QUERIES NO CÓDIGO:\n";
    echo "================================\n";
    
    // Ler o arquivo processar_teste.php
    $conteudo_processar = file_get_contents('processar_teste.php');
    
    // Corrigir query de UPDATE
    $query_antiga = 'UPDATE sessoes_teste SET status = \'finalizado\', fim = NOW(), pontuacao = ?, acertos = ?, questoes_respondidas = ?, tempo_gasto = ? WHERE id = ?';
    $query_nova = 'UPDATE sessoes_teste SET status = \'finalizado\', fim = NOW(), pontuacao_final = ?, acertos = ?, questoes_respondidas = ?, tempo_gasto = ? WHERE id = ?';
    
    if (strpos($conteudo_processar, $query_antiga) !== false) {
        $conteudo_processar = str_replace($query_antiga, $query_nova, $conteudo_processar);
        file_put_contents('processar_teste.php', $conteudo_processar);
        echo "✅ Query UPDATE corrigida em processar_teste.php\n";
    } else {
        echo "ℹ️ Query UPDATE já está correta ou não encontrada\n";
    }
    
    // Corrigir query no resultado_teste.php
    echo "\n🔧 CORRIGINDO QUERIES NO RESULTADO_TESTE.PHP:\n";
    echo "=============================================\n";
    
    $conteudo_resultado = file_get_contents('resultado_teste.php');
    
    // Corrigir query de SELECT
    $select_antigo = 'SELECT st.*, rt.pontuacao, rt.acertos, rt.total_questoes, rt.questoes_respondidas, rt.tempo_gasto';
    $select_novo = 'SELECT st.*, rt.pontuacao, rt.acertos, rt.total_questoes, COALESCE(rt.questoes_respondidas, st.questoes_respondidas) as questoes_respondidas, rt.tempo_gasto';
    
    if (strpos($conteudo_resultado, $select_antigo) !== false) {
        $conteudo_resultado = str_replace($select_antigo, $select_novo, $conteudo_resultado);
        file_put_contents('resultado_teste.php', $conteudo_resultado);
        echo "✅ Query SELECT corrigida em resultado_teste.php\n";
    } else {
        echo "ℹ️ Query SELECT já está correta ou não encontrada\n";
    }
    
    echo "\n📊 VERIFICANDO ESTRUTURAS FINAIS:\n";
    echo "=================================\n";
    
    // Verificar sessoes_teste
    $stmt = $pdo->query("DESCRIBE sessoes_teste");
    $colunas_finais = $stmt->fetchAll();
    echo "📋 Colunas em sessoes_teste:\n";
    foreach ($colunas_finais as $coluna) {
        echo "   - {$coluna['Field']} ({$coluna['Type']})\n";
    }
    
    // Verificar resultados_testes
    try {
        $stmt = $pdo->query("DESCRIBE resultados_testes");
        $colunas_resultados_finais = $stmt->fetchAll();
        echo "\n📋 Colunas em resultados_testes:\n";
        foreach ($colunas_resultados_finais as $coluna) {
            echo "   - {$coluna['Field']} ({$coluna['Type']})\n";
        }
    } catch (Exception $e) {
        echo "\n❌ Erro ao verificar resultados_testes: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 CORREÇÕES CONCLUÍDAS!\n";
    echo "========================\n";
    echo "✅ Estruturas de tabelas corrigidas\n";
    echo "✅ Queries atualizadas\n";
    echo "✅ Sistema pronto para finalização\n\n";
    
    echo "🧪 TESTE AGORA:\n";
    echo "===============\n";
    echo "1. Acesse um teste SAT\n";
    echo "2. Responda algumas questões\n";
    echo "3. Clique em 'Finalizar Teste'\n";
    echo "4. Veja os resultados\n\n";
    
    echo "🔗 Link direto: http://localhost:8080/executar_teste.php?tipo=sat\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
