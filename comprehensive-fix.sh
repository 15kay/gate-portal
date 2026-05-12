#!/bin/bash
# Comprehensive fix for all /gate-portal/ paths and error checking

echo "=========================================="
echo "GATE Portal - Comprehensive Fix Script"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

# Step 1: Remove all /gate-portal/ paths
echo "Step 1: Removing ALL /gate-portal/ references..."
sudo find . -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" \) -exec sed -i 's|/gate-portal/|/|g' {} +
sudo find . -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" \) -exec sed -i "s|'/gate-portal/|'/|g" {} +
sudo find . -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" \) -exec sed -i 's|"/gate-portal/|"/|g' {} +
echo "✓ Paths fixed"
echo ""

# Step 2: Check for remaining occurrences
echo "Step 2: Checking for remaining /gate-portal/ references..."
REMAINING=$(sudo grep -r "/gate-portal/" . --include="*.php" --include="*.css" --include="*.js" --include="*.html" 2>/dev/null | wc -l)
echo "Remaining occurrences: $REMAINING"
if [ $REMAINING -gt 0 ]; then
    echo "Files still containing /gate-portal/:"
    sudo grep -r "/gate-portal/" . --include="*.php" --include="*.css" --include="*.js" --include="*.html" 2>/dev/null | cut -d: -f1 | sort -u
fi
echo ""

# Step 3: Check PHP syntax errors
echo "Step 3: Checking for PHP syntax errors..."
ERROR_COUNT=0
for file in $(find . -name "*.php" -type f); do
    php -l "$file" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        echo "✗ Syntax error in: $file"
        php -l "$file"
        ERROR_COUNT=$((ERROR_COUNT + 1))
    fi
done
if [ $ERROR_COUNT -eq 0 ]; then
    echo "✓ No PHP syntax errors found"
else
    echo "✗ Found $ERROR_COUNT files with syntax errors"
fi
echo ""

# Step 4: Check file permissions
echo "Step 4: Setting proper file permissions..."
sudo chown -R www-data:www-data /var/www/html/gate-portal
sudo chmod -R 755 /var/www/html/gate-portal
sudo chmod -R 777 /var/www/html/gate-portal/uploads
echo "✓ Permissions set"
echo ""

# Step 5: Check Apache error log for recent errors
echo "Step 5: Checking recent Apache errors..."
echo "Last 10 PHP errors:"
sudo tail -20 /var/log/apache2/error.log | grep -i "php" | tail -10
echo ""

# Step 6: Test database connection
echo "Step 6: Testing database connection..."
php -r "
require_once 'config/db.php';
try {
    \$count = \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo \"✓ Database connected - Found \$count users\n\";
} catch (Exception \$e) {
    echo \"✗ Database error: \" . \$e->getMessage() . \"\n\";
}
"
echo ""

# Step 7: Restart Apache
echo "Step 7: Restarting Apache..."
sudo systemctl restart apache2
if [ $? -eq 0 ]; then
    echo "✓ Apache restarted successfully"
else
    echo "✗ Apache restart failed"
fi
echo ""

echo "=========================================="
echo "Fix Complete!"
echo "=========================================="
echo ""
echo "Test URLs:"
echo "- Login: http://13.60.96.145/"
echo "- Admin: http://13.60.96.145/admin/dashboard.php"
echo "- Alumni: http://13.60.96.145/alumni/dashboard.php"
echo ""
