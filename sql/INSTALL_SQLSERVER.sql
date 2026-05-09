-- ============================================================================
-- GATE Portal - SQL Server Installation Script
-- Walter Sisulu University
-- ============================================================================
-- Run this file in SQL Server Management Studio (SSMS)
-- ============================================================================

-- Create database
IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = 'gate_portal')
BEGIN
    CREATE DATABASE gate_portal;
END
GO

USE gate_portal;
GO

-- ============================================================================
-- CORE TABLES
-- ============================================================================

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'users')
BEGIN
    CREATE TABLE users (
        id INT IDENTITY(1,1) PRIMARY KEY,
        full_name NVARCHAR(150) NOT NULL,
        email NVARCHAR(150) UNIQUE NOT NULL,
        password NVARCHAR(255) NOT NULL,
        role NVARCHAR(20) DEFAULT 'alumni' CHECK (role IN ('super_admin','admin','reports_admin','alumni','employer')),
        created_at DATETIME2 DEFAULT GETDATE()
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'alumni_profiles')
BEGIN
    CREATE TABLE alumni_profiles (
        id INT IDENTITY(1,1) PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        student_id NVARCHAR(50),
        id_number NVARCHAR(20),
        phone NVARCHAR(20),
        graduation_year INT,
        degree NVARCHAR(150),
        department NVARCHAR(150),
        profile_photo NVARCHAR(255),
        bio NVARCHAR(MAX),
        linkedin_url NVARCHAR(255),
        gender NVARCHAR(20) CHECK (gender IN ('Male','Female','Other','Prefer not to say')),
        location NVARCHAR(200),
        updated_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'student_registry')
BEGIN
    CREATE TABLE student_registry (
        id INT IDENTITY(1,1) PRIMARY KEY,
        student_number NVARCHAR(50) UNIQUE NOT NULL,
        id_passport NVARCHAR(20) NOT NULL,
        full_name NVARCHAR(150) NOT NULL,
        degree NVARCHAR(150),
        department NVARCHAR(150),
        graduation_year INT,
        is_registered BIT DEFAULT 0,
        created_at DATETIME2 DEFAULT GETDATE()
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'employment_records')
BEGIN
    CREATE TABLE employment_records (
        id INT IDENTITY(1,1) PRIMARY KEY,
        user_id INT NOT NULL,
        employer NVARCHAR(200),
        job_title NVARCHAR(150),
        industry NVARCHAR(100),
        employment_type NVARCHAR(20) NOT NULL CHECK (employment_type IN ('Full-time','Part-time','Self-employed','Freelance','Unemployed','Further Studies')),
        start_date DATE,
        end_date DATE,
        is_current BIT DEFAULT 1,
        location NVARCHAR(150),
        description NVARCHAR(MAX),
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'alumni_cv')
BEGIN
    CREATE TABLE alumni_cv (
        id INT IDENTITY(1,1) PRIMARY KEY,
        user_id INT UNIQUE NOT NULL,
        cv_file NVARCHAR(255),
        skills NVARCHAR(MAX),
        summary NVARCHAR(MAX),
        keywords NVARCHAR(MAX),
        uploaded_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'alumni_skills')
BEGIN
    CREATE TABLE alumni_skills (
        id INT IDENTITY(1,1) PRIMARY KEY,
        user_id INT NOT NULL,
        skill_name NVARCHAR(100) NOT NULL,
        proficiency NVARCHAR(20) DEFAULT 'Intermediate' CHECK (proficiency IN ('Beginner','Intermediate','Advanced','Expert')),
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'employers')
BEGIN
    CREATE TABLE employers (
        id INT IDENTITY(1,1) PRIMARY KEY,
        company_name NVARCHAR(200) NOT NULL,
        contact_email NVARCHAR(150) NOT NULL,
        industry NVARCHAR(100),
        user_id INT UNIQUE,
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'opportunities')
BEGIN
    CREATE TABLE opportunities (
        id INT IDENTITY(1,1) PRIMARY KEY,
        title NVARCHAR(200) NOT NULL,
        company NVARCHAR(200) NOT NULL,
        industry NVARCHAR(100),
        location NVARCHAR(150),
        job_type NVARCHAR(20) DEFAULT 'Full-time' CHECK (job_type IN ('Full-time','Part-time','Contract','Internship')),
        description NVARCHAR(MAX),
        requirements NVARCHAR(MAX),
        deadline DATE,
        status NVARCHAR(20) DEFAULT 'open' CHECK (status IN ('open','closed','filled')),
        employer_id INT,
        created_by INT,
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (employer_id) REFERENCES employers(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES users(id)
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'candidate_submissions')
BEGIN
    CREATE TABLE candidate_submissions (
        id INT IDENTITY(1,1) PRIMARY KEY,
        opportunity_id INT NOT NULL,
        alumni_user_id INT NOT NULL,
        match_score TINYINT DEFAULT 0,
        score_breakdown NVARCHAR(MAX),
        status NVARCHAR(20) DEFAULT 'suggested' CHECK (status IN ('suggested','selected','submitted','accepted','rejected')),
        notes NVARCHAR(MAX),
        submitted_by INT,
        submitted_at DATETIME2 NULL,
        employer_released BIT DEFAULT 0,
        released_at DATETIME2 NULL,
        release_notes NVARCHAR(MAX),
        interview_scheduled_at DATETIME2 NULL,
        interview_type NVARCHAR(20) NULL CHECK (interview_type IN ('In-person','Video call','Phone call')),
        interview_location NVARCHAR(255) NULL,
        interview_notes NVARCHAR(MAX) NULL,
        created_at DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT UQ_opportunity_alumni UNIQUE(opportunity_id, alumni_user_id),
        FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE CASCADE,
        FOREIGN KEY (alumni_user_id) REFERENCES users(id),
        FOREIGN KEY (submitted_by) REFERENCES users(id)
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'events')
BEGIN
    CREATE TABLE events (
        id INT IDENTITY(1,1) PRIMARY KEY,
        title NVARCHAR(200) NOT NULL,
        description NVARCHAR(MAX),
        event_date DATE NOT NULL,
        location NVARCHAR(200),
        created_by INT,
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (created_by) REFERENCES users(id)
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'event_rsvps')
BEGIN
    CREATE TABLE event_rsvps (
        id INT IDENTITY(1,1) PRIMARY KEY,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        rsvp_at DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT UQ_event_user UNIQUE(event_id, user_id),
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'messages')
BEGIN
    CREATE TABLE messages (
        id INT IDENTITY(1,1) PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_id INT,
        subject NVARCHAR(200),
        body NVARCHAR(MAX) NOT NULL,
        is_broadcast BIT DEFAULT 0,
        sent_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (recipient_id) REFERENCES users(id)
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'message_reads')
BEGIN
    CREATE TABLE message_reads (
        id INT IDENTITY(1,1) PRIMARY KEY,
        message_id INT NOT NULL,
        user_id INT NOT NULL,
        read_at DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT UQ_message_user UNIQUE(message_id, user_id),
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'portal_settings')
BEGIN
    CREATE TABLE portal_settings (
        setting_key NVARCHAR(100) PRIMARY KEY,
        setting_value NVARCHAR(MAX),
        updated_at DATETIME2 DEFAULT GETDATE()
    );
END
GO

IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'audit_logs')
BEGIN
    CREATE TABLE audit_logs (
        id INT IDENTITY(1,1) PRIMARY KEY,
        user_id INT,
        actor_type NVARCHAR(20) DEFAULT 'alumni' CHECK (actor_type IN ('super_admin','admin','reports_admin','alumni','employer','system')),
        action NVARCHAR(100) NOT NULL,
        target_type NVARCHAR(50),
        target_id INT,
        details NVARCHAR(MAX),
        ip_address NVARCHAR(45),
        created_at DATETIME2 DEFAULT GETDATE(),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    );
