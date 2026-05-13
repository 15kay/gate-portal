-- ============================================================================
-- Fix existing alumni profiles: degree, faculty, graduation_year
-- ============================================================================
-- The registration form incorrectly stored student_registry.department
-- (which contains the faculty name) into alumni_profiles.department,
-- leaving alumni_profiles.faculty empty.
--
-- This script:
--   1. Copies degree, graduation_year from student_registry into alumni_profiles
--      where they are currently missing.
--   2. Moves the misplaced faculty value from alumni_profiles.department
--      into alumni_profiles.faculty, and clears department so the alumni
--      can select the correct department from the dropdown.
-- ============================================================================

USE gate_portal;

-- Step 1: Pull degree + graduation_year from student_registry for alumni
--         whose profiles are missing these values.
UPDATE alumni_profiles ap
JOIN users u ON u.id = ap.user_id
JOIN student_registry sr ON sr.student_number = ap.student_id
SET
    ap.degree           = COALESCE(NULLIF(ap.degree, ''),           sr.degree),
    ap.graduation_year  = COALESCE(ap.graduation_year,              sr.graduation_year)
WHERE u.role = 'alumni'
  AND (ap.degree IS NULL OR ap.degree = '' OR ap.graduation_year IS NULL);

-- Step 2: Fix the faculty/department mix-up.
--         student_registry.department actually stores the faculty name.
--         Profiles created via registration have faculty='' and department=<faculty name>.
--         Move that value to faculty and blank out department.
UPDATE alumni_profiles ap
JOIN users u ON u.id = ap.user_id
SET
    ap.faculty     = ap.department,
    ap.department  = ''
WHERE u.role = 'alumni'
  AND (ap.faculty IS NULL OR ap.faculty = '')
  AND ap.department IS NOT NULL
  AND ap.department != '';

-- Verification: show affected alumni profiles
SELECT
    u.full_name,
    ap.student_id,
    ap.degree,
    ap.faculty,
    ap.department,
    ap.graduation_year
FROM alumni_profiles ap
JOIN users u ON u.id = ap.user_id
WHERE u.role = 'alumni'
ORDER BY u.full_name;
