<?php
/**
 * Script de verificação da instalação completa
 * Execute para verificar se tudo está funcionando
 */

echo "🔍 VERIFICAÇÃO DA INSTALAÇÃO - DAYDREAMING\n";
echo "=========================================\n\n";

$problemas = [];
$sucessos = [];

// 1. Verificar PHP
echo "🐘 VERIFICANDO PHP...\n";
$php_version = phpversion();
if (version_compare($php_version, '7.4.0', '>=')) {
    $sucessos[] = "PHP $php_version (✅ Compatível)";
    echo "✅ PHP $php_version\n";
} else {
    $problemas[] = "PHP $php_version (❌ Requer 7.4+)";
    echo "❌ PHP $php_version (Requer 7.4+)\n";
}

// 2. Verificar extensões PHP
echo "\n🔌 VERIFICANDO EXTENSÕES PHP...\n";
$extensoes_necessarias = ['pdo', 'pdo_mysql', 'json', 'mbstring'];

foreach ($extensoes_necessarias as $ext) {
    if (extension_loaded($ext)) {
        $sucessos[] = "Extensão $ext";
        echo "✅ $ext\n";
    } else {
        $problemas[] = "Extensão $ext não encontrada";
        echo "❌ $ext\n";
    }
}

// 3. Verificar arquivos essenciais
echo "\n📁 VERIFICANDO ARQUIVOS ESSENCIAIS...\n";
$arquivos_essenciais = [
    'config.php' => 'Configuração do banco',
    'setup_database.php' => 'Script de configuração',
    'seed_questoes.php' => 'Script de questões',
    'index.php' => 'Página inicial',
    'login.php' => 'Sistema de login',
    'simulador_provas.php' => 'Simulador',
    'executar_teste.php' => 'Execução de testes',
    'interface_teste.php' => 'Interface do teste',
    'processar_teste.php' => 'Processamento',
    'resultado_teste.php' => 'Resultados',
    'historico_provas.php' => 'Histórico',
    'revisar_prova.php' => 'Revisão',
    'header_status.php' => 'Header de status'
];

foreach ($arquivos_essenciais as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        $sucessos[] = "$descricao ($arquivo)";
        echo "✅ $arquivo\n";
    } else {
        $problemas[] = "$descricao ($arquivo) não encontrado";
        echo "❌ $arquivo\n";
    }
}

// 4. Verificar pasta de exames
echo "\n📚 VERIFICANDO ARQUIVOS DE EXAMES...\n";
$arquivos_exames = [
    'exames/SAT/SAT_Test_4.json' => 'Questões SAT',
    'exames/SAT/Answers_SAT_Test_4.json' => 'Respostas SAT'
];

foreach ($arquivos_exames as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        $sucessos[] = "$descricao ($arquivo)";
        echo "✅ $arquivo\n";
    } else {
        $problemas[] = "$descricao ($arquivo) não encontrado";
        echo "❌ $arquivo\n";
    }
}

// 5. Verificar conexão com banco
echo "\n🗄️ VERIFICANDO CONEXÃO COM BANCO...\n";
try {
    if (file_exists('config.php')) {
        require_once 'config.php';
        $pdo = conectarBD();
        $sucessos[] = "Conexão com banco de dados";
        echo "✅ Conectado ao banco\n";
        
        // Verificar se as tabelas existem
        $tabelas_necessarias = [
            'usuarios', 'questoes', 'sessoes_teste', 
            'respostas_usuario', 'resultados_testes', 'badges',
            'usuario_badges', 'forum_categorias', 'forum_topicos',
            'forum_respostas', 'niveis_usuario', 'configuracoes_sistema'
        ];
        
        echo "\n📋 VERIFICANDO TABELAS PRINCIPAIS...\n";
        $tabelas_encontradas = 0;
        foreach ($tabelas_necessarias as $tabela) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $tabela");
                $count = $stmt->fetchColumn();
                $sucessos[] = "Tabela $tabela ($count registros)";
                echo "✅ $tabela ($count registros)\n";
                $tabelas_encontradas++;
            } catch (Exception $e) {
                $problemas[] = "Tabela $tabela não encontrada";
                echo "❌ $tabela (não encontrada)\n";
            }
        }
        
        // Verificar usuários padrão
        echo "\n👤 VERIFICANDO USUÁRIOS PADRÃO...\n";
        try {
            $stmt = $pdo->query("SELECT usuario, nome, is_admin FROM usuarios WHERE usuario IN ('admin', 'teste')");
            $usuarios = $stmt->fetchAll();
            
            foreach ($usuarios as $usuario) {
                $tipo = $usuario['is_admin'] ? 'Admin' : 'Usuário';
                $sucessos[] = "Usuário {$usuario['usuario']} ($tipo)";
                echo "✅ {$usuario['usuario']} - {$usuario['nome']} ($tipo)\n";
            }
            
            if (count($usuarios) < 2) {
                $problemas[] = "Usuários padrão não encontrados";
                echo "⚠️ Execute: php setup_database.php\n";
            }
        } catch (Exception $e) {
            $problemas[] = "Erro ao verificar usuários";
            echo "❌ Erro ao verificar usuários\n";
        }
        
        // Verificar questões
        echo "\n📝 VERIFICANDO QUESTÕES...\n";
        try {
            $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova");
            $questoes = $stmt->fetchAll();
            
            if (empty($questoes)) {
                $problemas[] = "Nenhuma questão encontrada";
                echo "⚠️ Execute: php seed_questoes.php\n";
            } else {
                foreach ($questoes as $questao) {
                    $sucessos[] = "Questões {$questao['tipo_prova']} ({$questao['total']})";
                    echo "✅ {$questao['tipo_prova']}: {$questao['total']} questões\n";
                }
            }
        } catch (Exception $e) {
            $problemas[] = "Erro ao verificar questões";
            echo "❌ Erro ao verificar questões\n";
        }
        
    } else {
        $problemas[] = "Arquivo config.php não encontrado";
        echo "❌ config.php não encontrado\n";
    }
} catch (Exception $e) {
    $problemas[] = "Erro de conexão com banco: " . $e->getMessage();
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
}

