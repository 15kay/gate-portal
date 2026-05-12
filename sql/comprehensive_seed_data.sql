-- Comprehensive seed data for GATE Portal with full alumni profiles
-- Run after INSTALL_ON_SERVER.sql

USE gate_portal;

-- Clear existing seed data (optional - comment out if you want to keep existing data)
DELETE FROM alumni_cv WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@alumni.wsu.ac.za');
DELETE FROM employment_records WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@alumni.wsu.ac.za');
DELETE FROM alumni_profiles WHERE user_id IN (SELECT id FROM users WHERE email LIKE '%@alumni.wsu.ac.za');
DELETE FROM users WHERE email LIKE '%@alumni.wsu.ac.za';

-- Insert sample alumni users (password: Alumni@123)
INSERT INTO users (email, password, role, full_name, created_at) VALUES
('thabo.mkhize@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Thabo Mkhize', '2023-01-15 10:30:00'),
('nomsa.dlamini@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Nomsa Dlamini', '2023-02-20 14:15:00'),
('sipho.ndlovu@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Sipho Ndlovu', '2023-03-10 09:45:00'),
('zanele.khumalo@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Zanele Khumalo', '2023-04-05 11:20:00'),
('mandla.nkosi@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Mandla Nkosi', '2023-05-12 16:00:00'),
('lindiwe.zulu@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Lindiwe Zulu', '2023-06-18 13:30:00'),
('bongani.sithole@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Bongani Sithole', '2023-07-22 10:10:00'),
('precious.mahlangu@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Precious Mahlangu', '2023-08-14 15:45:00'),
('themba.mokoena@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Themba Mokoena', '2023-09-08 12:00:00'),
('nandi.ngcobo@alumni.wsu.ac.za', '$2y$10$YPz8qE5Z9K3xJ2mN4vL6wOXrH8tF3sG1pQ7kR9mW5nV2cX4bY6aZ8', 'alumni', 'Nandi Ngcobo', '2023-10-25 14:30:00');

-- Get user IDs
SET @thabo_id = (SELECT id FROM users WHERE email = 'thabo.mkhize@alumni.wsu.ac.za');
SET @nomsa_id = (SELECT id FROM users WHERE email = 'nomsa.dlamini@alumni.wsu.ac.za');
SET @sipho_id = (SELECT id FROM users WHERE email = 'sipho.ndlovu@alumni.wsu.ac.za');
SET @zanele_id = (SELECT id FROM users WHERE email = 'zanele.khumalo@alumni.wsu.ac.za');
SET @mandla_id = (SELECT id FROM users WHERE email = 'mandla.nkosi@alumni.wsu.ac.za');
SET @lindiwe_id = (SELECT id FROM users WHERE email = 'lindiwe.zulu@alumni.wsu.ac.za');
SET @bongani_id = (SELECT id FROM users WHERE email = 'bongani.sithole@alumni.wsu.ac.za');
SET @precious_id = (SELECT id FROM users WHERE email = 'precious.mahlangu@alumni.wsu.ac.za');
SET @themba_id = (SELECT id FROM users WHERE email = 'themba.mokoena@alumni.wsu.ac.za');
SET @nandi_id = (SELECT id FROM users WHERE email = 'nandi.ngcobo@alumni.wsu.ac.za');

-- Insert comprehensive alumni profiles with faculty
INSERT INTO alumni_profiles (user_id, student_id, id_number, degree, faculty, department, graduation_year, phone, gender, location, bio, linkedin_url, profile_photo) VALUES
(@thabo_id, '201812345', '9501155678089', 'BSc Computer Science', 'Faculty of Engineering, Built Environment and Information Technology', 'Department of Business & Application Development', 2022, '+27 82 345 6789', 'Male', 'East London, Eastern Cape', 'Passionate software developer with expertise in full-stack web development. Experienced in building scalable applications using modern technologies. Committed to continuous learning and contributing to innovative tech solutions.', 'https://linkedin.com/in/thabo-mkhize', NULL),

