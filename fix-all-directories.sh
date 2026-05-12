#!/bin/bash
# Comprehensive fix for ALL /gate-portal/ references

echo "=========================================="
echo "COMPREHENSIVE FIX - All Directories"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

echo "Checking current status..."
echo "Alumni directory: $(grep -r '/gate-portal/' alumni/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Includes directory: $(grep -r '/gate-portal/' includes/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Auth directory: $(grep -r '/gate-portal/' auth/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Admin directory: $(grep -r '/gate-portal/' admin/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Employer directory: $(grep -r '/gate-portal/' employer/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo ""

echo "Applying fixes to ALL directories..."

# Fix includes directory (most important - used by all pages)
sudo sed -i 's|/gate-portal/|/|g' includes/*.php

# Fix alumni directory
sudo sed -i 's|/gate-portal/|/|g' alumni/*.php

# Fix auth directory
sudo sed -i 's|/gate-portal/|/|g' auth/*.php

# Fix admin directory
sudo sed -i 's|/gate-portal/|/|g' admin/*.php

# Fix employer directory
sudo sed -i 's|/gate-portal/|/|g' employer/*.php

# Fix root directory files
sudo sed -i 's|/gate-portal/|/|g' *.php 2>/dev/null

# Fix CSS files
sudo sed -i 's|/gate-portal/|/|g' assets/css/*.css 2>/dev/null

echo ""
echo "After fix:"
echo "Alumni directory: $(grep -r '/gate-portal/' alumni/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Includes directory: $(grep -r '/gate-portal/' includes/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Auth directory: $(grep -r '/gate-portal/' auth/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Admin directory: $(grep -r '/gate-portal/' admin/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo "Employer directory: $(grep -r '/gate-portal/' employer/ --include='*.php' 2>/dev/null | wc -l) occurrences"
echo ""

TOTAL=$(grep -r '/gate-portal/' . --include='*.php' --include='*.css' --exclude-dir=.git --exclude-dir=vendor 2>/dev/null | wc -l)
echo "Total remaining: $TOTAL occurrences"
echo ""

if [ $TOTAL -gt 0 ]; then
    echo "Remaining files with /gate-portal/:"
    grep -r '/gate-portal/' . --include='*.php' --include='*.css' --exclude-dir=.git --exclude-dir=vendor -l 2>/dev/null | head -10
    echo ""
fi

# Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2
echo "✓ Apache restarted"
echo ""

echo "=========================================="
echo "FIX COMPLETE!"
echo "=========================================="
echo ""
echo "Test URLs:"
echo "- Login: http://13.60.96.145/"
echo "- Alumni Dashboard: http://13.60.96.145/alumni/dashboard.php"
echo "- Admin Dashboard: http://13.60.96.145/admin/dashboard.php"
echo ""
echo "IMPORTANT: Clear your browser cache!"
echo "Press Ctrl+Shift+Delete or use Incognito mode"
echo ""
