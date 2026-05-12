#!/bin/bash
# Remove /gate-portal/ prefix from all PHP files on Ubuntu server

cd /var/www/html/gate-portal

echo "Removing /gate-portal/ prefix from all PHP files..."

# Use sed to replace /gate-portal/ with / in all PHP files
sudo find . -name "*.php" -type f -exec sed -i 's|/gate-portal/|/|g' {} +

echo "Done! All /gate-portal/ prefixes removed."
echo "Total files modified:"
sudo find . -name "*.php" -type f | wc -l
