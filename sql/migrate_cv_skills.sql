USE gate_portal;
ALTER TABLE alumni_cv
    ADD COLUMN IF NOT EXISTS skills_json    TEXT DEFAULT NULL AFTER skills,
    ADD COLUMN IF NOT EXISTS languages_json TEXT DEFAULT NULL AFTER languages;
