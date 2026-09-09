# Ubuntu 24.04 / Hostinger KVM 2 (Frankfurt) yayın

1. `deploy/ubuntu-setup.sh` paketleri kurar (Nginx, PHP 8.3, MySQL, Redis, UFW, Fail2ban, Certbot).
2. GitHub reposunu `/var/www/hacerilim` altına alın.
3. `.env` üretim değerleri:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://hacerilimvekulturdernegi.org`
   - `DB_CONNECTION=mysql` ve ubuntu-setup’taki kullanıcı
   - `SESSION_ENCRYPT=true`
   - `SESSION_SECURE_COOKIE=true`
   - Redis: `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`
4. `composer install --no-dev --optimize-autoloader`
5. `php artisan migrate --seed --force` (ilk kurulum; admin şifresini `ADMIN_PASSWORD` ile verin)
6. `php artisan storage:link`
7. `npm ci && npm run build`
8. `deploy/nginx.conf` dosyasını PHP soketi ile kopyalayın, ardından `certbot --nginx -d hacerilimvekulturdernegi.org -d www.hacerilimvekulturdernegi.org` çalıştırın.
9. Queue için Supervisor örneği:

```
[program:hacer-worker]
command=php /var/www/hacerilim/artisan queue:work redis --sleep=1 --tries=3
user=www-data
autostart=true
autorestart=true
```

10. Haftalık yedek: `deploy/backup.sh` crontab `15 3 * * 0`
11. Yönetim paneli: `https://alanadiniz.org/yonetim` — 2FA’yı profil ekranından açın.
12. SSH anahtarı kullanın; şifreyle root girişini kapatın.

Cloudflare proxy Türkiye gecikmesini düşürmek için isteğe bağlıdır.
