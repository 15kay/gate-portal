# GATE Portal — Graduate & Alumni Tracking & Engagement Portal

A web-based alumni management system built for **Walter Sisulu University (WSU)** that connects graduates with career opportunities, tracks employment outcomes, and facilitates engagement between alumni, the institution, and employers.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Default Accounts](#default-accounts)
- [User Roles](#user-roles)
- [Feature Reference](#feature-reference)
  - [Admin Features](#admin-features)
  - [Alumni Features](#alumni-features)
  - [Employer Features](#employer-features)
- [Candidate Pipeline](#candidate-pipeline)
- [Matching Engine](#matching-engine)
- [Email System](#email-system)
- [Security](#security)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Migrations](#migrations)

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 7.4 or higher |
| MySQL | 5.7 or higher |
| Web Server | Apache (XAMPP recommended) |
| Extensions | PDO, PDO_MySQL, ZipArchive, OpenSSL |

---

## Installation

1. Clone or copy the project into your web server root:
   ```
   C:\xampp\htdocs\gate-portal\
   ```

2. Start **Apache** and **MySQL** via the XAMPP Control Panel.

3. Import the database (see [Database Setup](#database-setup)).

4. Visit: `http://localhost/gate-portal/`

---

## Database Setup

### Fresh Install

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Import `sql/gate_portal.sql`

This creates the `gate_portal` database with all core tables and seeds the default admin accounts and sample student registry records.

### Existing Install — Run Migrations

If you already have the database and are updating, run the migration files in this order via phpMyAdmin:

| File | Purpose |
|------|---------|
| `sql/migrate_roles.sql` | Adds `super_admin` and `reports_admin` roles |
| `sql/migrate_verification.sql` | Adds `id_number` to profiles, creates `portal_settings` and `student_registry` tables |
| `sql/migrate_gender_location.sql` | Adds gender and location fields to alumni profiles |
| `sql/migrate_employment_description.sql` | Adds description field to employment records |
| `sql/migrate_cv_skills.sql` | Adds CV and skills tables |
| `sql/migrate_actors.sql` | Adds actor type tracking to audit logs |
| `sql/migrate_employer_portal.sql` | Adds employer portal login and candidate release tracking |
| `sql/migrate_new_modules.sql` | Adds alumni map and directory features |
| `sql/migrate_interviews.sql` | Adds interview scheduling columns to candidate submissions |

---

## Configuration

Edit `config/db.php` if your MySQL credentials differ from the defaults:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gate_portal');
```

All other settings (portal name, email, registration control, maintenance mode) are managed through the **Admin → Portal Settings** page and stored in the `portal_settings` database table.

---

## Default Accounts

> ⚠ Change all passwords immediately after first login.

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@gateportal.ac` | `Admin@1234` |
| Alumni Admin | `alumni.admin@gateportal.ac` | `Admin@1234` |
| Reports Admin | `reports@gateportal.ac` | `Admin@1234` |

Employer accounts are created by an admin through **Admin → Employers**.

Alumni accounts are self-registered at `auth/register.php` using a student number and SA ID/passport number verified against the student registry.

---

## User Roles

The system has five distinct roles with a strict permission hierarchy:

### Super Admin
Full system control. The only role that can access system settings, audit logs, and user/role management.

**Access:** Dashboard · Alumni Records · Opportunities · Matching · Candidate Selection · Submissions · Reports · Events · Messages · Employers · Student Registry · Manage Admins · Portal Settings · Audit Logs

### Alumni Admin
Handles daily operations. Cannot manage users, change roles, or access system settings.

**Access:** Dashboard · Alumni Records · Opportunities · Matching · Candidate Selection · Submissions · Reports · Events · Messages · Employers

### Reports Admin
Read-only analytics role. Cannot modify any records or run matching.

**Access:** Dashboard · Alumni Records (view) · Opportunities (view) · Submissions (view) · Reports

### Alumni / Graduate
Self-service portal access for registered graduates.

**Access:** Dashboard · Profile · Employment History · CV Builder · Job Matching · Alumni Directory · Events · Messages

### Employer
External portal access for companies with released candidate shortlists.

**Access:** Dashboard · Job Listings · Shortlisted Candidates · Interview Scheduling

---

## Feature Reference

### Admin Features

#### Dashboard
- Total alumni count, employed vs unemployed stats, upcoming events
- Employment breakdown bar chart
- Graduates by year bar chart
- Recent registrations table

#### Alumni Management
- Search and filter alumni by name, email, degree, department, graduation year, employment status
- View full alumni profile including employment history, CV, and skills
- Delete alumni accounts
- Export alumni data to CSV

#### Alumni Map
- Geographic distribution of alumni by location

#### Student Registry
- Pre-load student records from academic data (student number, ID/passport, degree, department, graduation year)
- Track which students have activated their portal accounts
- Bulk import support

#### Opportunities
- Create and manage job opportunities (title, company, industry, location, type, description, requirements, deadline)
- Link opportunities to employer accounts
- Open / closed / filled status management

#### Candidate Matching
- Run the automated matching engine against any open opportunity
- View suggested candidates with match scores and score breakdowns
- Re-run matching at any time to refresh scores

#### Candidate Selection
- Review matched candidates with full profile, CV preview, skills, and match score
- Select or reject candidates with optional notes
- Block selection if candidate has no CV on file
- Filter view by opportunity

#### Submissions
- Submit selected candidates to employers (marks as `submitted` and releases to employer portal)
- Optional message to employer on release
- Automatically emails employer with candidate table and zipped CVs
- Record final outcomes: Accepted / Rejected
- View interview dates scheduled by employers

#### Reports & Analytics
- Employment type breakdown with percentages
- Top industries bar chart
- Employability rate by department
- Scheduled interviews table (candidate, position, company, type, date, location)
- Export full alumni dataset to CSV

#### Events
- Create and manage events (title, description, date, location)
- View RSVP counts per event

#### Messages
- Send broadcast messages to all alumni
- Send individual messages to specific alumni
- View sent message history

#### Employers
- Create employer profiles (company name, contact email, industry)
- Link employer profiles to portal login accounts
- View employer list

#### Manage Admins *(Super Admin only)*
- Create admin accounts with role assignment (Super Admin / Alumni Admin / Reports Admin)
- Reset admin passwords
- Remove admin accounts (cannot remove Super Admin accounts)
- Role access matrix reference table

#### Portal Settings *(Super Admin only)*
- Portal name and institution name
- Contact email and phone
- Welcome message and footer text
- Maintenance mode toggle (alumni redirected, admins unaffected)
- Alumni self-registration open/closed control
- Microsoft 365 SMTP email configuration
- Session timeout and max login attempts
- System overview stats

#### Audit Logs *(Super Admin only)*
- Full log of all system actions with user, role, action, target, detail, IP, and timestamp
- Actor types: `super_admin`, `admin`, `reports_admin`, `alumni`, `system` (Matching Engine)

---

### Alumni Features

#### Dashboard
- Welcome message (if configured)
- Profile completion prompt
- Quick links to key sections

#### Profile Management
- Update personal details: name, phone, location, bio, LinkedIn URL
- Upload profile photo (JPEG/PNG/GIF, stored in `uploads/photos/`)
- View student number and graduation details (pre-filled from registry)

#### Employment History
- Add, edit, and delete employment records
- Fields: employer, job title, industry, employment type, start/end date, location, description
- Mark a record as current
- Employment types: Full-time, Part-time, Self-employed, Freelance, Unemployed, Further Studies

#### CV Builder & Upload
- Upload a CV file (PDF or Word, stored in `uploads/cvs/`)
- Add skills with proficiency levels
- Write a professional summary
- Add parsed keywords for matching

#### Job Matching
- View open opportunities matched to the alumni's profile
- See match score and score breakdown
- Apply / express interest

#### Alumni Directory
- Browse and search other registered alumni
- Filter by degree, department, graduation year

#### Events
- View upcoming events
- RSVP to events

#### Messages
- View inbox (broadcast and individual messages)
- Mark messages as read

---

### Employer Features

#### Dashboard
- Overview of active job listings and candidate counts

#### Job Listings
- Create and manage job postings
- Fields: title, type, location, deadline, description, requirements
- Open / closed / filled status

#### Shortlisted Candidates
- View candidates released by the alumni office per job
- Full candidate card: degree, graduation year, skills, summary, contact details, LinkedIn
- Match score with visual indicator
- CV preview (PDF inline) or download
- Schedule interviews per candidate

#### Interview Scheduling
- Set date/time, type (In-person / Video call / Phone call), location/link, and notes
- Reschedule existing interviews
- On save: candidate receives an email notification with full interview details; admin receives a notification email

---

## Candidate Pipeline

The full lifecycle of a candidate through the system:

```
suggested → selected → submitted → accepted
                ↓                      ↓
            rejected               rejected
```

| Status | Meaning |
|--------|---------|
| `suggested` | Auto-matched by the Matching Engine |
| `selected` | Manually approved by admin (requires CV on file) |
| `submitted` | Released to employer portal |
| `accepted` | Employer confirmed the candidate |
| `rejected` | Rejected at selection or outcome stage |

---

## Matching Engine

The automated matching engine scores each alumni against an opportunity out of **100 points**:

| Factor | Max Points | Logic |
|--------|-----------|-------|
| Keyword overlap | 40 pts | CV keywords, skills, degree, bio, and summary matched against opportunity requirements and description (4 pts per unique hit) |
| Industry match | 15 pts | Alumni employment industry matches opportunity industry |
| Degree relevance | 10 pts | Degree words found in opportunity requirements or description |
| Work experience | 10 pts | Has at least one non-unemployed employment record |
| Skills breadth | 10 pts | 5+ skills = 5 pts, 10+ skills = 10 pts |
| CV uploaded | 5 pts | Has a CV file on record |
| Recent graduate | 5 pts | Graduated within the last 7 years |
| Profile photo | 5 pts | Has a profile photo set |

Only alumni with a degree and phone number on their profile are eligible for matching. Scores are updated on every re-run. Only candidates with a score > 0 are inserted as suggestions.

---

## Email System

Emails are sent via `includes/mailer.php` using a raw SMTP socket with STARTTLS. Configuration is stored in `portal_settings` and managed through Admin → Portal Settings.

### Triggered Emails

| Trigger | Recipients |
|---------|-----------|
| Shortlist released to employer | Employer contact email (HTML table of candidates + zipped CVs attached) |
| Interview scheduled by employer | Candidate (alumni) + Admin (first admin account) |

### SMTP Configuration (Microsoft 365)

| Setting | Value |
|---------|-------|
| Host | `smtp.office365.com` |
| Port | `587` |
| Encryption | STARTTLS |
| Auth | Username + Password or App Password |

If no SMTP credentials are configured, the system falls back to PHP's `mail()` function.

---

## Security

- **Passwords** hashed with `password_hash()` using `PASSWORD_DEFAULT` (bcrypt)
- **CSRF protection** on all POST forms via token stored in session (`includes/csrf.php`)
- **Session fixation prevention** — `session_regenerate_id(true)` on login
- **Rate limiting** — 5 login attempts per IP per 10-minute window
- **Role-based access control** — every page calls `require_role()` or `require_min_role()`
- **Maintenance mode** — alumni blocked with 503, admins unaffected
- **Registration control** — can be closed by admin; closed state returns 503
- **CV selection guard** — candidates without a CV cannot be selected for submission
- **Super Admin protection** — Super Admin accounts cannot be deleted or have passwords reset by other admins
- **Audit logging** — all significant actions logged with user, IP, and timestamp

---

## Project Structure

```
gate-portal/
├── config/
│   └── db.php                  # PDO database connection
├── sql/
│   ├── gate_portal.sql         # Full schema + seed data (fresh install)
│   └── migrate_*.sql           # Incremental migration scripts
├── includes/
│   ├── auth_guard.php          # Role enforcement + permission helpers
│   ├── csrf.php                # CSRF token generation and verification
│   ├── mailer.php              # Raw SMTP mailer with STARTTLS + attachment support
│   ├── settings.php            # portal_settings helper with static cache
│   ├── audit.php               # Audit log writer
│   ├── faculties.php           # Faculty/department list
│   ├── header.php              # Shared navigation header
│   ├── footer.php              # Shared footer
│   ├── maintenance.php         # Maintenance mode page
│   └── 403.php                 # Access denied page
├── auth/
│   ├── login.php               # Login with rate limiting
│   ├── register.php            # Two-step alumni registration (identity verify → account create)
│   └── logout.php              # Session destroy + redirect
├── admin/
│   ├── dashboard.php           # Stats, charts, recent registrations
│   ├── alumni.php              # Alumni list with search/filter
│   ├── view_alumni.php         # Full alumni profile view
│   ├── candidate.php           # Candidate detail view
│   ├── alumni_map.php          # Geographic alumni map
│   ├── student_registry.php    # Student registry management
│   ├── opportunities.php       # Job opportunity management
│   ├── matching.php            # Matching engine runner + suggestions
│   ├── candidate_selection.php # Select/reject matched candidates
│   ├── submissions.php         # Submit to employer + outcome tracking
│   ├── reports.php             # Analytics, charts, interviews, CSV export
│   ├── events.php              # Event management
│   ├── messages.php            # Broadcast and individual messaging
│   ├── employers.php           # Employer profile management
│   ├── manage_admins.php       # Admin account and role management
│   ├── audit_logs.php          # Audit log viewer
│   ├── settings.php            # Portal settings
│   └── change_password.php     # Admin password change
├── alumni/
│   ├── dashboard.php           # Alumni home
│   ├── profile.php             # Profile and photo management
│   ├── employment.php          # Employment history
│   ├── cv_builder.php          # CV upload, skills, summary
│   ├── job_match.php           # Matched opportunities
│   ├── directory.php           # Alumni directory
│   ├── events.php              # Events and RSVP
│   ├── messages.php            # Message inbox
│   ├── verify_email.php        # Email verification
│   └── change_password.php     # Alumni password change
├── employer/
│   ├── dashboard.php           # Employer home
│   ├── jobs.php                # Job listing management
│   ├── shortlist.php           # Shortlisted candidates + interview scheduling
│   └── change_password.php     # Employer password change
├── assets/
│   └── css/
│       └── style.css           # Global stylesheet
├── uploads/
│   ├── photos/                 # Alumni profile photos
│   └── cvs/                    # Alumni CV files
├── index.php                   # Entry point — redirects by role
└── wsu-logo.svg                # WSU branding
```

---

## Database Schema

### Core Tables

| Table | Description |
|-------|-------------|
| `users` | All user accounts (alumni, employer, admin roles) |
| `alumni_profiles` | Extended profile data for alumni users |
| `student_registry` | Pre-loaded academic records used for registration verification |
| `employment_records` | Employment history entries per alumni |
| `alumni_cv` | CV file path, skills, summary, and parsed keywords per alumni |
| `opportunities` | Job opportunities created by admins |
| `candidate_submissions` | Matching results and pipeline status per alumni per opportunity |
| `employers` | Employer company profiles linked to user accounts |
| `events` | Institutional events |
| `event_rsvps` | Alumni RSVPs per event |
| `messages` | Broadcast and individual messages |
| `message_reads` | Read receipts per message per user |
| `portal_settings` | Key-value store for all configurable settings |
| `audit_logs` | System-wide action log |

### candidate_submissions Columns

| Column | Type | Description |
|--------|------|-------------|
| `opportunity_id` | INT | Linked opportunity |
| `alumni_user_id` | INT | Linked alumni |
| `match_score` | TINYINT | 0–100 score from matching engine |
| `status` | ENUM | `suggested`, `selected`, `submitted`, `accepted`, `rejected` |
| `notes` | TEXT | Admin notes on selection/rejection |
| `submitted_by` | INT | Admin who submitted |
| `employer_released` | TINYINT | Whether visible in employer portal |
| `released_at` | TIMESTAMP | When released to employer |
| `release_notes` | TEXT | Message sent to employer on release |
| `interview_scheduled_at` | DATETIME | Interview date/time set by employer |
| `interview_type` | ENUM | `In-person`, `Video call`, `Phone call` |
| `interview_location` | VARCHAR | Office address or meeting link |
| `interview_notes` | TEXT | Instructions for the candidate |

---

## Migrations

All migration files are idempotent (`IF NOT EXISTS`, `INSERT IGNORE`, `ADD COLUMN IF NOT EXISTS`) and safe to re-run. Always run them in the order listed in the [Database Setup](#database-setup) section.

To apply a migration, open it in phpMyAdmin's SQL tab and execute, or run via the MySQL CLI:

```bash
mysql -u root gate_portal < sql/migrate_interviews.sql
```
