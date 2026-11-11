CREATE TABLE IF NOT EXISTS training_programs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  sort_order SMALLINT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  subtitle VARCHAR(255) NOT NULL,
  category VARCHAR(80) NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(500) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_training_programs_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  request_type ENUM('public', 'inhouse', 'guest-lecture') NOT NULL,
  full_name VARCHAR(160) NOT NULL,
  organization VARCHAR(180) NULL,
  contact VARCHAR(190) NOT NULL,
  program_slug VARCHAR(100) NULL,
  schedule_location VARCHAR(255) NULL,
  sector_program VARCHAR(255) NULL,
  participants INT UNSIGNED NULL,
  preferences TEXT NULL,
  learning_goal TEXT NULL,
  session_topic TEXT NULL,
  privacy_consent TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('new', 'in_review', 'contacted', 'closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_training_requests_status_created (status, created_at),
  INDEX idx_training_requests_type_created (request_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO training_programs (slug, sort_order, title, subtitle, category, description, image) VALUES
('rcm', 1, 'RCM', 'Reliability Centered Maintenance', 'Reliability', 'Topik pemeliharaan berpusat pada keandalan.', 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=1200&q=85'),
('gas-engine-maintenance', 2, 'Gas Engine Maintenance', 'Compressor & Generator', 'Equipment', 'Pemeliharaan mesin gas, kompresor, dan generator.', 'https://images.unsplash.com/photo-1487875961445-47a00398c267?auto=format&fit=crop&w=1200&q=85'),
('plant-assessment', 3, 'Plant Assessment', 'Remaining Life / Condition Review', 'Reliability', 'Tinjauan remaining life dan kondisi aset.', 'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?auto=format&fit=crop&w=1200&q=85'),
('troubleshooting', 4, 'Troubleshooting', 'Metodologi diagnosa kegagalan', 'Diagnostics', 'Metodologi untuk mendiagnosa kegagalan.', 'assets/images/troubleshooting-training.png'),
('turbomachinery', 5, 'Turbomachinery', 'Turbin, kompresor & rotating equipment', 'Equipment', 'Topik turbin, kompresor, dan peralatan berputar.', 'https://images.unsplash.com/photo-1531988042231-d39a9cc12a9a?auto=format&fit=crop&w=1200&q=85'),
('vibration-analysis', 6, 'Vibration Analysis', 'Pengukuran & interpretasi getaran', 'Diagnostics', 'Topik pengukuran dan interpretasi getaran.', 'https://images.unsplash.com/photo-1504917595217-d4dc5ebe6122?auto=format&fit=crop&w=1200&q=85'),
('lube-oil-analysis', 7, 'Lube Oil Analysis', 'Analisis pelumas dan kondisi mesin', 'Diagnostics', 'Topik analisis pelumas dan kondisi mesin.', 'assets/images/lube-oil-analysis.png'),
('guest-lecture', 8, 'Guest Lecture', 'Sharing teknis untuk kampus', 'Academic', 'Sesi berbagi teknis untuk kampus atau asosiasi.', 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?auto=format&fit=crop&w=1200&q=85')
ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order), title = VALUES(title), subtitle = VALUES(subtitle), category = VALUES(category), description = VALUES(description), image = VALUES(image);

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
