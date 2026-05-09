-- ============================================================================
-- GATE Portal - Complete Database Installation Script
-- Walter Sisulu University
-- ============================================================================
-- Run this file on the WSU server to set up the complete database
-- ============================================================================

-- Create and use database
CREATE DATABASE IF NOT EXISTS gate_portal;
USE gate_portal;

-- ============================================================================
-- CORE TABLES
-- ============================================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin','reports_admin','alumni','employer') DEFAULT 'alumni',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS alumni_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_id VARCHAR(50),
    id_number VARCHAR(20),
    phone VARCHAR(20),
    graduation_year YEAR,
    degree VARCHAR(150),
    department VARCHAR(150),
    profile_photo VARCHAR(255),
    bio TEXT,
    linkedin_url VARCHAR(255),
    gender ENUM('Male','Female','Other','Prefer not to say'),
    location VARCHAR(200),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    id_passport VARCHAR(20) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    degree VARCHAR(150),
    department VARCHAR(150),
    graduation_year YEAR,
    is_registered TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS employment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    employer VARCHAR(200),
    job_title VARCHAR(150),
    industry VARCHAR(100),
    employment_type ENUM('Full-time','Part-time','Self-employed','Freelance','Unemployed','Further Studies') NOT NULL,
    start_date DATE,
    end_date DATE,
    is_current TINYINT(1) DEFAULT 1,
    location VARCHAR(150),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alumni_cv (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    cv_file VARCHAR(255),
    skills TEXT,
    summary TEXT,
    keywords TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS alumni_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    proficiency ENUM('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS employers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(200) NOT NULL,
    contact_email VARCHAR(150) NOT NULL,
    industry VARCHAR(100),
    user_id INT UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS opportunities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    company VARCHAR(200) NOT NULL,
    industry VARCHAR(100),
    location VARCHAR(150),
    job_type ENUM('Full-time','Part-time','Contract','Internship') DEFAULT 'Full-time',
    description TEXT,
    requirements TEXT,
    deadline DATE,
    status ENUM('open','closed','filled') DEFAULT 'open',
    employer_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS candidate_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opportunity_id INT NOT NULL,
    alumni_user_id INT NOT NULL,
    match_score TINYINT DEFAULT 0,
    score_breakdown TEXT,
    status ENUM('suggested','selected','submitted','accepted','rejected') DEFAULT 'suggested',
    notes TEXT,
    submitted_by INT,
    submitted_at TIMESTAMP NULL,
    employer_released TINYINT(1) DEFAULT 0,
    released_at TIMESTAMP NULL,
    release_notes TEXT,
    interview_scheduled_at DATETIME NULL,
    interview_type ENUM('In-person','Video call','Phone call') NULL,
    interview_location VARCHAR(255) NULL,
    interview_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(opportunity_id, alumni_user_id),
    FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
    FOREIGN KEY (alumni_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    location VARCHAR(200),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS event_rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    rsvp_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT,
    subject VARCHAR(200),
    body TEXT NOT NULL,
    is_broadcast TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (recipient_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS message_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS portal_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    actor_type ENUM('super_admin','admin','reports_admin','alumni','employer','system') DEFAULT 'alumni',
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================================
-- DEFAULT ADMIN ACCOUNTS
-- ============================================================================
-- Password for all: Admin@1234

INSERT IGNORE INTO users (full_name, email, password, role) VALUES
('System Administrator', 'admin@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'super_admin'),
('Alumni Affairs Officer', 'alumni.admin@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'admin'),
('Reports Analyst', 'reports@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'reports_admin');

-- ============================================================================
-- SAMPLE STUDENT REGISTRY DATA
-- ============================================================================

INSERT IGNORE INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year) VALUES
('201900001', '9001015009087', 'Thabo Nkosi', 'BSc Computer Science', 'Faculty of Science & Technology', 2023),
('201900002', '9203025009083', 'Nomsa Dlamini', 'BCom Accounting', 'Faculty of Business', 2022),
('201900003', 'A12345678', 'John Mokoena', 'BA Education', 'Faculty of Education', 2021);

-- ============================================================================
-- DEFAULT PORTAL SETTINGS
-- ============================================================================

INSERT IGNORE INTO portal_settings (setting_key, setting_value) VALUES
('portal_name', 'GATE Portal'),
('institution_name', 'Walter Sisulu University'),
('contact_email', 'alumni@wsu.ac.za'),
('contact_phone', '+27 (0)47 502 2111'),
('welcome_message', 'Welcome to the Graduate & Alumni Tracking & Engagement Portal'),
('footer_text', '© 2024 Walter Sisulu University. All rights reserved.'),
('maintenance_mode', '0'),
('registration_open', '1'),
('smtp_host', 'smtp.office365.com'),
('smtp_port', '587'),
('smtp_username', ''),
('smtp_password', ''),
('smtp_from_email', 'noreply@wsu.ac.za'),
('smtp_from_name', 'WSU GATE Portal'),
('session_timeout', '3600'),
('max_login_attempts', '5');

-- ============================================================================
-- INSTALLATION COMPLETE
-- ============================================================================

SELECT 'Database installation completed successfully!' AS Status;
SELECT COUNT(*) AS AdminAccounts FROM users WHERE role IN ('super_admin','admin','reports_admin');
SELECT COUNT(*) AS StudentRecords FROM student_registry;
