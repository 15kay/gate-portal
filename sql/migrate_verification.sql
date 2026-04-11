-- Run this in phpMyAdmin if you already have the database set up

-- Add ID number to alumni profiles
ALTER TABLE alumni_profiles ADD COLUMN IF NOT EXISTS id_number VARCHAR(20) AFTER student_id;

-- Portal settings table
CREATE TABLE IF NOT EXISTS portal_settings (
    `key`      VARCHAR(100) PRIMARY KEY,
    `value`    TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Default settings
INSERT IGNORE INTO portal_settings (`key`, `value`) VALUES
('portal_name',       'GATE Portal'),
('institution_name',  'Walter Sisulu University'),
('contact_email',     'alumni@wsu.ac.za'),
('registration_open', '1');

-- Create student registry table
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

-- Sample student records for testing
INSERT IGNORE INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year) VALUES
('201900001', '9001015009087', 'Thabo Nkosi', 'BSc Computer Science', 'Faculty of Science & Technology', 2023),
('201900002', '9203025009083', 'Nomsa Dlamini', 'BCom Accounting', 'Faculty of Business', 2022),
('201900003', 'A12345678', 'John Mokoena', 'BA Education', 'Faculty of Education', 2021);
