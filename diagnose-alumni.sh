#!/bin/bash
# Diagnostic script for alumni dashboard errors

echo "=========================================="
echo "Alumni Dashboard Diagnostics"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

# Check Apache error log for recent errors
echo "Recent Apache Errors (last 30 lines):"
echo "--------------------------------------"
sudo tail -30 /var/log/apache2/error.log
echo ""

# Check application error log
echo "Application Error Log:"
echo "----------------------"
if [ -f "logs/error.log" ]; then
    tail -30 logs/error.log
else
    echo "No application error log found"
fi
echo ""

# Test database connection
echo "Testing Database Connection:"
echo "----------------------------"
php -r "
require_once 'config/db.php';
try {
    \$count = \$pdo->query('SELECT COUNT(*) FROM users WHERE role=\"alumni\"')->fetchColumn();
    echo \"✓ Database connected - Found \$count alumni users\n\";
    
    // Test alumni_profiles table
    \$profiles = \$pdo->query('SELECT COUNT(*) FROM alumni_profiles')->fetchColumn();
    echo \"✓ Found \$profiles alumni profiles\n\";
    
    // Test employment_records table
    \$jobs = \$pdo->query('SELECT COUNT(*) FROM employment_records')->fetchColumn();
    echo \"✓ Found \$jobs employment records\n\";
    
} catch (Exception \$e) {
    echo \"✗ Database error: \" . \$e->getMessage() . \"\n\";
}
"
echo ""

# Test alumni dashboard directly
echo "Testing Alumni Dashboard PHP:"
echo "-----------------------------"
php -r "
\$_SESSION = ['user_id' => 4, 'role' => 'alumni', 'full_name' => 'Test User'];
\$_SERVER['REQUEST_URI'] = '/alumni/dashboard.php';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
try {
    ob_start();
    include 'alumni/dashboard.php';
    \$output = ob_get_clean();
    echo \"✓ Dashboard loaded successfully\n\";
} catch (Exception \$e) {
    echo \"✗ Dashboard error: \" . \$e->getMessage() . \"\n\";
    echo \"   File: \" . \$e->getFile() . \":\" . \$e->getLine() . \"\n\";
}
"
echo ""

# Check for /gate-portal/ references
echo "Checking for /gate-portal/ references:"
echo "--------------------------------------"
REMAINING=$(grep -r "/gate-portal/" alumni/ --include="*.php" 2>/dev/null | wc -l)
echo "Found $REMAINING occurrences in alumni/ directory"
if [ $REMAINING -gt 0 ]; then
    grep -n "/gate-portal/" alumni/dashboard.php 2>/dev/null | head -5
fi
echo ""

# Check file permissions
echo "File Permissions:"
echo "-----------------"
ls -la alumni/dashboard.php
echo ""

echo "=========================================="
echo "Diagnostics Complete"
echo "=========================================="
