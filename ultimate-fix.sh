#!/bin/bash
# Ultimate fix - Remove ALL /gate-portal/ references permanently

echo "=========================================="
echo "ULTIMATE FIX - Remove ALL /gate-portal/"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

# Step 1: Stash any local changes
echo "Step 1: Stashing local changes..."
sudo -u www-data git stash
echo "✓ Local changes stashed"
echo ""

# Step 2: Pull latest code
echo "Step 2: Pulling latest code..."
sudo -u www-data git pull origin main
echo "✓ Code updated"
echo ""

# Step 3: Remove ALL /gate-portal/ references (multiple passes)
echo "Step 3: Removing ALL /gate-portal/ references..."

# Pass 1: Basic replacement
sudo find . -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" \) \
    -exec sed -i 's|/gate-portal/|/|g' {} \;

# Pass 2: With single quotes
sudo find . -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" \) \
    -exec sed -i "s|'/gate-portal/|'/|g" {} \;

# Pass 3: With double quotes
sudo find . -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" \) \
    -exec sed -i 's|"/gate-portal/|"/|g' {} \;

# Pass 4: URL encoded
sudo find . -type f \( -name "*.php" -o -name "*.css" -o -name "*.js" -o -name "*.html" \) \
    -exec sed -i 's|%2Fgate-portal%2F|%2F|g' {} \;

# Pass 5: In href attributes
sudo find . -type f \( -name "*.php" -o -name "*.html" \) \
    -exec sed -i 's|href="/gate-portal/|href="/|g' {} \;

# Pass 6: In src attributes
sudo find . -type f \( -name "*.php" -o -name "*.html" \) \
    -exec sed -i 's|src="/gate-portal/|src="/|g' {} \;

# Pass 7: In action attributes
sudo find . -type f \( -name "*.php" -o -name "*.html" \) \
    -exec sed -i 's|action="/gate-portal/|action="/|g' {} \;

# Pass 8: In Location headers
sudo find . -type f -name "*.php" \
    -exec sed -i "s|Location: /gate-portal/|Location: /|g" {} \;

echo "✓ All /gate-portal/ references removed"
echo ""

# Step 4: Count remaining occurrences
echo "Step 4: Checking for remaining occurrences..."
REMAINING=$(sudo grep -r "/gate-portal/" . \
    --include="*.php" --include="*.css" --include="*.js" --include="*.html" \
    --exclude-dir=.git --exclude-dir=node_modules --exclude-dir=vendor \
    2>/dev/null | wc -l)

echo "Remaining /gate-portal/ occurrences: $REMAINING"

if [ $REMAINING -gt 0 ]; then
    echo ""
    echo "Files still containing /gate-portal/:"
    sudo grep -r "/gate-portal/" . \
        --include="*.php" --include="*.css" --include="*.js" --include="*.html" \
        --exclude-dir=.git --exclude-dir=node_modules --exclude-dir=vendor \
        2>/dev/null | cut -d: -f1 | sort -u
    echo ""
    echo "Showing first 10 occurrences:"
    sudo grep -rn "/gate-portal/" . \
        --include="*.php" --include="*.css" --include="*.js" --include="*.html" \
        --exclude-dir=.git --exclude-dir=node_modules --exclude-dir=vendor \
        2>/dev/null | head -10
fi
echo ""

# Step 5: Create/verify .env file
echo "Step 5: Checking .env file..."
if [ ! -f ".env" ]; then
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
else
    echo "✓ .env file exists"
fi
echo ""

# Step 6: Create logs directory
echo "Step 6: Setting up logs directory..."
sudo mkdir -p logs
sudo chown www-data:www-data logs
sudo chmod 777 logs
echo "✓ Logs directory ready"
echo ""

# Step 7: Set permissions
echo "Step 7: Setting file permissions..."
sudo chown -R www-data:www-data /var/www/html/gate-portal
sudo chmod -R 755 /var/www/html/gate-portal
sudo chmod -R 777 /var/www/html/gate-portal/uploads
sudo chmod -R 777 /var/www/html/gate-portal/logs
echo "✓ Permissions set"
echo ""

# Step 8: Restart Apache
echo "Step 8: Restarting Apache..."
sudo systemctl restart apache2
if [ $? -eq 0 ]; then
    echo "✓ Apache restarted"
else
    echo "✗ Apache restart failed"
fi
echo ""

# Step 9: Test the site
echo "Step 9: Testing site..."
echo "Login page:"
curl -s -o /dev/null -w "  HTTP %{http_code}\n" http://localhost/auth/login.php

echo "Index page:"
curl -s -o /dev/null -w "  HTTP %{http_code}\n" http://localhost/index.php

echo ""

echo "=========================================="
echo "ULTIMATE FIX COMPLETE!"
echo "=========================================="
echo ""
echo "Site URL: http://13.60.96.145/"
echo ""
echo "If you still see /gate-portal/ in URLs:"
echo "1. Clear your browser cache (Ctrl+Shift+Delete)"
echo "2. Try incognito/private browsing mode"
echo "3. Check: sudo grep -r '/gate-portal/' /var/www/html/gate-portal --include='*.php' | head -5"
echo ""
