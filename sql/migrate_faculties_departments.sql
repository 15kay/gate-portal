-- ============================================================================
-- Add faculties and departments tables
-- ============================================================================

USE gate_portal;

-- Create faculties table
CREATE TABLE IF NOT EXISTS faculties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (faculty_id) REFERENCES faculties(id) ON DELETE CASCADE
);

-- Add faculty column to alumni_profiles (ignore error if exists)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'gate_portal' AND TABLE_NAME = 'alumni_profiles' AND COLUMN_NAME = 'faculty');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE alumni_profiles ADD COLUMN faculty VARCHAR(200) AFTER department', 
    'SELECT "Column faculty already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert WSU faculties
INSERT IGNORE INTO faculties (name) VALUES
('Faculty of Engineering, the Built Environment and Information Technology'),
('Faculty of Law, Humanities and Social Sciences'),
('Faculty of Management and Public Administration'),
('Faculty of Economics and Financial Sciences'),
('Faculty of Medicine and Health Sciences'),
('Faculty of Natural Sciences'),
('Faculty of Education');

-- Insert departments for each faculty
-- Faculty 1: Engineering, Built Environment & IT
INSERT IGNORE INTO departments (faculty_id, name) VALUES
(1, 'Civil Engineering'),
(1, 'Electrical Engineering'),
(1, 'Mechanical Engineering'),
(1, 'Computer Science'),
(1, 'Information Technology'),
(1, 'Architecture'),
(1, 'Construction Management'),
(1, 'Quantity Surveying');

-- Faculty 2: Law, Humanities & Social Sciences
INSERT IGNORE INTO departments (faculty_id, name) VALUES
(2, 'Law'),
(2, 'English'),
(2, 'History'),
(2, 'Sociology'),
(2, 'Psychology'),
(2, 'Social Work'),
(2, 'Development Studies'),
(2, 'Political Science');

-- Faculty 3: Management & Public Administration
INSERT IGNORE INTO departments (faculty_id, name) VALUES
(3, 'Business Management'),
(3, 'Public Administration'),
(3, 'Human Resource Management'),
(3, 'Marketing'),
(3, 'Tourism Management'),
(3, 'Office Management');

-- Faculty 4: Economics & Financial Sciences
INSERT IGNORE INTO departments (faculty_id, name) VALUES
(4, 'Economics'),
(4, 'Accounting'),
(4, 'Financial Management'),
(4, 'Auditing'),
(4, 'Taxation');

-- Faculty 5: Medicine & Health Sciences
INSERT IGNORE INTO departments (faculty_id, name) VALUES
(5, 'Medicine'),
(5, 'Nursing'),
(5, 'Pharmacy'),
(5, 'Physiotherapy'),
(5, 'Radiography'),
(5, 'Environmental Health'),
(5, 'Biomedical Sciences');

-- Faculty 6: Natural Sciences
INSERT IGNORE INTO departments (faculty_id, name) VALUES
(6, 'Mathematics'),
(6, 'Physics'),
(6, 'Chemistry'),
(6, 'Biology'),
(6, 'Biochemistry'),
(6, 'Microbiology'),
(6, 'Botany'),
(6, 'Zoology');

-- Faculty 7: Education
INSERT IGNORE INTO departments (faculty_id, name) VALUES
(7, 'Foundation Phase Education'),
(7, 'Intermediate Phase Education'),
(7, 'Senior Phase Education'),
(7, 'Further Education and Training'),
(7, 'Educational Psychology'),
(7, 'Curriculum Studies'),
(7, 'Educational Management');

SELECT 'Faculties and departments migration completed!' AS Status;
SELECT COUNT(*) AS Faculties FROM faculties;
SELECT COUNT(*) AS Departments FROM departments;
