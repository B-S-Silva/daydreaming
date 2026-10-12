<?php
/**
 * Script para criar as tabelas restantes
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🔧 COMPLETANDO TABELAS RESTANTES\n";
echo "=================================\n\n";

try {
    // Conectar
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "📡 Conectado ao banco de dados!\n\n";
    
    // Criar tabela logs_sistema
    echo "📝 Criando tabela logs_sistema...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            acao VARCHAR(100) NOT NULL,
            tabela_afetada VARCHAR(50) NULL,
            registro_id INT NULL,
            detalhes TEXT NULL,
            ip VARCHAR(45) NULL,
            data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_usuario_id (usuario_id),
            INDEX idx_acao (acao),
            INDEX idx_data_hora (data_hora),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela logs_sistema criada!\n";
    
    // Criar tabela historico_experiencia
    echo "📈 Criando tabela historico_experiencia...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS historico_experiencia (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            tipo_acao ENUM('teste_completado', 'badge_conquistada', 'primeiro_teste', 'bonus_velocidade', 'bonus_pontuacao', 'participacao_forum', 'login_diario') NOT NULL,
            experiencia_ganha INT NOT NULL,
            contexto VARCHAR(100) NULL,
            nivel_anterior INT NOT NULL,
            nivel_posterior INT NOT NULL,
            detalhes JSON NULL,
            data_acao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_usuario_id (usuario_id),
            INDEX idx_tipo_acao (tipo_acao),
            INDEX idx_data_acao (data_acao),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela historico_experiencia criada!\n";
    
    // Criar tabela forum_curtidas
    echo "❤️ Criando tabela forum_curtidas...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_curtidas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            topico_id INT NULL,
            resposta_id INT NULL,
            data_curtida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_usuario_id (usuario_id),
            INDEX idx_topico_id (topico_id),
            INDEX idx_resposta_id (resposta_id),
            UNIQUE KEY unique_usuario_topico (usuario_id, topico_id),
            UNIQUE KEY unique_usuario_resposta (usuario_id, resposta_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (topico_id) REFERENCES forum_topicos(id) ON DELETE CASCADE,
            FOREIGN KEY (resposta_id) REFERENCES forum_respostas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela forum_curtidas criada!\n";
    
    // Criar tabela forum_moderacao
    echo "🛡️ Criando tabela forum_moderacao...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_moderacao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            moderador_id INT NOT NULL,
            tipo_conteudo ENUM('topico', 'resposta') NOT NULL,
            conteudo_id INT NOT NULL,
            acao ENUM('aprovar', 'rejeitar', 'editar', 'deletar', 'fixar', 'desfixar', 'fechar', 'abrir') NOT NULL,
            motivo TEXT NULL,
            data_acao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_moderador_id (moderador_id),
            INDEX idx_tipo_conteudo (tipo_conteudo),
            INDEX idx_data_acao (data_acao),
            FOREIGN KEY (moderador_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela forum_moderacao criada!\n";
    
    // Criar tabela respostas_usuario
    echo "📝 Criando tabela respostas_usuario...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS respostas_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sessao_id INT NOT NULL,
            questao_id INT NOT NULL,
            questao_numero INT NOT NULL,
            resposta_usuario ENUM('a', 'b', 'c', 'd', 'e') NULL,
            resposta_correta ENUM('a', 'b', 'c', 'd', 'e') NOT NULL,
            esta_correta BOOLEAN NOT NULL,
            tempo_resposta INT NULL,
            tentativas INT DEFAULT 1,
            marcada_revisao BOOLEAN DEFAULT FALSE,
            data_resposta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sessao_id (sessao_id),
            INDEX idx_questao_id (questao_id),
            INDEX idx_esta_correta (esta_correta),
            UNIQUE KEY unique_sessao_questao (sessao_id, questao_numero),
            FOREIGN KEY (sessao_id) REFERENCES sessoes_teste(id) ON DELETE CASCADE,
            FOREIGN KEY (questao_id) REFERENCES questoes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela respostas_usuario criada!\n";
    
    // Criar tabela configuracoes_sistema
    echo "⚙️ Criando tabela configuracoes_sistema...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS configuracoes_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chave VARCHAR(100) UNIQUE NOT NULL,
            valor TEXT NOT NULL,
            tipo ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
            categoria VARCHAR(50) DEFAULT 'geral',
            descricao TEXT NULL,
            editavel BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_chave (chave),
            INDEX idx_categoria (categoria)
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela configuracoes_sistema criada!\n";
    
    // Criar tabela notificacoes
    echo "🔔 Criando tabela notificacoes...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notificacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            tipo ENUM('badge_conquistada', 'nivel_subiu', 'novo_teste', 'resposta_forum', 'sistema') NOT NULL,
            titulo VARCHAR(200) NOT NULL,
            mensagem TEXT NOT NULL,
            icone VARCHAR(10) NULL,
            url VARCHAR(255) NULL,
            lida BOOLEAN DEFAULT FALSE,
            data_leitura TIMESTAMP NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            data_expiracao TIMESTAMP NULL,
            INDEX idx_usuario_id (usuario_id),
            INDEX idx_tipo (tipo),
            INDEX idx_lida (lida),
            INDEX idx_data_criacao (data_criacao),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela notificacoes criada!\n";
    
    // Inserir configurações do sistema
    echo "⚙️ Inserindo configurações do sistema...\n";
    $configuracoes = [
        ['sistema_nome', 'DayDreamming - StudyAbroad Platform', 'string', 'geral', 'Nome do sistema'],
        ['sistema_versao', '2.0.0', 'string', 'geral', 'Versão atual do sistema'],
        ['manutencao_ativa', 'false', 'boolean', 'sistema', 'Se o sistema está em manutenção'],
        ['registro_aberto', 'true', 'boolean', 'usuarios', 'Se novos registros estão permitidos'],
        ['max_tentativas_login', '5', 'integer', 'seguranca', 'Máximo de tentativas de login'],
        ['tempo_bloqueio_minutos', '30', 'integer', 'seguranca', 'Tempo de bloqueio em minutos'],
        ['duracao_sessao_horas', '8', 'integer', 'seguranca', 'Duração da sessão em horas'],
        ['xp_base_teste', '20', 'integer', 'gamificacao', 'XP base por completar um teste'],
        ['xp_bonus_badge', '50', 'integer', 'gamificacao', 'XP bônus por conquistar uma badge'],
        ['forum_moderacao_ativa', 'true', 'boolean', 'forum', 'Se a moderação do fórum está ativa'],
        ['notificacoes_ativas', 'true', 'boolean', 'sistema', 'Se as notificações estão ativas'],
        ['backup_automatico', 'true', 'boolean', 'sistema', 'Se o backup automático está ativo'],
        ['log_detalhado', 'true', 'boolean', 'sistema', 'Se o log detalhado está ativo'],
        ['email_smtp_host', 'smtp.gmail.com', 'string', 'email', 'Servidor SMTP para envio de emails'],
        ['email_smtp_port', '587', 'integer', 'email', 'Porta do servidor SMTP'],
        ['email_from', 'noreply@daydreamming.com', 'string', 'email', 'Email remetente padrão']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO configuracoes_sistema (chave, valor, tipo, categoria, descricao) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE valor = VALUES(valor)
    ");
    
    foreach ($configuracoes as $config) {
        $stmt->execute($config);
    }
    echo "✅ Configurações inseridas!\n";
    
    // Inserir mais categorias do fórum
    echo "💬 Adicionando categorias restantes do fórum...\n";
    $novas_categorias = [
        ['🌍 Destinos', 'Informações específicas sobre países e cidades', '#fd7e14', '🌍', 7],
        ['📚 Preparação', 'Materiais de estudo e métodos de preparação', '#20c997', '📚', 8]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO forum_categorias (nome, descricao, cor, icone, ordem) 
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE descricao = VALUES(descricao)
    ");
    
    foreach ($novas_categorias as $cat) {
        $stmt->execute($cat);
    }
    echo "✅ Categorias adicionais criadas!\n";
    
    // Verificar tabelas criadas
    echo "\n🔍 Verificando todas as tabelas...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Total de tabelas: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  ✅ $table\n";
    }
    
    // Verificar dados
    echo "\n📈 Verificando dados...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    echo "👥 Usuários: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM forum_categorias");
    echo "💬 Categorias: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM badges");
    echo "🏆 Badges: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM questoes");
    echo "❓ Questões: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM configuracoes_sistema");
    echo "⚙️ Configurações: " . $stmt->fetchColumn() . "\n";
    
    echo "\n🎉 BANCO DE DADOS COMPLETADO COM SUCESSO!\n\n";
    
    echo "🔑 CREDENCIAIS DE ACESSO:\n";
    echo "   Usuário: admin\n";
    echo "   Senha: admin123\n";
    echo "   Email: admin@daydreamming.com\n\n";
    
    echo "🌐 SISTEMA PRONTO PARA USO!\n";
    echo "   - Acesse: http://localhost:8080/\n";
    echo "   - Login: http://localhost:8080/login.php\n";
    echo "   - Verificação: http://localhost:8080/verificar_instalacao.php\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
