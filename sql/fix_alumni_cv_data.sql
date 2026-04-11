USE gate_portal;

-- Check what's actually in alumni_cv
SELECT u.full_name, cv.skills, cv.parsed_keywords, cv.profile_score
FROM users u
LEFT JOIN alumni_cv cv ON cv.user_id = u.id
WHERE u.role = 'alumni'
ORDER BY u.full_name;

-- Re-insert/update alumni_cv for all seeded alumni
-- Thabo Nkosi — BSc CS, software developer
INSERT INTO alumni_cv (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
SELECT id,
  'Python:Expert,JavaScript:Advanced,PHP:Advanced,SQL:Advanced,React:Intermediate,Node.js:Intermediate,Git:Expert,Docker:Intermediate,AWS:Intermediate,Agile:Advanced,Scrum:Intermediate,Problem Solving:Expert,Communication:Advanced',
  'English:Native,Zulu:Native,Afrikaans:Conversational',
  'AWS Cloud Practitioner, freeCodeCamp JavaScript Algorithms',
  'Results-driven software developer with 2 years of experience building scalable web applications using PHP, Python and JavaScript. Currently at Takealot Group developing e-commerce microservices. Passionate about cloud-native architecture and open-source contribution.',
  'python,javascript,php,sql,react,nodejs,git,docker,aws,agile,scrum,software,developer,engineering,cloud,database,programming,technology',
  88
FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'
ON DUPLICATE KEY UPDATE
  skills=VALUES(skills), languages=VALUES(languages), certifications=VALUES(certifications),
  summary=VALUES(summary), parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Sipho Zulu — BSc IT, cloud support
INSERT INTO alumni_cv (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
SELECT id,
  'Python:Intermediate,SQL:Intermediate,Linux:Advanced,AWS:Advanced,Networking:Advanced,Git:Intermediate,Docker:Beginner,JavaScript:Beginner,Agile:Intermediate,Customer Service:Advanced,Problem Solving:Advanced',
  'English:Native,Zulu:Native',
  'AWS Solutions Architect Associate, CompTIA Network+',
  'IT professional with 3 years of experience in technical support and cloud infrastructure. Transitioned from Vodacom network support to AWS cloud support. Seeking a graduate programme to formalise my cloud engineering career path.',
  'python,sql,linux,aws,networking,git,docker,javascript,agile,cloud,infrastructure,support,technology,information',
  76
FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'
ON DUPLICATE KEY UPDATE
  skills=VALUES(skills), languages=VALUES(languages), certifications=VALUES(certifications),
  summary=VALUES(summary), parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Bongani Ndlovu — BCom Marketing, data analytics
INSERT INTO alumni_cv (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
SELECT id,
  'Google Analytics:Expert,SEO:Advanced,Social Media:Expert,Excel:Advanced,Power BI:Intermediate,SQL:Beginner,Python:Beginner,Data Analysis:Intermediate,CRM:Advanced,Communication:Expert,Agile:Beginner',
  'English:Native,Zulu:Fluent,Afrikaans:Basic',
  'Google Analytics Certified, HubSpot Content Marketing',
  'Digital marketing specialist with 2 years at Ogilvy South Africa managing social strategy for FMCG clients. Strong data analytics background using Power BI and Google Analytics. Exploring the intersection of marketing technology and data science.',
  'google analytics,seo,social media,excel,power bi,sql,python,data analysis,crm,marketing,digital,analytics,communication',
  72
FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za'
ON DUPLICATE KEY UPDATE
  skills=VALUES(skills), languages=VALUES(languages), certifications=VALUES(certifications),
  summary=VALUES(summary), parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Nomsa Dlamini — BCom Accounting
INSERT INTO alumni_cv (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
SELECT id,
  'Accounting:Expert,Auditing:Expert,Excel:Advanced,Sage:Advanced,IFRS:Expert,Taxation:Advanced,Financial Reporting:Expert,Budgeting:Advanced,Communication:Advanced',
  'English:Native,Zulu:Native,Afrikaans:Conversational',
  'SAICA Articles (in progress), CIMA Certificate',
  'Chartered accountant in training with 2 years at KPMG South Africa in the audit division. Specialising in financial reporting, IFRS compliance and SME advisory.',
  'accounting,auditing,excel,sage,ifrs,taxation,financial,reporting,budgeting,finance,analysis',
  80
FROM users WHERE email='nomsa.dlamini@alumni.wsu.ac.za'
ON DUPLICATE KEY UPDATE
  skills=VALUES(skills), languages=VALUES(languages), certifications=VALUES(certifications),
  summary=VALUES(summary), parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Ayanda Mthembu — BCom HR
INSERT INTO alumni_cv (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
SELECT id,
  'Human Resources:Expert,Recruitment:Advanced,Excel:Advanced,Labour Law:Intermediate,Training and Development:Advanced,Communication:Expert,Microsoft Office:Advanced,Agile:Beginner',
  'English:Native,Zulu:Native,Sotho:Conversational',
  'SABPP HR Practitioner, LinkedIn Recruiting Certificate',
  'HR generalist with 2 years at Shoprite Holdings managing end-to-end recruitment and onboarding for retail operations.',
  'human resources,recruitment,excel,labour law,training,communication,microsoft office,hr,management,people',
  74
FROM users WHERE email='ayanda.mthembu@alumni.wsu.ac.za'
ON DUPLICATE KEY UPDATE
  skills=VALUES(skills), languages=VALUES(languages), certifications=VALUES(certifications),
  summary=VALUES(summary), parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Lungelo Khumalo — BEng Civil Engineering
INSERT INTO alumni_cv (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
SELECT id,
  'AutoCAD:Advanced,Civil Engineering:Expert,Project Management:Advanced,Structural Analysis:Advanced,Microsoft Office:Intermediate,Problem Solving:Expert,Quality Control:Advanced',
  'English:Native,Zulu:Native',
  'ECSA Candidate Engineer',
  'Civil engineer with 3 years at SANRAL working on road infrastructure projects in the Eastern Cape. Proficient in AutoCAD and structural analysis.',
  'autocad,civil,engineering,project,management,structural,analysis,quality,control,infrastructure,construction',
  70
FROM users WHERE email='lungelo.khumalo@alumni.wsu.ac.za'
ON DUPLICATE KEY UPDATE
  skills=VALUES(skills), languages=VALUES(languages), certifications=VALUES(certifications),
  summary=VALUES(summary), parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Nokwanda Cele — BA Law
INSERT INTO alumni_cv (user_id, skills, languages, certifications, summary, parsed_keywords, profile_score)
SELECT id,
  'Legal Research:Expert,Academic Writing:Expert,Communication:Expert,Microsoft Office:Advanced,Research:Expert,Negotiation:Intermediate',
  'English:Native,Zulu:Native,Xhosa:Conversational',
  'LLB (in progress)',
  'Candidate attorney at Webber Wentzel with a background in legal research and academic writing. Passionate about commercial law and dispute resolution.',
  'legal,research,writing,communication,law,negotiation,analysis,academic',
  68
FROM users WHERE email='nokwanda.cele@alumni.wsu.ac.za'
ON DUPLICATE KEY UPDATE
  skills=VALUES(skills), languages=VALUES(languages), certifications=VALUES(certifications),
  summary=VALUES(summary), parsed_keywords=VALUES(parsed_keywords), profile_score=VALUES(profile_score);

-- Reset ALL suggested match scores to 0 so re-running matching recalculates fresh
UPDATE candidate_submissions SET match_score = 0 WHERE status = 'suggested';

-- Verify
SELECT u.full_name, cv.skills IS NOT NULL AS has_skills, cv.parsed_keywords IS NOT NULL AS has_keywords
FROM users u
LEFT JOIN alumni_cv cv ON cv.user_id = u.id
WHERE u.role = 'alumni';
