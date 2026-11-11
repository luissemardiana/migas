CREATE TABLE IF NOT EXISTS training_batches (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program_slug VARCHAR(100) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  delivery_format VARCHAR(80) NOT NULL,
  location VARCHAR(180) NULL,
  status ENUM('proposed', 'confirmed', 'closed') NOT NULL DEFAULT 'proposed',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_training_batch (program_slug, start_date, end_date),
  INDEX idx_training_batches_public (status, start_date),
  CONSTRAINT fk_training_batches_program FOREIGN KEY (program_slug) REFERENCES training_programs (slug) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO training_batches (program_slug, start_date, end_date, delivery_format, location, status) VALUES
('rcm', '2026-12-15', '2026-12-16', 'Daring (kelas langsung)', NULL, 'proposed'),
('vibration-analysis', '2027-01-19', '2027-01-21', 'Daring (kelas langsung)', NULL, 'proposed'),
('gas-engine-maintenance', '2027-02-16', '2027-02-17', 'Daring (kelas langsung)', NULL, 'proposed')
ON DUPLICATE KEY UPDATE delivery_format = VALUES(delivery_format), location = VALUES(location), status = VALUES(status);
