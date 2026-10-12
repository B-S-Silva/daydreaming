<?php
/**
 * Script de diagnóstico para o simulador de provas
 */

echo "🔍 DIAGNÓSTICO DO SIMULADOR DE PROVAS\n";
echo "=====================================\n\n";

// Verificar arquivos necessários
echo "📁 VERIFICANDO ARQUIVOS:\n";
echo "========================\n";

$arquivos_necessarios = [
    'config.php',
    'verificar_auth.php',
    'badges_manager.php',
    'questoes_manager.php',
    'header_status.php',
    'simulador_provas.php'
];

foreach ($arquivos_necessarios as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - OK\n";
    } else {
        echo "❌ $arquivo - FALTANDO\n";
    }
}

echo "\n📂 VERIFICANDO DIRETÓRIOS:\n";
echo "==========================\n";

$diretorios = [
    'public/css',
    'public/js'
];

foreach ($diretorios as $dir) {
    if (is_dir($dir)) {
        echo "✅ $dir - OK\n";
    } else {
        echo "❌ $dir - FALTANDO\n";
    }
}

// Verificar se o arquivo CSS existe
echo "\n🎨 VERIFICANDO ARQUIVOS CSS/JS:\n";
echo "===============================\n";

$arquivos_publicos = [
    'public/css/style.css',
    'public/js/main.js'
];

foreach ($arquivos_publicos as $arquivo) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - OK\n";
    } else {
        echo "❌ $arquivo - FALTANDO\n";
    }
}

// Testar conexão com banco
echo "\n🗄️ TESTANDO CONEXÃO COM BANCO:\n";
echo "===============================\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    echo "✅ Conexão com banco - OK\n";
    
    // Verificar tabelas necessárias
    $tabelas_necessarias = [
        'usuarios',
        'resultados_testes',
        'badges',
        'usuario_badges',
        'questoes'
    ];
    
    foreach ($tabelas_necessarias as $tabela) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Tabela $tabela - OK\n";
        } else {
            echo "❌ Tabela $tabela - FALTANDO\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
}

// Verificar se há questões no banco
echo "\n❓ VERIFICANDO QUESTÕES NO BANCO:\n";
echo "=================================\n";

try {
    $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova");
    $questoes = $stmt->fetchAll();
    
    if (empty($questoes)) {
        echo "⚠️ Nenhuma questão encontrada no banco!\n";
    } else {
        foreach ($questoes as $questao) {
            echo "✅ {$questao['tipo_prova']}: {$questao['total']} questões\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar questões: " . $e->getMessage() . "\n";
}

// Testar se a página carrega
echo "\n🌐 TESTANDO CARREGAMENTO DA PÁGINA:\n";
echo "===================================\n";

// Simular uma sessão para teste
session_start();
$_SESSION['usuario_id'] = 1; // ID do admin
$_SESSION['usuario_nome'] = 'Admin';
$_SESSION['logado'] = true;

ob_start();
try {
    include 'simulador_provas.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    if (strlen($output) > 1000) {
        echo "✅ Página carrega corretamente\n";
        echo "📏 Tamanho da saída: " . strlen($output) . " bytes\n";
    } else {
        echo "⚠️ Página carrega mas pode ter problemas\n";
        echo "📏 Tamanho da saída: " . strlen($output) . " bytes\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Erro ao carregar página: " . $e->getMessage() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "❌ Erro fatal: " . $e->getMessage() . "\n";
}

// Verificar logs de erro do PHP
echo "\n📋 VERIFICANDO LOGS DE ERRO:\n";
echo "============================\n";

$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    echo "📁 Arquivo de log: $log_file\n";
    $logs = file_get_contents($log_file);
    $linhas_recentes = array_slice(explode("\n", $logs), -10);
    
    foreach ($linhas_recentes as $linha) {
        if (!empty(trim($linha))) {
            echo "📝 $linha\n";
        }
    }
} else {
    echo "ℹ️ Nenhum arquivo de log configurado\n";
}

echo "\n🎯 RECOMENDAÇÕES:\n";
echo "=================\n";

// Verificar se faltam arquivos críticos
if (!file_exists('public/css/style.css')) {
    echo "🔧 Criar arquivo public/css/style.css\n";
}

if (!file_exists('public/js/main.js')) {
    echo "🔧 Criar arquivo public/js/main.js\n";
}

if (!file_exists('header_status.php')) {
    echo "🔧 Criar arquivo header_status.php\n";
}

echo "\n✅ DIAGNÓSTICO CONCLUÍDO!\n";
?>
