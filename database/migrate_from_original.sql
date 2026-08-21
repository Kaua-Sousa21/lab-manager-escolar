-- Execute somente se você já utiliza o banco do projeto original.
-- Faça backup antes da migração.
USE lab_manager;

ALTER TABLE users MODIFY role ENUM('admin','coordinator','technician','teacher','common') NOT NULL DEFAULT 'teacher';

CREATE TABLE IF NOT EXISTS students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    registration VARCHAR(50) NOT NULL UNIQUE,
    grade VARCHAR(30) NOT NULL,
    class_name VARCHAR(50) NOT NULL,
    birth_date DATE NULL,
    guardian_name VARCHAR(150) NULL,
    guardian_phone VARCHAR(30) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_students_name (name), INDEX idx_students_class (grade, class_name), INDEX idx_students_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE loans
    ADD COLUMN student_id INT UNSIGNED NULL AFTER user_id,
    MODIFY status ENUM('reserved','borrowed','returned','overdue','cancelled') NOT NULL DEFAULT 'borrowed';

ALTER TABLE loans ADD CONSTRAINT fk_loans_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(80) NOT NULL,
    entity VARCHAR(80) NOT NULL,
    entity_id INT UNSIGNED NULL,
    details VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_created (created_at), INDEX idx_audit_entity (entity, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Ajustes do módulo de agendamento para professores.
UPDATE equipment e
SET e.status = 'available'
WHERE e.status = 'reserved'
  AND NOT EXISTS (
      SELECT 1 FROM loans l
      WHERE l.equipment_id = e.id
        AND l.status IN ('borrowed','overdue')
  );

ALTER TABLE loans
    ADD INDEX idx_loans_schedule (equipment_id, status, withdrawal_date, expected_return_date);