(@nomsa_id, '201823456', '9603225789012', 'BCom Accounting', 'Faculty of Economics and Financial Sciences', 'Department of Accounting Sciences', 2021, '+27 83 456 7890', 'Female', 'Port Elizabeth, Eastern Cape', 'Chartered Accountant with strong analytical skills and attention to detail. Experienced in financial reporting, auditing, and tax compliance. Dedicated to maintaining the highest standards of financial integrity and transparency.', 'https://linkedin.com/in/nomsa-dlamini', NULL),

(@sipho_id, '201934567', '9705115890123', 'BEng Electrical Engineering', 'Faculty of Engineering, Built Environment and Information Technology', 'Department of Electrical Engineering', 2023, '+27 84 567 8901', 'Male', 'Mthatha, Eastern Cape', 'Electrical engineer specializing in renewable energy systems and power distribution. Passionate about sustainable energy solutions for rural communities. Strong problem-solving skills and hands-on technical expertise.', 'https://linkedin.com/in/sipho-ndlovu', NULL),

(@zanele_id, '201845678', '9802205901234', 'BA Social Work', 'Faculty of Law, Humanities and Social Sciences', 'Social Sciences', 2020, '+27 85 678 9012', 'Female', 'Butterworth, Eastern Cape', 'Community development practitioner with a heart for social justice. Experienced in working with vulnerable populations and implementing community-based programs. Strong advocacy and counseling skills.', 'https://linkedin.com/in/zanele-khumalo', NULL),

(@mandla_id, '201956789', '9904125012345', 'BSc Information Technology', 'Faculty of Engineering, Built Environment and Information Technology', 'Department of Network & Information Technology', 2023, '+27 86 789 0123', 'Male', 'East London, Eastern Cape', 'IT specialist with expertise in network administration, cybersecurity, and cloud computing. Certified in AWS and Cisco technologies. Passionate about securing digital infrastructure and optimizing IT operations.', 'https://linkedin.com/in/mandla-nkosi', NULL),

(@lindiwe_id, '201867890', '9707215123456', 'BCom Business Management', 'Faculty of Management and Public Administration Sciences', 'Department of Management', 2022, '+27 87 890 1234', 'Female', 'Queenstown, Eastern Cape', 'Business analyst and project manager with strong leadership skills. Experienced in strategic planning, process improvement, and stakeholder management. Committed to driving organizational excellence and innovation.', 'https://linkedin.com/in/lindiwe-zulu', NULL),

(@bongani_id, '201978901', '9808125234567', 'BEng Civil Engineering', 'Faculty of Engineering, Built Environment and Information Technology', 'Department of Civil Engineering', 2023, '+27 88 901 2345', 'Male', 'East London, Eastern Cape', 'Civil engineer with expertise in infrastructure development and construction management. Experienced in road design, structural analysis, and project coordination. Passionate about building sustainable infrastructure for South Africa.', 'https://linkedin.com/in/bongani-sithole', NULL),

(@precious_id, '201889012', '9909205345678', 'BA Education', 'Faculty of Education', 'Department of Mathematics, Science and Technology', 2022, '+27 89 012 3456', 'Female', 'Mthatha, Eastern Cape', 'Dedicated mathematics educator committed to inspiring the next generation. Experienced in curriculum development and innovative teaching methods. Passionate about making mathematics accessible and engaging for all learners.', 'https://linkedin.com/in/precious-mahlangu', NULL),

(@themba_id, '202001234', '0001015456789', 'BSc Chemistry', 'Faculty of Natural Sciences', 'Department of Chemical and Physical Sciences', 2024, '+27 81 123 4567', 'Male', 'Port Elizabeth, Eastern Cape', 'Research-oriented chemist with laboratory experience in analytical chemistry and quality control. Strong foundation in chemical analysis and instrumentation. Eager to contribute to pharmaceutical or industrial chemistry sectors.', 'https://linkedin.com/in/themba-mokoena', NULL),

