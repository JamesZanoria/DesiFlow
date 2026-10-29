<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# DesiFlow

## Cashier POS workflows

The cashier portal is available to authenticated users with the `cashier` role:

- `/cashier` — daily cashier dashboard and unpaid orders.
- `/cashier/orders` — active kitchen orders and read-only quick view.
- `/cashier/menu` and `/cashier/cart` — cashier-only searchable menu, live editable cart, add-ons, and checkout.
- `/cashier/transactions` — cashier-owned payment history, digital receipts, and void/refund requests.
- `/admin/transaction-requests` — administrator review of void/refund requests.

The public customer ordering page at `/menu` and its guest cart/order APIs have been removed. Cashier ordering remains available through the authenticated cashier module.

The cashier POS migration is additive. After configuring the existing database in `.env`, run:

```powershell
cd laravel-app
composer install
# Only generate a key if APP_KEY is empty:
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan test
```

Do not replace an existing `.env`; set `APP_URL`, `DB_CONNECTION`, and the matching database connection values for the local environment. The application timezone is `Asia/Manila`, which defines the cashier dashboard's business-day boundary. The project SQL snapshot `database/complete_database.sql` is PostgreSQL-specific; provision the existing base tables for the configured database before applying migrations on a new database.

Cashier orders are saved as `awaiting_payment`; successful payment moves them into the kitchen's `pending` queue. Cash amounts are validated to two decimal places and GCash references must contain 6–50 letters, digits, or hyphens; GCash references are stored as manually supplied and are not externally verified. Payments are recorded once per order. Void/refund requests preserve the original payment row and only change its transaction status after an administrator approves the request.
