#!/bin/bash
# Fix all /gate-portal/ references in alumni directory

echo "=========================================="
echo "Fixing Alumni Directory"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal/alumni

echo "Before fix:"
grep -r "/gate-portal/" . --include="*.php" 2>/dev/null | wc -l
echo ""

echo "Applying fixes..."

# Fix all /gate-portal/ references
sudo sed -i 's|/gate-portal/|/|g' *.php

echo ""
echo "After fix:"
grep -r "/gate-portal/" . --include="*.php" 2>/dev/null | wc -l
echo ""

# Restart Apache
echo "Restarting Apache..."
sudo systemctl restart apache2
echo "✓ Done"
echo ""

echo "=========================================="
echo "Alumni directory fixed!"
echo "=========================================="
echo ""
echo "Test: http://13.60.96.145/alumni/dashboard.php"
echo ""
