<?php
/**
 * Script Simplificado para Instalação do Banco de Dados
 */

// Configurações de conexão
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🚀 INSTALAÇÃO SIMPLIFICADA DO DAYDREAMMING\n";
echo "==========================================\n\n";

try {
    // ETAPA 1: Conectar ao MySQL
    echo "📡 ETAPA 1: Conectando ao MySQL...\n";
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✅ Conexão estabelecida!\n\n";
    
    // ETAPA 2: Criar banco de dados
    echo "📊 ETAPA 2: Criando banco de dados...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$config['database']}");
    echo "✅ Banco de dados criado/selecionado!\n\n";
    
    // ETAPA 3: Executar script de estrutura
    echo "🏗️ ETAPA 3: Criando estrutura das tabelas...\n";
    $sqlFile = __DIR__ . '/database_simples.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo database_simples.sql não encontrado!");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir em comandos e executar
    $commands = explode(';', $sql);
    $tabelas_criadas = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^(--|\/\*|\*)/', $command)) {
            try {
                $pdo->exec($command);
                if (stripos($command, 'CREATE TABLE') !== false) {
                    $tabelas_criadas++;
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "⚠️ Aviso: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "✅ Estrutura criada! Tabelas: $tabelas_criadas\n\n";
    
    // ETAPA 4: Inserir dados iniciais
    echo "📝 ETAPA 4: Inserindo dados iniciais...\n";
    $dadosFile = __DIR__ . '/dados_iniciais.sql';
    if (!file_exists($dadosFile)) {
        throw new Exception("Arquivo dados_iniciais.sql não encontrado!");
    }
    
    $sql = file_get_contents($dadosFile);
    $commands = explode(';', $sql);
    $registros_inseridos = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^(--|\/\*|\*)/', $command)) {
            try {
                $pdo->exec($command);
                if (stripos($command, 'INSERT INTO') !== false) {
                    $registros_inseridos++;
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "⚠️ Aviso: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "✅ Dados inseridos! Comandos executados: $registros_inseridos\n\n";
    
    // ETAPA 5: Verificar instalação
    echo "🔍 ETAPA 5: Verificando instalação...\n";
    
    // Verificar tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tabelas criadas: " . count($tables) . "\n";
    
    // Verificar usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $usuarios = $stmt->fetchColumn();
    echo "👥 Usuários: $usuarios\n";
    
    // Verificar badges
    $stmt = $pdo->query("SELECT COUNT(*) FROM badges");
    $badges = $stmt->fetchColumn();
    echo "🏆 Badges: $badges\n";
    
    // Verificar categorias
    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_categorias");
    $categorias = $stmt->fetchColumn();
    echo "💬 Categorias do fórum: $categorias\n";
    
    // Verificar questões
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes");
    $questoes = $stmt->fetchColumn();
    echo "📝 Questões: $questoes\n";
    
    // Verificar configurações
    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracoes_sistema");
    $configs = $stmt->fetchColumn();
    echo "⚙️ Configurações: $configs\n\n";
    
    // ETAPA 6: Resultado final
    echo "🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "====================================\n\n";
    
    echo "🔑 CREDENCIAIS DE ACESSO:\n";
    echo "   Usuário: admin\n";
    echo "   Senha: admin123\n";
    echo "   Email: admin@daydreamming.com\n\n";
    
    echo "📋 RESUMO DA INSTALAÇÃO:\n";
    echo "   ✅ Banco de dados: {$config['database']}\n";
    echo "   ✅ Tabelas criadas: " . count($tables) . "\n";
    echo "   ✅ Usuários: $usuarios\n";
    echo "   ✅ Badges: $badges\n";
    echo "   ✅ Questões: $questoes\n";
    echo "   ✅ Configurações: $configs\n\n";
    
    echo "🌐 PRÓXIMOS PASSOS:\n";
    echo "   1. Configure o arquivo config.php\n";
    echo "   2. Acesse o sistema via navegador\n";
    echo "   3. Faça login com as credenciais acima\n";
    echo "   4. ⚠️ ALTERE A SENHA após o primeiro login!\n\n";
    
    echo "🔗 LINKS ÚTEIS:\n";
    echo "   - Sistema: http://localhost:8080/\n";
    echo "   - Login: http://localhost:8080/login.php\n";
    echo "   - Verificação: http://localhost:8080/verificar_instalacao.php\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO NA INSTALAÇÃO:\n";
    echo "======================\n";
    echo "Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    
    echo "🔧 SOLUÇÕES POSSÍVEIS:\n";
    echo "   - Verifique se o MySQL está rodando\n";
    echo "   - Confirme as credenciais de conexão\n";
    echo "   - Verifique permissões do usuário MySQL\n";
    echo "   - Certifique-se de que os arquivos SQL existem\n\n";
    
    exit(1);
}
?>
