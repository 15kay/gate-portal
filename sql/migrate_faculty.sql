-- Add faculty column to alumni_profiles table
ALTER TABLE alumni_profiles 
ADD COLUMN faculty VARCHAR(255) DEFAULT NULL AFTER degree;

-- Update existing records to set faculty based on department (optional mapping)
-- You can customize this mapping based on your department-to-faculty relationships
UPDATE alumni_profiles SET faculty = 'Faculty of Engineering, Built Environment and Information Technology' 
WHERE department IN ('Computer Science', 'Information Technology', 'Engineering', 'Civil Engineering', 'Electrical Engineering', 'Mechanical Engineering', 'Architecture');

UPDATE alumni_profiles SET faculty = 'Faculty of Law, Humanities and Social Sciences' 
WHERE department IN ('Law', 'History', 'English', 'Sociology', 'Psychology', 'Political Science', 'Social Work');

UPDATE alumni_profiles SET faculty = 'Faculty of Management and Public Administration Sciences' 
WHERE department IN ('Business Management', 'Public Administration', 'Human Resource Management', 'Marketing', 'Project Management');

UPDATE alumni_profiles SET faculty = 'Faculty of Economics and Financial Sciences' 
WHERE department IN ('Economics', 'Accounting', 'Finance', 'Financial Management', 'Auditing');

UPDATE alumni_profiles SET faculty = 'Faculty of Medicine and Health Sciences' 
WHERE department IN ('Medicine', 'Nursing', 'Pharmacy', 'Public Health', 'Biomedical Sciences', 'Physiotherapy');

UPDATE alumni_profiles SET faculty = 'Faculty of Natural Sciences' 
WHERE department IN ('Mathematics', 'Physics', 'Chemistry', 'Biology', 'Botany', 'Zoology', 'Environmental Science');

UPDATE alumni_profiles SET faculty = 'Faculty of Education' 
WHERE department IN ('Education', 'Early Childhood Development', 'Foundation Phase', 'Intermediate Phase', 'Senior Phase', 'FET Phase');
