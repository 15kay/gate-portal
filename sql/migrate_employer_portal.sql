USE gate_portal;

-- 1. Add employer portal login support
ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','admin','reports_admin','alumni','employer') DEFAULT 'alumni';

ALTER TABLE employers
    ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL AFTER id,
    ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- 2. Add release tracking columns to candidate_submissions
ALTER TABLE candidate_submissions
    ADD COLUMN IF NOT EXISTS employer_released TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS released_at       TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS released_by       INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS release_notes     TEXT DEFAULT NULL;
