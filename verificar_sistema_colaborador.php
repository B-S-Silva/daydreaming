<?php
/**
 * Verificação rápida do sistema para colaboradores
 * Execute este arquivo para verificar se tudo está funcionando
 */

echo "🔍 VERIFICAÇÃO RÁPIDA DO SISTEMA - COLABORADORES\n";
echo "===============================================\n";
echo "📅 " . date('Y-m-d H:i:s') . "\n\n";

$problemas = [];
$sucessos = [];

// 1. Verificar arquivos essenciais
echo "📁 VERIFICANDO ARQUIVOS ESSENCIAIS...\n";
echo "=====================================\n";

$arquivos_essenciais = [
    'config.php' => 'Configurações do banco',
    'verificar_auth.php' => 'Sistema de autenticação',
    'forum.php' => 'Sistema de fórum',
    'admin_forum.php' => 'Painel administrativo',
    'simulador_provas.php' => 'Sistema de simulados',
    'login.php' => 'Página de login',
    'index.php' => 'Página inicial'
];

foreach ($arquivos_essenciais as $arquivo => $descricao) {
    if (file_exists($arquivo)) {
        echo "✅ $arquivo - $descricao\n";
        $sucessos[] = "Arquivo $arquivo existe";
    } else {
        echo "❌ $arquivo - $descricao (FALTANDO)\n";
        $problemas[] = "Arquivo $arquivo não encontrado";
    }
}

// 2. Verificar conexão com banco
echo "\n🗄️ VERIFICANDO BANCO DE DADOS...\n";
echo "================================\n";

try {
    if (file_exists('config.php')) {
        require_once 'config.php';
        $pdo = conectarBD();
        echo "✅ Conexão com banco estabelecida\n";
        $sucessos[] = "Conexão com banco OK";
        
        // Verificar tabelas essenciais
        $tabelas_essenciais = [
            'usuarios' => 'Usuários do sistema',
            'forum_categorias' => 'Categorias do fórum',
            'forum_topicos' => 'Tópicos do fórum',
            'forum_respostas' => 'Respostas do fórum',
            'questoes' => 'Questões dos simulados',
            'sessoes_teste' => 'Sessões de teste'
        ];
        
        foreach ($tabelas_essenciais as $tabela => $descricao) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $tabela");
                $count = $stmt->fetchColumn();
                echo "✅ $tabela: $count registros - $descricao\n";
                $sucessos[] = "Tabela $tabela OK ($count registros)";
            } catch (Exception $e) {
                echo "❌ $tabela: ERRO - $descricao\n";
                $problemas[] = "Tabela $tabela com problema: " . $e->getMessage();
            }
        }
        
    } else {
        echo "❌ config.php não encontrado\n";
        $problemas[] = "Arquivo config.php não existe";
    }
    
} catch (Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    $problemas[] = "Erro de conexão com banco: " . $e->getMessage();
}

// 3. Verificar estrutura do fórum (nova versão)
echo "\n💬 VERIFICANDO FÓRUM ATUALIZADO...\n";
echo "==================================\n";

