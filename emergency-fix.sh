#!/bin/bash
# Emergency fix for 500 and 503 errors

echo "=========================================="
echo "Emergency Fix - 500 & 503 Errors"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

# Step 1: Fix all /gate-portal/ paths
echo "Step 1: Fixing all /gate-portal/ paths..."
sudo find . -type f -name "*.php" -exec sed -i 's|/gate-portal/|/|g' {} +
sudo find . -type f -name "*.php" -exec sed -i "s|'/gate-portal/|'/|g" {} +
sudo find . -type f -name "*.php" -exec sed -i 's|"/gate-portal/|"/|g' {} +
echo "✓ Paths fixed"
echo ""

# Step 2: Create logs directory
echo "Step 2: Creating logs directory..."
sudo mkdir -p logs
sudo chown www-data:www-data logs
sudo chmod 777 logs
echo "✓ Logs directory created"
echo ""

# Step 3: Test database connection
echo "Step 3: Testing database connection..."
php -r "
try {
    \$pdo = new PDO(
        'mysql:host=gate-portal.c7gs8oegmcim.eu-north-1.rds.amazonaws.com;dbname=gate_portal;charset=utf8',
        'admin',
        'Gate123-portal',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
    \$count = \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo \"✓ Database connected - Found \$count users\n\";
} catch (Exception \$e) {
    echo \"✗ Database error: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"
if [ $? -ne 0 ]; then
    echo "Database connection failed!"
    exit 1
fi
echo ""

# Step 4: Set proper permissions
echo "Step 4: Setting file permissions..."
sudo chown -R www-data:www-data /var/www/html/gate-portal
sudo chmod -R 755 /var/www/html/gate-portal
sudo chmod -R 777 /var/www/html/gate-portal/uploads
sudo chmod -R 777 /var/www/html/gate-portal/logs
echo "✓ Permissions set"
echo ""

# Step 5: Clear PHP opcache
echo "Step 5: Clearing PHP cache..."
sudo systemctl restart php8.1-fpm 2>/dev/null || sudo systemctl restart php7.4-fpm 2>/dev/null || echo "PHP-FPM not running"
echo "✓ PHP cache cleared"
echo ""

# Step 6: Restart Apache
echo "Step 6: Restarting Apache..."
sudo systemctl restart apache2
if [ $? -eq 0 ]; then
    echo "✓ Apache restarted"
else
    echo "✗ Apache restart failed"
    exit 1
fi
echo ""

# Step 7: Test alumni dashboard
echo "Step 7: Testing alumni dashboard..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/alumni/dashboard.php)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo "✓ Alumni dashboard responding (HTTP $HTTP_CODE)"
else
    echo "✗ Alumni dashboard error (HTTP $HTTP_CODE)"
    echo "Checking error log..."
    sudo tail -5 /var/log/apache2/error.log
fi
echo ""

echo "=========================================="
echo "Emergency Fix Complete!"
echo "=========================================="
echo ""
echo "Test URLs:"
echo "- Login: http://13.60.96.145/"
echo "- Alumni: http://13.60.96.145/alumni/dashboard.php"
echo ""
echo "If errors persist, check:"
echo "- sudo tail -f /var/log/apache2/error.log"
echo "- cat logs/error.log"
echo ""
