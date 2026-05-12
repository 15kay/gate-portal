#!/bin/bash
# Comprehensive deployment script for GATE Portal on Ubuntu server

echo "=========================================="
echo "GATE Portal - Server Deployment Script"
echo "=========================================="
echo ""

# Database connection details
DB_HOST="gate-portal.c7gs8oegmcim.eu-north-1.rds.amazonaws.com"
DB_USER="admin"
DB_PASS="Gate123-portal"
DB_NAME="gate_portal"

# Step 1: Pull latest code
echo "Step 1: Pulling latest code from GitHub..."
cd /var/www/html/gate-portal
sudo -u www-data git stash
sudo -u www-data git pull origin main
echo "✓ Code updated"
echo ""

# Step 2: Remove /gate-portal/ paths
echo "Step 2: Removing /gate-portal/ prefix from all PHP files..."
sudo find . -name "*.php" -type f -exec sed -i 's|/gate-portal/|/|g' {} +
echo "✓ Paths fixed"
echo ""

# Step 3: Add faculty column
echo "Step 3: Adding faculty column to alumni_profiles table..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "ALTER TABLE alumni_profiles ADD COLUMN IF NOT EXISTS faculty VARCHAR(255) DEFAULT NULL AFTER degree;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ Faculty column added"
else
    echo "⚠ Faculty column may already exist (this is OK)"
fi
echo ""

# Step 4: Verify database structure
echo "Step 4: Verifying alumni_profiles table structure..."
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE alumni_profiles;"
echo ""

# Step 5: Set proper permissions
echo "Step 5: Setting file permissions..."
sudo chown -R www-data:www-data /var/www/html/gate-portal
sudo chmod -R 755 /var/www/html/gate-portal
sudo chmod -R 777 /var/www/html/gate-portal/uploads
echo "✓ Permissions set"
echo ""

echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Visit http://13.60.96.145/ to test the site"
echo "2. Login with: admin@gateportal.ac / Admin@1234"
echo "3. Check alumni profiles at: http://13.60.96.145/admin/alumni.php"
echo ""
