# Elevanix - Smart Software Company Management System

Elevanix, abbreviated as ESSCMS, is a Laravel 13 software company management system foundation for company registration approval, role-based access, dashboards, clients, projects, tasks, work sessions, invoices, payments, notifications and audit logs.

This project is not subscription based. There are no plans, trials, subscriptions, plan limits or subscription payment modules.

## Technology Stack

- Laravel 13
- PHP 8.3+
- Blade
- Bootstrap 5
- Vanilla JavaScript
- MySQL
- SweetAlert2
- Font Awesome
- Laravel Mail and Notifications
- Pest for tests

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Update `.env` with your local database and mail credentials. Do not commit real secrets.

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Custom CSS and JavaScript are loaded directly from `public/assets/css/app.css` and `public/assets/js/app.js`.

## Queue And Mail

Mail uses Laravel's configured mail driver. The real `.env` may contain working SMTP credentials; keep them private.

For queued jobs:

```bash
php artisan queue:listen
```

## Testing

```bash
php artisan test
```

The test environment uses in-memory SQLite from `phpunit.xml`.

## Demo Accounts

After running the seeder:

- Super Admin: `superadmin@elevanix.test` / `Password@123`
- Company Admin: `admin@novastack.test` / `Password@123`
- Company Admin: `admin@brightforge.test` / `Password@123`
- Employee: `employee1@elevanix.test` / `Password@123`
- Employee: `employee2@elevanix.test` / `Password@123`

## Main Modules Implemented

- Public landing page
- Email or username login
- Public company registration request
- Super Admin request approval and rejection
- Role middleware
- Company approval middleware
- Super Admin dashboard
- Company Admin dashboard
- Employee dashboard
- Company listing and activation/suspension
- Elevanix Blade layout, sidebar, navbar and logo
- Database schema for core ESSCMS tables
- Demo seeder

## Security Notes

- Authentication uses Laravel guards, hashed passwords and session regeneration.
- Logout is POST only.
- Role checks are enforced server-side.
- Company users are blocked when their company is not active.
- Company registration stores uploaded logos on the public disk after image validation.
- Real `.env` secrets must never be copied to documentation or source files.

## Deployment Checklist

- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Set a valid `APP_URL` including `http://` or `https://`
- Configure MySQL credentials
- Configure SMTP credentials
- Run `php artisan migrate --force`
- Run `php artisan storage:link`
- Configure queue workers if database notifications/jobs are queued
