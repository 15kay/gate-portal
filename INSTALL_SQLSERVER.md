# WSU Server Database Installation Guide (SQL Server)

## Prerequisites

- SQL Server Management Studio (SSMS) installed
- Access to WSU SQL Server: `clestudtrack02.wsu.ac.za`
- Credentials: `smmakola` / `Kgau123@M`
- PHP SQL Server drivers installed on web server

---

## Step 1: Install Database Schema

### Using SQL Server Management Studio (SSMS)

1. **Open SSMS**

2. **Connect to Server:**
   - Server name: `clestudtrack02.wsu.ac.za`
   - Authentication: SQL Server Authentication
   - Login: `smmakola`
   - Password: `Kgau123@M`
   - Click **Connect**

3. **Open the Installation Script:**
   - File → Open → File
   - Navigate to: `sql/INSTALL_SQLSERVER.sql`
   - Or copy the entire contents of the file

4. **Execute the Script:**
   - Click **Execute** (or press F5)
   - Wait for "Database installation completed successfully!"
   - Check the Messages tab for confirmation

5. **Verify Installation:**
   - Expand **Databases** in Object Explorer
   - You should see `gate_portal` database
   - Expand it to see 17 tables

---

## Step 2: Configure PHP for SQL Server

### On the WSU Web Server

The web server needs PHP SQL Server drivers installed:

```bash
# Check if drivers are installed
php -m | grep sqlsrv

# If not installed, you need:
# - php_sqlsrv extension
# - php_pdo_sqlsrv extension
```

**For Windows Server with IIS:**
1. Download Microsoft Drivers for PHP for SQL Server
2. Copy `php_sqlsrv.dll` and `php_pdo_sqlsrv.dll` to PHP extensions folder
3. Edit `php.ini`:
   ```ini
   extension=php_sqlsrv.dll
   extension=php_pdo_sqlsrv.dll
   ```
4. Restart IIS

---

## Step 3: Configure Application

### Update .env file on the server:

```env
# Application
APP_ENV=production

# Database - SQL Server
DB_TYPE=sqlsrv
DB_HOST=clestudtrack02.wsu.ac.za
DB_USER=smmakola
DB_PASS=Kgau123@M
DB_NAME=gate_portal

# SMTP Email
SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM_NAME=GATE Portal - WSU
```

**Important:** Use `DB_TYPE=sqlsrv` for SQL Server (not `mysql`)

---

## Step 4: Test the Connection

1. **Visit the portal:**
   ```
   https://clestudtrack02.wsu.ac.za/gate-portal/
   ```

2. **Login as Super Admin:**
   - Email: `admin@gateportal.ac`
   - Password: `Admin@1234`

3. **If you see a 503 error:**
   - Check that SQL Server drivers are installed
   - Verify `.env` has `DB_TYPE=sqlsrv`
   - Check SQL Server allows remote connections
   - Verify firewall allows port 1433

---

## What Gets Installed

### Database: `gate_portal`

### Tables (17 total):
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

---

## Key Differences: SQL Server vs MySQL

The application now supports both database types:

| Feature | MySQL | SQL Server |
|---------|-------|------------|
| .env DB_TYPE | `mysql` | `sqlsrv` |
| Auto Increment | `AUTO_INCREMENT` | `IDENTITY(1,1)` |
| Text Fields | `TEXT` | `NVARCHAR(MAX)` |
| Boolean | `TINYINT(1)` | `BIT` |
| Timestamp | `TIMESTAMP` | `DATETIME2` |
| Enum | `ENUM('a','b')` | `NVARCHAR + CHECK` |

The PHP code automatically detects `DB_TYPE` and uses the correct PDO driver.

---

## Troubleshooting

### "Could not find driver" error:
```bash
# Install SQL Server drivers for PHP
# Windows: Download from Microsoft
# Linux: sudo apt-get install php-sqlsrv php-pdo-sqlsrv
```

### Connection timeout:
- Check SQL Server allows remote connections
- Verify firewall allows port 1433
- Test with: `telnet clestudtrack02.wsu.ac.za 1433`

### Login failed for user:
- Verify credentials in SSMS first
- Check SQL Server authentication mode (must allow SQL Server Authentication, not just Windows)

### Tables not created:
- Check SSMS Messages tab for errors
- Verify user has CREATE DATABASE and CREATE TABLE permissions

---

## After Installation

1. **Change admin passwords** (Admin → Change Password)
2. **Configure portal settings** (Admin → Portal Settings)
3. **Load student registry** (Admin → Student Registry)
4. **Test email** (Admin → Messages → Send test message)

---

## Need Help?

Check the main README.md for full documentation.