try {
    if (isset($pdo)) {
        // Verificar se aprovação está como DEFAULT TRUE
        $stmt = $pdo->query("SHOW CREATE TABLE forum_topicos");
        $create_table = $stmt->fetch();
        
        if (strpos($create_table[1], 'DEFAULT TRUE') !== false || strpos($create_table[1], "DEFAULT '1'") !== false) {
            echo "✅ forum_topicos: Aprovação automática configurada\n";
            $sucessos[] = "Fórum com aprovação automática";
        } else {
            echo "⚠️ forum_topicos: Ainda com aprovação manual\n";
            $problemas[] = "Fórum ainda requer aprovação manual";
        }
        
        // Verificar se há tópicos pendentes
        $stmt = $pdo->query("SELECT COUNT(*) FROM forum_topicos WHERE aprovado = 0");
        $pendentes = $stmt->fetchColumn();
        
        if ($pendentes > 0) {
            echo "⚠️ $pendentes tópicos pendentes de aprovação\n";
            $problemas[] = "$pendentes tópicos pendentes";
        } else {
            echo "✅ Nenhum tópico pendente de aprovação\n";
            $sucessos[] = "Sem tópicos pendentes";
        }
        
        // Verificar categorias do fórum
        $stmt = $pdo->query("SELECT COUNT(*) FROM forum_categorias WHERE ativo = 1");
        $categorias = $stmt->fetchColumn();
        
        if ($categorias > 0) {
            echo "✅ $categorias categorias ativas no fórum\n";
            $sucessos[] = "$categorias categorias do fórum";
        } else {
            echo "❌ Nenhuma categoria ativa no fórum\n";
            $problemas[] = "Sem categorias no fórum";
        }
        
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar fórum: " . $e->getMessage() . "\n";
    $problemas[] = "Erro na verificação do fórum";
}

// 4. Verificar usuários padrão
echo "\n👥 VERIFICANDO USUÁRIOS PADRÃO...\n";
echo "=================================\n";

try {
    if (isset($pdo)) {
        // Verificar admin
        $stmt = $pdo->prepare("SELECT id, nome, usuario, is_admin FROM usuarios WHERE usuario = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "✅ Usuário admin encontrado (ID: {$admin['id']}, Admin: " . ($admin['is_admin'] ? 'SIM' : 'NÃO') . ")\n";
            $sucessos[] = "Usuário admin configurado";
        } else {
            echo "❌ Usuário admin não encontrado\n";
            $problemas[] = "Usuário admin não existe";
        }
        
        // Verificar usuário teste
        $stmt = $pdo->prepare("SELECT id, nome, usuario, is_admin FROM usuarios WHERE usuario = 'teste'");
        $stmt->execute();
        $teste = $stmt->fetch();
        
        if ($teste) {
            echo "✅ Usuário teste encontrado (ID: {$teste['id']}, Admin: " . ($teste['is_admin'] ? 'SIM' : 'NÃO') . ")\n";
            $sucessos[] = "Usuário teste configurado";
        } else {
            echo "❌ Usuário teste não encontrado\n";
            $problemas[] = "Usuário teste não existe";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar usuários: " . $e->getMessage() . "\n";
    $problemas[] = "Erro na verificação de usuários";
}

// 5. Verificar questões
echo "\n📝 VERIFICANDO QUESTÕES...\n";
echo "==========================\n";

try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM questoes WHERE ativa = 1");
        $questoes = $stmt->fetchColumn();
        
        if ($questoes > 0) {
            echo "✅ $questoes questões ativas encontradas\n";
            $sucessos[] = "$questoes questões disponíveis";
        } else {
            echo "⚠️ Nenhuma questão encontrada (execute: php seed_questoes.php)\n";
            $problemas[] = "Sem questões carregadas";
        }
        
        // Verificar tipos de prova
        $stmt = $pdo->query("SELECT DISTINCT tipo_prova FROM questoes");
        $tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tipos) > 0) {
            echo "✅ Tipos de prova: " . implode(', ', $tipos) . "\n";
            $sucessos[] = count($tipos) . " tipos de prova";
        }
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar questões: " . $e->getMessage() . "\n";
    $problemas[] = "Erro na verificação de questões";
}

// 6. Resumo final
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RESUMO DA VERIFICAÇÃO\n";
echo str_repeat("=", 50) . "\n\n";

echo "✅ SUCESSOS (" . count($sucessos) . "):\n";
foreach ($sucessos as $sucesso) {
    echo "   • $sucesso\n";
}

if (count($problemas) > 0) {
    echo "\n❌ PROBLEMAS (" . count($problemas) . "):\n";
    foreach ($problemas as $problema) {
        echo "   • $problema\n";
    }
    
    echo "\n🔧 SOLUÇÕES RECOMENDADAS:\n";
    echo "=========================\n";
    
    if (in_array("Arquivo config.php não existe", $problemas)) {
        echo "1. Crie o arquivo config.php com suas credenciais MySQL\n";
    }
    
    if (strpos(implode(' ', $problemas), 'Tabela') !== false) {
        echo "2. Execute: php setup_database.php\n";
    }
    
    if (strpos(implode(' ', $problemas), 'questões') !== false) {
        echo "3. Execute: php seed_questoes.php\n";
    }
    
    if (strpos(implode(' ', $problemas), 'aprovação') !== false) {
        echo "4. Execute novamente: php setup_database.php (para atualizar fórum)\n";
    }
    
    echo "5. Verifique se MySQL está rodando\n";
    echo "6. Confirme permissões do usuário MySQL\n";
    
} else {
    echo "\n🎉 SISTEMA TOTALMENTE FUNCIONAL!\n";
    echo "================================\n";
    echo "✅ Todos os componentes estão funcionando\n";
    echo "✅ Banco de dados configurado corretamente\n";
    echo "✅ Fórum atualizado (sem aprovação prévia)\n";
    echo "✅ Usuários padrão criados\n";
    echo "✅ Pronto para desenvolvimento!\n\n";
    
    echo "🌐 PRÓXIMOS PASSOS:\n";
    echo "===================\n";
    echo "1. Inicie o servidor: php -S localhost:8080 -t .\n";
    echo "2. Acesse: http://localhost:8080/\n";
    echo "3. Login: admin/admin123 ou teste/teste123\n";
    echo "4. Teste o fórum: http://localhost:8080/forum.php\n";
    echo "5. Comece a desenvolver! 🚀\n";
}

echo "\n📞 SUPORTE:\n";
echo "===========\n";
echo "• README_COLABORADORES.md - Documentação completa\n";
echo "• verificar_instalacao.php - Verificação detalhada\n";
echo "• teste_*.php - Testes específicos\n";
?>