(@nandi_id, '202012345', '0102025567890', 'BCom Economics', 'Faculty of Economics and Financial Sciences', 'Department of Business Management and Economics', 2024, '+27 82 234 5678', 'Female', 'East London, Eastern Cape', 'Economics graduate with strong quantitative and analytical skills. Interested in economic policy, development economics, and financial markets. Passionate about using economic insights to drive sustainable development.', 'https://linkedin.com/in/nandi-ngcobo', NULL);

-- Insert employment records
INSERT INTO employment_records (user_id, employer, job_title, industry, employment_type, start_date, end_date, is_current, location, description) VALUES
-- Thabo (Software Developer)
(@thabo_id, 'Derivco', 'Junior Software Developer', 'Information Technology', 'Full-time', '2022-03-01', NULL, 1, 'East London', 'Developing and maintaining web applications using PHP, JavaScript, and MySQL. Collaborating with cross-functional teams to deliver high-quality software solutions. Participating in code reviews and agile development processes.'),
(@thabo_id, 'WSU IT Department', 'IT Intern', 'Education', 'Internship', '2021-07-01', '2022-02-28', 0, 'Mthatha', 'Provided technical support to staff and students. Assisted in maintaining university IT infrastructure and troubleshooting hardware/software issues.'),

-- Nomsa (Accountant)
(@nomsa_id, 'KPMG South Africa', 'Audit Associate', 'Finance & Accounting', 'Full-time', '2021-02-01', NULL, 1, 'Port Elizabeth', 'Conducting financial audits for corporate clients across various industries. Preparing audit reports and ensuring compliance with IFRS standards. Collaborating with senior auditors on complex audit engagements.'),
(@nomsa_id, 'PwC South Africa', 'Audit Trainee', 'Finance & Accounting', 'Internship', '2020-01-15', '2021-01-31', 0, 'Port Elizabeth', 'Completed articles as part of CA training program. Gained exposure to audit procedures, financial statement preparation, and client communication.'),

-- Sipho (Electrical Engineer)
(@sipho_id, 'Eskom Holdings', 'Graduate Electrical Engineer', 'Energy & Utilities', 'Full-time', '2023-07-01', NULL, 1, 'Port Elizabeth', 'Working on power distribution systems and renewable energy integration projects. Conducting site inspections and technical assessments. Collaborating with senior engineers on grid modernization initiatives.'),

-- Zanele (Social Worker)
(@zanele_id, 'Department of Social Development', 'Social Worker', 'Government & Public Sector', 'Full-time', '2020-08-01', NULL, 1, 'Butterworth', 'Providing counseling and support services to vulnerable families and children. Implementing community development programs and conducting home visits. Collaborating with NGOs and community organizations.'),

-- Mandla (IT Specialist)
(@mandla_id, 'Dimension Data', 'Network Administrator', 'Information Technology', 'Full-time', '2023-09-01', NULL, 1, 'East London', 'Managing enterprise network infrastructure and ensuring optimal performance. Implementing cybersecurity measures and monitoring network security. Providing technical support and troubleshooting network issues.'),

-- Lindiwe (Business Analyst)
(@lindiwe_id, 'Standard Bank', 'Business Analyst', 'Banking & Financial Services', 'Full-time', '2022-06-01', NULL, 1, 'East London', 'Analyzing business processes and identifying improvement opportunities. Gathering requirements and translating them into technical specifications. Collaborating with IT teams to implement business solutions.'),

-- Bongani (Civil Engineer)
(@bongani_id, 'SANRAL', 'Graduate Civil Engineer', 'Construction & Infrastructure', 'Full-time', '2023-08-01', NULL, 1, 'East London', 'Working on road construction and maintenance projects across the Eastern Cape. Conducting site inspections and quality control assessments. Preparing technical reports and project documentation.'),

