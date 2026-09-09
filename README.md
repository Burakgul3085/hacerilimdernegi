# Hâcer İlim ve Kültür Derneği

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
Varsayılan kullanıcı: `info@hacerilimvekulturdernegi.org` / `password` (üretimde mutlaka değiştirin)

## Marka

Logo: `public/images/logo-mark.png`. Marka renkleri: mürekkep siyahı `#161513`, sıcak taupe `#8A7A62`, krem `#FBF6EC`.

## Sunucu

Domain: `hacerilimvekulturdernegi.org`. Hostinger KVM 2, Ubuntu 24.04, Frankfurt kurulumu için `deploy/` klasörüne bakın.
