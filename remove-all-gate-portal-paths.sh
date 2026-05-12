#!/bin/bash
# Complete removal of /gate-portal/ paths from all files

echo "=========================================="
echo "Removing ALL /gate-portal/ references"
echo "=========================================="
echo ""

cd /var/www/html/gate-portal

# Remove /gate-portal/ from PHP files
echo "Fixing PHP files..."
sudo find . -name "*.php" -type f -exec sed -i 's|/gate-portal/|/|g' {} +
sudo find . -name "*.php" -type f -exec sed -i "s|'/gate-portal/|'/|g" {} +
sudo find . -name "*.php" -type f -exec sed -i 's|"/gate-portal/|"/|g' {} +

# Remove /gate-portal/ from CSS files
echo "Fixing CSS files..."
sudo find . -name "*.css" -type f -exec sed -i 's|/gate-portal/|/|g' {} +

# Remove /gate-portal/ from JS files
echo "Fixing JS files..."
sudo find . -name "*.js" -type f -exec sed -i 's|/gate-portal/|/|g' {} +

# Remove /gate-portal/ from HTML files
echo "Fixing HTML files..."
sudo find . -name "*.html" -type f -exec sed -i 's|/gate-portal/|/|g' {} +

echo ""
echo "✓ All /gate-portal/ references removed"
echo ""

# Count remaining occurrences
echo "Checking for any remaining /gate-portal/ references..."
REMAINING=$(sudo grep -r "/gate-portal/" . --include="*.php" --include="*.css" --include="*.js" --include="*.html" 2>/dev/null | wc -l)
echo "Remaining occurrences: $REMAINING"
echo ""

if [ $REMAINING -gt 0 ]; then
    echo "Files still containing /gate-portal/:"
    sudo grep -r "/gate-portal/" . --include="*.php" --include="*.css" --include="*.js" --include="*.html" 2>/dev/null | cut -d: -f1 | sort -u
    echo ""
fi

echo "=========================================="
echo "Done!"
echo "=========================================="
