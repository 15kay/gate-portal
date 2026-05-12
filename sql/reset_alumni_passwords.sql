-- Reset all alumni passwords to: Alumni@123
USE gate_portal;

-- New password hash for Alumni@123
UPDATE users 
SET password = '$2y$10$NGGeK9eZYzNZFLQETSyri.wZUVRnENw/T500tGjMK7d1NxCFyLqkG'
WHERE email IN (
    'john.doe@alumni.wsu.ac.za',
    'sarah.smith@alumni.wsu.ac.za',
    'michael.jones@alumni.wsu.ac.za',
    'linda.williams@alumni.wsu.ac.za',
    'david.brown@alumni.wsu.ac.za',
    'emma.davis@alumni.wsu.ac.za',
    'james.wilson@alumni.wsu.ac.za',
    'olivia.taylor@alumni.wsu.ac.za'
);

-- Verify the update
SELECT email, role, full_name 
FROM users 
WHERE email LIKE '%@alumni.wsu.ac.za'
ORDER BY email;

SELECT 'All alumni passwords reset to: Alumni@123' AS Result;
