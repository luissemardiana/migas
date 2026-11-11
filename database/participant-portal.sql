-- Additive migration: existing programs, proposed batches and leads remain unchanged.
CREATE TABLE IF NOT EXISTS portal_migrations (version VARCHAR(80) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
 role ENUM('participant','admin') NOT NULL DEFAULT 'participant', session_version INT NOT NULL DEFAULT 1,
 is_active TINYINT NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS participant_profiles (
 user_id BIGINT UNSIGNED PRIMARY KEY, phone VARCHAR(40) NULL, company VARCHAR(180) NULL,
 position VARCHAR(140) NULL, address TEXT NULL, identity_info VARCHAR(190) NULL, photo_path VARCHAR(190) NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS password_reset_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, used_at DATETIME NULL,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS auth_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bucket CHAR(64) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX(bucket,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS program_details (
 program_slug VARCHAR(100) PRIMARY KEY, level VARCHAR(80) NULL, duration VARCHAR(100) NULL,
 method ENUM('online','offline','hybrid') NULL, objectives TEXT NULL, curriculum TEXT NULL,
 requirements TEXT NULL, audience TEXT NULL,
 FOREIGN KEY(program_slug) REFERENCES training_programs(slug) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS batch_settings (
 batch_id BIGINT UNSIGNED PRIMARY KEY, capacity INT UNSIGNED NULL, registration_deadline DATETIME NULL,
 price DECIMAL(12,2) NULL, method ENUM('online','offline','hybrid') NULL,
 payment_instructions TEXT NULL, equipment TEXT NULL, practical_info TEXT NULL,
 FOREIGN KEY(batch_id) REFERENCES training_batches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS instructors (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, bio TEXT NOT NULL,
 expertise TEXT NULL, experience TEXT NULL, certifications TEXT NULL, training_history TEXT NULL,
 photo VARCHAR(500) NULL, is_published TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS program_instructors (
 program_slug VARCHAR(100) NOT NULL, instructor_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(program_slug,instructor_id), FOREIGN KEY(program_slug) REFERENCES training_programs(slug) ON DELETE CASCADE,
 FOREIGN KEY(instructor_id) REFERENCES instructors(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS articles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(140) NOT NULL UNIQUE,
 title VARCHAR(200) NOT NULL, category VARCHAR(80) NOT NULL, excerpt TEXT NULL,
 body MEDIUMTEXT NOT NULL, image VARCHAR(500) NULL, published_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS testimonials (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(160) NOT NULL, organization VARCHAR(180) NULL,
 quote TEXT NOT NULL, is_published TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS faqs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, question VARCHAR(255) NOT NULL, answer TEXT NOT NULL,
 sort_order INT NOT NULL DEFAULT 0, is_published TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS registrations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, batch_id BIGINT UNSIGNED NOT NULL,
 status ENUM('pending','under_review','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
 payment_status ENUM('not_required','unpaid','pending','paid') NOT NULL DEFAULT 'unpaid',
 price DECIMAL(12,2) NOT NULL, admin_remarks TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE(user_id,batch_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(batch_id) REFERENCES training_batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS enrollments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, registration_id BIGINT UNSIGNED NOT NULL UNIQUE,
 status ENUM('active','completed','revoked') NOT NULL DEFAULT 'active', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(registration_id) REFERENCES registrations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS training_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL,
 starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, method ENUM('online','offline','hybrid') NOT NULL,
 location VARCHAR(255) NULL, meeting_url VARCHAR(500) NULL, instructor_id BIGINT UNSIGNED NULL,
 attendance_token_hash CHAR(64) NULL, attendance_open_at DATETIME NULL, attendance_close_at DATETIME NULL,
 FOREIGN KEY(batch_id) REFERENCES training_batches(id), FOREIGN KEY(instructor_id) REFERENCES instructors(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS lessons (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL,
 module_title VARCHAR(180) NOT NULL, title VARCHAR(200) NOT NULL, body MEDIUMTEXT NULL,
 kind ENUM('reading','video','pdf','presentation','practical') NOT NULL DEFAULT 'reading',
 file_path VARCHAR(190) NULL, file_name VARCHAR(190) NULL, video_url VARCHAR(500) NULL,
 sort_order INT NOT NULL DEFAULT 0, is_published TINYINT NOT NULL DEFAULT 0,
 FOREIGN KEY(batch_id) REFERENCES training_batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS lesson_completions (
 user_id BIGINT UNSIGNED NOT NULL, lesson_id BIGINT UNSIGNED NOT NULL, completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(user_id,lesson_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(lesson_id) REFERENCES lessons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS assignments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL,
 instructions TEXT NOT NULL, due_at DATETIME NULL, is_published TINYINT NOT NULL DEFAULT 0,
 FOREIGN KEY(batch_id) REFERENCES training_batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS assignment_submissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assignment_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 answer TEXT NULL, file_path VARCHAR(190) NULL, file_name VARCHAR(190) NULL,
 status ENUM('submitted','passed','revision') NOT NULL DEFAULT 'submitted', feedback TEXT NULL, submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(assignment_id,user_id), FOREIGN KEY(assignment_id) REFERENCES assignments(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS attendance_records (
 user_id BIGINT UNSIGNED NOT NULL, session_id BIGINT UNSIGNED NOT NULL,
 status ENUM('present','late','absent','excused') NOT NULL, checked_in_at DATETIME NULL,
 PRIMARY KEY(user_id,session_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(session_id) REFERENCES training_sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS assessments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL, title VARCHAR(200) NOT NULL,
 instructions TEXT NULL, kind ENUM('quiz','exam','practical') NOT NULL DEFAULT 'quiz',
 opens_at DATETIME NOT NULL, closes_at DATETIME NOT NULL, duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
 pass_score DECIMAL(5,2) NOT NULL DEFAULT 70, reveal_answers TINYINT NOT NULL DEFAULT 0,
 is_published TINYINT NOT NULL DEFAULT 0, FOREIGN KEY(batch_id) REFERENCES training_batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS assessment_questions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_id BIGINT UNSIGNED NOT NULL,
 question TEXT NOT NULL, options_json JSON NOT NULL, correct_option INT UNSIGNED NOT NULL,
 sort_order INT NOT NULL DEFAULT 0, FOREIGN KEY(assessment_id) REFERENCES assessments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS assessment_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, assessment_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 started_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, submitted_at DATETIME NULL, score DECIMAL(5,2) NULL,
 passed TINYINT NULL, feedback TEXT NULL, UNIQUE(assessment_id,user_id),
 FOREIGN KEY(assessment_id) REFERENCES assessments(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS assessment_answers (
 attempt_id BIGINT UNSIGNED NOT NULL, question_id BIGINT UNSIGNED NOT NULL, selected_option INT UNSIGNED NULL,
 PRIMARY KEY(attempt_id,question_id), FOREIGN KEY(attempt_id) REFERENCES assessment_attempts(id),
 FOREIGN KEY(question_id) REFERENCES assessment_questions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS certificates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, enrollment_id BIGINT UNSIGNED NOT NULL UNIQUE,
 certificate_number VARCHAR(100) NOT NULL UNIQUE, participant_name VARCHAR(160) NOT NULL,
 program_name VARCHAR(180) NOT NULL, verification_token CHAR(64) NOT NULL UNIQUE, file_path VARCHAR(190) NOT NULL,
 approved_by BIGINT UNSIGNED NOT NULL, issued_at DATETIME NOT NULL, revoked_at DATETIME NULL,
 FOREIGN KEY(enrollment_id) REFERENCES enrollments(id), FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, title VARCHAR(180) NOT NULL,
 body TEXT NOT NULL, destination VARCHAR(30) NOT NULL DEFAULT 'overview', read_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id), INDEX(user_id,read_at,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, actor_id BIGINT UNSIGNED NULL, action VARCHAR(100) NOT NULL,
 subject_id BIGINT UNSIGNED NULL, detail TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS notification_dispatches (
 event_key VARCHAR(180) PRIMARY KEY, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
