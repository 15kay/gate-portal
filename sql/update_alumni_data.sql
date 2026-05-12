-- Update existing alumni profiles with complete data
USE gate_portal;

-- Get user IDs for existing alumni
SET @john_id = (SELECT id FROM users WHERE email = 'john.doe@alumni.wsu.ac.za');
SET @sarah_id = (SELECT id FROM users WHERE email = 'sarah.smith@alumni.wsu.ac.za');
SET @michael_id = (SELECT id FROM users WHERE email = 'michael.jones@alumni.wsu.ac.za');
SET @linda_id = (SELECT id FROM users WHERE email = 'linda.williams@alumni.wsu.ac.za');
SET @david_id = (SELECT id FROM users WHERE email = 'david.brown@alumni.wsu.ac.za');
SET @emma_id = (SELECT id FROM users WHERE email = 'emma.davis@alumni.wsu.ac.za');
SET @james_id = (SELECT id FROM users WHERE email = 'james.wilson@alumni.wsu.ac.za');
SET @olivia_id = (SELECT id FROM users WHERE email = 'olivia.taylor@alumni.wsu.ac.za');

-- Update alumni profiles with faculty and gender
UPDATE alumni_profiles SET 
    faculty = 'Faculty of Engineering, Built Environment and Information Technology',
    department = 'Department of Business & Application Development',
    gender = 'Male'
WHERE user_id = @john_id;

UPDATE alumni_profiles SET 
    faculty = 'Faculty of Economics and Financial Sciences',
    department = 'Department of Accounting Sciences',
    gender = 'Female'
WHERE user_id = @sarah_id;

UPDATE alumni_profiles SET 
    faculty = 'Faculty of Engineering, Built Environment and Information Technology',
    department = 'Department of Electrical Engineering',
    gender = 'Male'
WHERE user_id = @michael_id;

UPDATE alumni_profiles SET 
    faculty = 'Faculty of Law, Humanities and Social Sciences',
    department = 'Social Sciences',
    gender = 'Female'
WHERE user_id = @linda_id;

UPDATE alumni_profiles SET 
    faculty = 'Faculty of Engineering, Built Environment and Information Technology',
    department = 'Department of Network & Information Technology',
    gender = 'Male'
WHERE user_id = @david_id;

UPDATE alumni_profiles SET 
    faculty = 'Faculty of Management and Public Administration Sciences',
    department = 'Department of Management',
    gender = 'Female'
WHERE user_id = @emma_id;

UPDATE alumni_profiles SET 
    faculty = 'Faculty of Engineering, Built Environment and Information Technology',
    department = 'Department of Civil Engineering',
    gender = 'Male'
WHERE user_id = @james_id;

UPDATE alumni_profiles SET 
    faculty = 'Faculty of Education',
    department = 'Department of Mathematics, Science and Technology',
    gender = 'Female'
WHERE user_id = @olivia_id;

-- Insert or update CV data for alumni
INSERT INTO alumni_cv (user_id, summary, skills) VALUES
(@john_id, 'Passionate software developer with 2+ years of experience in web development. Proficient in PHP, JavaScript, and modern web frameworks. Strong problem-solving skills and ability to work in agile teams.', 'PHP, JavaScript, HTML, CSS, MySQL, Git, Laravel, React, Problem Solving, Team Collaboration'),
(@sarah_id, 'Qualified Chartered Accountant with expertise in financial auditing and reporting. Strong analytical skills and attention to detail. Experience with IFRS and tax compliance.', 'Financial Auditing, IFRS, Tax Compliance, Excel, SAP, Financial Reporting, Risk Assessment, Analytical Thinking'),
(@michael_id, 'Electrical engineer specializing in renewable energy systems and power distribution. Passionate about sustainable energy solutions and grid optimization.', 'Electrical Systems, Renewable Energy, AutoCAD, MATLAB, Power Distribution, Project Management, Technical Documentation'),
(@linda_id, 'Dedicated social worker with experience in community development and family support services. Strong interpersonal skills and commitment to social justice.', 'Community Development, Case Management, Counseling, Report Writing, Crisis Intervention, Empathy, Communication'),
(@david_id, 'IT specialist with expertise in network administration and cybersecurity. Experience managing enterprise-level infrastructure and implementing security protocols.', 'Network Administration, Cybersecurity, Windows Server, Linux, Cisco, Firewall Configuration, Troubleshooting, IT Support'),
(@emma_id, 'Business analyst with strong background in process improvement and data analysis. Skilled at translating business requirements into technical solutions.', 'Business Analysis, Data Analysis, SQL, Power BI, Process Mapping, Requirements Gathering, Stakeholder Management, Agile'),
(@james_id, 'Civil engineer with experience in road construction and infrastructure development. Strong project management skills and knowledge of construction standards.', 'Civil Engineering, AutoCAD, Project Management, Construction Management, Site Supervision, Quality Control, SANS Standards'),
(@olivia_id, 'Passionate mathematics educator with experience teaching high school students. Committed to making mathematics accessible and engaging for all learners.', 'Mathematics Teaching, Curriculum Development, Classroom Management, Student Assessment, Educational Technology, Patience, Communication')
ON DUPLICATE KEY UPDATE
    summary = VALUES(summary),
    skills = VALUES(skills);

SELECT 'Alumni profiles and CV data updated successfully!' AS Result;