END
GO

-- ============================================================================
-- DEFAULT ADMIN ACCOUNTS
-- ============================================================================
-- Password for all: Admin@1234

IF NOT EXISTS (SELECT * FROM users WHERE email = 'admin@gateportal.ac')
BEGIN
    INSERT INTO users (full_name, email, password, role) VALUES
    ('System Administrator', 'admin@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'super_admin');
END
GO

IF NOT EXISTS (SELECT * FROM users WHERE email = 'alumni.admin@gateportal.ac')
BEGIN
    INSERT INTO users (full_name, email, password, role) VALUES
    ('Alumni Affairs Officer', 'alumni.admin@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'admin');
END
GO

IF NOT EXISTS (SELECT * FROM users WHERE email = 'reports@gateportal.ac')
BEGIN
    INSERT INTO users (full_name, email, password, role) VALUES
    ('Reports Analyst', 'reports@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'reports_admin');
END
GO

-- ============================================================================
-- SAMPLE STUDENT REGISTRY DATA
-- ============================================================================

IF NOT EXISTS (SELECT * FROM student_registry WHERE student_number = '201900001')
BEGIN
    INSERT INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year) VALUES
    ('201900001', '9001015009087', 'Thabo Nkosi', 'BSc Computer Science', 'Faculty of Science & Technology', 2023);
END
GO

IF NOT EXISTS (SELECT * FROM student_registry WHERE student_number = '201900002')
BEGIN
    INSERT INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year) VALUES
    ('201900002', '9203025009083', 'Nomsa Dlamini', 'BCom Accounting', 'Faculty of Business', 2022);
END
GO

IF NOT EXISTS (SELECT * FROM student_registry WHERE student_number = '201900003')
BEGIN
    INSERT INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year) VALUES
    ('201900003', 'A12345678', 'John Mokoena', 'BA Education', 'Faculty of Education', 2021);
END
GO

-- ============================================================================
-- DEFAULT PORTAL SETTINGS
-- ============================================================================

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'portal_name')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('portal_name', 'GATE Portal');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'institution_name')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('institution_name', 'Walter Sisulu University');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'contact_email')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('contact_email', 'alumni@wsu.ac.za');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'contact_phone')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('contact_phone', '+27 (0)47 502 2111');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'welcome_message')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('welcome_message', 'Welcome to the Graduate & Alumni Tracking & Engagement Portal');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'footer_text')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('footer_text', '© 2024 Walter Sisulu University. All rights reserved.');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'maintenance_mode')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('maintenance_mode', '0');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'registration_open')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('registration_open', '1');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'smtp_host')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('smtp_host', 'smtp.office365.com');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'smtp_port')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('smtp_port', '587');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'smtp_username')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('smtp_username', '');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'smtp_password')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('smtp_password', '');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'smtp_from_email')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('smtp_from_email', 'noreply@wsu.ac.za');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'smtp_from_name')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('smtp_from_name', 'WSU GATE Portal');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'session_timeout')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('session_timeout', '3600');
GO

IF NOT EXISTS (SELECT * FROM portal_settings WHERE setting_key = 'max_login_attempts')
    INSERT INTO portal_settings (setting_key, setting_value) VALUES ('max_login_attempts', '5');
GO

-- ============================================================================
-- INSTALLATION COMPLETE
-- ============================================================================

PRINT 'Database installation completed successfully!';
SELECT COUNT(*) AS AdminAccounts FROM users WHERE role IN ('super_admin','admin','reports_admin');
SELECT COUNT(*) AS StudentRecords FROM student_registry;
GO
