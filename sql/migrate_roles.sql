-- Run this in phpMyAdmin if you already imported the original SQL
-- Step 1: Update the role column to include new roles
ALTER TABLE users MODIFY COLUMN role ENUM('super_admin','admin','reports_admin','alumni') DEFAULT 'alumni';

-- Step 2: Upgrade existing admin to super_admin
UPDATE users SET role = 'super_admin' WHERE email = 'admin@gateportal.ac';

-- Step 3: Add sample admin accounts (password: Admin@1234)
INSERT IGNORE INTO users (full_name, email, password, role) VALUES
('Alumni Affairs Officer', 'alumni.admin@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'admin'),
('Reports Analyst', 'reports@gateportal.ac', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'reports_admin');
