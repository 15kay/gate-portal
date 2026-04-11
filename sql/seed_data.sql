USE gate_portal;

-- ─────────────────────────────────────────────
-- STUDENT REGISTRY
-- ─────────────────────────────────────────────
INSERT IGNORE INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year, is_registered) VALUES
('201900004', '9506145009081', 'Sipho Zulu',         'BSc Information Technology', 'Faculty of Science & Technology', 2022, 1),
('201900005', '9712200009082', 'Ayanda Mthembu',     'BCom Human Resources',       'Faculty of Business',             2023, 1),
('201900006', '9804085009084', 'Lungelo Khumalo',    'BEng Civil Engineering',     'Faculty of Engineering',          2021, 1),
('201900007', '9901155009085', 'Zanele Mokoena',     'BA Social Work',             'Faculty of Humanities',           2020, 1),
('201900008', '0002280009086', 'Thandeka Sithole',   'BSc Nursing',                'Faculty of Health Sciences',      2023, 1),
('201900009', '9610105009088', 'Bongani Ndlovu',     'BCom Marketing',             'Faculty of Business',             2022, 1),
('201900010', '9807225009089', 'Nokwanda Cele',      'BA Law',                     'Faculty of Law',                  2021, 1);

UPDATE student_registry SET is_registered = 1 WHERE student_number IN ('201900001','201900002','201900003');

-- ─────────────────────────────────────────────
-- ALUMNI USERS  (password: Admin@1234)
-- ─────────────────────────────────────────────
INSERT IGNORE INTO users (full_name, email, password, role) VALUES
('Thabo Nkosi',      'thabo.nkosi@alumni.wsu.ac.za',     '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Nomsa Dlamini',    'nomsa.dlamini@alumni.wsu.ac.za',   '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('John Mokoena',     'john.mokoena@alumni.wsu.ac.za',    '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Sipho Zulu',       'sipho.zulu@alumni.wsu.ac.za',      '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Ayanda Mthembu',   'ayanda.mthembu@alumni.wsu.ac.za',  '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Lungelo Khumalo',  'lungelo.khumalo@alumni.wsu.ac.za', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Zanele Mokoena',   'zanele.mokoena@alumni.wsu.ac.za',  '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Thandeka Sithole', 'thandeka.sithole@alumni.wsu.ac.za','$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Bongani Ndlovu',   'bongani.ndlovu@alumni.wsu.ac.za',  '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni'),
('Nokwanda Cele',    'nokwanda.cele@alumni.wsu.ac.za',   '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni');

