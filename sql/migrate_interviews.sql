ALTER TABLE candidate_submissions
    ADD COLUMN IF NOT EXISTS interview_scheduled_at DATETIME DEFAULT NULL AFTER release_notes,
    ADD COLUMN IF NOT EXISTS interview_location      VARCHAR(255) DEFAULT NULL AFTER interview_scheduled_at,
    ADD COLUMN IF NOT EXISTS interview_notes         TEXT DEFAULT NULL AFTER interview_location,
    ADD COLUMN IF NOT EXISTS interview_type          ENUM('In-person','Video call','Phone call') DEFAULT NULL AFTER interview_notes;
