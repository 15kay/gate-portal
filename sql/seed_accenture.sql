USE gate_portal;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. ACCENTURE EMPLOYER PORTAL USER  (password: Admin@1234)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO users (full_name, email, password, role) VALUES
('Accenture Recruiter', 'recruiter@accenture.com',
 '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'employer');

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. ACCENTURE EMPLOYER RECORD  (linked to portal user)
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO employers
    (user_id, company_name, industry, website, contact_name, contact_email, contact_phone, address, created_by)
VALUES (
    (SELECT id FROM users WHERE email='recruiter@accenture.com'),
    'Accenture South Africa',
    'Technology & Consulting',
    'https://www.accenture.com/za-en',
    'Naledi Khumalo',
    'recruiter@accenture.com',
    '+27 11 208 8000',
    '138 West Street, Sandton, Johannesburg',
    1
);

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. ACCENTURE OPPORTUNITY
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO opportunities
    (title, company, industry, location, type, description, requirements, deadline, status, created_by, employer_id)
VALUES (
    'Technology Analyst Graduate Programme',
    'Accenture South Africa',
    'Technology & Consulting',
    'Johannesburg',
    'Full-time',
    'Accenture''s Technology Analyst Graduate Programme is a 2-year rotational programme designed for high-potential graduates. You will work across software engineering, cloud infrastructure, data analytics and digital transformation projects for top-tier clients across banking, retail and government sectors. You will receive structured mentorship, professional certifications support and exposure to cutting-edge technologies.',
    'BSc Computer Science, BSc Information Technology, or BCom Information Systems. Strong programming skills in Python, Java or JavaScript. Knowledge of SQL and databases. Understanding of cloud platforms (AWS, Azure or GCP). Agile and Scrum methodology awareness. Excellent communication and problem-solving skills. South African citizen or permanent resident. Graduated within the last 3 years.',
    '2025-09-30',
    'open',
    1,
    (SELECT id FROM employers WHERE company_name='Accenture South Africa')
);

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. ALUMNI CV DATA  (rich skills + keywords for strong matching)
-- ─────────────────────────────────────────────────────────────────────────────

-- Thabo Nkosi — BSc CS, software developer, strong tech match
INSERT INTO alumni_cv
    (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
VALUES (
    (SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'),
    'Python:Expert,JavaScript:Advanced,PHP:Advanced,SQL:Advanced,React:Intermediate,Node.js:Intermediate,Git:Expert,Docker:Intermediate,AWS:Intermediate,Agile:Advanced,Scrum:Intermediate,Problem Solving:Expert,Communication:Advanced',
    'English:Native,Zulu:Native,Afrikaans:Conversational',
    'AWS Cloud Practitioner, freeCodeCamp JavaScript Algorithms',
    'Results-driven software developer with 2 years of experience building scalable web applications using PHP, Python and JavaScript. Currently at Takealot Group developing e-commerce microservices. Passionate about cloud-native architecture and open-source contribution.',
    'python,javascript,php,sql,react,nodejs,git,docker,aws,agile,scrum,software,developer,engineering,cloud,database,programming',
    88
)
ON DUPLICATE KEY UPDATE
    skills=VALUES(skills), languages=VALUES(languages),
    certifications=VALUES(certifications), summary=VALUES(summary),
    parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Sipho Zulu — BSc IT, cloud support, good tech match
INSERT INTO alumni_cv
    (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
VALUES (
    (SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),
    'Python:Intermediate,SQL:Intermediate,Linux:Advanced,AWS:Advanced,Networking:Advanced,Git:Intermediate,Docker:Beginner,JavaScript:Beginner,Agile:Intermediate,Customer Service:Advanced,Problem Solving:Advanced',
    'English:Native,Zulu:Native',
    'AWS Solutions Architect Associate, CompTIA Network+',
    'IT professional with 3 years of experience in technical support and cloud infrastructure. Transitioned from Vodacom network support to AWS cloud support. Seeking a graduate programme to formalise my cloud engineering career path.',
    'python,sql,linux,aws,networking,git,docker,javascript,agile,cloud,infrastructure,support,technology',
    76
)
ON DUPLICATE KEY UPDATE
    skills=VALUES(skills), languages=VALUES(languages),
    certifications=VALUES(certifications), summary=VALUES(summary),
    parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Bongani Ndlovu — BCom Marketing, digital/data, partial match
INSERT INTO alumni_cv
    (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
VALUES (
    (SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za'),
    'Google Analytics:Expert,SEO:Advanced,Social Media:Expert,Excel:Advanced,Power BI:Intermediate,SQL:Beginner,Python:Beginner,Data Analysis:Intermediate,CRM:Advanced,Communication:Expert,Agile:Beginner',
    'English:Native,Zulu:Fluent,Afrikaans:Basic',
    'Google Analytics Certified, HubSpot Content Marketing',
    'Digital marketing specialist with 2 years at Ogilvy South Africa managing social strategy for FMCG clients. Strong data analytics background using Power BI and Google Analytics. Exploring the intersection of marketing technology and data science.',
    'google analytics,seo,social media,excel,power bi,sql,python,data analysis,crm,marketing,digital,analytics',
    72
)
ON DUPLICATE KEY UPDATE
    skills=VALUES(skills), languages=VALUES(languages),
    certifications=VALUES(certifications), summary=VALUES(summary),
    parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Nomsa Dlamini — BCom Accounting, low tech match (for contrast)
INSERT INTO alumni_cv
    (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
VALUES (
    (SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za'),
    'Accounting:Expert,Auditing:Expert,Excel:Advanced,Sage:Advanced,IFRS:Expert,Taxation:Advanced,Financial Reporting:Expert,Budgeting:Advanced,Communication:Advanced',
    'English:Native,Zulu:Native,Afrikaans:Conversational',
    'SAICA Articles (in progress), CIMA Certificate',
    'Chartered accountant in training with 2 years at KPMG South Africa in the audit division. Specialising in financial reporting, IFRS compliance and SME advisory. Strong Excel and Sage proficiency.',
    'accounting,auditing,excel,sage,ifrs,taxation,financial,reporting,budgeting,finance',
    80
)
ON DUPLICATE KEY UPDATE
    skills=VALUES(skills), languages=VALUES(languages),
    certifications=VALUES(certifications), summary=VALUES(summary),
    parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Ayanda Mthembu — BCom HR, low tech match (for contrast)
INSERT INTO alumni_cv
    (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
VALUES (
    (SELECT id FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za'),
    'Human Resources:Expert,Recruitment:Advanced,Excel:Advanced,Labour Law:Intermediate,Training and Development:Advanced,Communication:Expert,Microsoft Office:Advanced,Agile:Beginner',
    'English:Native,Zulu:Native,Sotho:Conversational',
    'SABPP HR Practitioner, LinkedIn Recruiting Certificate',
    'HR generalist with 2 years at Shoprite Holdings managing end-to-end recruitment and onboarding for retail operations. Passionate about talent development and organisational culture.',
    'human resources,recruitment,excel,labour law,training,communication,microsoft office,hr',
    74
)
ON DUPLICATE KEY UPDATE
    skills=VALUES(skills), languages=VALUES(languages),
    certifications=VALUES(certifications), summary=VALUES(summary),
    parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. CANDIDATE SUBMISSIONS for Accenture opportunity
--    Pre-scored, selected, submitted and RELEASED so employer sees them now
-- ─────────────────────────────────────────────────────────────────────────────
SET @accenture_opp = (SELECT id FROM opportunities WHERE title='Technology Analyst Graduate Programme' LIMIT 1);
SET @admin_id      = (SELECT id FROM users WHERE email='admin@gateportal.ac' LIMIT 1);

-- Thabo Nkosi — 88 pts, strong match, released
INSERT IGNORE INTO candidate_submissions
    (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by,
     employer_released, released_at, released_by, release_notes)
VALUES (
    @accenture_opp,
    (SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'),
    88, 'submitted',
    'Excellent fit — BSc CS, Python/JS/AWS skills, current dev role at Takealot. Highly recommended.',
    @admin_id, 1, NOW(), @admin_id,
    'Please review these shortlisted candidates for your Technology Analyst Graduate Programme. All candidates are WSU graduates with verified academic records. We recommend Thabo Nkosi and Sipho Zulu as your top two candidates.'
);

-- Sipho Zulu — 74 pts, good match, released
INSERT IGNORE INTO candidate_submissions
    (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by,
     employer_released, released_at, released_by, release_notes)
VALUES (
    @accenture_opp,
    (SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),
    74, 'submitted',
    'Strong cloud background — AWS certified, Linux proficient. Good cultural fit for Accenture infrastructure team.',
    @admin_id, 1, NOW(), @admin_id,
    'Please review these shortlisted candidates for your Technology Analyst Graduate Programme. All candidates are WSU graduates with verified academic records. We recommend Thabo Nkosi and Sipho Zulu as your top two candidates.'
);

-- Bongani Ndlovu — 52 pts, partial match, released
INSERT IGNORE INTO candidate_submissions
    (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by,
     employer_released, released_at, released_by, release_notes)
VALUES (
    @accenture_opp,
    (SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za'),
    52, 'submitted',
    'Partial match — strong data analytics skills (Power BI, SQL). Could suit Accenture''s digital marketing analytics team.',
    @admin_id, 1, NOW(), @admin_id,
    'Please review these shortlisted candidates for your Technology Analyst Graduate Programme. All candidates are WSU graduates with verified academic records. We recommend Thabo Nkosi and Sipho Zulu as your top two candidates.'
);

-- Nomsa Dlamini — 28 pts, low match, NOT released (admin rejected)
INSERT IGNORE INTO candidate_submissions
    (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by,
     employer_released, released_at)
VALUES (
    @accenture_opp,
    (SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za'),
    28, 'rejected',
    'Accounting background — not a fit for this tech role.',
    @admin_id, 0, NULL
);

-- ─────────────────────────────────────────────────────────────────────────────
-- 6. UPDATE alumni_profiles with gender + location for the seeded alumni
-- ─────────────────────────────────────────────────────────────────────────────
UPDATE alumni_profiles SET gender='Male',   location='Cape Town, South Africa'      WHERE user_id=(SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Female', location='Johannesburg, South Africa'   WHERE user_id=(SELECT id FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Male',   location='Mthatha, South Africa'        WHERE user_id=(SELECT id FROM users WHERE email='john.mokoena@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Male',   location='Cape Town, South Africa'      WHERE user_id=(SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Female', location='Johannesburg, South Africa'   WHERE user_id=(SELECT id FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Male',   location='East London, South Africa'    WHERE user_id=(SELECT id FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Female', location='Mthatha, South Africa'        WHERE user_id=(SELECT id FROM users WHERE email='zanele.mokoena@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Female', location='East London, South Africa'    WHERE user_id=(SELECT id FROM users WHERE email='thandeka.sithole@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Male',   location='Cape Town, South Africa'      WHERE user_id=(SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za');
UPDATE alumni_profiles SET gender='Female', location='Johannesburg, South Africa'   WHERE user_id=(SELECT id FROM users WHERE email='nokwanda.cele@alumni.wsu.ac.za');
