-- ============================================================================
-- GATE Portal — Demo Seed Data
-- Walter Sisulu University
-- ============================================================================
-- Run AFTER gate_portal.sql (or INSTALL_ON_SERVER.sql) has been executed.
-- All alumni passwords: Alumni@1234
-- ============================================================================

USE gate_portal;

-- ============================================================================
-- PORTAL SETTINGS
-- ============================================================================

INSERT INTO portal_settings (setting_key, setting_value) VALUES
('portal_name',        'GATE Portal'),
('institution_name',   'Walter Sisulu University'),
('contact_email',      'alumni@wsu.ac.za'),
('contact_phone',      '+27 (0)47 502 2111'),
('welcome_message',    'Welcome to the WSU Graduate & Alumni Tracking & Engagement Portal. Connect with opportunities, stay engaged with your alma mater, and advance your career.'),
('registration_open',  '1'),
('maintenance_mode',   '0')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ============================================================================
-- ALUMNI USERS  (password hash = Alumni@1234)
-- ============================================================================

INSERT IGNORE INTO users (full_name, email, password, role, created_at) VALUES
('Thabo Mkhize',      'thabo.mkhize@alumni.wsu.ac.za',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2022-04-10 08:30:00'),
('Nomsa Dlamini',     'nomsa.dlamini@alumni.wsu.ac.za',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2021-03-15 09:00:00'),
('Sipho Ndlovu',      'sipho.ndlovu@alumni.wsu.ac.za',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2023-08-20 10:15:00'),
('Zanele Khumalo',    'zanele.khumalo@alumni.wsu.ac.za',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2020-09-05 11:00:00'),
('Mandla Nkosi',      'mandla.nkosi@alumni.wsu.ac.za',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2023-10-01 08:45:00'),
('Lindiwe Zulu',      'lindiwe.zulu@alumni.wsu.ac.za',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2022-07-18 14:00:00'),
('Bongani Sithole',   'bongani.sithole@alumni.wsu.ac.za',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2023-09-12 09:30:00'),
('Precious Mahlangu', 'precious.mahlangu@alumni.wsu.ac.za', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2022-02-28 10:00:00'),
('Themba Mokoena',    'themba.mokoena@alumni.wsu.ac.za',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2024-06-01 08:00:00'),
('Nandi Ngcobo',      'nandi.ngcobo@alumni.wsu.ac.za',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2024-03-10 13:00:00');

-- ============================================================================
-- RESOLVE USER IDs
-- ============================================================================

SET @thabo    = (SELECT id FROM users WHERE email = 'thabo.mkhize@alumni.wsu.ac.za');
SET @nomsa    = (SELECT id FROM users WHERE email = 'nomsa.dlamini@alumni.wsu.ac.za');
SET @sipho    = (SELECT id FROM users WHERE email = 'sipho.ndlovu@alumni.wsu.ac.za');
SET @zanele   = (SELECT id FROM users WHERE email = 'zanele.khumalo@alumni.wsu.ac.za');
SET @mandla   = (SELECT id FROM users WHERE email = 'mandla.nkosi@alumni.wsu.ac.za');
SET @lindiwe  = (SELECT id FROM users WHERE email = 'lindiwe.zulu@alumni.wsu.ac.za');
SET @bongani  = (SELECT id FROM users WHERE email = 'bongani.sithole@alumni.wsu.ac.za');
SET @precious = (SELECT id FROM users WHERE email = 'precious.mahlangu@alumni.wsu.ac.za');
SET @themba   = (SELECT id FROM users WHERE email = 'themba.mokoena@alumni.wsu.ac.za');
SET @nandi    = (SELECT id FROM users WHERE email = 'nandi.ngcobo@alumni.wsu.ac.za');
SET @admin    = (SELECT id FROM users WHERE email = 'admin@gateportal.ac');

-- ============================================================================
-- STUDENT REGISTRY  (pre-loaded academic records)
-- ============================================================================

INSERT IGNORE INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year, is_registered) VALUES
('201812001', '9501155678089', 'Thabo Mkhize',      'BSc Computer Science',       'Faculty of Engineering, Built Environment and Information Technology', 2022, 1),
('201823002', '9603225789012', 'Nomsa Dlamini',     'BCom Accounting',             'Faculty of Economics and Financial Sciences',                          2021, 1),
('201934003', '9705115890123', 'Sipho Ndlovu',      'BEng Electrical Engineering', 'Faculty of Engineering, Built Environment and Information Technology', 2023, 1),
('201845004', '9802205901234', 'Zanele Khumalo',    'BA Social Work',              'Faculty of Law, Humanities and Social Sciences',                       2020, 1),
('201956005', '9904125012345', 'Mandla Nkosi',      'BSc Information Technology',  'Faculty of Engineering, Built Environment and Information Technology', 2023, 1),
('201867006', '9707215123456', 'Lindiwe Zulu',      'BCom Business Management',    'Faculty of Management and Public Administration Sciences',             2022, 1),
('201978007', '9808125234567', 'Bongani Sithole',   'BEng Civil Engineering',      'Faculty of Engineering, Built Environment and Information Technology', 2023, 1),
('201889008', '9909205345678', 'Precious Mahlangu', 'BA Education',                'Faculty of Education',                                                 2022, 1),
('202001009', '0001015456789', 'Themba Mokoena',    'BSc Chemistry',               'Faculty of Natural Sciences',                                          2024, 1),
('202012010', '0102025567890', 'Nandi Ngcobo',      'BCom Economics',              'Faculty of Economics and Financial Sciences',                           2024, 1),
-- Unregistered records available for demo registration
('202100011', '0203035678901', 'Ayanda Dube',       'BSc Nursing',                 'Faculty of Health Sciences',                                           2025, 0),
('202100012', '0304045789012', 'Siyanda Mthembu',   'BEng Mechanical Engineering', 'Faculty of Engineering, Built Environment and Information Technology', 2025, 0);

-- ============================================================================
-- ALUMNI PROFILES
-- ============================================================================

INSERT IGNORE INTO alumni_profiles
    (user_id, student_id, id_number, degree, department, graduation_year, phone, gender, location, bio, linkedin_url)
VALUES
(@thabo,
 '201812001', '9501155678089',
 'BSc Computer Science',
 'Faculty of Engineering, Built Environment and Information Technology',
 2022, '+27 82 345 6789', 'Male', 'East London, Eastern Cape',
 'Full-stack developer with 2+ years building web applications in PHP and JavaScript. Passionate about clean code and scalable architecture.',
 'https://linkedin.com/in/thabo-mkhize'),

(@nomsa,
 '201823002', '9603225789012',
 'BCom Accounting',
 'Faculty of Economics and Financial Sciences',
 2021, '+27 83 456 7890', 'Female', 'Port Elizabeth, Eastern Cape',
 'Chartered Accountant with expertise in financial auditing and IFRS reporting. Detail-oriented and committed to financial integrity.',
 'https://linkedin.com/in/nomsa-dlamini'),

(@sipho,
 '201934003', '9705115890123',
 'BEng Electrical Engineering',
 'Faculty of Engineering, Built Environment and Information Technology',
 2023, '+27 84 567 8901', 'Male', 'Mthatha, Eastern Cape',
 'Electrical engineer focused on renewable energy and rural electrification. Eager to contribute to South Africa''s energy transition.',
 'https://linkedin.com/in/sipho-ndlovu'),

(@zanele,
 '201845004', '9802205901234',
 'BA Social Work',
 'Faculty of Law, Humanities and Social Sciences',
 2020, '+27 85 678 9012', 'Female', 'Butterworth, Eastern Cape',
 'Community development practitioner with 4+ years supporting vulnerable families. Strong counselling and advocacy background.',
 'https://linkedin.com/in/zanele-khumalo'),

(@mandla,
 '201956005', '9904125012345',
 'BSc Information Technology',
 'Faculty of Engineering, Built Environment and Information Technology',
 2023, '+27 86 789 0123', 'Male', 'East London, Eastern Cape',
 'Network and cloud specialist certified in AWS and Cisco. Passionate about cybersecurity and enterprise infrastructure.',
 'https://linkedin.com/in/mandla-nkosi'),

(@lindiwe,
 '201867006', '9707215123456',
 'BCom Business Management',
 'Faculty of Management and Public Administration Sciences',
 2022, '+27 87 890 1234', 'Female', 'East London, Eastern Cape',
 'Business analyst bridging the gap between business needs and technical solutions. Experienced in banking sector process improvement.',
 'https://linkedin.com/in/lindiwe-zulu'),

(@bongani,
 '201978007', '9808125234567',
 'BEng Civil Engineering',
 'Faculty of Engineering, Built Environment and Information Technology',
 2023, '+27 88 901 2345', 'Male', 'East London, Eastern Cape',
 'Civil engineer working on national road infrastructure. Committed to building sustainable and resilient public works.',
 'https://linkedin.com/in/bongani-sithole'),

(@precious,
 '201889008', '9909205345678',
 'BA Education',
 'Faculty of Education',
 2022, '+27 89 012 3456', 'Female', 'Mthatha, Eastern Cape',
 'High school mathematics teacher dedicated to making maths accessible. Developing innovative lesson plans and mentoring struggling learners.',
 'https://linkedin.com/in/precious-mahlangu'),

(@themba,
 '202001009', '0001015456789',
 'BSc Chemistry',
 'Faculty of Natural Sciences',
 2024, '+27 81 123 4567', 'Male', 'Port Elizabeth, Eastern Cape',
 'Recent chemistry graduate with strong laboratory and analytical skills. Actively seeking opportunities in pharmaceutical or industrial chemistry.',
 NULL),

(@nandi,
 '202012010', '0102025567890',
 'BCom Economics',
 'Faculty of Economics and Financial Sciences',
 2024, '+27 82 234 5678', 'Female', 'East London, Eastern Cape',
 'Economics honours student researching rural development in the Eastern Cape. Interested in economic policy and financial markets.',
 'https://linkedin.com/in/nandi-ngcobo');

-- ============================================================================
-- EMPLOYMENT RECORDS
-- ============================================================================

INSERT IGNORE INTO employment_records (user_id, employer, job_title, industry, employment_type, start_date, end_date, is_current, location, description) VALUES
-- Thabo — current role + prior internship
(@thabo, 'Derivco', 'Junior Software Developer', 'Information Technology', 'Full-time',
 '2022-03-01', NULL, 1, 'East London',
 'Building and maintaining web applications using PHP, JavaScript, and MySQL. Participating in agile sprints and code reviews.'),
(@thabo, 'WSU IT Department', 'IT Intern', 'Education', 'Full-time',
 '2021-07-01', '2022-02-28', 0, 'Mthatha',
 'Provided technical support to staff and students. Assisted with hardware maintenance and network troubleshooting.'),

-- Nomsa — current role + articles
(@nomsa, 'KPMG South Africa', 'Audit Associate', 'Finance & Accounting', 'Full-time',
 '2021-02-01', NULL, 1, 'Port Elizabeth',
 'Conducting financial audits for corporate clients. Preparing audit reports and ensuring IFRS compliance.'),
(@nomsa, 'PwC South Africa', 'Audit Trainee', 'Finance & Accounting', 'Full-time',
 '2020-01-15', '2021-01-31', 0, 'Port Elizabeth',
 'Completed SAICA articles. Gained exposure to audit procedures, financial statement preparation, and client communication.'),

-- Sipho — current role
(@sipho, 'Eskom Holdings', 'Graduate Electrical Engineer', 'Energy & Utilities', 'Full-time',
 '2023-07-01', NULL, 1, 'Port Elizabeth',
 'Working on power distribution systems and renewable energy integration. Conducting site inspections and technical assessments.'),

-- Zanele — current role
(@zanele, 'Department of Social Development', 'Social Worker', 'Government & Public Sector', 'Full-time',
 '2020-08-01', NULL, 1, 'Butterworth',
 'Providing counselling and support to vulnerable families. Implementing community development programmes and conducting home visits.'),

-- Mandla — current role
(@mandla, 'Dimension Data', 'Network Administrator', 'Information Technology', 'Full-time',
 '2023-09-01', NULL, 1, 'East London',
 'Managing enterprise network infrastructure. Implementing cybersecurity measures and providing Tier 2 technical support.'),

-- Lindiwe — current role
(@lindiwe, 'Standard Bank', 'Business Analyst', 'Banking & Financial Services', 'Full-time',
 '2022-06-01', NULL, 1, 'East London',
 'Analysing business processes and gathering requirements. Translating business needs into technical specifications for IT teams.'),

-- Bongani — current role
(@bongani, 'SANRAL', 'Graduate Civil Engineer', 'Construction & Infrastructure', 'Full-time',
 '2023-08-01', NULL, 1, 'East London',
 'Road construction and maintenance projects across the Eastern Cape. Site inspections, quality control, and technical reporting.'),

-- Precious — current role
(@precious, 'Mthatha High School', 'Mathematics Teacher', 'Education', 'Full-time',
 '2022-01-15', NULL, 1, 'Mthatha',
 'Teaching Grade 10–12 mathematics. Developing lesson plans, running extra classes, and preparing learners for matric exams.'),

-- Themba — unemployed (recent graduate)
(@themba, NULL, NULL, NULL, 'Unemployed',
 '2024-06-01', NULL, 1, 'Port Elizabeth',
 'Actively seeking opportunities in pharmaceutical or industrial chemistry after completing BSc Chemistry with distinction.'),

-- Nandi — further studies
(@nandi, 'Walter Sisulu University', 'Honours Student', 'Education', 'Further Studies',
 '2024-02-01', NULL, 1, 'East London',
 'Pursuing BCom Honours in Economics. Conducting research on rural economic development in the Eastern Cape.');

-- ============================================================================
-- CV / SKILLS DATA
-- ============================================================================

INSERT IGNORE INTO alumni_cv (user_id, summary, skills, keywords) VALUES
(@thabo,
 'Results-driven full-stack developer with 2+ years of experience. Proficient in PHP, JavaScript, and MySQL. Strong problem-solving skills and a passion for clean, maintainable code.',
 'PHP, JavaScript, MySQL, HTML5, CSS3, React, Laravel, Git, RESTful APIs, Agile, Problem Solving',
 'software,developer,php,javascript,mysql,web,react,laravel,api,agile,backend,frontend'),

(@nomsa,
 'Qualified Chartered Accountant with 3+ years of audit experience. Strong knowledge of IFRS and tax compliance. Analytical, detail-oriented, and committed to professional ethics.',
 'Financial Auditing, IFRS, Tax Compliance, Financial Reporting, SAP, Excel, Risk Assessment, Internal Controls',
 'accountant,audit,financial,ifrs,tax,compliance,reporting,chartered,ca,finance,excel,sap'),

(@sipho,
 'Electrical engineer specialising in renewable energy and power distribution. Fresh graduate with strong academic background and hands-on project experience.',
 'Electrical Engineering, Power Systems, Renewable Energy, AutoCAD, MATLAB, Circuit Design, Project Management',
 'electrical,engineer,power,energy,renewable,solar,eskom,distribution,technical,design'),

(@zanele,
 'Compassionate social worker with 4+ years in community development and family support. Strong counselling, advocacy, and stakeholder engagement skills.',
 'Social Work, Counselling, Community Development, Case Management, Report Writing, Crisis Intervention, Advocacy',
 'social,worker,community,development,counselling,support,advocacy,welfare,families,children'),

(@mandla,
 'IT specialist certified in AWS and Cisco. Experienced in network administration, cybersecurity, and cloud infrastructure. Committed to securing enterprise environments.',
 'Network Administration, Cybersecurity, AWS, Cisco, Linux, Windows Server, Firewall, Cloud Computing, Troubleshooting',
 'network,administrator,it,cybersecurity,aws,cisco,cloud,security,infrastructure,linux'),

(@lindiwe,
 'Business analyst with 2+ years in banking. Skilled in requirements gathering, process improvement, and stakeholder management. Data-driven and solutions-focused.',
 'Business Analysis, Requirements Gathering, Process Improvement, SQL, Power BI, Agile, Stakeholder Management, BPMN',
 'business,analyst,analysis,requirements,process,banking,finance,data,agile,sql,powerbi'),

(@bongani,
 'Civil engineer with focus on road infrastructure and construction management. Strong project coordination and quality control skills. Passionate about sustainable public works.',
 'Civil Engineering, Structural Design, AutoCAD, Project Coordination, Site Inspection, Quality Control, SANS Standards',
 'civil,engineer,construction,infrastructure,roads,structural,design,project,sanral,technical'),

(@precious,
 'Dedicated mathematics educator with 2+ years of teaching experience. Passionate about inclusive education and innovative teaching methods that inspire learners.',
 'Mathematics Teaching, Curriculum Development, Lesson Planning, Classroom Management, Student Assessment, Mentoring',
 'teacher,education,mathematics,teaching,curriculum,classroom,students,learning,school,educator'),

(@themba,
 'Recent chemistry graduate with strong laboratory and analytical skills. Proficient in chromatography and spectroscopy. Eager to contribute to pharmaceutical or industrial chemistry.',
 'Analytical Chemistry, Laboratory Techniques, Quality Control, Chromatography, Spectroscopy, Chemical Analysis, Research',
 'chemistry,chemist,laboratory,analytical,research,quality,control,pharmaceutical,science'),

(@nandi,
 'Economics honours student with strong quantitative and research skills. Interested in economic policy, development economics, and financial markets.',
 'Economic Analysis, Quantitative Research, Data Analysis, Excel, STATA, Economic Modelling, Report Writing',
 'economics,economist,analysis,research,data,financial,markets,development,policy,quantitative');

-- ============================================================================
-- EVENTS  (mix of upcoming and past for realistic dashboard)
-- ============================================================================

INSERT IGNORE INTO events (title, description, event_date, location, created_by) VALUES
('Alumni Networking Evening',
 'An evening of networking with fellow WSU alumni and industry professionals across the Eastern Cape. Light refreshments provided.',
 DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'WSU East London Campus — Conference Centre', @admin),

('Career Development Workshop',
 'Hands-on workshop covering CV writing, LinkedIn optimisation, interview techniques, and job search strategies. Led by industry experts.',
 DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'WSU Mthatha Campus — Auditorium', @admin),

('Annual Alumni Gala Dinner',
 'Our prestigious annual gala celebrating WSU alumni achievements. Awards ceremony, keynote address, and networking dinner.',
 DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'ICC East London', @admin),

('Tech Talk: AI & the Future of Work',
 'Panel discussion featuring WSU alumni working in technology sharing insights on artificial intelligence, automation, and career opportunities.',
 DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'WSU Butterworth Campus — IT Lab', @admin),

('Homecoming Day 2024',
 'Annual homecoming celebration for all WSU graduates. Campus tours, faculty reunions, and a braai on the lawns.',
 DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'WSU Main Campus, Mthatha', @admin);

-- ============================================================================
-- MESSAGES  (broadcast from admin to all alumni)
-- ============================================================================

INSERT IGNORE INTO messages (sender_id, recipient_id, subject, body, is_broadcast, sent_at) VALUES
(@admin, NULL,
 'Welcome to the GATE Portal',
 'Dear Alumni,\n\nWelcome to the WSU Graduate & Alumni Tracking & Engagement (GATE) Portal.\n\nPlease take a moment to complete your profile, update your employment status, and explore upcoming events. Your participation helps WSU track graduate outcomes and connect you with new opportunities.\n\nIf you need assistance, contact us at alumni@wsu.ac.za.\n\nWarm regards,\nWSU Alumni Office',
 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),

(@admin, NULL,
 'Upcoming: Alumni Networking Evening',
 'Dear Alumni,\n\nA reminder that our Alumni Networking Evening is coming up soon. This is a great opportunity to reconnect with fellow graduates and meet industry professionals.\n\nPlease RSVP through the Events section of the portal.\n\nWe look forward to seeing you there!\n\nWSU Alumni Office',
 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- ============================================================================
-- OPPORTUNITIES
-- ============================================================================

INSERT IGNORE INTO opportunities (title, company, industry, location, description, requirements, deadline, status, created_by) VALUES
('Graduate Software Developer',
 'Derivco', 'Information Technology', 'East London',
 'Join our growing development team building world-class gaming and fintech software. You will work across the full stack in an agile environment with experienced mentors.',
 'BSc Computer Science or IT; proficiency in at least one of PHP, Java, or Python; strong problem-solving skills; team player.',
 DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'open', @admin),

('Audit Associate — Graduate Programme',
 'KPMG South Africa', 'Finance & Accounting', 'Port Elizabeth',
 'Our graduate audit programme offers structured training, mentorship, and a clear path to CA(SA). Work with top-tier clients across multiple industries.',
 'BCom Accounting or equivalent; strong academic record; analytical mindset; excellent written and verbal communication.',
 DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'open', @admin),

('Graduate Electrical Engineer',
 'Eskom Holdings', 'Energy & Utilities', 'Mthatha',
 'Support our distribution and renewable energy teams across the Eastern Cape. Gain hands-on experience in grid maintenance, fault analysis, and project delivery.',
 'BEng Electrical Engineering; willingness to work in the field; valid driver''s licence; strong technical aptitude.',
 DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'open', @admin),

('Network Engineer — Graduate',
 'Dimension Data', 'Information Technology', 'East London',
 'Maintain and expand enterprise network infrastructure for major clients. Exposure to Cisco, cloud networking, and cybersecurity in a fast-paced environment.',
 'BSc IT or Computer Science; CCNA advantageous; knowledge of TCP/IP and network security fundamentals.',
 DATE_ADD(CURDATE(), INTERVAL 50 DAY), 'open', @admin),

('Civil Engineering Intern (12 months)',
 'SANRAL', 'Construction & Infrastructure', 'Port Elizabeth',
 '12-month structured internship on national road projects. Gain site experience, work alongside registered engineers, and build your professional portfolio.',
 'BEng Civil Engineering; AutoCAD proficiency; valid driver''s licence; strong academic record.',
 DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'open', @admin);

-- ============================================================================
-- VERIFICATION SUMMARY
-- ============================================================================

SELECT 'Demo seed data loaded successfully!' AS Status;
SELECT COUNT(*) AS alumni_users        FROM users WHERE role = 'alumni';
SELECT COUNT(*) AS alumni_profiles     FROM alumni_profiles;
SELECT COUNT(*) AS employment_records  FROM employment_records;
SELECT COUNT(*) AS cv_records          FROM alumni_cv;
SELECT COUNT(*) AS events              FROM events;
SELECT COUNT(*) AS messages            FROM messages;
SELECT COUNT(*) AS opportunities       FROM opportunities;
SELECT COUNT(*) AS student_registry    FROM student_registry;
