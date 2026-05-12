-- Seed data for GATE Portal
-- Run after INSTALL_ON_SERVER.sql

USE gate_portal;

-- Insert sample alumni users (passwords are all: Alumni@123)
INSERT INTO users (email, password, role, full_name, created_at) VALUES
('john.doe@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'John Doe', NOW()),
('sarah.smith@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Sarah Smith', NOW()),
('michael.jones@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Michael Jones', NOW()),
('linda.williams@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Linda Williams', NOW()),
('david.brown@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'David Brown', NOW()),
('emma.davis@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Emma Davis', NOW()),
('james.wilson@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'James Wilson', NOW()),
('olivia.taylor@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Olivia Taylor', NOW());

-- Get user IDs for alumni profiles
SET @john_id = (SELECT id FROM users WHERE email = 'john.doe@alumni.wsu.ac.za');
SET @sarah_id = (SELECT id FROM users WHERE email = 'sarah.smith@alumni.wsu.ac.za');
SET @michael_id = (SELECT id FROM users WHERE email = 'michael.jones@alumni.wsu.ac.za');
SET @linda_id = (SELECT id FROM users WHERE email = 'linda.williams@alumni.wsu.ac.za');
SET @david_id = (SELECT id FROM users WHERE email = 'david.brown@alumni.wsu.ac.za');
SET @emma_id = (SELECT id FROM users WHERE email = 'emma.davis@alumni.wsu.ac.za');
SET @james_id = (SELECT id FROM users WHERE email = 'james.wilson@alumni.wsu.ac.za');
SET @olivia_id = (SELECT id FROM users WHERE email = 'olivia.taylor@alumni.wsu.ac.za');

-- Insert alumni profiles
INSERT INTO alumni_profiles (user_id, student_number, id_number, degree, department, graduation_year, phone, location, bio) VALUES
(@john_id, '201812345', '9501155678089', 'BSc Computer Science', 'Faculty of Science', 2022, '+27 82 123 4567', 'East London, Eastern Cape', 'Software developer passionate about web technologies'),
(@sarah_id, '201823456', '9603225789012', 'BCom Accounting', 'Faculty of Commerce', 2021, '+27 83 234 5678', 'Mthatha, Eastern Cape', 'Chartered accountant with 3 years experience'),
(@michael_id, '201934567', '9705115890123', 'BEng Electrical Engineering', 'Faculty of Engineering', 2023, '+27 84 345 6789', 'Port Elizabeth, Eastern Cape', 'Electrical engineer specializing in renewable energy'),
(@linda_id, '201845678', '9802205901234', 'BA Social Work', 'Faculty of Humanities', 2020, '+27 85 456 7890', 'Butterworth, Eastern Cape', 'Community development practitioner'),
(@david_id, '201956789', '9904125012345', 'BSc Information Technology', 'Faculty of Science', 2023, '+27 86 567 8901', 'East London, Eastern Cape', 'IT specialist and network administrator'),
(@emma_id, '201867890', '9707215123456', 'BCom Business Management', 'Faculty of Commerce', 2022, '+27 87 678 9012', 'Mthatha, Eastern Cape', 'Business analyst and project manager'),
(@james_id, '201978901', '9808125234567', 'BEng Civil Engineering', 'Faculty of Engineering', 2023, '+27 88 789 0123', 'East London, Eastern Cape', 'Civil engineer working on infrastructure projects'),
(@olivia_id, '201889012', '9909205345678', 'BA Education', 'Faculty of Education', 2022, '+27 89 890 1234', 'Queenstown, Eastern Cape', 'High school mathematics teacher');

-- Insert employment records
INSERT INTO employment_records (alumni_user_id, employer, job_title, industry, employment_type, start_date, is_current, location, description) VALUES
(@john_id, 'Tech Solutions SA', 'Junior Developer', 'Information Technology', 'Full-time', '2022-03-01', 1, 'East London', 'Developing web applications using PHP and JavaScript'),
(@sarah_id, 'KPMG South Africa', 'Audit Associate', 'Finance & Accounting', 'Full-time', '2021-02-01', 1, 'Port Elizabeth', 'Conducting financial audits for corporate clients'),
(@michael_id, 'Eskom Holdings', 'Electrical Engineer', 'Energy & Utilities', 'Full-time', '2023-07-01', 1, 'Port Elizabeth', 'Working on power distribution systems'),
(@linda_id, 'Department of Social Development', 'Social Worker', 'Government & Public Sector', 'Full-time', '2020-08-01', 1, 'Butterworth', 'Community outreach and support programs'),
(@david_id, 'WSU IT Department', 'Network Administrator', 'Education', 'Full-time', '2023-09-01', 1, 'Mthatha', 'Managing university network infrastructure'),
(@emma_id, 'Standard Bank', 'Business Analyst', 'Banking & Financial Services', 'Full-time', '2022-06-01', 1, 'East London', 'Analyzing business processes and requirements'),
(@james_id, 'SANRAL', 'Civil Engineer', 'Construction & Infrastructure', 'Full-time', '2023-08-01', 1, 'East London', 'Road construction and maintenance projects'),
(@olivia_id, 'Queenstown High School', 'Mathematics Teacher', 'Education', 'Full-time', '2022-01-15', 1, 'Queenstown', 'Teaching Grade 10-12 mathematics');

-- Insert student registry entries
INSERT INTO student_registry (student_number, id_number, degree, department, graduation_year, activated) VALUES
('201812345', '9501155678089', 'BSc Computer Science', 'Faculty of Science', 2022, 1),
('201823456', '9603225789012', 'BCom Accounting', 'Faculty of Commerce', 2021, 1),
('201934567', '9705115890123', 'BEng Electrical Engineering', 'Faculty of Engineering', 2023, 1),
('201845678', '9802205901234', 'BA Social Work', 'Faculty of Humanities', 2020, 1),
('201956789', '9904125012345', 'BSc Information Technology', 'Faculty of Science', 2023, 1),
('201867890', '9707215123456', 'BCom Business Management', 'Faculty of Commerce', 2022, 1),
('201978901', '9808125234567', 'BEng Civil Engineering', 'Faculty of Engineering', 2023, 1),
('201889012', '9909205345678', 'BA Education', 'Faculty of Education', 2022, 1),
('202012346', '0001015456789', 'BSc Chemistry', 'Faculty of Science', 2024, 0),
('202023457', '0102025567890', 'BCom Economics', 'Faculty of Commerce', 2024, 0);

-- Insert sample events
INSERT INTO events (title, description, event_date, location, created_by) VALUES
('Alumni Networking Evening', 'Join us for an evening of networking with fellow WSU alumni and industry professionals.', '2024-06-15 18:00:00', 'WSU East London Campus', 1),
('Career Development Workshop', 'Workshop on CV writing, interview skills, and job search strategies.', '2024-07-20 10:00:00', 'WSU Mthatha Campus', 1),
('Annual Alumni Gala Dinner', 'Our annual gala dinner celebrating WSU alumni achievements.', '2024-08-30 19:00:00', 'ICC East London', 1);

-- Insert sample employers
INSERT INTO employers (company_name, contact_email, industry, created_at) VALUES
('Deloitte South Africa', 'recruitment@deloitte.co.za', 'Consulting & Professional Services', NOW()),
('Sasol Limited', 'careers@sasol.com', 'Energy & Utilities', NOW()),
('Nedbank Group', 'talent@nedbank.co.za', 'Banking & Financial Services', NOW());

-- Get employer IDs
SET @deloitte_id = (SELECT id FROM employers WHERE company_name = 'Deloitte South Africa');
SET @sasol_id = (SELECT id FROM employers WHERE company_name = 'Sasol Limited');
SET @nedbank_id = (SELECT id FROM employers WHERE company_name = 'Nedbank Group');

-- Insert sample opportunities
INSERT INTO opportunities (title, company, industry, location, employment_type, description, requirements, deadline, status, employer_id, created_by) VALUES
('Graduate Software Developer', 'Deloitte South Africa', 'Consulting & Professional Services', 'Johannesburg', 'Full-time', 
'We are seeking talented graduate software developers to join our technology consulting team. You will work on exciting projects for leading South African companies.', 
'BSc Computer Science or IT, Strong programming skills in Java/Python/C#, Problem-solving abilities, Team player', 
'2024-06-30', 'open', @deloitte_id, 1),

('Junior Process Engineer', 'Sasol Limited', 'Energy & Utilities', 'Secunda', 'Full-time',
'Join our engineering team as a junior process engineer. You will be involved in optimizing chemical processes and ensuring plant efficiency.',
'BEng Chemical Engineering, Understanding of process control, Safety-conscious, Willing to work shifts',
'2024-07-15', 'open', @sasol_id, 1),

('Graduate Trainee - Banking', 'Nedbank Group', 'Banking & Financial Services', 'Port Elizabeth', 'Full-time',
'Our graduate trainee program offers comprehensive training in retail banking operations, customer service, and financial products.',
'BCom Finance/Accounting/Economics, Strong numerical skills, Customer service orientation, Excellent communication',
'2024-08-31', 'open', @nedbank_id, 1);

-- Insert portal settings
INSERT INTO portal_settings (setting_key, setting_value) VALUES
('portal_name', 'GATE Portal'),
('institution_name', 'Walter Sisulu University'),
('contact_email', 'alumni@wsu.ac.za'),
('contact_phone', '+27 47 502 2111'),
('welcome_message', 'Welcome to the WSU Graduate & Alumni Tracking & Engagement Portal'),
('footer_text', '© 2024 Walter Sisulu University. All rights reserved.'),
('maintenance_mode', '0'),
('allow_registration', '1');
