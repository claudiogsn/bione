-- ============================================================
-- Sistema de Perguntas de Evento - Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS perguntas_evento
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE perguntas_evento;

-- ------------------------------------------------------------
-- Cronograma do evento
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS event_schedule (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  title       VARCHAR(200) NOT NULL,
  description TEXT NULL,
  start_time  TIME NOT NULL,
  end_time    TIME NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Perguntas enviadas pelos participantes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS event_questions (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  participant_name VARCHAR(120) NOT NULL,
  question         TEXT NOT NULL,
  status           ENUM('pending','shown','archived') DEFAULT 'pending',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Estado atual do painel (singleton, id=1)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS panel_state (
  id                 INT PRIMARY KEY,
  active_question_id INT NULL,
  updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (active_question_id) REFERENCES event_questions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO panel_state (id, active_question_id) VALUES (1, NULL);

-- ------------------------------------------------------------
-- Configurações visuais do painel (singleton, id=1)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS panel_settings (
  id               INT PRIMARY KEY,
  background_color VARCHAR(20) DEFAULT '#0f172a',
  font_color       VARCHAR(20) DEFAULT '#ffffff',
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO panel_settings (id, background_color, font_color)
VALUES (1, '#0f172a', '#ffffff');

-- ------------------------------------------------------------
-- Dados de exemplo (opcional)
-- ------------------------------------------------------------
INSERT INTO event_schedule (title, description, start_time, end_time, sort_order) VALUES
('Credenciamento',      'Recepção dos participantes',              '08:30:00', '09:00:00', 1),
('Abertura',            'Boas-vindas e apresentação do evento',    '09:00:00', '09:30:00', 2),
('Palestra Principal',  'Tendências de tecnologia em 2026',        '09:30:00', '10:30:00', 3),
('Coffee Break',        'Networking e café',                       '10:30:00', '11:00:00', 4),
('Painel de Perguntas', 'Discussão aberta com especialistas',      '11:00:00', '12:00:00', 5),
('Encerramento',        'Agradecimentos e sorteios',               '12:00:00', '12:30:00', 6);
