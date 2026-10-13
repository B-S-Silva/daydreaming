CREATE TABLE IF NOT EXISTS forum_categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  descricao TEXT NULL,
  cor VARCHAR(7) NOT NULL DEFAULT '#187bcd',
  icone VARCHAR(50) NOT NULL DEFAULT '💬',
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  ordem INT NOT NULL DEFAULT 0,
  data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_forum_categorias_ativo_ordem (ativo, ordem, nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_topicos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT NOT NULL,
  autor_id INT NOT NULL,
  usuario_id INT NULL,
  titulo VARCHAR(255) NOT NULL,
  conteudo TEXT NOT NULL,
  visualizacoes INT NOT NULL DEFAULT 0,
  fixado BOOLEAN NOT NULL DEFAULT FALSE,
  fechado BOOLEAN NOT NULL DEFAULT FALSE,
  aprovado BOOLEAN NOT NULL DEFAULT TRUE,
  data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_forum_topicos_categoria_atualizacao (categoria_id, data_atualizacao),
  INDEX idx_forum_topicos_autor (autor_id),
  INDEX idx_forum_topicos_usuario (usuario_id),
  INDEX idx_forum_topicos_moderacao (aprovado, fixado, fechado),
  CONSTRAINT fk_forum_topicos_categoria FOREIGN KEY (categoria_id)
    REFERENCES forum_categorias (id) ON DELETE RESTRICT,
  CONSTRAINT fk_forum_topicos_autor FOREIGN KEY (autor_id)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_forum_topicos_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_respostas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  topico_id INT NOT NULL,
  autor_id INT NOT NULL,
  usuario_id INT NULL,
  conteudo TEXT NOT NULL,
  aprovado BOOLEAN NOT NULL DEFAULT TRUE,
  data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_atualizacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_forum_respostas_topico_data (topico_id, data_criacao),
  INDEX idx_forum_respostas_autor (autor_id),
  INDEX idx_forum_respostas_usuario (usuario_id),
  INDEX idx_forum_respostas_aprovado (aprovado),
  CONSTRAINT fk_forum_respostas_topico FOREIGN KEY (topico_id)
    REFERENCES forum_topicos (id) ON DELETE CASCADE,
  CONSTRAINT fk_forum_respostas_autor FOREIGN KEY (autor_id)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_forum_respostas_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_curtidas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  topico_id INT NULL,
  resposta_id INT NULL,
  tipo_curtida ENUM('like', 'dislike') NOT NULL DEFAULT 'like',
  data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_curtida_topico (usuario_id, topico_id),
  UNIQUE KEY unique_curtida_resposta (usuario_id, resposta_id),
  INDEX idx_forum_curtidas_topico (topico_id),
  INDEX idx_forum_curtidas_resposta (resposta_id),
  CONSTRAINT fk_forum_curtidas_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_forum_curtidas_topico FOREIGN KEY (topico_id)
    REFERENCES forum_topicos (id) ON DELETE CASCADE,
  CONSTRAINT fk_forum_curtidas_resposta FOREIGN KEY (resposta_id)
    REFERENCES forum_respostas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS forum_moderacao (
  id INT AUTO_INCREMENT PRIMARY KEY,
  moderador_id INT NOT NULL,
  tipo_conteudo VARCHAR(20) NULL,
  conteudo_id INT NULL,
  topico_id INT NULL,
  resposta_id INT NULL,
  acao VARCHAR(30) NOT NULL,
  motivo TEXT NULL,
  data_acao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_forum_moderacao_data (data_acao),
  INDEX idx_forum_moderacao_alvo (tipo_conteudo, conteudo_id),
  INDEX idx_forum_moderacao_topico (topico_id),
  INDEX idx_forum_moderacao_resposta (resposta_id),
  CONSTRAINT fk_forum_moderacao_moderador FOREIGN KEY (moderador_id)
    REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
