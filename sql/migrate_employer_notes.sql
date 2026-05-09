-- Add private employer notes to candidate submissions
-- Run once: ALTER TABLE candidate_submissions ADD COLUMN employer_notes TEXT NULL DEFAULT NULL AFTER interview_notes;

ALTER TABLE candidate_submissions
ADD COLUMN employer_notes TEXT NULL DEFAULT NULL AFTER interview_notes;
