# Hacer İlim ve Kültür Derneği

Kurumsal dinamik web sitesi: Laravel 13, Filament 5 yönetim paneli, MySQL, Tailwind.

## Yerel çalıştırma

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
# .env içinde DB_CONNECTION=sqlite bırakabilirsiniz
php artisan migrate --seed
php artisan storage:link
npm install
npm run dev
php artisan serve
```

Yönetim: `/yonetim`  
Varsayılan kullanıcı: `yonetim@hacerilim.org` / `password` (üretimde mutlaka değiştirin)

## Marka

Logo: `public/images/logo.svg` — asıl logo panelden (`Site ayarları`) yüklenebilir. Renkler: orman yeşili `#143D2C`, altın `#C4A35A`, krem `#F6F1E7`.

## Sunucu

Hostinger KVM 2, Ubuntu 24.04, Frankfurt: `deploy/` klasörüne bakın.