-- Precious (Teacher)
(@precious_id, 'Mthatha High School', 'Mathematics Teacher', 'Education', 'Full-time', '2022-01-15', NULL, 1, 'Mthatha', 'Teaching Grade 10-12 mathematics and preparing students for matric examinations. Developing lesson plans and assessment materials. Mentoring students and providing extra classes for struggling learners.'),

-- Themba (Unemployed - Recent Graduate)
(@themba_id, NULL, NULL, NULL, 'Unemployed', '2024-05-01', NULL, 1, 'Port Elizabeth', 'Actively seeking opportunities in pharmaceutical or chemical industries. Completed BSc Chemistry with distinction and looking to apply laboratory skills in a professional setting.'),

-- Nandi (Further Studies)
(@nandi_id, 'Walter Sisulu University', 'Honours Student', 'Education', 'Further Studies', '2024-02-01', NULL, 1, 'East London', 'Pursuing BCom Honours in Economics. Conducting research on economic development in rural Eastern Cape. Expected completion: December 2024.');

-- Insert CV data with skills and summaries
INSERT INTO alumni_cv (user_id, cv_file, summary, skills, certifications, languages, keywords, profile_score, updated_at) VALUES
(@thabo_id, 'thabo_mkhize_cv.pdf', 
'Results-driven software developer with 2+ years of experience in full-stack web development. Proficient in PHP, JavaScript, MySQL, and modern web frameworks. Strong problem-solving abilities and passion for creating efficient, scalable applications. Proven track record of delivering high-quality code in agile environments.',
'PHP, JavaScript, MySQL, HTML5, CSS3, React, Node.js, Git, Laravel, RESTful APIs, Agile Development, Problem Solving, Team Collaboration',
'AWS Certified Cloud Practitioner, Oracle Certified Associate Java Programmer',
'English (Fluent), isiZulu (Native), isiXhosa (Conversational)',
'software,developer,php,javascript,mysql,web,development,programming,coding,agile,react,laravel,api,database,frontend,backend',
85, NOW()),

(@nomsa_id, 'nomsa_dlamini_cv.pdf',
'Qualified Chartered Accountant with 3+ years of audit experience across diverse industries. Strong technical knowledge of IFRS and audit procedures. Excellent analytical skills with attention to detail. Committed to maintaining professional ethics and delivering quality audit services.',
'Financial Auditing, IFRS, Tax Compliance, Financial Reporting, Excel Advanced, SAP, Risk Assessment, Internal Controls, Analytical Thinking, Client Communication',
'Chartered Accountant (SA), SAICA Member, Advanced Excel Certification',
'English (Fluent), isiZulu (Native), Afrikaans (Intermediate)',
'accountant,audit,financial,ifrs,tax,compliance,reporting,chartered,ca,kpmg,finance,accounting,analysis,excel',
90, NOW()),

(@sipho_id, 'sipho_ndlovu_cv.pdf',
'Electrical engineer with specialization in renewable energy and power systems. Fresh graduate with strong academic background and practical experience from university projects. Passionate about sustainable energy solutions and rural electrification.',
'Electrical Engineering, Power Systems, Renewable Energy, AutoCAD, MATLAB, Circuit Design, Project Management, Technical Documentation, Problem Solving',
'Professional Engineer in Training (PrEng), Solar PV Installation Certificate',
'English (Fluent), isiZulu (Native), isiXhosa (Fluent)',
'electrical,engineer,power,energy,renewable,solar,eskom,engineering,technical,design,systems,distribution',
75, NOW()),

