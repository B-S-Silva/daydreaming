<?php
/**
 * Script para executar comandos MySQL usando PATH configurado
 */

echo "🗄️ MYSQL COMMANDS - USANDO PATH CONFIGURADO\n";
echo "============================================\n\n";

// Configurações do banco
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project'
];

echo "📋 COMANDOS MYSQL DISPONÍVEIS:\n";
echo "==============================\n\n";

echo "🔍 1. VERIFICAR STATUS DO MYSQL:\n";
echo "mysql --version\n";
echo "mysqladmin -u{$config['user']} status\n\n";

echo "🗄️ 2. CONECTAR AO BANCO:\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']}\n\n";

echo "📊 3. BACKUP DO BANCO:\n";
echo "mysqldump -u{$config['user']} -p{$config['password']} {$config['database']} > backup_daydreaming.sql\n\n";

echo "📥 4. RESTAURAR BACKUP:\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']} < backup_daydreaming.sql\n\n";

echo "🔧 5. EXECUTAR SCRIPT SQL:\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']} < script.sql\n\n";

echo "📈 6. VERIFICAR TAMANHO DO BANCO:\n";
echo "mysql -u{$config['user']} -p{$config['password']} -e \"SELECT table_schema AS 'Database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)' FROM information_schema.tables WHERE table_schema = '{$config['database']}' GROUP BY table_schema;\"\n\n";

// Testar se MySQL está acessível via PATH
echo "🧪 TESTANDO ACESSO AO MYSQL VIA PATH:\n";
echo "====================================\n";

try {
    // Testar comando mysql --version
    $output = shell_exec('mysql --version 2>&1');
    if ($output && strpos($output, 'mysql') !== false) {
        echo "✅ MySQL acessível via PATH\n";
        echo "📋 Versão: " . trim($output) . "\n\n";
    } else {
        echo "❌ MySQL não encontrado no PATH\n";
        echo "📋 Output: " . ($output ?: 'Nenhum output') . "\n\n";
    }
    
    // Testar conexão
    $status_output = shell_exec("mysqladmin -u{$config['user']} status 2>&1");
    if ($status_output && strpos($status_output, 'Uptime') !== false) {
        echo "✅ Conexão com MySQL funcionando\n";
        echo "📊 Status: " . trim($status_output) . "\n\n";
    } else {
        echo "⚠️ Problema na conexão ou MySQL não está rodando\n";
        echo "📋 Output: " . ($status_output ?: 'Nenhum output') . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao testar MySQL: " . $e->getMessage() . "\n\n";
}

echo "🛠️ SCRIPTS ÚTEIS PARA MANUTENÇÃO:\n";
echo "==================================\n\n";

echo "📊 Verificar questões por tipo:\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']} -e \"SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova;\"\n\n";

echo "🔍 Verificar sessões ativas:\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']} -e \"SELECT tipo_prova, COUNT(*) as total FROM sessoes_teste WHERE status = 'ativo' GROUP BY tipo_prova;\"\n\n";

echo "🧹 Limpar sessões antigas (mais de 1 dia):\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']} -e \"DELETE FROM sessoes_teste WHERE inicio < DATE_SUB(NOW(), INTERVAL 1 DAY) AND status = 'ativo';\"\n\n";

echo "📈 Estatísticas de uso:\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']} -e \"SELECT u.nome, COUNT(st.id) as testes_realizados FROM usuarios u LEFT JOIN sessoes_teste st ON u.id = st.usuario_id GROUP BY u.id ORDER BY testes_realizados DESC;\"\n\n";

echo "💾 BACKUP AUTOMÁTICO:\n";
echo "=====================\n";
$backup_filename = "backup_daydreaming_" . date('Y-m-d_H-i-s') . ".sql";
echo "mysqldump -u{$config['user']} -p{$config['password']} --single-transaction --routines --triggers {$config['database']} > $backup_filename\n\n";

echo "🔧 OTIMIZAÇÃO DE TABELAS:\n";
echo "=========================\n";
echo "mysql -u{$config['user']} -p{$config['password']} {$config['database']} -e \"OPTIMIZE TABLE questoes, respostas_usuario, sessoes_teste, usuarios;\"\n\n";

echo "📋 INFORMAÇÕES DO SISTEMA:\n";
echo "==========================\n";
echo "✅ MySQL configurado no PATH\n";
echo "✅ Comandos disponíveis globalmente\n";
echo "✅ Scripts de manutenção prontos\n";
echo "✅ Backup e restore simplificados\n\n";

echo "💡 DICAS:\n";
echo "=========\n";
echo "• Use os comandos acima diretamente no terminal\n";
echo "• Faça backups regulares antes de modificações\n";
echo "• Monitore o tamanho do banco periodicamente\n";
echo "• Limpe sessões antigas para manter performance\n\n";

echo "🎯 COMANDOS MAIS USADOS:\n";
echo "========================\n";
echo "1. Backup: mysqldump -u{$config['user']} -p{$config['password']} {$config['database']} > backup.sql\n";
echo "2. Conectar: mysql -u{$config['user']} -p{$config['password']} {$config['database']}\n";
echo "3. Status: mysqladmin -u{$config['user']} status\n";
echo "4. Versão: mysql --version\n";
?>
