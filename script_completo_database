-- =====================================================
-- SCRIPT COMPLETO PARA CRIAÇÃO DO BANCO DE DADOS
-- Sistema DayDreamming - StudyAbroad Platform
-- =====================================================
-- 
-- Este script cria todas as tabelas necessárias para o funcionamento
-- completo do sistema, incluindo:
-- - Sistema de usuários e autenticação
-- - Sistema de fórum
-- - Sistema de simulador de provas
-- - Sistema de badges e gamificação
-- 
-- INSTRUÇÕES DE USO:
-- 1. Crie um banco de dados MySQL chamado 'db_daydreamming_project'
-- 2. Execute este script no banco criado
-- 3. Configure o arquivo config.php com as credenciais corretas
-- 4. Acesse o sistema através do navegador
-- 
-- =====================================================

-- Criar banco de dados (descomente se necessário)
 CREATE DATABASE IF NOT EXISTS db_daydreamming_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
 USE db_daydreamming_project;

-- =====================================================
-- TABELAS DO SISTEMA DE USUÁRIOS
-- =====================================================

-- Tabela principal de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_login TIMESTAMP NULL,
    token_recuperacao VARCHAR(100) NULL,
    token_expiracao TIMESTAMP NULL,
    INDEX idx_usuario (usuario),
    INDEX idx_email (email),
    INDEX idx_ativo (ativo)
);

-- =====================================================
-- TABELAS DO SISTEMA DE FÓRUM
-- =====================================================

-- Tabela de categorias do fórum
CREATE TABLE IF NOT EXISTS forum_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    cor VARCHAR(7) DEFAULT '#007bff',
    icone VARCHAR(10) DEFAULT '📝',
    ativo BOOLEAN DEFAULT TRUE,
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_ordem (ordem)
);

-- Tabela de tópicos do fórum
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
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES forum_categorias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_categoria (categoria_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_aprovado (aprovado),
    INDEX idx_data_criacao (data_criacao)
);

-- Tabela de respostas do fórum
CREATE TABLE IF NOT EXISTS forum_respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topico_id INT NOT NULL,
    usuario_id INT NOT NULL,
    conteudo TEXT NOT NULL,
    aprovado BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topico_id) REFERENCES forum_topicos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_topico (topico_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_aprovado (aprovado),
    INDEX idx_data_criacao (data_criacao)
);

-- =====================================================
-- TABELAS DO SISTEMA DE SIMULADOR DE PROVAS
-- =====================================================

-- Tabela para armazenar sessões de teste
CREATE TABLE IF NOT EXISTS sessoes_teste (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo_prova ENUM('toefl', 'ielts', 'sat', 'dele', 'delf', 'testdaf', 'jlpt', 'hsk') NOT NULL,
    inicio DATETIME NOT NULL,
    fim DATETIME NULL,
    duracao_minutos INT NOT NULL,
    status ENUM('ativo', 'finalizado', 'expirado') DEFAULT 'ativo',
    pontuacao DECIMAL(5,2) NULL,
    acertos INT NULL,
    questoes_respondidas INT NULL,
    tempo_gasto INT NULL, -- em segundos
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_tipo (usuario_id, tipo_prova),
    INDEX idx_status (status),
    INDEX idx_inicio (inicio)
);

-- Tabela para armazenar resultados detalhados dos testes
CREATE TABLE IF NOT EXISTS resultados_testes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    sessao_id INT NOT NULL,
    tipo_prova ENUM('toefl', 'ielts', 'sat', 'dele', 'delf', 'testdaf', 'jlpt', 'hsk') NOT NULL,
    pontuacao DECIMAL(5,2) NOT NULL,
    acertos INT NOT NULL,
    total_questoes INT NOT NULL,
    questoes_respondidas INT NOT NULL,
    tempo_gasto INT NOT NULL, -- em segundos
    data_realizacao DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (sessao_id) REFERENCES sessoes_teste(id) ON DELETE CASCADE,
    INDEX idx_usuario_data (usuario_id, data_realizacao),
    INDEX idx_tipo_prova (tipo_prova),
    INDEX idx_pontuacao (pontuacao)
);

