USE gate_portal;

CREATE TABLE IF NOT EXISTS opportunities (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    company     VARCHAR(200) NOT NULL,
    industry    VARCHAR(100),
    location    VARCHAR(150),
    type        ENUM('Full-time','Part-time','Contract','Internship','Freelance') DEFAULT 'Full-time',
    description TEXT,
    requirements TEXT,
    deadline    DATE,
    status      ENUM('open','closed','filled') DEFAULT 'open',
    created_by  INT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS candidate_submissions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id  INT NOT NULL,
    alumni_user_id  INT NOT NULL,
    match_score     TINYINT UNSIGNED DEFAULT 0,
    status          ENUM('suggested','selected','submitted','accepted','rejected') DEFAULT 'suggested',
    notes           TEXT,
    submitted_by    INT,
    submitted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE(opportunity_id, alumni_user_id),
    FOREIGN KEY (opportunity_id)  REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (alumni_user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by)    REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    user_email  VARCHAR(150),
    action      VARCHAR(100) NOT NULL,
    target      VARCHAR(200),
    detail      TEXT,
    ip          VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
