# GATE Portal — Graduate & Alumni Tracking & Engagement Portal

## Setup Instructions

### 1. Database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Import `sql/gate_portal.sql`

### 2. Configuration
Edit `config/db.php` if your MySQL credentials differ from defaults:
- Host: `localhost`
- User: `root`
- Password: *(empty)*
- Database: `gate_portal`

### 3. Access
Visit: `http://localhost/gate-portal/`

---

## Default Admin Login
| Field    | Value                  |
|----------|------------------------|
| Email    | admin@gateportal.ac    |
| Password | password               |

> ⚠ Change the admin password after first login via phpMyAdmin.

---

## Features

### Admin
- Dashboard with employment stats & charts
- Alumni management (search, filter, view, delete)
- Reports & analytics with CSV export
- Events management
- Broadcast & individual messaging
- Employer management
- Job opportunities & candidate matching
- Candidate selection & submissions with outcome tracking
- Audit logs
- Role & admin management
- Student registry
- Portal settings

### Alumni
- Self-registration & login
- Profile management with photo upload
- Employment history tracking
- CV builder & upload
- Job matching
- Alumni directory
- Event RSVP
- Message inbox

### Employer
- Dashboard
- Job listings management
- Shortlisted candidate viewer

---

## Project Structure
```
gate-portal/
├── config/db.php          # DB connection
├── sql/                   # Database schema & migrations
├── includes/              # Shared header, footer, auth guard, CSRF, mailer, settings
├── auth/                  # Login, register, logout
├── admin/                 # Admin pages
├── alumni/                # Alumni pages
├── employer/              # Employer pages
├── assets/                # CSS & JS
└── uploads/               # Profile photos & CVs
```
