#!/usr/bin/env bash
set -euo pipefail

# Hostinger KVM 2 / Ubuntu 24.04 LTS — Frankfurt
# root veya sudo ile çalıştırın.

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y nginx mysql-server redis-server unzip git curl \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl \
    php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-redis \
    certbot python3-certbot-nginx fail2ban ufw composer

ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

systemctl enable --now nginx mysql redis-server php8.3-fpm fail2ban

mysql -e "CREATE DATABASE IF NOT EXISTS hacerilim CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS 'hacerilim'@'localhost' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON hacerilim.* TO 'hacerilim'@'localhost'; FLUSH PRIVILEGES;"

mkdir -p /var/www/hacerilim
chown -R www-data:www-data /var/www/hacerilim

echo "Repo'yu /var/www/hacerilim içine klonlayın, .env doldurun, ardından:"
echo "  cd /var/www/hacerilim"
echo "  composer install --no-dev --optimize-autoloader"
echo "  php artisan key:generate"
echo "  php artisan migrate --seed --force"
echo "  php artisan storage:link"
echo "  php artisan config:cache && php artisan route:cache && php artisan view:cache"
echo "  npm ci && npm run build"
echo "Nginx conf: deploy/nginx.conf -> /etc/nginx/sites-available/hacerilim"
echo "certbot --nginx -d domaininiz.org"
echo "APP_DEBUG=false, SESSION_ENCRYPT=true, FILESYSTEM_DISK=public"
