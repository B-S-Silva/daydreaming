<?php
/**
 * Script de Instalação do Banco de Dados
 * Sistema DayDreamming - StudyAbroad Platform
 * 
 * Este script automatiza a criação do banco de dados e todas as tabelas necessárias.
 * Execute este arquivo via navegador ou linha de comando para instalar o sistema.
 * 
 * IMPORTANTE: Execute apenas uma vez durante a instalação inicial!
 */

// Configurações de conexão (ajuste conforme necessário)
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

// Configurações de exibição
ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(300); // 5 minutos para execução

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação do Banco de Dados - DayDreamming</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .status {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            border-left: 5px solid;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .progress {
            background: #f0f0f0;
            border-radius: 10px;
            padding: 3px;
            margin: 20px 0;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 20px;
            border-radius: 8px;
            transition: width 0.3s ease;
            color: white;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            max-height: 300px;
            border: 1px solid #dee2e6;
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .step h3 {
            margin-top: 0;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalação do Sistema DayDreamming</h1>
        
        <?php if (!isset($_POST['instalar'])): ?>
            
            <div class="info">
                <h3>📋 Pré-requisitos</h3>
                <ul>
                    <li>PHP 7.4+ (recomendado PHP 8.0+)</li>
                    <li>MySQL 5.7+ ou MariaDB 10.3+</li>
                    <li>Extensões PHP: PDO, pdo_mysql, mbstring</li>
                    <li>Permissões de escrita no diretório do projeto</li>
                </ul>
            </div>
            
            <div class="warning">
                <h3>⚠️ Atenção</h3>
                <p>Este script irá:</p>
                <ul>
                    <li>Criar o banco de dados <strong><?php echo $config['database']; ?></strong></li>
                    <li>Criar todas as tabelas necessárias</li>
                    <li>Inserir dados iniciais (usuários, categorias, badges, etc.)</li>
                    <li>Configurar índices e triggers para otimização</li>
                </ul>
                <p><strong>IMPORTANTE:</strong> Se o banco já existir, alguns dados podem ser sobrescritos!</p>
            </div>
            
            <div class="step">
                <h3>🔧 Configurações Atuais</h3>
                <ul>
                    <li><strong>Host:</strong> <?php echo $config['host']; ?></li>
                    <li><strong>Usuário:</strong> <?php echo $config['user']; ?></li>
                    <li><strong>Banco:</strong> <?php echo $config['database']; ?></li>
                    <li><strong>Charset:</strong> <?php echo $config['charset']; ?></li>
                </ul>
                <p><em>Para alterar essas configurações, edite o arquivo instalar_database.php</em></p>
            </div>
            
            <form method="post" style="text-align: center;">
                <button type="submit" name="instalar" class="btn">
                    🚀 Iniciar Instalação
                </button>
            </form>
            
        <?php else: ?>
            
            <div class="progress">
                <div class="progress-bar" id="progressBar" style="width: 0%">0%</div>
            </div>
            
            <div id="log">
                <?php
                // Iniciar instalação
                try {
                    echo "<div class='info'><strong>🔄 Iniciando instalação...</strong></div>";
                    flush();
                    
                    // Verificar se o arquivo SQL existe
                    $sqlFile = __DIR__ . '/database_completa.sql';
                    if (!file_exists($sqlFile)) {
                        throw new Exception("Arquivo database_completa.sql não encontrado!");
                    }
                    
                    updateProgress(10, "Arquivo SQL encontrado");
                    
                    // Conectar ao MySQL (sem especificar banco)
                    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
                    $pdo = new PDO($dsn, $config['user'], $config['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
                    ]);
                    
                    updateProgress(20, "Conexão com MySQL estabelecida");
                    
                    // Ler e executar o arquivo SQL
                    $sql = file_get_contents($sqlFile);
                    if ($sql === false) {
                        throw new Exception("Erro ao ler o arquivo SQL");
                    }
                    
                    updateProgress(30, "Arquivo SQL carregado");
                    
                    // Dividir o SQL em comandos individuais
                    $commands = explode(';', $sql);
                    $totalCommands = count($commands);
                    $executedCommands = 0;
                    
                    updateProgress(40, "Iniciando execução dos comandos SQL");
                    
                    foreach ($commands as $command) {
                        $command = trim($command);
                        if (!empty($command) && !preg_match('/^(--|\/\*|\*)/', $command)) {
                            try {
                                $pdo->exec($command);
                                $executedCommands++;
                                
                                // Atualizar progresso
                                $progress = 40 + (($executedCommands / $totalCommands) * 50);
                                updateProgress($progress, "Executando comando $executedCommands de $totalCommands");
                                
                            } catch (PDOException $e) {
                                // Ignorar erros de comandos que já existem
                                if (strpos($e->getMessage(), 'already exists') === false && 
                                    strpos($e->getMessage(), 'Duplicate') === false) {
                                    echo "<div class='warning'>⚠️ Aviso no comando $executedCommands: " . htmlspecialchars($e->getMessage()) . "</div>";
                                }
                            }
                        }
                    }
                    
                    updateProgress(95, "Comandos SQL executados com sucesso");
                    
                    // Verificar se as tabelas foram criadas
                    $pdo->exec("USE {$config['database']}");
                    $stmt = $pdo->query("SHOW TABLES");
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    updateProgress(100, "Instalação concluída!");
                    
                    echo "<div class='success'>";
                    echo "<h3>✅ Instalação Concluída com Sucesso!</h3>";
                    echo "<p><strong>Tabelas criadas:</strong> " . count($tables) . "</p>";
                    echo "<ul>";
                    foreach ($tables as $table) {
                        echo "<li>$table</li>";
                    }
                    echo "</ul>";
                    echo "</div>";
                    
                    echo "<div class='info'>";
                    echo "<h3>🔑 Credenciais de Acesso</h3>";
                    echo "<p><strong>Usuário Administrador:</strong></p>";
                    echo "<ul>";
                    echo "<li><strong>Usuário:</strong> admin</li>";
                    echo "<li><strong>Senha:</strong> admin123</li>";
                    echo "<li><strong>Email:</strong> admin@daydreamming.com</li>";
                    echo "</ul>";
                    echo "<p><strong>⚠️ IMPORTANTE:</strong> Altere a senha do administrador após o primeiro login!</p>";
                    echo "</div>";
                    
                    echo "<div class='info'>";
                    echo "<h3>📝 Próximos Passos</h3>";
                    echo "<ol>";
                    echo "<li>Configure o arquivo <code>config.php</code> com as credenciais do banco</li>";
                    echo "<li>Acesse o sistema através do navegador</li>";
                    echo "<li>Faça login com as credenciais do administrador</li>";
                    echo "<li>Altere a senha padrão</li>";
                    echo "<li>Configure as demais opções do sistema</li>";
                    echo "</ol>";
                    echo "</div>";
                    
                    echo "<div style='text-align: center; margin-top: 30px;'>";
                    echo "<a href='index.php' class='btn'>🏠 Ir para o Sistema</a>";
                    echo "<a href='login.php' class='btn'>🔐 Fazer Login</a>";
                    echo "</div>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>";
                    echo "<h3>❌ Erro na Instalação</h3>";
                    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
                    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
                    echo "</div>";
                    
                    echo "<div class='warning'>";
                    echo "<h3>🔧 Possíveis Soluções</h3>";
                    echo "<ul>";
                    echo "<li>Verifique as credenciais de conexão com o banco</li>";
                    echo "<li>Certifique-se de que o MySQL está rodando</li>";
                    echo "<li>Verifique se o usuário tem permissões para criar bancos</li>";
                    echo "<li>Confirme se o arquivo database_completa.sql existe</li>";
                    echo "</ul>";
                    echo "</div>";
                }
                
                function updateProgress($percent, $message) {
                    echo "<script>";
                    echo "document.getElementById('progressBar').style.width = '{$percent}%';";
                    echo "document.getElementById('progressBar').textContent = Math.round($percent) + '%';";
                    echo "</script>";
                    echo "<div class='info'>📍 $message</div>";
                    flush();
                    usleep(100000); // 0.1 segundo de delay para visualização
                }
                ?>
            </div>
            
        <?php endif; ?>
    </div>
</body>
</html>
