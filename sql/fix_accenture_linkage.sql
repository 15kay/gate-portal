USE gate_portal;

-- Fix: ensure Accenture opportunity has correct employer_id
UPDATE opportunities
SET employer_id = (SELECT id FROM employers WHERE company_name = 'Accenture South Africa' LIMIT 1)
WHERE company = 'Accenture South Africa';

-- Fix: reset seeded match scores so re-running matching recalculates them fresh
-- (only reset 'suggested' status — don't touch selected/submitted/accepted)
UPDATE candidate_submissions cs
JOIN opportunities o ON o.id = cs.opportunity_id
SET cs.match_score = 0
WHERE o.company = 'Accenture South Africa'
  AND cs.status = 'suggested';

-- Verify the linkage
SELECT o.id, o.title, o.company, o.employer_id, e.company_name, e.user_id AS emp_user_id
FROM opportunities o
LEFT JOIN employers e ON e.id = o.employer_id
WHERE o.company = 'Accenture South Africa';
