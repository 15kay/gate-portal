USE gate_portal;

-- External Actor: Employer / Opportunity Provider
CREATE TABLE IF NOT EXISTS employers (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) NOT NULL,
    industry     VARCHAR(100),
    website      VARCHAR(255),
    contact_name VARCHAR(150),
    contact_email VARCHAR(150),
    contact_phone VARCHAR(30),
    address      VARCHAR(255),
    notes        TEXT,
    created_by   INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Link opportunities to employer record (optional — keeps backward compat)
ALTER TABLE opportunities
    ADD COLUMN IF NOT EXISTS employer_id INT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS contact_name  VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS contact_email VARCHAR(150) DEFAULT NULL,
    ADD FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE SET NULL;

-- Track which actor type performed each audit action
ALTER TABLE audit_logs
    ADD COLUMN IF NOT EXISTS actor_type ENUM('super_admin','admin','reports_admin','alumni','system') DEFAULT 'admin' AFTER user_email;