(@zanele_id, 'zanele_khumalo_cv.pdf',
'Compassionate social worker with 4+ years of experience in community development and family support services. Strong counseling and advocacy skills. Dedicated to empowering vulnerable communities and promoting social justice.',
'Social Work, Counseling, Community Development, Case Management, Report Writing, Crisis Intervention, Advocacy, Stakeholder Engagement, Empathy, Communication',
'Registered Social Worker (SACSSP), Trauma Counseling Certificate',
'English (Fluent), isiXhosa (Native), isiZulu (Conversational)',
'social,worker,community,development,counseling,support,advocacy,welfare,families,children,ngo',
80, NOW()),

(@mandla_id, 'mandla_nkosi_cv.pdf',
'IT specialist with expertise in network administration and cybersecurity. Certified in AWS and Cisco technologies. Strong technical skills and commitment to securing digital infrastructure. Recent graduate eager to contribute to enterprise IT environments.',
'Network Administration, Cybersecurity, AWS, Cisco, Linux, Windows Server, Firewall Configuration, Cloud Computing, Troubleshooting, Technical Support',
'AWS Certified Solutions Architect, CCNA, CompTIA Security+',
'English (Fluent), isiZulu (Native), isiXhosa (Intermediate)',
'network,administrator,it,cybersecurity,aws,cisco,cloud,security,infrastructure,technical,support,linux',
82, NOW()),

(@lindiwe_id, 'lindiwe_zulu_cv.pdf',
'Business analyst with 2+ years of experience in banking sector. Strong analytical and problem-solving skills. Experienced in requirements gathering, process improvement, and stakeholder management. Committed to driving business value through data-driven insights.',
'Business Analysis, Requirements Gathering, Process Improvement, Stakeholder Management, SQL, Data Analysis, Project Management, Agile, BPMN, Documentation',
'Certified Business Analysis Professional (CBAP), Agile Scrum Master',
'English (Fluent), isiZulu (Native), Afrikaans (Intermediate)',
'business,analyst,analysis,requirements,process,improvement,banking,finance,data,project,management,agile',
88, NOW()),

(@bongani_id, 'bongani_sithole_cv.pdf',
'Civil engineer with focus on infrastructure development and construction management. Recent graduate with strong academic foundation and practical experience from internships. Passionate about building sustainable infrastructure for South Africa.',
'Civil Engineering, Structural Design, AutoCAD, Project Coordination, Site Inspection, Quality Control, Construction Management, Technical Reporting, Problem Solving',
'Professional Engineer in Training (PrEng), Construction Health & Safety Certificate',
'English (Fluent), isiZulu (Native), isiXhosa (Fluent)',
'civil,engineer,construction,infrastructure,roads,structural,design,project,sanral,engineering,technical',
78, NOW()),

(@precious_id, 'precious_mahlangu_cv.pdf',
'Dedicated mathematics educator with 2+ years of teaching experience. Passionate about making mathematics accessible and engaging for all learners. Strong curriculum development and classroom management skills. Committed to student success and academic excellence.',
'Mathematics Teaching, Curriculum Development, Lesson Planning, Classroom Management, Student Assessment, Educational Technology, Mentoring, Communication, Patience',
'SACE Registered Teacher, Advanced Teaching Methods Certificate',
'English (Fluent), isiZulu (Native), isiXhosa (Conversational)',
'teacher,education,mathematics,teaching,curriculum,classroom,students,learning,school,educator',
72, NOW()),

(@themba_id, 'themba_mokoena_cv.pdf',
'Recent chemistry graduate with strong laboratory skills and research experience. Proficient in analytical techniques and quality control procedures. Eager to apply scientific knowledge in pharmaceutical or industrial chemistry settings.',
'Analytical Chemistry, Laboratory Techniques, Quality Control, Chromatography, Spectroscopy, Chemical Analysis, Research, Data Analysis, Safety Protocols',
'Laboratory Safety Certificate, Good Laboratory Practice (GLP) Training',
'English (Fluent), isiZulu (Native), isiXhosa (Intermediate)',
'chemistry,chemist,laboratory,analytical,research,quality,control,pharmaceutical,science,analysis',
65, NOW()),

