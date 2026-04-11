ALTER TABLE employment_records
    ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL AFTER location;
