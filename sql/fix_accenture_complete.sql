USE gate_portal;

-- ── STEP 1: Show current state ────────────────────────────────────────────────
SELECT '=== EMPLOYER USER ===' AS info;
SELECT id, full_name, email, role FROM users WHERE email = 'recruiter@accenture.com';

SELECT '=== EMPLOYER RECORD ===' AS info;
SELECT id, user_id, company_name, industry FROM employers WHERE company_name LIKE '%Accenture%';

SELECT '=== OPPORTUNITY ===' AS info;
SELECT id, title, company, employer_id, status FROM opportunities WHERE company LIKE '%Accenture%';

SELECT '=== CANDIDATE SUBMISSIONS ===' AS info;
SELECT cs.id, u.full_name, cs.match_score, cs.status, cs.employer_released
FROM candidate_submissions cs
JOIN opportunities o ON o.id = cs.opportunity_id
JOIN users u ON u.id = cs.alumni_user_id
WHERE o.company LIKE '%Accenture%';

-- ── STEP 2: Ensure employer user exists ──────────────────────────────────────
INSERT IGNORE INTO users (full_name, email, password, role) VALUES
('Accenture Recruiter', 'recruiter@accenture.com',
 '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'employer');

-- ── STEP 3: Ensure employer record exists and is linked to the user ───────────
INSERT INTO employers (user_id, company_name, industry, website, contact_name, contact_email, contact_phone, address, created_by)
SELECT
    (SELECT id FROM users WHERE email='recruiter@accenture.com'),
    'Accenture South Africa', 'Technology & Consulting',
    'https://www.accenture.com/za-en', 'Naledi Khumalo',
    'recruiter@accenture.com', '+27 11 208 8000',
    '138 West Street, Sandton, Johannesburg', 1
WHERE NOT EXISTS (SELECT 1 FROM employers WHERE company_name = 'Accenture South Africa');

-- If record exists but user_id is NULL, fix it
UPDATE employers
SET user_id = (SELECT id FROM users WHERE email='recruiter@accenture.com')
WHERE company_name = 'Accenture South Africa' AND (user_id IS NULL OR user_id = 0);

-- ── STEP 4: Ensure opportunity exists and employer_id is correct ──────────────
INSERT INTO opportunities (title, company, industry, location, type, description, requirements, deadline, status, created_by, employer_id)
SELECT
    'Technology Analyst Graduate Programme',
    'Accenture South Africa', 'Technology & Consulting', 'Johannesburg', 'Full-time',
    'Accenture Technology Analyst Graduate Programme — 2-year rotational programme across software engineering, cloud infrastructure, data analytics and digital transformation.',
    'BSc Computer Science, BSc Information Technology, or BCom Information Systems. Python, Java or JavaScript programming skills. SQL and database knowledge. Cloud platforms AWS Azure or GCP. Agile Scrum methodology. South African citizen graduated within last 3 years.',
    '2025-09-30', 'open', 1,
    (SELECT id FROM employers WHERE company_name='Accenture South Africa' LIMIT 1)
WHERE NOT EXISTS (
    SELECT 1 FROM opportunities WHERE company = 'Accenture South Africa'
);

-- Fix employer_id if opportunity exists but employer_id is NULL
UPDATE opportunities
SET employer_id = (SELECT id FROM employers WHERE company_name='Accenture South Africa' LIMIT 1)
WHERE company = 'Accenture South Africa'
  AND (employer_id IS NULL OR employer_id = 0);

-- ── STEP 5: Insert released candidate submissions ─────────────────────────────
SET @opp_id   = (SELECT id FROM opportunities WHERE company='Accenture South Africa' LIMIT 1);
SET @admin_id = (SELECT id FROM users WHERE email='admin@gateportal.ac' LIMIT 1);
SET @rel_note = 'Please review these shortlisted WSU graduates for your Technology Analyst Graduate Programme. We recommend Thabo Nkosi and Sipho Zulu as top candidates.';

-- Thabo Nkosi — strong match
INSERT INTO candidate_submissions
    (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by, employer_released, released_at, released_by, release_notes)
SELECT @opp_id,
    (SELECT id FROM users WHERE email='thabo.nkosi@alumni.wsu.ac.za'),
    88, 'submitted',
    'Excellent fit — BSc CS, Python/JS/AWS skills, current dev role at Takealot.',
    @admin_id, 1, NOW(), @admin_id, @rel_note
WHERE @opp_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    match_score=88, status='submitted', employer_released=1,
    released_at=NOW(), released_by=@admin_id, release_notes=@rel_note;

-- Sipho Zulu — good match
INSERT INTO candidate_submissions
    (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by, employer_released, released_at, released_by, release_notes)
SELECT @opp_id,
    (SELECT id FROM users WHERE email='sipho.zulu@alumni.wsu.ac.za'),
    74, 'submitted',
    'Strong cloud background — AWS certified, Linux proficient.',
    @admin_id, 1, NOW(), @admin_id, @rel_note
WHERE @opp_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    match_score=74, status='submitted', employer_released=1,
    released_at=NOW(), released_by=@admin_id, release_notes=@rel_note;

-- Bongani Ndlovu — partial match
INSERT INTO candidate_submissions
    (opportunity_id, alumni_user_id, match_score, status, notes, submitted_by, employer_released, released_at, released_by, release_notes)
SELECT @opp_id,
    (SELECT id FROM users WHERE email='bongani.ndlovu@alumni.wsu.ac.za'),
    52, 'submitted',
    'Partial match — data analytics skills, Power BI, SQL.',
    @admin_id, 1, NOW(), @admin_id, @rel_note
WHERE @opp_id IS NOT NULL
ON DUPLICATE KEY UPDATE
    match_score=52, status='submitted', employer_released=1,
    released_at=NOW(), released_by=@admin_id, release_notes=@rel_note;

-- ── STEP 6: Verify final state ────────────────────────────────────────────────
SELECT '=== FINAL VERIFICATION ===' AS info;

SELECT
    e.id AS employer_id,
    e.company_name,
    e.user_id,
    u.email AS employer_login,
    o.id AS opp_id,
    o.title,
    o.employer_id AS opp_employer_id,
    COUNT(cs.id) AS released_candidates
FROM employers e
JOIN users u ON u.id = e.user_id
JOIN opportunities o ON o.employer_id = e.id
LEFT JOIN candidate_submissions cs ON cs.opportunity_id = o.id AND cs.employer_released = 1
WHERE e.company_name = 'Accenture South Africa'
GROUP BY e.id, o.id;

SELECT '=== RELEASED CANDIDATES ===' AS info;
SELECT u.full_name, cs.match_score, cs.status, cs.employer_released
FROM candidate_submissions cs
JOIN users u ON u.id = cs.alumni_user_id
WHERE cs.opportunity_id = @opp_id AND cs.employer_released = 1;
