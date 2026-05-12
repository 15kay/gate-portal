#!/bin/bash
# Check application logs and test pages

echo "=========================================="
echo "Application Error Log Check"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

# Check if logs directory exists
if [ -d "logs" ]; then
    echo "Logs directory exists"
    ls -la logs/
    echo ""
    
    if [ -f "logs/error.log" ]; then
        echo "Application Error Log (last 50 lines):"
        echo "---------------------------------------"
        tail -50 logs/error.log
    else
        echo "No error.log file found"
    fi
else
    echo "Logs directory does not exist - creating it..."
    sudo mkdir -p logs
    sudo chown www-data:www-data logs
    sudo chmod 777 logs
fi
echo ""

# Test pages with curl
echo "Testing Pages with curl:"
echo "------------------------"

echo "1. Testing index.php..."
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost/index.php

echo "2. Testing auth/login.php..."
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost/auth/login.php

echo "3. Testing alumni/dashboard.php (will redirect without session)..."
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost/alumni/dashboard.php

echo "4. Testing admin/dashboard.php (will redirect without session)..."
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost/admin/dashboard.php

echo ""

# Check .env file
echo "Checking .env file:"
echo "-------------------"
if [ -f ".env" ]; then
    echo "✓ .env file exists"
    echo "Contents (passwords hidden):"
    cat .env | sed 's/=.*/=***/'
else
    echo "✗ .env file missing!"
    echo "Creating .env file..."
    cat > .env << 'EOF'
APP_ENV=production
DB_TYPE=mysql
DB_HOST=gate-portal.c7gs8oegmcim.eu-north-1.rds.amazonaws.com
DB_USER=admin
DB_PASS=Gate123-portal
DB_NAME=gate_portal
EOF
    sudo chown www-data:www-data .env
    sudo chmod 644 .env
    echo "✓ .env file created"
fi
echo ""

# Test PHP directly
echo "Testing PHP Execution:"
echo "----------------------"
php -r "
\$_SERVER['REQUEST_URI'] = '/test';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
try {
    require_once 'config/db.php';
    echo \"✓ config/db.php loaded successfully\n\";
    \$count = \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo \"✓ Database query successful - \$count users\n\";
} catch (Exception \$e) {
    echo \"✗ Error: \" . \$e->getMessage() . \"\n\";
}
"
echo ""

echo "=========================================="
echo "Check Complete"
echo "=========================================="
