#!/bin/bash
# Ubuntu Server Deployment Script for GATE Portal
# Run this once on your Ubuntu server: sudo bash setup_ubuntu.sh

set -e

echo "=== GATE Portal Ubuntu Server Setup ==="

# Update system
echo "→ Updating system packages..."
apt-get update
apt-get upgrade -y

# Install required packages
echo "→ Installing Apache, PHP, and extensions..."
apt-get install -y apache2 php php-mysql php-pdo php-zip php-mbstring php-xml php-curl git unzip

# Enable Apache modules
echo "→ Enabling Apache modules..."
a2enmod rewrite
a2enmod ssl

# Create web directory
echo "→ Setting up web directory..."
WEB_DIR="/var/www/html/gate-portal"
mkdir -p $WEB_DIR

# Set permissions
echo "→ Setting permissions..."
chown -R www-data:www-data $WEB_DIR
chmod -R 755 $WEB_DIR
mkdir -p $WEB_DIR/uploads/photos $WEB_DIR/uploads/cvs
chmod -R 777 $WEB_DIR/uploads

# Configure Apache
echo "→ Configuring Apache..."
cat > /etc/apache2/sites-available/gate-portal.conf <<'EOF'
<VirtualHost *:80>
    ServerName 13.60.96.145
    DocumentRoot /var/www/html/gate-portal
    
    <Directory /var/www/html/gate-portal>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/gate-portal-error.log
    CustomLog ${APACHE_LOG_DIR}/gate-portal-access.log combined
</VirtualHost>
EOF

a2ensite gate-portal.conf
systemctl reload apache2

# Clone repository (if not exists)
if [ ! -d "$WEB_DIR/.git" ]; then
    echo "→ Cloning repository..."
    read -p "Enter GitHub repository URL: " REPO_URL
    git clone $REPO_URL $WEB_DIR
    chown -R www-data:www-data $WEB_DIR
fi

# Create .env file
echo "→ Creating .env file..."
if [ ! -f "$WEB_DIR/.env" ]; then
    cp $WEB_DIR/.env.production $WEB_DIR/.env
    echo "⚠ Edit $WEB_DIR/.env with your RDS credentials"
fi

# Set deploy.php executable
chmod +x $WEB_DIR/deploy.php

echo ""
echo "✓ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Edit /var/www/html/gate-portal/.env with your RDS credentials"
echo "2. Run: cd /var/www/html/gate-portal && php setup_database.php"
echo "3. Configure GitHub webhook to: http://13.60.96.145/gate-portal/deploy.php"
echo "4. Test deployment: git push origin main"
echo ""
