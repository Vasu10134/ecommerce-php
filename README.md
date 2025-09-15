<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel"></a></p>

### Single Ecommerce (Laravel)

A simple multi-section ecommerce web app built on Laravel. It includes a customer storefront, product catalog with categories, cart/checkout, and an admin panel for managing products, orders, banners, featured links, etc.

### Tech stack
- Laravel (PHP 8)
- Blade views, jQuery/Bootstrap front-end
- MySQL (or MariaDB)
- Apache (XAMPP) recommended for local dev

### Quick start (local)
1) Requirements
- PHP 8.x, Composer
- MySQL/MariaDB
- Apache with `mod_rewrite` enabled and `AllowOverride All`

2) Install
- Place the project at `C:/xampp/htdocs/ecommerce-php`
- From `project/` run:
```
composer install
cp .env.example .env   # on Windows, copy the file manually
php artisan key:generate
```
- Create a database and set DB_ values in `project/.env`
- (Optional demo data) Run migrations/seeds:
```
php artisan migrate --seed
```

3) Serve the app
- Point Apache to the Laravel public folder:
  - URL for XAMPP: `http://localhost/ecommerce-php/project/public/`
  - Recommended: create a VirtualHost with `DocumentRoot "C:/xampp/htdocs/ecommerce-php/project/public"`
- Make sure public assets are available under `project/public/assets`.
  - If needed, copy or junction the root `assets` folder to `project/public/assets`.

4) Fix homepage Featured Links (optional, local-only)
```
php project/fix_featured_links.php
```
This points the homepage Featured Links to your local category routes.

### Useful environment values
- `APP_URL=http://localhost/ecommerce-php/project/public`
- `ASSET_URL` can be left empty for local

### Default routes
- Storefront: `/`
- Categories: `/category/{category?}/{subcategory?}/{childcategory?}`
- Product page: `/item/{slug}`
- Admin panel: `/admin`

### Notes
- If pages 404, verify `mod_rewrite` is enabled and `.htaccess` is honored (AllowOverride All).
- Clear caches when changing env/config:
```
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear
```

### License
MIT (Laravel framework and included code are MIT-licensed).
