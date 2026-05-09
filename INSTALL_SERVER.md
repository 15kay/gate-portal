# WSU Server Database Installation Guide

## Quick Installation Steps

### Option 1: Using phpMyAdmin (Recommended)

1. **Access phpMyAdmin on the WSU server:**
   ```
   https://clestudtrack02.wsu.ac.za/phpmyadmin
   ```

2. **Login with your credentials:**
   - Username: `smmakola`
   - Password: `Kgau123@M`

3. **Import the SQL file:**
   - Click on the **Import** tab
   - Click **Choose File** and select: `sql/INSTALL_ON_SERVER.sql`
   - Click **Go** at the bottom
   - Wait for "Import has been successfully finished"

4. **Verify installation:**
   - You should see the `gate_portal` database in the left sidebar
   - Click on it to see all tables

---

### Option 2: Using MySQL Command Line

1. **SSH into the server:**
   ```bash
   ssh smmakola@clestudtrack02.wsu.ac.za
   ```

2. **Navigate to the project:**
   ```bash
   cd /var/www/html/gate-portal
   ```

3. **Run the installation:**
   ```bash
   mysql -u smmakola -p gate_portal < sql/INSTALL_ON_SERVER.sql
   ```
   Enter password when prompted: `Kgau123@M`

4. **Verify:**
   ```bash
   mysql -u smmakola -p -e "USE gate_portal; SHOW TABLES;"
   ```

---

## What Gets Installed

### Database: `gate_portal`

### Tables Created (17 total):
- `users` - All user accounts
- `alumni_profiles` - Alumni profile data
- `student_registry` - Pre-loaded student records
- `employment_records` - Employment history
- `alumni_cv` - CV files and data
- `alumni_skills` - Skills with proficiency
- `employers` - Employer companies
- `opportunities` - Job opportunities
- `candidate_submissions` - Matching and pipeline
- `events` - Events
- `event_rsvps` - Event RSVPs
- `messages` - Messages
- `message_reads` - Read receipts
- `portal_settings` - System configuration
- `audit_logs` - Activity logs

### Default Admin Accounts:
| Email | Password | Role |
|-------|----------|------|
| admin@gateportal.ac | Admin@1234 | Super Admin |
| alumni.admin@gateportal.ac | Admin@1234 | Alumni Admin |
| reports@gateportal.ac | Admin@1234 | Reports Admin |

⚠️ **Change all passwords immediately after first login!**

### Sample Data:
- 3 admin accounts
- 3 sample student registry records
- Default portal settings

---

## After Installation

1. **Test the portal:**
   ```
   https://clestudtrack02.wsu.ac.za/gate-portal/
   ```

2. **Login as Super Admin:**
   - Email: `admin@gateportal.ac`
   - Password: `Admin@1234`

3. **Change admin passwords:**
   - Go to Admin → Change Password

4. **Configure portal settings:**
   - Go to Admin → Portal Settings
   - Update contact information
   - Configure SMTP email settings

5. **Load student registry:**
   - Go to Admin → Student Registry
   - Import your student data

---

## Troubleshooting

### "Database already exists" error:
The script uses `CREATE DATABASE IF NOT EXISTS` and `INSERT IGNORE`, so it's safe to re-run.

### Permission denied:
```bash
chmod 755 /var/www/html/gate-portal
chmod 777 /var/www/html/gate-portal/uploads/photos
chmod 777 /var/www/html/gate-portal/uploads/cvs
```

### Can't login:
- Verify database connection in `.env` file
- Check that `DB_HOST=localhost` (not the external hostname)
- Ensure Apache and MySQL are running

---

## Need Help?

Check the main README.md for full documentation.
