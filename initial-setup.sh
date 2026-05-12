#!/bin/bash
# Initial Ubuntu Server Setup for GATE Portal

set -e

echo "=== GATE Portal - Initial Server Setup ==="
echo ""

# Update system
echo "→ Updating system packages..."
sudo apt-get update

# Install Apache
echo "→ Installing Apache..."
sudo apt-get install -y apache2

# Install PHP and extensions
echo "→ Installing PHP and extensions..."
sudo apt-get install -y php libapache2-mod-php php-mysql php-pdo php-zip php-mbstring php-xml php-curl

# Install Git
echo "→ Installing Git..."
sudo apt-get install -y git unzip

# Create web directory
echo "→ Creating web directory..."
sudo mkdir -p /var/www/html
cd /var/www/html

# Clone repository
echo ""
echo "→ Ready to clone repository"
read -p "Enter your GitHub repository URL: " REPO_URL
sudo git clone $REPO_URL gate-portal

# Set permissions
echo "→ Setting permissions..."
sudo chown -R www-data:www-data /var/www/html/gate-portal
sudo chmod -R 755 /var/www/html/gate-portal
sudo mkdir -p /var/www/html/gate-portal/uploads/photos
sudo mkdir -p /var/www/html/gate-portal/uploads/cvs
sudo chmod -R 777 /var/www/html/gate-portal/uploads

# Enable Apache modules
echo "→ Enabling Apache modules..."
sudo a2enmod rewrite

# Configure Apache
echo "→ Configuring Apache..."
sudo bash -c 'cat > /etc/apache2/sites-available/gate-portal.conf <<EOF
<VirtualHost *:80>
    ServerName 13.60.96.145
    DocumentRoot /var/www/html/gate-portal
    
    <Directory /var/www/html/gate-portal>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/gate-portal-error.log
    CustomLog \${APACHE_LOG_DIR}/gate-portal-access.log combined
</VirtualHost>
EOF'

# Enable site and restart Apache
sudo a2ensite gate-portal.conf
sudo systemctl restart apache2

# Create .env file
echo "→ Creating .env file..."
cd /var/www/html/gate-portal
if [ -f .env.production ]; then
    sudo cp .env.production .env
elif [ -f .env.example ]; then
    sudo cp .env.example .env
fi
sudo chown www-data:www-data .env

echo ""
echo "✓ Setup complete!"
echo ""
echo "Next steps:"
echo "1. Edit .env file: sudo nano /var/www/html/gate-portal/.env"
echo "2. Add your RDS credentials"
echo "3. Run database setup: cd /var/www/html/gate-portal && php setup_database.php"
echo "4. Visit: http://13.60.96.145/gate-portal/"
echo ""
