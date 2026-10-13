CREATE DATABASE IF NOT EXISTS db_daydreamming_project
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_daydreamming_project;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  usuario VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  is_admin BOOLEAN DEFAULT FALSE,
  ativo BOOLEAN DEFAULT TRUE,
  data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ultimo_acesso TIMESTAMP NULL,
  ultimo_logout TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_acesso (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  ip VARCHAR(45) NOT NULL,
  sucesso BOOLEAN NOT NULL,
  data_tentativa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_logs_acesso_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_sistema (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  acao VARCHAR(50) NOT NULL,
  detalhes TEXT NULL,
  data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(45) NULL,
  CONSTRAINT fk_logs_sistema_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS node_sessions (
  id_hash CHAR(64) PRIMARY KEY,
  usuario_id INT NULL,
  csrf_token CHAR(64) NOT NULL,
  mensagem VARCHAR(255) NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira_em TIMESTAMP NOT NULL,
  INDEX idx_node_sessions_expira (expira_em),
  CONSTRAINT fk_node_sessions_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