-- ─────────────────────────────────────────────
-- ALUMNI PROFILES
-- ─────────────────────────────────────────────
INSERT IGNORE INTO alumni_profiles (user_id, student_id, id_number, phone, graduation_year, degree, department, bio, linkedin_url) VALUES
((SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'),
 '201900001','9001015009087','+27 71 234 5678',2023,'BSc Computer Science','Faculty of Science & Technology',
 'Software developer passionate about fintech and open-source.','https://linkedin.com/in/thabo-nkosi'),

((SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za'),
 '201900002','9203025009083','+27 82 345 6789',2022,'BCom Accounting','Faculty of Business',
 'Chartered accountant with a focus on SME advisory.','https://linkedin.com/in/nomsa-dlamini'),

((SELECT id FROM users WHERE email='john.mokoena@alumni.wsu.ac.za'),
 '201900003','A12345678','+27 73 456 7890',2021,'BA Education','Faculty of Education',
 'High school mathematics teacher and curriculum developer.',NULL),

((SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),
 '201900004','9506145009081','+27 84 567 8901',2022,'BSc Information Technology','Faculty of Science & Technology',
 'IT support specialist transitioning into cloud engineering.','https://linkedin.com/in/sipho-zulu'),

((SELECT id FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za'),
 '201900005','9712200009082','+27 65 678 9012',2023,'BCom Human Resources','Faculty of Business',
 'HR generalist with experience in talent acquisition.','https://linkedin.com/in/ayanda-mthembu'),

((SELECT id FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za'),
 '201900006','9804085009084','+27 76 789 0123',2021,'BEng Civil Engineering','Faculty of Engineering',
 'Civil engineer working on infrastructure projects in the Eastern Cape.',NULL),

((SELECT id FROM users WHERE email='zanele.mokoena@alumni.wsu.ac.za'),
 '201900007','9901155009085','+27 83 890 1234',2020,'BA Social Work','Faculty of Humanities',
 'Social worker focused on youth development programmes.','https://linkedin.com/in/zanele-mokoena'),

((SELECT id FROM users WHERE email='thandeka.sithole@alumni.wsu.ac.za'),
 '201900008','0002280009086','+27 72 901 2345',2023,'BSc Nursing','Faculty of Health Sciences',
 'Registered nurse specialising in paediatric care.',NULL),

((SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za'),
 '201900009','9610105009088','+27 61 012 3456',2022,'BCom Marketing','Faculty of Business',
 'Digital marketing specialist with a love for data analytics.','https://linkedin.com/in/bongani-ndlovu'),

((SELECT id FROM users WHERE email='nokwanda.cele@alumni.wsu.ac.za'),
 '201900010','9807225009089','+27 79 123 4567',2021,'BA Law','Faculty of Law',
 'Legal researcher and aspiring advocate.','https://linkedin.com/in/nokwanda-cele');

-- ─────────────────────────────────────────────
-- EMPLOYMENT RECORDS
-- ─────────────────────────────────────────────
INSERT INTO employment_records (user_id, employer, job_title, industry, employment_type, start_date, end_date, is_current, location) VALUES
((SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'),
 'Takealot Group','Junior Software Developer','E-Commerce','Full-time','2023-03-01',NULL,1,'Cape Town'),

((SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za'),
 'KPMG South Africa','Audit Associate','Finance','Full-time','2022-07-01',NULL,1,'Johannesburg'),

((SELECT id FROM users WHERE email='john.mokoena@alumni.wsu.ac.za'),
 'Mthatha High School','Mathematics Teacher','Education','Full-time','2021-01-15',NULL,1,'Mthatha'),

((SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),
 'Vodacom','IT Support Technician','Telecommunications','Full-time','2022-06-01','2024-01-31',0,'Durban'),

((SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),
 'Amazon Web Services','Cloud Support Associate','Technology','Full-time','2024-02-01',NULL,1,'Cape Town'),

((SELECT id FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za'),
 'Shoprite Holdings','HR Coordinator','Retail','Full-time','2023-05-01',NULL,1,'Johannesburg'),

((SELECT id FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za'),
 'SANRAL','Graduate Civil Engineer','Engineering','Full-time','2021-08-01',NULL,1,'East London'),

((SELECT id FROM users WHERE email='zanele.mokoena@alumni.wsu.ac.za'),
 'Department of Social Development','Social Worker','Government','Full-time','2020-04-01',NULL,1,'Mthatha'),

((SELECT id FROM users WHERE email='thandeka.sithole@alumni.wsu.ac.za'),
 'Frere Hospital','Staff Nurse','Healthcare','Full-time','2023-09-01',NULL,1,'East London'),

((SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za'),
 'Ogilvy South Africa','Digital Marketing Executive','Marketing','Full-time','2022-10-01',NULL,1,'Cape Town'),

((SELECT id FROM users WHERE email='nokwanda.cele@alumni.wsu.ac.za'),
 'WSU Law Clinic','Legal Researcher','Legal','Part-time','2021-03-01','2022-12-31',0,'Mthatha'),

((SELECT id FROM users WHERE email='nokwanda.cele@alumni.wsu.ac.za'),
 'Webber Wentzel','Candidate Attorney','Legal','Full-time','2023-02-01',NULL,1,'Johannesburg');

-- ─────────────────────────────────────────────
-- EMPLOYERS
-- ─────────────────────────────────────────────
INSERT INTO employers (company_name, industry, website, contact_name, contact_email, contact_phone, address, created_by) VALUES
('Takealot Group',      'E-Commerce',        'https://www.takealot.com',  'Priya Naidoo',   'priya.naidoo@takealot.com',   '+27 21 111 2222','10 Rua Vasco da Gama, Cape Town',      1),
('KPMG South Africa',   'Finance',           'https://www.kpmg.co.za',    'David Ferreira', 'david.ferreira@kpmg.co.za',   '+27 11 647 7111','85 Empire Road, Johannesburg',         1),
('Vodacom',             'Telecommunications','https://www.vodacom.co.za', 'Lerato Molefe',  'lerato.molefe@vodacom.co.za', '+27 11 653 5000','Vodacom World, Midrand',               1),
('SANRAL',              'Engineering',       'https://www.sanral.co.za',  'Andile Dube',    'andile.dube@sanral.co.za',    '+27 12 844 8000','48 Tambotie Ave, Pretoria',            1),
('Ogilvy South Africa', 'Marketing',         'https://www.ogilvy.com/za', 'Samantha Botha', 'samantha.botha@ogilvy.com',   '+27 21 467 5000','1 Bridgeway, Century City, Cape Town', 1);

-- ─────────────────────────────────────────────
-- OPPORTUNITIES
-- ─────────────────────────────────────────────
INSERT INTO opportunities (title, company, industry, location, type, description, requirements, deadline, status, created_by, employer_id) VALUES
('Junior Software Developer',  'Takealot Group',      'E-Commerce',        'Cape Town',    'Full-time',  'Join our engineering team building scalable e-commerce solutions.','BSc CS or IT, 1+ year experience, PHP/Python/JS skills.',    '2025-08-31','open',   1,1),
('Graduate Accountant',        'KPMG South Africa',   'Finance',           'Johannesburg', 'Full-time',  'Rotational graduate programme across audit, tax and advisory.',   'BCom Accounting, completed SAICA articles preferred.',        '2025-07-15','open',   1,2),
('Network Support Intern',     'Vodacom',             'Telecommunications','Durban',        'Internship', '6-month internship in the network operations centre.',           'BSc IT or Computer Networks, final year or recent graduate.', '2025-06-30','open',   2,3),
('Site Engineer',              'SANRAL',              'Engineering',       'East London',  'Contract',   '12-month contract on the N2 Wild Coast road project.',           'BEng Civil, valid drivers licence, AutoCAD proficiency.',    '2025-09-30','open',   2,4),
('Social Media Strategist',    'Ogilvy South Africa', 'Marketing',         'Cape Town',    'Full-time',  'Drive social strategy for major FMCG and retail clients.',       'BCom Marketing or Communications, 2+ years agency experience.','2025-07-31','open',  1,5),
('HR Graduate Trainee',        'Shoprite Holdings',   'Retail',            'Johannesburg', 'Full-time',  'Two-year structured HR graduate programme.',                     'BCom HR or Industrial Psychology, strong Excel skills.',      '2025-08-15','open',   2,NULL),
('Legal Research Assistant',   'Webber Wentzel',      'Legal',             'Johannesburg', 'Part-time',  'Support senior associates with research and drafting.',          'LLB or final year law student, excellent writing skills.',    '2025-06-15','filled', 1,NULL);

-- ─────────────────────────────────────────────
-- CANDIDATE SUBMISSIONS
-- ─────────────────────────────────────────────
INSERT IGNORE INTO candidate_submissions (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by) VALUES
((SELECT id FROM opportunities WHERE title='Junior Software Developer'),
 (SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'),   92,'submitted','Strong match — CS background and current dev role.',1),

((SELECT id FROM opportunities WHERE title='Junior Software Developer'),
 (SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),    74,'suggested','IT background; lacks direct dev experience.',1),

((SELECT id FROM opportunities WHERE title='Graduate Accountant'),
 (SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za'), 95,'accepted', 'Excellent fit — KPMG already employs her.',1),

((SELECT id FROM opportunities WHERE title='Network Support Intern'),
 (SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),    80,'selected', 'Good networking fundamentals from Vodacom stint.',2),

((SELECT id FROM opportunities WHERE title='Site Engineer'),
 (SELECT id FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za'),88,'submitted','Civil engineering grad, Eastern Cape based.',2),

((SELECT id FROM opportunities WHERE title='Social Media Strategist'),
 (SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za'),85,'suggested','Digital marketing experience at Ogilvy.',1),

((SELECT id FROM opportunities WHERE title='HR Graduate Trainee'),
 (SELECT id FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za'),90,'submitted','HR coordinator with Shoprite — ideal candidate.',2),

((SELECT id FROM opportunities WHERE title='Legal Research Assistant'),
 (SELECT id FROM users WHERE email='nokwanda.cele@alumni.wsu.ac.za'), 97,'accepted', 'Already placed — candidate attorney at Webber Wentzel.',1);

-- ─────────────────────────────────────────────
-- EVENTS
-- ─────────────────────────────────────────────
INSERT INTO events (title, description, event_date, location, created_by) VALUES
('2025 Alumni Homecoming',           'Annual reunion for all WSU graduates. Networking, awards and campus tours.',             '2025-09-20','WSU Main Campus, Mthatha',           1),
('Career Fair — Tech & Engineering', 'Meet top employers in technology and engineering. Bring your CV.',                      '2025-07-12','WSU Great Hall, Mthatha',             2),
('Entrepreneurship Bootcamp',        'Two-day intensive workshop on starting and funding a business in South Africa.',         '2025-08-05','WSU Business School, East London',    1),
('Mentorship Programme Launch',      'Kick-off event for the 2025 alumni mentorship cohort. Mentors and mentees meet.',        '2025-06-28','Online (Zoom)',                        2),
('Health Sciences Networking Night', 'Informal networking for alumni in healthcare, nursing and public health.',               '2025-10-15','Frere Hospital Conference Centre',    1);

-- ─────────────────────────────────────────────
-- EVENT RSVPs
-- ─────────────────────────────────────────────
INSERT IGNORE INTO event_rsvps (event_id, user_id) VALUES
((SELECT id FROM events WHERE title='2025 Alumni Homecoming'),           (SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='2025 Alumni Homecoming'),           (SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='2025 Alumni Homecoming'),           (SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='2025 Alumni Homecoming'),           (SELECT id FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='2025 Alumni Homecoming'),           (SELECT id FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Career Fair — Tech & Engineering'), (SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Career Fair — Tech & Engineering'), (SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Career Fair — Tech & Engineering'), (SELECT id FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Entrepreneurship Bootcamp'),        (SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Entrepreneurship Bootcamp'),        (SELECT id FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Mentorship Programme Launch'),      (SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Mentorship Programme Launch'),      (SELECT id FROM users WHERE email='nokwanda.cele@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Health Sciences Networking Night'), (SELECT id FROM users WHERE email='thandeka.sithole@alumni.wsu.ac.za')),
((SELECT id FROM events WHERE title='Health Sciences Networking Night'), (SELECT id FROM users WHERE email='zanele.mokoena@alumni.wsu.ac.za'));

-- ─────────────────────────────────────────────
-- MESSAGES
-- ─────────────────────────────────────────────
INSERT INTO messages (sender_id, recipient_id, subject, body, is_broadcast) VALUES
(1, NULL, 'Welcome to the GATE Portal!',
 'Dear Alumni,\n\nWelcome to the Graduate & Alumni Tracking & Engagement Portal. Update your profile, browse job opportunities, and RSVP to upcoming events.\n\nRegards,\nAlumni Affairs Office', 1),

(1, (SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'),
 'Opportunity Match: Junior Software Developer',
 'Hi Thabo, we have identified you as a strong match for the Junior Software Developer role at Takealot. Please log in to review and accept.', 0),

(1, (SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za'),
 'Congratulations — KPMG Graduate Accountant',
 'Hi Nomsa, great news! Your application to KPMG has been accepted. Please confirm your start date via the portal.', 0),

(1, (SELECT id FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za'),
 'Site Engineer Contract — SANRAL',
 'Hi Lungelo, you have been shortlisted for the SANRAL Site Engineer contract in East London. Please update your profile with your latest CV.', 0),

((SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'), 1,
 'Re: Opportunity Match: Junior Software Developer',
 'Thank you for the notification. I have reviewed the opportunity and I am very interested. Please proceed with my application.', 0);

-- ─────────────────────────────────────────────
-- AUDIT LOGS
-- ─────────────────────────────────────────────
INSERT INTO audit_logs (user_id, user_email, actor_type, action, target, detail, ip) VALUES
(1,    'admin@gateportal.ac',        'super_admin',  'LOGIN',             'auth',                  'Super admin logged in',                               '127.0.0.1'),
(1,    'admin@gateportal.ac',        'super_admin',  'CREATE_EVENT',      'events',                'Created event: 2025 Alumni Homecoming',                '127.0.0.1'),
(2,    'alumni.admin@gateportal.ac', 'admin',        'CREATE_OPPORTUNITY','opportunities',         'Created opportunity: Junior Software Developer',       '192.168.1.10'),
(2,    'alumni.admin@gateportal.ac', 'admin',        'SUBMIT_CANDIDATE',  'candidate_submissions', 'Submitted Thabo Nkosi for Junior Software Developer',  '192.168.1.10'),
(3,    'reports@gateportal.ac',      'reports_admin','EXPORT_REPORT',     'reports',               'Exported employment stats CSV',                        '192.168.1.15'),
(1,    'admin@gateportal.ac',        'super_admin',  'BROADCAST_MESSAGE', 'messages',              'Sent broadcast: Welcome to the GATE Portal!',          '127.0.0.1'),
(1,    'admin@gateportal.ac',        'super_admin',  'CREATE_EMPLOYER',   'employers',             'Added employer: Takealot Group',                       '127.0.0.1'),
(NULL, 'system',                     'system',       'SCHEDULED_CLEANUP', 'audit_logs',            'Automated session cleanup ran',                        NULL);
