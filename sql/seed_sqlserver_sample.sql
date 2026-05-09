USE gate_portal;
GO

-- All seed data uses INSERT IGNORE which doesn't exist in SQL Server
-- We'll use IF NOT EXISTS checks instead

-- ─────────────────────────────────────────────
-- STUDENT REGISTRY
-- ─────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM student_registry WHERE student_number = '201900004')
    INSERT INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year, is_registered) 
    VALUES ('201900004', '9506145009081', 'Sipho Zulu', 'BSc Information Technology', 'Faculty of Science & Technology', 2022, 1);

IF NOT EXISTS (SELECT 1 FROM student_registry WHERE student_number = '201900005')
    INSERT INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year, is_registered) 
    VALUES ('201900005', '9712200009082', 'Ayanda Mthembu', 'BCom Human Resources', 'Faculty of Business', 2023, 1);

IF NOT EXISTS (SELECT 1 FROM student_registry WHERE student_number = '201900006')
    INSERT INTO student_registry (student_number, id_passport, full_name, degree, department, graduation_year, is_registered) 
    VALUES ('201900006', '9804085009084', 'Lungelo Khumalo', 'BEng Civil Engineering', 'Faculty of Engineering', 2021, 1);

UPDATE student_registry SET is_registered = 1 WHERE student_number IN ('201900001','201900002','201900003');
GO

-- ─────────────────────────────────────────────
-- ALUMNI USERS (password: Admin@1234)
-- ─────────────────────────────────────────────
IF NOT EXISTS (SELECT 1 FROM users WHERE email = 'thabo.nkosi@alumni.wsu.ac.za')
    INSERT INTO users (full_name, email, password, role) 
    VALUES ('Thabo Nkosi', 'thabo.nkosi@alumni.wsu.ac.za', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni');

IF NOT EXISTS (SELECT 1 FROM users WHERE email = 'sipho.zulu@alumni.wsu.ac.za')
    INSERT INTO users (full_name, email, password, role) 
    VALUES ('Sipho Zulu', 'sipho.zulu@alumni.wsu.ac.za', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni');

IF NOT EXISTS (SELECT 1 FROM users WHERE email = 'ayanda.mthembu@alumni.wsu.ac.za')
    INSERT INTO users (full_name, email, password, role) 
    VALUES ('Ayanda Mthembu', 'ayanda.mthembu@alumni.wsu.ac.za', '$2y$10$x.ngyDwICT6RFQnDCETHQ.Hehbyvp5iLxcCZaG4MJ0QMuiz9.EmFm', 'alumni');
GO

PRINT 'Seed data loaded successfully!';
GO
