<?php
/**
 * Verificação completa de todas as tabelas do projeto
 */

echo "🔍 VERIFICAÇÃO COMPLETA DAS TABELAS\n";
echo "===================================\n\n";

try {
    require_once 'config.php';
    $pdo = conectarBD();
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Lista completa de todas as tabelas necessárias
    $tabelas_necessarias = [
        'usuarios' => [
            'descricao' => 'Sistema de login e perfis',
            'campos_essenciais' => ['id', 'nome', 'usuario', 'senha', 'email', 'is_admin']
        ],
        'questoes' => [
            'descricao' => 'Banco de questões dos exames',
            'campos_essenciais' => ['id', 'numero_questao', 'tipo_prova', 'enunciado', 'resposta_correta', 'ativa']
        ],
        'sessoes_teste' => [
            'descricao' => 'Controle de sessões de teste',
            'campos_essenciais' => ['id', 'usuario_id', 'tipo_prova', 'status', 'pontuacao_final', 'acertos']
        ],
        'respostas_usuario' => [
            'descricao' => 'Respostas individuais dos usuários',
            'campos_essenciais' => ['id', 'sessao_id', 'questao_id', 'resposta_usuario', 'esta_correta']
        ],
        'resultados_testes' => [
            'descricao' => 'Resultados finalizados dos testes',
            'campos_essenciais' => ['id', 'usuario_id', 'sessao_id', 'tipo_prova', 'pontuacao']
        ],
        'badges' => [
            'descricao' => 'Sistema de conquistas/badges',
            'campos_essenciais' => ['id', 'nome', 'descricao', 'icone']
        ],
        'usuario_badges' => [
            'descricao' => 'Badges conquistadas por usuário',
            'campos_essenciais' => ['id', 'usuario_id', 'badge_id', 'data_conquista']
        ],
        'forum_categorias' => [
            'descricao' => 'Categorias do fórum',
            'campos_essenciais' => ['id', 'nome', 'descricao', 'cor', 'icone']
        ],
        'forum_topicos' => [
            'descricao' => 'Tópicos do fórum',
            'campos_essenciais' => ['id', 'categoria_id', 'titulo', 'conteudo', 'autor_id']
        ],
        'forum_respostas' => [
            'descricao' => 'Respostas do fórum',
            'campos_essenciais' => ['id', 'topico_id', 'conteudo', 'autor_id']
        ],
        'niveis_usuario' => [
            'descricao' => 'Sistema de níveis e experiência',
            'campos_essenciais' => ['id', 'usuario_id', 'nivel_atual', 'experiencia_total']
        ],
        'configuracoes_sistema' => [
            'descricao' => 'Configurações gerais do sistema',
            'campos_essenciais' => ['id', 'chave', 'valor', 'tipo', 'categoria']
        ],
        'logs_sistema' => [
            'descricao' => 'Logs de ações do sistema',
            'campos_essenciais' => ['id', 'acao', 'detalhes', 'data_criacao']
        ],
        'logs_acesso' => [
            'descricao' => 'Logs de login/logout',
            'campos_essenciais' => ['id', 'tipo_evento', 'sucesso', 'data_evento']
        ],
        'notificacoes' => [
            'descricao' => 'Sistema de notificações',
            'campos_essenciais' => ['id', 'usuario_id', 'titulo', 'mensagem', 'lida']
        ],
        'historico_experiencia' => [
            'descricao' => 'Histórico de ganho de experiência',
            'campos_essenciais' => ['id', 'usuario_id', 'acao', 'xp_ganho']
        ],
        'forum_curtidas' => [
            'descricao' => 'Curtidas do fórum',
            'campos_essenciais' => ['id', 'usuario_id', 'tipo_curtida']
        ],
        'forum_moderacao' => [
            'descricao' => 'Moderação do fórum',
            'campos_essenciais' => ['id', 'moderador_id', 'acao', 'data_acao']
        ]
    ];
    
    echo "📋 VERIFICANDO EXISTÊNCIA DAS TABELAS:\n";
    echo "=======================================\n";
    
    $tabelas_existentes = 0;
    $problemas = [];
    
    foreach ($tabelas_necessarias as $tabela => $info) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$tabela'");
            if ($stmt->rowCount() > 0) {
                echo "✅ $tabela - {$info['descricao']}\n";
                $tabelas_existentes++;
                
                // Verificar campos essenciais
                $stmt = $pdo->query("DESCRIBE $tabela");
                $campos = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $campos_faltando = [];
                foreach ($info['campos_essenciais'] as $campo) {
                    if (!in_array($campo, $campos)) {
                        $campos_faltando[] = $campo;
                    }
                }
                
                if (!empty($campos_faltando)) {
                    $problemas[] = "Tabela $tabela: campos faltando - " . implode(', ', $campos_faltando);
                    echo "   ⚠️ Campos faltando: " . implode(', ', $campos_faltando) . "\n";
                }
                
            } else {
                echo "❌ $tabela - NÃO ENCONTRADA\n";
                $problemas[] = "Tabela $tabela não encontrada";
            }
        } catch (Exception $e) {
            echo "❌ $tabela - ERRO: " . $e->getMessage() . "\n";
            $problemas[] = "Erro ao verificar tabela $tabela: " . $e->getMessage();
        }
    }
    
    echo "\n📊 VERIFICANDO DADOS INICIAIS:\n";
    echo "==============================\n";
    
    // Verificar usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $usuarios_count = $stmt->fetchColumn();
    echo "👤 Usuários: $usuarios_count\n";
    
    if ($usuarios_count >= 2) {
        $stmt = $pdo->query("SELECT usuario, nome, is_admin FROM usuarios ORDER BY is_admin DESC");
        $usuarios = $stmt->fetchAll();
        foreach ($usuarios as $usuario) {
            $tipo = $usuario['is_admin'] ? 'Admin' : 'Usuário';
            echo "   • {$usuario['usuario']} - {$usuario['nome']} ($tipo)\n";
        }
    } else {
        $problemas[] = "Poucos usuários cadastrados ($usuarios_count)";
    }
    
    // Verificar questões
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes");
    $questoes_count = $stmt->fetchColumn();
    echo "\n❓ Questões: $questoes_count\n";
    
    if ($questoes_count > 0) {
        $stmt = $pdo->query("SELECT tipo_prova, COUNT(*) as total FROM questoes GROUP BY tipo_prova");
        $questoes_por_tipo = $stmt->fetchAll();
        foreach ($questoes_por_tipo as $tipo) {
            echo "   • {$tipo['tipo_prova']}: {$tipo['total']} questões\n";
        }
    } else {
        echo "   ⚠️ Nenhuma questão encontrada - Execute: php seed_questoes.php\n";
    }
    
    // Verificar badges
    $stmt = $pdo->query("SELECT COUNT(*) FROM badges");
    $badges_count = $stmt->fetchColumn();
    echo "\n🏆 Badges: $badges_count\n";
    
    // Verificar categorias do fórum
    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_categorias");
    $categorias_count = $stmt->fetchColumn();
    echo "\n💬 Categorias do fórum: $categorias_count\n";
    
    // Verificar configurações
    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracoes_sistema");
    $configs_count = $stmt->fetchColumn();
    echo "\n⚙️ Configurações do sistema: $configs_count\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📈 RESUMO DA VERIFICAÇÃO\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "✅ TABELAS ENCONTRADAS: $tabelas_existentes/" . count($tabelas_necessarias) . "\n";
    echo "✅ DADOS INICIAIS:\n";
    echo "   • Usuários: $usuarios_count\n";
    echo "   • Questões: $questoes_count\n";
    echo "   • Badges: $badges_count\n";
    echo "   • Categorias fórum: $categorias_count\n";
    echo "   • Configurações: $configs_count\n";
    
    if (empty($problemas)) {
        echo "\n🎉 ESTRUTURA PERFEITA!\n";
        echo "======================\n";
        echo "✅ Todas as tabelas necessárias estão presentes\n";
        echo "✅ Todos os campos essenciais estão corretos\n";
        echo "✅ Dados iniciais carregados\n";
        echo "✅ Sistema pronto para colaboradores\n\n";
        
        echo "🚀 COMANDOS PARA COLABORADORES:\n";
        echo "===============================\n";
        echo "1. git clone [repositorio]\n";
        echo "2. cd DayDreaming\n";
        echo "3. php setup_database.php\n";
        echo "4. php seed_questoes.php\n";
        echo "5. php -S localhost:8080\n";
        echo "6. Acesse http://localhost:8080\n";
        echo "7. Login: admin / admin123\n\n";
        
    } else {
        echo "\n⚠️ PROBLEMAS ENCONTRADOS:\n";
        echo "=========================\n";
        foreach ($problemas as $problema) {
            echo "• $problema\n";
        }
        
        echo "\n🔧 AÇÕES RECOMENDADAS:\n";
        echo "======================\n";
        echo "1. Execute: php setup_database.php\n";
        echo "2. Execute: php seed_questoes.php\n";
        echo "3. Verifique novamente: php verificar_tabelas_completas.php\n\n";
    }
    
    echo "📋 LISTA COMPLETA DE TABELAS:\n";
    echo "=============================\n";
    foreach ($tabelas_necessarias as $tabela => $info) {
        echo "• $tabela - {$info['descricao']}\n";
    }
    
    echo "\n📞 SUPORTE:\n";
    echo "===========\n";
    echo "• SETUP_COLABORADORES.md - Guia completo\n";
    echo "• setup_database.php - Configuração automática\n";
    echo "• seed_questoes.php - Carregamento de questões\n";
    echo "• verificar_instalacao.php - Diagnóstico geral\n";
    
} catch (Exception $e) {
    echo "❌ ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n\n";
    
    echo "🔧 POSSÍVEIS SOLUÇÕES:\n";
    echo "======================\n";
    echo "1. Verifique se o MySQL está rodando\n";
    echo "2. Confirme as credenciais no config.php\n";
    echo "3. Execute: php setup_database.php\n";
    echo "4. Verifique permissões de acesso ao banco\n";
}
?>
