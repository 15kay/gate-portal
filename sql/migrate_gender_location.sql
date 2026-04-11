ALTER TABLE alumni_profiles
    ADD COLUMN gender ENUM('Male','Female','Non-binary','Prefer not to say') DEFAULT NULL AFTER phone,
    ADD COLUMN location VARCHAR(150) DEFAULT NULL AFTER gender;
