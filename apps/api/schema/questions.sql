USE db_daydreamming_project;

CREATE TABLE IF NOT EXISTS questoes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo_prova VARCHAR(20) NOT NULL,
  numero_questao INT NOT NULL,
  enunciado TEXT NOT NULL,
  alternativa_a TEXT NULL,
  alternativa_b TEXT NULL,
  alternativa_c TEXT NULL,
  alternativa_d TEXT NULL,
  alternativa_e TEXT NULL,
  resposta_correta VARCHAR(10) NULL,
  tipo_questao ENUM('multipla_escolha', 'dissertativa') NOT NULL DEFAULT 'multipla_escolha',
  resposta_dissertativa TEXT NULL,
  dificuldade ENUM('facil', 'medio', 'dificil') NOT NULL DEFAULT 'medio',
  materia VARCHAR(100) NULL,
  assunto VARCHAR(100) NULL,
  explicacao TEXT NULL,
  ativa BOOLEAN NOT NULL DEFAULT TRUE,
  data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_tipo_numero (tipo_prova, numero_questao),
  INDEX idx_questoes_ativa_tipo (ativa, tipo_prova)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