(@nandi_id, 'nandi_ngcobo_cv.pdf',
'Economics graduate with strong quantitative and analytical skills. Currently pursuing Honours degree. Interested in economic policy, development economics, and financial markets. Passionate about using economic insights to drive sustainable development.',
'Economic Analysis, Quantitative Research, Data Analysis, Excel, STATA, Economic Modeling, Report Writing, Critical Thinking, Research',
'Financial Modeling Certificate, Data Analysis with Excel',
'English (Fluent), isiZulu (Native), isiXhosa (Fluent)',
'economics,economist,analysis,research,data,financial,markets,development,policy,quantitative',
70, NOW());

-- Insert student registry entries
INSERT INTO student_registry (student_id, id_number, degree, department, graduation_year, activated) VALUES
('201812345', '9501155678089', 'BSc Computer Science', 'Department of Business & Application Development', 2022, 1),
('201823456', '9603225789012', 'BCom Accounting', 'Department of Accounting Sciences', 2021, 1),
('201934567', '9705115890123', 'BEng Electrical Engineering', 'Department of Electrical Engineering', 2023, 1),
('201845678', '9802205901234', 'BA Social Work', 'Social Sciences', 2020, 1),
('201956789', '9904125012345', 'BSc Information Technology', 'Department of Network & Information Technology', 2023, 1),
('201867890', '9707215123456', 'BCom Business Management', 'Department of Management', 2022, 1),
('201978901', '9808125234567', 'BEng Civil Engineering', 'Department of Civil Engineering', 2023, 1),
('201889012', '9909205345678', 'BA Education', 'Department of Mathematics, Science and Technology', 2022, 1),
('202001234', '0001015456789', 'BSc Chemistry', 'Department of Chemical and Physical Sciences', 2024, 1),
('202012345', '0102025567890', 'BCom Economics', 'Department of Business Management and Economics', 2024, 1);

-- Insert sample events
INSERT INTO events (title, description, event_date, location, created_by) VALUES
('Alumni Networking Evening 2024', 'Join us for an evening of networking with fellow WSU alumni and industry professionals. Connect with graduates across various sectors and explore collaboration opportunities.', '2024-06-15 18:00:00', 'WSU East London Campus - Conference Centre', 1),
('Career Development Workshop', 'Comprehensive workshop covering CV writing, interview skills, LinkedIn optimization, and job search strategies. Led by industry experts and career counselors.', '2024-07-20 10:00:00', 'WSU Mthatha Campus - Auditorium', 1),
('Annual Alumni Gala Dinner', 'Our prestigious annual gala dinner celebrating WSU alumni achievements. Awards ceremony, keynote speeches, and networking opportunities.', '2024-08-30 19:00:00', 'ICC East London', 1),
('Tech Talk: AI and Machine Learning', 'Technical seminar on artificial intelligence and machine learning trends. Featuring alumni working in tech industry sharing insights and experiences.', '2024-09-12 14:00:00', 'WSU Butterworth Campus - IT Lab', 1);

-- Insert sample employers
INSERT INTO employers (company_name, contact_email, industry, created_at) VALUES
('Deloitte South Africa', 'recruitment@deloitte.co.za', 'Consulting & Professional Services', NOW()),
('Sasol Limited', 'careers@sasol.com', 'Energy & Utilities', NOW()),
('Nedbank Group', 'talent@nedbank.co.za', 'Banking & Financial Services', NOW()),
('Transnet SOC Ltd', 'recruitment@transnet.net', 'Logistics & Transportation', NOW()),
('MTN South Africa', 'careers@mtn.co.za', 'Telecommunications', NOW());

-- Get employer IDs
SET @deloitte_id = (SELECT id FROM employers WHERE company_name = 'Deloitte South Africa');
SET @sasol_id = (SELECT id FROM employers WHERE company_name = 'Sasol Limited');
SET @nedbank_id = (SELECT id FROM employers WHERE company_name = 'Nedbank Group');
SET @transnet_id = (SELECT id FROM employers WHERE company_name = 'Transnet SOC Ltd');
SET @mtn_id = (SELECT id FROM employers WHERE company_name = 'MTN South Africa');