-- Tabela para armazenar respostas individuais dos usuários
CREATE TABLE IF NOT EXISTS respostas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sessao_id INT NOT NULL,
    questao_numero INT NOT NULL,
    resposta_usuario CHAR(1) NOT NULL, -- a, b, c, d, e
    resposta_correta CHAR(1) NULL, -- a, b, c, d, e
    acertou BOOLEAN NOT NULL DEFAULT FALSE,
    tempo_resposta INT NULL, -- tempo em segundos para responder
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sessao_id) REFERENCES sessoes_teste(id) ON DELETE CASCADE,
    UNIQUE KEY unique_sessao_questao (sessao_id, questao_numero),
    INDEX idx_sessao (sessao_id),
    INDEX idx_acertou (acertou)
);

-- Tabela para armazenar questões
CREATE TABLE IF NOT EXISTS questoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_prova ENUM('toefl', 'ielts', 'sat', 'dele', 'delf', 'testdaf', 'jlpt', 'hsk') NOT NULL,
    numero_questao INT NOT NULL,
    enunciado TEXT NOT NULL,
    alternativa_a TEXT NOT NULL,
    alternativa_b TEXT NOT NULL,
    alternativa_c TEXT NOT NULL,
    alternativa_d TEXT NOT NULL,
    alternativa_e TEXT NOT NULL,
    resposta_correta CHAR(1) NOT NULL,
    dificuldade ENUM('facil', 'medio', 'dificil') DEFAULT 'medio',
    materia VARCHAR(100) NULL,
    assunto VARCHAR(200) NULL,
    explicacao TEXT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo_prova (tipo_prova),
    INDEX idx_dificuldade (dificuldade),
    INDEX idx_ativo (ativo)
);

-- =====================================================
-- TABELAS DO SISTEMA DE BADGES E GAMIFICAÇÃO
-- =====================================================

-- Tabela para armazenar badges/conquistas
CREATE TABLE IF NOT EXISTS badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    icone VARCHAR(10) NOT NULL,
    tipo ENUM('pontuacao', 'frequencia', 'especial', 'nivel') NOT NULL,
    condicao_valor INT NULL, -- valor necessário para conquistar
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_tipo (tipo, ativo)
);

-- Tabela para relacionar usuários com badges conquistadas
CREATE TABLE IF NOT EXISTS usuario_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    badge_id INT NOT NULL,
    data_conquista DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_badge (usuario_id, badge_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_data_conquista (data_conquista)
);

-- Tabela para armazenar níveis de usuário
CREATE TABLE IF NOT EXISTS niveis_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nivel_atual INT DEFAULT 1,
    experiencia_total INT DEFAULT 0,
    experiencia_nivel INT DEFAULT 0,
    experiencia_necessaria INT DEFAULT 100,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario (usuario_id)
);

-- =====================================================
-- INSERÇÃO DE DADOS INICIAIS
-- =====================================================

