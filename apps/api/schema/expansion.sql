-- Additive schema for the study, profile, and notification features.
-- Run only after verifying the target database and confirming these table names are absent.
-- Existing tables are referenced but never altered.

CREATE TABLE perfil_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    escola VARCHAR(150) NULL,
    serie VARCHAR(50) NULL,
    localizacao VARCHAR(150) NULL,
    gpa DECIMAL(3,2) NULL,
    idiomas TEXT NULL,
    exames TEXT NULL,
    interesses VARCHAR(255) NULL,
    biografia TEXT NULL,
    rastreamento_paises BOOLEAN NOT NULL DEFAULT FALSE,
    data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_perfil_usuario_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuario_gpa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    gpa DECIMAL(3,2) NOT NULL,
    notas_utilizadas TEXT NOT NULL,
    data_calculo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_gpa_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_gpa_usuario_data (usuario_id, data_calculo),
    INDEX idx_usuario_gpa_gpa (gpa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE historico_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    detalhes TEXT NULL,
    pontos INT NOT NULL DEFAULT 0,
    data_atividade DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historico_atividades_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_historico_atividades_usuario_data (usuario_id, data_atividade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    icone VARCHAR(10) NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    categoria VARCHAR(30) NULL,
    condicao_valor INT NULL,
    raridade VARCHAR(20) NULL,
    experiencia_bonus INT NULL,
    ativa BOOLEAN NOT NULL DEFAULT TRUE,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE usuario_badges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    badge_id INT NOT NULL,
    data_conquista DATETIME NOT NULL,
    contexto VARCHAR(100) NULL,
    notificado BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_badges_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_badges_badge
        FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_badge (usuario_id, badge_id),
    INDEX idx_usuario_badges_data (usuario_id, data_conquista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE paises_visitados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    codigo_pais VARCHAR(10) NOT NULL,
    nome_pais VARCHAR(100) NOT NULL,
    primeira_visita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultima_visita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_visitas INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_paises_visitados_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pais_visitado_usuario_codigo (usuario_id, codigo_pais),
    INDEX idx_paises_visitados_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE niveis_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL UNIQUE,
    nivel_atual INT NOT NULL DEFAULT 1,
    experiencia_total INT NOT NULL DEFAULT 0,
    experiencia_nivel INT NOT NULL DEFAULT 0,
    experiencia_necessaria INT NOT NULL DEFAULT 100,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_niveis_usuario_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notificacoes_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('forum_resposta', 'forum_mencao', 'badge_conquistada', 'nivel_subiu', 'sistema') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    link VARCHAR(255) NULL,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacoes_usuario_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_notificacoes_usuario_lida (usuario_id, lida),
    INDEX idx_notificacoes_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