// 6. Verificar permissões
echo "\n🔐 VERIFICANDO PERMISSÕES...\n";
$diretorios_escrita = ['.', 'exames'];

foreach ($diretorios_escrita as $dir) {
    if (is_dir($dir) && is_writable($dir)) {
        $sucessos[] = "Permissão de escrita em $dir";
        echo "✅ $dir (escrita permitida)\n";
    } else {
        $problemas[] = "Sem permissão de escrita em $dir";
        echo "❌ $dir (sem permissão de escrita)\n";
    }
}

// 7. Resumo final
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RESUMO DA VERIFICAÇÃO\n";
echo str_repeat("=", 50) . "\n\n";

echo "✅ SUCESSOS (" . count($sucessos) . "):\n";
foreach (array_slice($sucessos, 0, 10) as $sucesso) {
    echo "   • $sucesso\n";
}
if (count($sucessos) > 10) {
    echo "   • ... e mais " . (count($sucessos) - 10) . " itens\n";
}

if (!empty($problemas)) {
    echo "\n❌ PROBLEMAS (" . count($problemas) . "):\n";
    foreach ($problemas as $problema) {
        echo "   • $problema\n";
    }
}

echo "\n🎯 STATUS GERAL:\n";
if (empty($problemas)) {
    echo "🎉 INSTALAÇÃO PERFEITA! Sistema pronto para uso.\n\n";
    
    echo "🌐 PRÓXIMOS PASSOS:\n";
    echo "===================\n";
    echo "1. Inicie o servidor: php -S localhost:8080\n";
    echo "2. Acesse: http://localhost:8080\n";
    echo "3. Faça login com admin/admin123\n";
    echo "4. Teste o simulador de provas\n\n";
    
} elseif (count($problemas) <= 3) {
    echo "⚠️ INSTALAÇÃO QUASE PRONTA! Alguns ajustes necessários.\n\n";
    
    echo "🔧 AÇÕES RECOMENDADAS:\n";
    echo "======================\n";
    echo "1. Execute: php setup_database.php\n";
    echo "2. Execute: php seed_questoes.php\n";
    echo "3. Verifique permissões: chmod -R 755 .\n\n";
    
} else {
    echo "❌ INSTALAÇÃO INCOMPLETA! Vários problemas encontrados.\n\n";
    
    echo "🆘 AÇÕES NECESSÁRIAS:\n";
    echo "=====================\n";
    echo "1. Execute: php setup_database.php\n";
    echo "2. Execute: php seed_questoes.php\n";
    echo "3. Verifique config.php\n";
    echo "4. Instale extensões PHP necessárias\n\n";
}

echo "📞 SUPORTE:\n";
echo "===========\n";
echo "• README.md - Documentação completa\n";
echo "• COMANDOS_COLABORADORES.md - Instruções essenciais\n";
echo "• setup_database.php - Configuração do banco\n";
echo "• seed_questoes.php - Carregamento de questões\n";
echo "• verificar_tabelas_completas.php - Verificação detalhada\n";
?>
