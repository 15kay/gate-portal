CREATE DATABASE IF NOT EXISTS gate_portal;
USE gate_portal;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin','reports_admin','alumni') DEFAULT 'alumni',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alumni_profiles (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Pre-loaded student records for registration verification
CREATE TABLE student_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    id_passport VARCHAR(20) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    degree VARCHAR(150),
    department VARCHAR(150),
    graduation_year YEAR,
    is_registered TINYINT(1) DEFAULT 0,  -- 1 = already has portal account
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE employment_records (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    location VARCHAR(200),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE event_rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    rsvp_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(event_id, user_id),
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE messages (
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

CREATE TABLE message_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(message_id, user_id),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);

-- Super Admin (password: Admin@1234)
INSERT INTO users (full_name, email, password, role) VALUES
('System Administrator', 'admin@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'super_admin');

-- Sample Admin accounts
INSERT INTO users (full_name, email, password, role) VALUES
('Alumni Affairs Officer', 'alumni.admin@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'admin'),
('Reports Analyst', 'reports@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'reports_admin');

-- Sample student registry (admin pre-loads these from academic records)
INSERT INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year) VALUES
('201900001', '9001015009087', 'Thabo Nkosi', 'BSc Computer Science', 'Faculty of Science & Technology', 2023),
('201900002', '9203025009083', 'Nomsa Dlamini', 'BCom Accounting', 'Faculty of Business', 2022),
('201900003', 'A12345678', 'John Mokoena', 'BA Education', 'Faculty of Education', 2021);