-- Insert sample opportunities
INSERT INTO opportunities (title, company, industry, location, employment_type, description, requirements, deadline, status, employer_id, created_by) VALUES
('Graduate Software Developer', 'Deloitte South Africa', 'Consulting & Professional Services', 'Johannesburg', 'Full-time', 
'We are seeking talented graduate software developers to join our technology consulting team. You will work on exciting projects for leading South African companies, developing innovative solutions using cutting-edge technologies.', 
'BSc Computer Science or IT, Strong programming skills in Java/Python/C#, Understanding of software development lifecycle, Problem-solving abilities, Team player, Excellent communication skills', 
'2024-12-31', 'open', @deloitte_id, 1),

('Junior Process Engineer', 'Sasol Limited', 'Energy & Utilities', 'Secunda', 'Full-time',
'Join our engineering team as a junior process engineer. You will be involved in optimizing chemical processes, ensuring plant efficiency, and contributing to our sustainability initiatives.',
'BEng Chemical Engineering, Understanding of process control and optimization, Safety-conscious mindset, Willing to work shifts, Strong analytical skills',
'2024-12-15', 'open', @sasol_id, 1),

('Graduate Trainee - Banking', 'Nedbank Group', 'Banking & Financial Services', 'Port Elizabeth', 'Full-time',
'Our graduate trainee program offers comprehensive training in retail banking operations, customer service, and financial products. Fast-track your career in banking with structured development and mentorship.',
'BCom Finance/Accounting/Economics, Strong numerical and analytical skills, Customer service orientation, Excellent communication, Willingness to learn',
'2024-11-30', 'open', @nedbank_id, 1),

('Network Engineer', 'MTN South Africa', 'Telecommunications', 'East London', 'Full-time',
'Exciting opportunity for a network engineer to join our infrastructure team. Work on maintaining and expanding our telecommunications network across the Eastern Cape region.',
'BSc IT or Computer Science, CCNA certification preferred, Knowledge of network protocols and security, Experience with Cisco equipment, Problem-solving skills',
'2024-12-20', 'open', @mtn_id, 1),

('Civil Engineering Intern', 'Transnet SOC Ltd', 'Logistics & Transportation', 'Port Elizabeth', 'Internship',
'12-month internship program for civil engineering graduates. Gain hands-on experience in infrastructure projects, port development, and construction management.',
'BEng Civil Engineering, Strong academic record, AutoCAD proficiency, Willingness to learn, Valid driver\'s license',
'2024-11-15', 'open', @transnet_id, 1);

-- Update portal settings
INSERT INTO portal_settings (setting_key, setting_value) VALUES
('portal_name', 'GATE Portal'),
('institution_name', 'Walter Sisulu University'),
('contact_email', 'alumni@wsu.ac.za'),
('contact_phone', '+27 47 502 2111'),
('welcome_message', 'Welcome to the WSU Graduate & Alumni Tracking & Engagement Portal. Connect with opportunities, stay engaged with your alma mater, and advance your career.'),
('footer_text', '© 2024 Walter Sisulu University. All rights reserved.'),
('maintenance_mode', '0'),
('allow_registration', '1')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Success message
SELECT 'Comprehensive seed data inserted successfully!' AS Status,
       (SELECT COUNT(*) FROM users WHERE role = 'alumni') AS Alumni_Users,
       (SELECT COUNT(*) FROM alumni_profiles) AS Alumni_Profiles,
       (SELECT COUNT(*) FROM employment_records) AS Employment_Records,
       (SELECT COUNT(*) FROM alumni_cv) AS CV_Records,
       (SELECT COUNT(*) FROM opportunities) AS Opportunities;
