# Network Site

Laravel 12 hotspot card store and dashboard for internet network owners.

## Demo Accounts

- Network owner: `owner@gptnet.test` / `password`
- Super admin: `admin@vinex.test` / `password`

## Local Setup

```bash
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

The seeded public store is available at `/` and the dashboard at `/login`.

## Notes

- Customer order pages use signed token URLs: `/orders/{access_token}?signature=...`.
- The browser stores the latest signed order URL in `localStorage` as `last_order_url`.
- Card imports use Laravel Excel and import one-column XLSX/CSV files into the selected active package.
- This local XAMPP PHP CLI has `gd` disabled, so Composer is configured with `platform.ext-gd` for Laravel Excel dependency resolution.
