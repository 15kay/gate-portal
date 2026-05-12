# Ubuntu Server Deployment Guide

## Server Details
- **IP Address:** 13.60.96.145
- **OS:** Ubuntu
- **Database:** AWS RDS MySQL

---

## Step 1: Push Local Code to GitHub

```bash
cd c:\xampp\htdocs\gate-portal
git add .
git commit -m "Prepare for production deployment"
git push origin main
```

---

## Step 2: Connect to Ubuntu Server

```bash
ssh ubuntu@13.60.96.145
# Or use PuTTY on Windows
```

---

## Step 3: Run Setup Script on Server

```bash
# Download and run the setup script
sudo apt-get update
sudo apt-get install -y git

# Clone repository
cd /var/www/html
sudo git clone https://github.com/YOUR_USERNAME/gate-portal.git
cd gate-portal

# Run setup
sudo bash setup_ubuntu.sh
```

---

## Step 4: Configure RDS Database

### 4.1 Create RDS Instance (if not exists)
1. Go to AWS Console → RDS
2. Create MySQL database
3. Note the endpoint, username, and password

### 4.2 Update .env File

```bash
sudo nano /var/www/html/gate-portal/.env
```

Update with your RDS credentials:
```env
APP_ENV=production

DB_TYPE=mysql
DB_HOST=your-rds-endpoint.us-east-1.rds.amazonaws.com
DB_USER=admin
DB_PASS=your-secure-password
DB_NAME=gate_portal

SMTP_HOST=smtp.office365.com
SMTP_PORT=587
SMTP_USER=your-email@wsu.ac.za
SMTP_PASS=your-email-password
SMTP_FROM_NAME=GATE Portal - WSU
```

### 4.3 Initialize Database

```bash
cd /var/www/html/gate-portal
php setup_database.php
```

---

## Step 5: Configure GitHub Webhook

1. Go to your GitHub repository
2. Navigate to: **Settings** → **Webhooks** → **Add webhook**
3. Configure:
   - **Payload URL:** `http://13.60.96.145/gate-portal/deploy.php`
   - **Content type:** `application/json`
   - **Secret:** Generate a strong secret key
   - **Events:** Select "Just the push event"
   - **Active:** ✓ Check

4. Update the secret in deploy.php:
```bash
sudo nano /var/www/html/gate-portal/deploy.php
# Change WEBHOOK_SECRET to your generated secret
```

---

## Step 6: Test Deployment

### Local Machine:
```bash
cd c:\xampp\htdocs\gate-portal
echo "# Test deployment" >> README.md
git add README.md
git commit -m "Test webhook deployment"
git push origin main
```

### Check Server:
```bash
ssh ubuntu@13.60.96.145
tail -f /var/www/html/gate-portal/deploy.log
```

You should see the deployment log with SUCCESS status.

---

## Step 7: Access the Portal

Open browser: `http://13.60.96.145/gate-portal/`

Default login:
- **Email:** admin@gateportal.ac
- **Password:** Admin@1234

---

## Troubleshooting

### Check Apache Logs
```bash
sudo tail -f /var/log/apache2/gate-portal-error.log
```

### Check Deployment Log
```bash
tail -f /var/www/html/gate-portal/deploy.log
```

### Fix Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/gate-portal
sudo chmod -R 755 /var/www/html/gate-portal
sudo chmod -R 777 /var/www/html/gate-portal/uploads
```

### Test RDS Connection
```bash
cd /var/www/html/gate-portal
php -r "require 'config/db.php'; echo 'Connected successfully';"
```

### Restart Apache
```bash
sudo systemctl restart apache2
```

---

## Security Recommendations

1. **Enable HTTPS:**
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d your-domain.wsu.ac.za
```

2. **Configure Firewall:**
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable
```

3. **Secure RDS:**
   - Set security group to only allow connections from 13.60.96.145
   - Use strong passwords
   - Enable encryption at rest

4. **Change Default Passwords:**
   - Login to portal and change all admin passwords immediately

---

## Continuous Deployment Workflow

After initial setup, any push to GitHub main branch will automatically deploy:

```bash
# Local development
git add .
git commit -m "Your changes"
git push origin main

# Server automatically pulls and updates via webhook
```

Monitor deployments at: `http://13.60.96.145/gate-portal/deploy.log`