-- Inserir usuário administrador padrão
-- Senha: admin123 (altere após o primeiro login)
INSERT INTO usuarios (nome, usuario, email, senha, is_admin, ativo) VALUES 
('Administrador', 'admin', 'admin@daydreamming.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE, TRUE)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- Inserir categorias padrão do fórum
INSERT INTO forum_categorias (nome, descricao, cor, icone, ativo, ordem) VALUES 
('Geral', 'Discussões gerais sobre estudar no exterior', '#007bff', '💬', TRUE, 1),
('Testes Internacionais', 'Dúvidas e dicas sobre TOEFL, IELTS, SAT, etc.', '#28a745', '📝', TRUE, 2),
('Universidades', 'Informações sobre universidades no exterior', '#17a2b8', '🎓', TRUE, 3),
('Bolsas de Estudo', 'Oportunidades de bolsas e financiamento', '#ffc107', '💰', TRUE, 4),
('Experiências', 'Relatos de quem já estudou fora', '#6f42c1', '✈️', TRUE, 5),
('Dúvidas Técnicas', 'Problemas com o sistema e suporte', '#dc3545', '🔧', TRUE, 6)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- Inserir badges padrão
INSERT INTO badges (codigo, nome, descricao, icone, tipo, condicao_valor) VALUES
('primeiro_teste', 'Primeiro Passo', 'Completou seu primeiro teste', '🎯', 'especial', 1),
('satisfatorio', 'Satisfatório', 'Obteve pontuação entre 60-69%', '🥉', 'pontuacao', 60),
('bom', 'Bom Desempenho', 'Obteve pontuação entre 70-79%', '🥈', 'pontuacao', 70),
('muito_bom', 'Muito Bom', 'Obteve pontuação entre 80-89%', '🥇', 'pontuacao', 80),
('excelencia', 'Excelência', 'Obteve pontuação acima de 90%', '🏆', 'pontuacao', 90),
('especialista_toefl', 'Especialista TOEFL', 'Completou 5 testes do TOEFL', '🇺🇸', 'frequencia', 5),
('especialista_ielts', 'Especialista IELTS', 'Completou 5 testes do IELTS', '🇬🇧', 'frequencia', 5),
('especialista_sat', 'Especialista SAT', 'Completou 5 testes do SAT', '🎓', 'frequencia', 5),
('especialista_delf', 'Especialista DELF', 'Completou 5 testes do DELF', '🇫🇷', 'frequencia', 5),
('especialista_testdaf', 'Especialista TestDaF', 'Completou 5 testes do TestDaF', '🇩🇪', 'frequencia', 5),
('especialista_jlpt', 'Especialista JLPT', 'Completou 5 testes do JLPT', '🇯🇵', 'frequencia', 5),
('especialista_hsk', 'Especialista HSK', 'Completou 5 testes do HSK', '🇨🇳', 'frequencia', 5),
('consistente', 'Consistente', 'Obteve 5 resultados acima de 70%', '📈', 'frequencia', 5),
('dedicado', 'Dedicado', 'Obteve 10 resultados acima de 70%', '💪', 'frequencia', 10),
('maratonista', 'Maratonista', 'Completou 20 testes', '🏃', 'frequencia', 20),
('perfeccionista', 'Perfeccionista', 'Obteve 100% em um teste', '💯', 'pontuacao', 100),
('rapido', 'Velocista', 'Completou um teste em tempo recorde', '⚡', 'especial', 1),
('persistente', 'Persistente', 'Completou 50 testes', '🔥', 'frequencia', 50),
('primeiro_post', 'Primeira Participação', 'Criou seu primeiro tópico no fórum', '📝', 'especial', 1),
('ativo_forum', 'Participativo', 'Criou 10 tópicos no fórum', '💬', 'frequencia', 10)
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- Inserir questões de exemplo
INSERT INTO questoes (tipo_prova, numero_questao, enunciado, alternativa_a, alternativa_b, alternativa_c, alternativa_d, alternativa_e, resposta_correta, materia, assunto) VALUES
('toefl', 1, 'Esta é uma questão de exemplo do TOEFL. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'a', 'Reading', 'Academic'),
('ielts', 1, 'Esta é uma questão de exemplo do IELTS. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'b', 'Listening', 'Academic'),
('sat', 1, 'Esta é uma questão de exemplo do SAT. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'c', 'Math', 'Algebra'),
('dele', 1, 'Esta é uma questão de exemplo do DELE. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'd', 'Comprensión', 'Lectora'),
('delf', 1, 'Esta é uma questão de exemplo do DELF. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'a', 'Compréhension', 'Écrite'),
('testdaf', 1, 'Esta é uma questão de exemplo do TestDaF. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'b', 'Leseverstehen', 'Akademisch'),
('jlpt', 1, 'Esta é uma questão de exemplo do JLPT. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'c', '読解', 'N3'),
('hsk', 1, 'Esta é uma questão de exemplo do HSK. O conteúdo real será carregado posteriormente via arquivo JSON/XML.', 'Alternativa A', 'Alternativa B', 'Alternativa C', 'Alternativa D', 'Alternativa E', 'd', '阅读', 'HSK4')
ON DUPLICATE KEY UPDATE enunciado=VALUES(enunciado);

-- =====================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- =====================================================

CREATE INDEX IF NOT EXISTS idx_resultados_usuario_pontuacao ON resultados_testes(usuario_id, pontuacao DESC);
CREATE INDEX IF NOT EXISTS idx_sessoes_usuario_status ON sessoes_teste(usuario_id, status, inicio DESC);
CREATE INDEX IF NOT EXISTS idx_forum_topicos_categoria_data ON forum_topicos(categoria_id, data_criacao DESC);
CREATE INDEX IF NOT EXISTS idx_forum_respostas_topico_data ON forum_respostas(topico_id, data_criacao ASC);

-- =====================================================
-- VIEWS PARA CONSULTAS OTIMIZADAS
-- =====================================================

-- View para estatísticas rápidas do usuário
CREATE OR REPLACE VIEW vw_estatisticas_usuario AS
SELECT 
    u.id as usuario_id,
    u.nome,
    COUNT(DISTINCT rt.id) as total_testes,
    AVG(rt.pontuacao) as media_pontuacao,
    MAX(rt.pontuacao) as melhor_pontuacao,
    MIN(rt.pontuacao) as pior_pontuacao,
    SUM(CASE WHEN rt.pontuacao >= 70 THEN 1 ELSE 0 END) as testes_aprovados,
    COUNT(DISTINCT ub.id) as total_badges,
    COALESCE(nu.nivel_atual, 1) as nivel_atual,
    COALESCE(nu.experiencia_total, 0) as experiencia_total,
    COUNT(DISTINCT ft.id) as total_topicos_forum,
    COUNT(DISTINCT fr.id) as total_respostas_forum
FROM usuarios u
LEFT JOIN resultados_testes rt ON u.id = rt.usuario_id
LEFT JOIN usuario_badges ub ON u.id = ub.usuario_id
LEFT JOIN niveis_usuario nu ON u.id = nu.usuario_id
LEFT JOIN forum_topicos ft ON u.id = ft.usuario_id
LEFT JOIN forum_respostas fr ON u.id = fr.usuario_id
GROUP BY u.id, u.nome, nu.nivel_atual, nu.experiencia_total;

-- View para estatísticas do fórum
CREATE OR REPLACE VIEW vw_estatisticas_forum AS
SELECT 
    fc.id as categoria_id,
    fc.nome as categoria_nome,
    fc.cor as categoria_cor,
    fc.icone as categoria_icone,
    COUNT(DISTINCT ft.id) as total_topicos,
    COUNT(DISTINCT fr.id) as total_respostas,
    MAX(ft.data_criacao) as ultimo_topico,
    MAX(fr.data_criacao) as ultima_resposta
FROM forum_categorias fc
LEFT JOIN forum_topicos ft ON fc.id = ft.categoria_id AND ft.aprovado = 1
LEFT JOIN forum_respostas fr ON ft.id = fr.topico_id AND fr.aprovado = 1
WHERE fc.ativo = 1
GROUP BY fc.id, fc.nome, fc.cor, fc.icone
ORDER BY fc.ordem;

-- =====================================================
-- TRIGGERS PARA AUTOMAÇÃO
-- =====================================================

-- Trigger para criar nível inicial do usuário
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_criar_nivel_usuario
AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
    INSERT INTO niveis_usuario (usuario_id, nivel_atual, experiencia_total, experiencia_nivel, experiencia_necessaria)
    VALUES (NEW.id, 1, 0, 0, 100);
END//
DELIMITER ;

-- =====================================================
-- COMENTÁRIOS SOBRE O USO DAS TABELAS
-- =====================================================

/*
ESTRUTURA DO BANCO DE DADOS:

1. SISTEMA DE USUÁRIOS:
   - usuarios: Tabela principal com dados de login e perfil
   - Campos: id, nome, usuario, email, senha, is_admin, ativo, data_criacao

2. SISTEMA DE FÓRUM:
   - forum_categorias: Categorias dos tópicos
   - forum_topicos: Tópicos criados pelos usuários
   - forum_respostas: Respostas aos tópicos

3. SISTEMA DE SIMULADOR:
   - sessoes_teste: Sessões de teste iniciadas
   - resultados_testes: Resultados finais dos testes
   - respostas_usuario: Respostas individuais
   - questoes: Banco de questões dos simulados

4. SISTEMA DE GAMIFICAÇÃO:
   - badges: Definição das conquistas
   - usuario_badges: Badges conquistadas por usuário
   - niveis_usuario: Sistema de níveis e experiência

5. VIEWS E OTIMIZAÇÕES:
   - vw_estatisticas_usuario: Estatísticas consolidadas
   - vw_estatisticas_forum: Estatísticas do fórum
   - Índices para performance

CREDENCIAIS PADRÃO:
- Usuário: admin
- Senha: admin123
- Email: admin@daydreamming.com

LEMBRE-SE DE:
1. Alterar a senha do administrador após o primeiro login
2. Configurar o arquivo config.php com as credenciais corretas
3. Ajustar as permissões de arquivos conforme necessário
4. Fazer backup regular do banco de dados
*/

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================

SELECT 'Script executado com sucesso! Banco de dados configurado.' as status;