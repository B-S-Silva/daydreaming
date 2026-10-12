<?php
/**
 * Script para executar a instalação do banco de dados via linha de comando
 */

// Configurações de conexão
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🚀 Iniciando instalação do banco de dados DayDreamming...\n\n";

try {
    // Conectar ao MySQL (sem especificar banco)
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
    echo "📡 Conectando ao MySQL...\n";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
    ]);
    echo "✅ Conexão estabelecida com sucesso!\n\n";
    
    // Verificar se o arquivo SQL existe
    $sqlFile = __DIR__ . '/database_completa.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("❌ Arquivo database_completa.sql não encontrado!");
    }
    echo "📄 Arquivo SQL encontrado: $sqlFile\n";
    
    // Ler o arquivo SQL
    echo "📖 Lendo arquivo SQL...\n";
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("❌ Erro ao ler o arquivo SQL");
    }
    echo "✅ Arquivo carregado com sucesso!\n\n";
    
    // Dividir o SQL em comandos individuais
    echo "⚙️ Processando comandos SQL...\n";
    $commands = explode(';', $sql);
    $totalCommands = count($commands);
    $executedCommands = 0;
    $errors = 0;
    
    echo "📊 Total de comandos a executar: $totalCommands\n\n";
    
    foreach ($commands as $index => $command) {
        $command = trim($command);
        if (!empty($command) && !preg_match('/^(--|\/\*|\*)/', $command)) {
            try {
                $pdo->exec($command);
                $executedCommands++;
                
                // Mostrar progresso a cada 50 comandos
                if ($executedCommands % 50 == 0 || $executedCommands == 1) {
                    $percent = round(($executedCommands / $totalCommands) * 100, 1);
                    echo "🔄 Progresso: $executedCommands/$totalCommands comandos ($percent%)\n";
                }
                
            } catch (PDOException $e) {
                // Ignorar erros de comandos que já existem
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate') === false) {
                    $errors++;
                    echo "⚠️ Aviso no comando " . ($index + 1) . ": " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✅ Processamento concluído!\n";
    echo "📈 Comandos executados: $executedCommands\n";
    echo "⚠️ Avisos/Erros: $errors\n\n";
    
    // Verificar se o banco foi criado
    echo "🔍 Verificando instalação...\n";
    $pdo->exec("USE {$config['database']}");
    
    // Verificar tabelas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tabelas criadas: " . count($tables) . "\n";
    
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    // Verificar usuários
    echo "\n👥 Verificando usuários...\n";
    $stmt = $pdo->query("SELECT nome, usuario, is_admin FROM usuarios");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        $type = $user['is_admin'] ? 'ADMIN' : 'USER';
        echo "  - {$user['nome']} ({$user['usuario']}) [$type]\n";
    }
    
    // Verificar badges
    echo "\n🏆 Verificando badges...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM badges");
    $badgeCount = $stmt->fetchColumn();
    echo "  - Total de badges: $badgeCount\n";
    
    // Verificar questões
    echo "\n📝 Verificando questões...\n";
    $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova");
    $questoes = $stmt->fetchAll();
    
    foreach ($questoes as $questao) {
        echo "  - {$questao['tipo_prova']}: {$questao['total']} questões\n";
    }
    
    echo "\n🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO!\n\n";
    echo "🔑 CREDENCIAIS DE ACESSO:\n";
    echo "   Usuário: admin\n";
    echo "   Senha: admin123\n";
    echo "   Email: admin@daydreamming.com\n\n";
    echo "⚠️ IMPORTANTE: Altere a senha após o primeiro login!\n\n";
    echo "🌐 Próximos passos:\n";
    echo "   1. Configure o arquivo config.php\n";
    echo "   2. Acesse o sistema via navegador\n";
    echo "   3. Faça login com as credenciais acima\n";
    echo "   4. Altere a senha do administrador\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO NA INSTALAÇÃO:\n";
    echo "   Erro: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n\n";
    
    echo "🔧 POSSÍVEIS SOLUÇÕES:\n";
    echo "   - Verifique as credenciais de conexão\n";
    echo "   - Certifique-se de que o MySQL está rodando\n";
    echo "   - Verifique permissões do usuário MySQL\n";
    echo "   - Confirme se o arquivo database_completa.sql existe\n\n";
    
    exit(1);
}
?>
