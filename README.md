# Elevanix - Smart Software Company Management System

Elevanix is a Laravel-based multi-tenant SaaS platform for managing software companies, their employees, clients, projects, tasks, attendance, work sessions, payments, invoices, reports, notifications, and audit history from one role-aware workspace.

The application is built as a final-year BEng (Hons) Software Engineering project and is organized around three authenticated user areas: Super Admin, Company Admin, and Employee.

## Contents

- [Project Purpose](#project-purpose)
- [SaaS Model](#saas-model)
- [User Roles](#user-roles)
- [Core Modules](#core-modules)
- [Main Workflows](#main-workflows)
- [Technology Stack](#technology-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Database and Seed Data](#database-and-seed-data)
- [Mail, Queue, Scheduler, and Storage](#mail-queue-scheduler-and-storage)
- [Useful Commands](#useful-commands)
- [Testing](#testing)
- [Security Notes](#security-notes)
- [Reports and Exports](#reports-and-exports)
- [Frontend and Design](#frontend-and-design)
- [Troubleshooting](#troubleshooting)
- [Future Improvements](#future-improvements)
- [Project Status](#project-status)
- [Author and License](#author-and-license)

## Project Purpose

Elevanix helps a SaaS platform owner onboard and manage software companies while giving each approved company its own operational workspace. It supports day-to-day company administration, employee self-service, client/project delivery tracking, payment and invoice records, attendance, leave, file sharing, reporting, and audit visibility.

The system is not a generic Laravel starter. It contains custom domain models, controllers, middleware, policies, seeders, dashboards, reports, and Blade interfaces for a software company management workflow.

## SaaS Model

Elevanix uses a shared-application, shared-database SaaS model with tenant separation based on `company_id`.

- The Super Admin manages the platform itself.
- Each approved company acts as a tenant.
- Company Admins manage only their own company records.
- Employees access their own company data according to assigned permissions.
- Subscription plans define company limits such as employees, clients, projects, storage, and trial days.
- Middleware blocks access for unapproved companies, inactive employees, or inactive subscriptions.

Tenant isolation is implemented in application logic through company-aware queries, route middleware, policies, and helper concerns such as company access handlers.

## User Roles

### Super Admin

Super Admin users manage the SaaS platform:

- Review, approve, or reject company registration requests.
- Manage companies and company statuses.
- Manage subscription plans and subscriptions.
- Verify SaaS subscription payments.
- View platform revenue.
- View platform reports, notifications, audit logs, settings, users, and profile details.

### Company Admin

Company Admin users manage a single company tenant:

- Maintain company profile and company settings.
- Manage employees and employee permissions.
- Manage clients, project requests, projects, tasks, work sessions, attendance, leave requests, documents, payments, invoices, feedback, reports, notifications, activity logs, and profile details.
- Review project requests and convert approved requests into projects.
- Verify or reject client-project payment records.

### Employee

Employee users work inside their assigned company:

- View the employee dashboard.
- View assigned projects and tasks.
- Start/stop task work, update task progress/status, add comments, and upload task files.
- Manage own attendance, work sessions, leave requests, documents, notifications, profile, password, performance, and activity history.
- Access selected clients and reports only when granted the matching built-in permissions.

## Core Modules

- Public pages: landing page, about, contact, privacy policy, terms and company registration.
- Authentication: login, logout, forgot password, reset password, and dashboard redirection.
- Company onboarding: company registration requests, approval/rejection, approved company records, and demo tenant data.
- Subscription management: subscription plans, company subscriptions, subscription payments, status changes, and platform revenue.
- Employee management: employee CRUD, status management, password reset flow, permission assignment, permission copy, and permission reset.
- Client management: client records, project links, payments, invoices, and status control.
- Project requests and projects: request review, approval/rejection, conversion, project assignment, project files, and status tracking.
- Tasks: task assignment, review, status changes, progress, comments, file upload/download, and work session integration.
- Attendance and leave: check-in/check-out, attendance editing, absence marking command, leave request review/cancel workflows.
- Work sessions: employee work logs, admin adjustment, CSV/PDF export.
- Finance: client-project payments, SaaS subscription payments, invoices, invoice items, printing, and payment status handling.
- Reports: platform reports and company reports with CSV/PDF export options.
- Notifications and audit logs: database notifications and activity/audit history.
- Settings and profiles: platform settings, company settings, profile image upload, and password changes.

## Main Workflows

### Company Onboarding

1. A company submits a registration request from `/company/register`.
2. The Super Admin reviews the request under `/super-admin/company-requests`.
3. Approval creates or activates the company/admin path used by the tenant.
4. Rejection stores the rejection reason and notifies the requester.

### Tenant Operations

1. A Company Admin logs in after company approval and active subscription validation.
2. The Company Admin creates employees, clients, project requests/projects, tasks, invoices, payments, and company documents.
3. Employees log in to manage assigned work, attendance, leave, files, notifications, and profile data.
4. Company Admins use reports and exports to review performance, attendance, revenue, payments, invoices, and operational data.

### Subscription and Revenue

1. Super Admin defines subscription plans.
2. Companies are attached to subscriptions.
3. Subscription payment records are reviewed by the Super Admin.
4. Verified, received, or paid subscription payments count toward recognized platform revenue.

## Technology Stack

### Backend

- PHP `^8.3`
- Laravel `13.x`
- MySQL for local/default application database
- SQLite in-memory database for automated tests
- Laravel database sessions, cache, queues, notifications, mail, policies, middleware, seeders, migrations, and Blade views
- `barryvdh/laravel-dompdf` for PDF generation

### Frontend

- Blade templates
- Bootstrap 5.3.3 via CDN
- Font Awesome 6.5.2 via CDN
- SweetAlert2 via CDN
- Chart.js via CDN
- Google Fonts Poppins in the Blade layouts
- Custom CSS and JavaScript in `public/assets/css/app.css` and `public/assets/js/app.js`
- Vite/Tailwind tooling is present through `resources/css/app.css`, `resources/js/app.js`, `vite.config.js`, and `package.json`

### Development and Testing

- Composer
- Node.js and npm
- Vite
- Laravel Pint
- Pest with Laravel plugin
- PHPUnit configuration through `phpunit.xml`

## Project Structure

```text
app/
  Console/Commands/        Custom Artisan commands, including attendance absence marking
  Http/Controllers/        Public, auth, Super Admin, Company Admin, and Employee controllers
  Http/Middleware/         Role, permission, company approval, employee active, subscription checks
  Models/                  Domain models such as Company, User, Project, Task, Payment, Invoice
  Notifications/           Company registration notification classes
  Policies/                Authorization policies
  Support/                 Permission catalog, dashboard navigation, audit logger, helpers

bootstrap/
  app.php                  Route, middleware, and exception bootstrap configuration

config/
  app.php, database.php, mail.php, queue.php, filesystems.php, services.php

database/
  migrations/              Database schema
  seeders/                 Permission, system setting, subscription, super admin, and demo data seeders

public/
  assets/css/app.css       Main custom application stylesheet used by Blade layouts
  assets/js/app.js         Main custom application JavaScript used by Blade layouts

resources/
  views/                   Blade views grouped by public, auth, super-admin, company-admin, employee
  css/app.css              Vite/Tailwind entry
  js/app.js                Vite JavaScript entry

routes/
  web.php                  Web routes for public, auth, Super Admin, Company Admin, and Employee areas
  console.php              Console routes and custom console closures

tests/
  Feature/                 Feature tests for platform, company admin, employee, auth, reports, payments
  Unit/                    Unit tests
```

## Installation

### Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL or a compatible database server
- PHP extensions required by Laravel and DomPDF

### Setup Steps

Clone or open the project, then run:

```bash
composer install
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

On Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Create the configured MySQL database. The example environment uses:

```env
DB_CONNECTION=mysql
DB_DATABASE=elevanix
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and seeders:

```bash
php artisan migrate --seed
```

Create the public storage link:

```bash
php artisan storage:link
```

Build frontend assets:

```bash
npm run build
```

Start the local development stack:

```bash
composer run dev
```

The `dev` script runs Laravel's development server, the queue listener, and Vite together.

## Environment Configuration

Important `.env` values:

```env
APP_NAME="Elevanix"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=elevanix
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

For production, set `APP_ENV=production`, set `APP_DEBUG=false`, configure a real database, queue worker, mail provider, HTTPS `APP_URL`, backups, log rotation, and secure secret management.

## Database and Seed Data

The migrations create tables for:

- users, password reset tokens, sessions, cache, jobs, failed jobs
- companies and company registration requests
- clients, project requests, projects, project-user assignments
- tasks, task comments, work sessions, work files
- attendance and leave requests
- payments, invoices, invoice items
- feedback, notifications, audit logs
- permissions and user permissions
- subscription plans, subscriptions, company settings, and system settings

The default seed flow includes permissions, system settings, subscription plans, a Super Admin, demo companies, demo subscriptions, demo users, demo clients, demo projects, demo finance records, demo work sessions, company registration requests, and audit logs.

Development/demo accounts created by the seeders include:

| Role | Username | Email | Password |
| --- | --- | --- | --- |
| Super Admin | `superadmin` | `superadmin@elevanix.test` | `Password@123` |
| Company Admin | `novastack_admin` | `admin@novastack.test` | `Password@123` |
| Company Admin | `brightforge_admin` | `admin@brightforge.test` | `Password@123` |
| Employee | `maya` | `employee1@elevanix.test` | `Password@123` |
| Employee | `arun` | `employee2@elevanix.test` | `Password@123` |

Use these credentials only in local or demo environments.

## Mail, Queue, Scheduler, and Storage

### Mail

The application uses Laravel mail and notifications. Registration approval, rejection, and received notifications are implemented under `app/Notifications`.

Configure SMTP or another Laravel mailer through `.env`. In tests, `phpunit.xml` sets `MAIL_MAILER=array`.

### Queue

The default queue connection is database-backed in `.env.example`:

```env
QUEUE_CONNECTION=database
```

Run a queue worker in environments where queued jobs are used:

```bash
php artisan queue:work
```

The local `composer run dev` script uses:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

### Scheduler and Attendance Command

No recurring schedule is currently registered in `routes/console.php`. The project includes a custom Artisan command for marking absent attendance:

```bash
php artisan attendance:mark-absent
php artisan attendance:mark-absent --date=2026-08-07
```

If this should run automatically in production, add it to Laravel's scheduler and configure the system cron or scheduler runner for the deployment environment.

### Storage

Uploaded files use Laravel filesystem disks. Public uploads include profile images, company logos, and work files stored under paths such as `avatars`, `profile-images`, `company-logos`, and `work-files/{companyId}` on the public disk.

Run this once after installation:

```bash
php artisan storage:link
```

If the link already exists, Laravel will report that `public/storage` is already present.

## Useful Commands

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan migrate --seed
php artisan db:seed
php artisan route:list
php artisan queue:work
php artisan attendance:mark-absent
php artisan storage:link
npm run dev
npm run build
composer run dev
composer test
php artisan test
vendor/bin/pint
```

Avoid destructive database reset commands unless you intentionally want to remove local data.

## Testing

Automated tests are configured in `phpunit.xml`.

The test environment uses:

- SQLite in-memory database
- array mailer
- sync queue
- array session driver
- array cache store

Run the full test suite:

```bash
php artisan test
```

Or through Composer:

```bash
composer test
```

Recent local verification passed with:

```text
134 tests, 391 assertions
```

The test suite covers authentication, public pages, registration, Super Admin workflows, Company Admin workflows, Employee workflows, reports, payments, permissions, tenancy checks, dashboards, and related feature behavior.

## Security Notes

- Authentication is handled through Laravel's auth system and custom controllers.
- Authorization uses role middleware, permission middleware, Laravel policies, and company-aware access checks.
- Main middleware aliases include `role`, `permission`, `company.approved`, `employee.active`, and `subscription.active`.
- Employee permissions are implemented by the built-in Elevanix permission catalog and permission tables, not by Spatie Permission.
- Tenant access is constrained through `company_id`, middleware, policies, and company access concerns.
- Payment views separate SaaS subscription payments from client-project payments.
- Audit logs record important platform and tenant activity.
- Password reset routes are present and should be paired with a real mail provider in production.
- Demo credentials must be changed or removed before deployment.
- Production deployments should enforce HTTPS, strong secrets, backups, queue supervision, storage permissions, and `APP_DEBUG=false`.

## Reports and Exports

Elevanix supports CSV and PDF exports in multiple areas:

- Super Admin revenue CSV/PDF exports.
- Super Admin report CSV/PDF exports.
- Company Admin report CSV/PDF exports.
- Attendance CSV/PDF exports.
- Work session CSV/PDF exports.
- Invoice print views.

PDF generation uses `barryvdh/laravel-dompdf`.

## Frontend and Design

The user interface is built with Blade views and custom dashboard layouts for public pages, authentication, Super Admin, Company Admin, and Employee areas.

The active Blade layouts load:

- Poppins from Google Fonts
- Bootstrap 5.3.3
- Font Awesome 6.5.2
- SweetAlert2
- Chart.js
- `public/assets/css/app.css`
- `public/assets/js/app.js`

The repository also contains Vite/Tailwind tooling. The Vite config references `resources/css/app.css` and `resources/js/app.js`, and `npm run build` compiles the Vite bundle.

## Troubleshooting

### `APP_KEY` Missing

Run:

```bash
php artisan key:generate
```

### Database Connection Fails

Check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`. Make sure the database exists before running migrations.

### Public Uploads Do Not Load

Run:

```bash
php artisan storage:link
```

Then confirm the web server can read `public/storage`.

### Queued Work Does Not Run

Start a queue worker:

```bash
php artisan queue:work
```

For production, supervise the worker with the hosting platform or a process manager.

### Mail Does Not Send

Configure a real mail provider in `.env`. The repository does not define a custom `mail:test` command; use Laravel's configured mail/notification flows or write a small local verification route/command if needed during development.

### Build Warnings

`npm run build` may warn that the optional `fontaine` package is not installed for optimized font fallbacks. The build can still complete successfully.

## Future Improvements

Potential next steps:

- Add a production deployment guide.
- Add scheduler registration for automatic attendance absence marking.
- Add stronger subscription renewal and billing automation.
- Add more granular report filters and analytics dashboards.
- Add browser-based end-to-end tests for critical workflows.
- Add API endpoints if mobile or external integrations are required.
- Add a formal top-level license file.
- Add backup, restore, and operational monitoring documentation.

## Project Status

Elevanix is an active academic/portfolio SaaS project with the main platform, tenant, and employee workflows implemented. Local verification currently passes migrations, route discovery, asset build, and automated tests. Production use would still require deployment hardening, real mail/queue/storage configuration, secret rotation, and operational monitoring.

## Author and License

This project is developed as a BEng (Hons) Software Engineering final-year project.

No standalone top-level `LICENSE` file is currently present in this repository. Add a formal license before public distribution or open-source release.
