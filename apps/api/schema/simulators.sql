CREATE TABLE IF NOT EXISTS sessoes_teste (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  tipo_prova VARCHAR(20) NOT NULL,
  inicio DATETIME NOT NULL,
  fim DATETIME NULL,
  duracao_minutos INT NOT NULL,
  questoes_respondidas INT NOT NULL DEFAULT 0,
  status ENUM('ativo', 'finalizado', 'expirado') NOT NULL DEFAULT 'ativo',
  pontuacao_final DECIMAL(5, 2) NULL,
  acertos INT NOT NULL DEFAULT 0,
  tempo_gasto INT NOT NULL DEFAULT 0,
  INDEX idx_sessoes_teste_usuario_status (usuario_id, status),
  INDEX idx_sessoes_teste_usuario_inicio (usuario_id, inicio),
  CONSTRAINT fk_sessoes_teste_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS respostas_usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sessao_id INT NOT NULL,
  questao_id INT NOT NULL,
  questao_numero INT NOT NULL,
  resposta_usuario TEXT NULL,
  resposta_dissertativa_usuario TEXT NULL,
  resposta_correta TEXT NULL,
  esta_correta BOOLEAN NOT NULL DEFAULT FALSE,
  data_resposta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_resposta_sessao_questao (sessao_id, questao_numero),
  INDEX idx_respostas_usuario_questao (questao_id),
  CONSTRAINT fk_respostas_usuario_sessao FOREIGN KEY (sessao_id)
    REFERENCES sessoes_teste (id) ON DELETE CASCADE,
  CONSTRAINT fk_respostas_usuario_questao FOREIGN KEY (questao_id)
    REFERENCES questoes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resultados_testes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  sessao_id INT NOT NULL,
  tipo_prova VARCHAR(20) NOT NULL,
  pontuacao DECIMAL(5, 2) NOT NULL,
  acertos INT NOT NULL,
  total_questoes INT NOT NULL,
  questoes_respondidas INT NOT NULL,
  tempo_gasto INT NOT NULL,
  data_realizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_resultados_testes_usuario_data (usuario_id, data_realizacao),
  INDEX idx_resultados_testes_sessao (sessao_id),
  CONSTRAINT fk_resultados_testes_usuario FOREIGN KEY (usuario_id)
    REFERENCES usuarios (id) ON DELETE CASCADE,
  CONSTRAINT fk_resultados_testes_sessao FOREIGN KEY (sessao_id)
    REFERENCES sessoes_teste (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
