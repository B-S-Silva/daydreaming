<?php
/**
 * Script para criar tabelas uma por uma
 */

// Configurações
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'db_daydreamming_project',
    'charset' => 'utf8mb4'
];

echo "🚀 CRIANDO TABELAS DO DAYDREAMMING\n";
echo "==================================\n\n";

try {
    // Conectar
    $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Criar banco
    echo "📊 Criando banco de dados...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$config['database']}");
    echo "✅ Banco criado!\n\n";
    
    // Criar tabela usuarios
    echo "👥 Criando tabela usuarios...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            usuario VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            senha VARCHAR(255) NOT NULL,
            is_admin BOOLEAN DEFAULT FALSE,
            ativo BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultimo_acesso TIMESTAMP NULL,
            ultimo_logout TIMESTAMP NULL
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela usuarios criada!\n";
    
    // Criar tabela logs_acesso
    echo "📝 Criando tabela logs_acesso...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS logs_acesso (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NULL,
            ip VARCHAR(45) NOT NULL,
            sucesso BOOLEAN NOT NULL,
            data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela logs_acesso criada!\n";
    
    // Criar tabela forum_categorias
    echo "💬 Criando tabela forum_categorias...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT NULL,
            cor VARCHAR(7) DEFAULT '#007bff',
            icone VARCHAR(10) DEFAULT '📝',
            ativo BOOLEAN DEFAULT TRUE,
            ordem INT DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela forum_categorias criada!\n";
    
    // Criar tabela forum_topicos
    echo "📋 Criando tabela forum_topicos...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_topicos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria_id INT NOT NULL,
            usuario_id INT NOT NULL,
            titulo VARCHAR(200) NOT NULL,
            conteudo TEXT NOT NULL,
            aprovado BOOLEAN DEFAULT FALSE,
            fixado BOOLEAN DEFAULT FALSE,
            fechado BOOLEAN DEFAULT FALSE,
            visualizacoes INT DEFAULT 0,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (categoria_id) REFERENCES forum_categorias(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela forum_topicos criada!\n";
    
    // Criar tabela forum_respostas
    echo "💭 Criando tabela forum_respostas...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS forum_respostas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            topico_id INT NOT NULL,
            usuario_id INT NOT NULL,
            conteudo TEXT NOT NULL,
            aprovado BOOLEAN DEFAULT FALSE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (topico_id) REFERENCES forum_topicos(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela forum_respostas criada!\n";
    
    // Criar tabela questoes
    echo "❓ Criando tabela questoes...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo_prova ENUM('toefl', 'ielts', 'sat', 'gre', 'gmat', 'dele', 'delf', 'testdaf', 'jlpt', 'hsk') NOT NULL,
            numero_questao INT NOT NULL,
            enunciado TEXT NOT NULL,
            alternativa_a VARCHAR(500) NOT NULL,
            alternativa_b VARCHAR(500) NOT NULL,
            alternativa_c VARCHAR(500) NOT NULL,
            alternativa_d VARCHAR(500) NOT NULL,
            alternativa_e VARCHAR(500) NULL,
            resposta_correta ENUM('a', 'b', 'c', 'd', 'e') NOT NULL,
            dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
            materia VARCHAR(100) NULL,
            assunto VARCHAR(100) NULL,
            explicacao TEXT NULL,
            ativa BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_tipo_numero (tipo_prova, numero_questao)
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela questoes criada!\n";
    
    // Criar tabela sessoes_teste
    echo "🎯 Criando tabela sessoes_teste...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessoes_teste (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            tipo_prova ENUM('toefl', 'ielts', 'sat', 'gre', 'gmat', 'dele', 'delf', 'testdaf', 'jlpt', 'hsk') NOT NULL,
            inicio DATETIME NOT NULL,
            fim DATETIME NULL,
            duracao_minutos INT NOT NULL,
            status ENUM('ativo', 'finalizado', 'expirado', 'cancelado') DEFAULT 'ativo',
            questoes_total INT NOT NULL,
            questoes_respondidas INT DEFAULT 0,
            tempo_gasto INT DEFAULT 0,
            pontuacao DECIMAL(5,2) NULL,
            acertos INT NULL,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela sessoes_teste criada!\n";
    
    // Criar tabela resultados_testes
    echo "📊 Criando tabela resultados_testes...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS resultados_testes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            sessao_id INT NOT NULL,
            tipo_prova ENUM('toefl', 'ielts', 'sat', 'gre', 'gmat', 'dele', 'delf', 'testdaf', 'jlpt', 'hsk') NOT NULL,
            pontuacao DECIMAL(5,2) NOT NULL,
            acertos INT NOT NULL,
            erros INT NOT NULL,
            nao_respondidas INT NOT NULL,
            total_questoes INT NOT NULL,
            tempo_gasto INT NOT NULL,
            data_realizacao DATETIME NOT NULL,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (sessao_id) REFERENCES sessoes_teste(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela resultados_testes criada!\n";
    
    // Criar tabela badges
    echo "🏆 Criando tabela badges...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            codigo VARCHAR(50) UNIQUE NOT NULL,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT NOT NULL,
            icone VARCHAR(10) NOT NULL,
            tipo ENUM('pontuacao', 'frequencia', 'especial', 'tempo', 'social') NOT NULL,
            categoria ENUM('teste', 'forum', 'geral', 'social') DEFAULT 'teste',
            condicao_valor INT NULL,
            raridade ENUM('comum', 'raro', 'epico', 'lendario') DEFAULT 'comum',
            experiencia_bonus INT DEFAULT 50,
            ativa BOOLEAN DEFAULT TRUE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela badges criada!\n";
    
    // Criar tabela usuario_badges
    echo "🎖️ Criando tabela usuario_badges...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS usuario_badges (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            badge_id INT NOT NULL,
            data_conquista DATETIME NOT NULL,
            contexto VARCHAR(100) NULL,
            notificado BOOLEAN DEFAULT FALSE,
            data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_usuario_badge (usuario_id, badge_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
            FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela usuario_badges criada!\n";
    
    // Criar tabela niveis_usuario
    echo "📈 Criando tabela niveis_usuario...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS niveis_usuario (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            nivel_atual INT DEFAULT 1,
            experiencia_total INT DEFAULT 0,
            experiencia_nivel INT DEFAULT 0,
            experiencia_necessaria INT DEFAULT 100,
            testes_completados INT DEFAULT 0,
            melhor_pontuacao DECIMAL(5,2) DEFAULT 0.00,
            media_pontuacao DECIMAL(5,2) DEFAULT 0.00,
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_usuario (usuario_id),
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    echo "✅ Tabela niveis_usuario criada!\n";
    
    // Verificar tabelas criadas
    echo "\n🔍 Verificando tabelas criadas...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 Total de tabelas: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  ✅ $table\n";
    }
    
    echo "\n🎉 ESTRUTURA BÁSICA CRIADA COM SUCESSO!\n\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}
?>
