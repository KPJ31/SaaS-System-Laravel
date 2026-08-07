# Elevanix

Smart Software Company Management System

Elevanix is a SaaS-based software company and software agency management platform built with Laravel. It centralizes company operations such as company onboarding, employees, clients, project requests, projects, tasks, work tracking, attendance, leave, calendar events, payments, invoices, reports, notifications, and audit history.

The system has three authenticated user areas: Super Admin, Company Admin, and Employee.

## About the System

Elevanix lets a platform owner manage multiple software companies from one application while each approved company works inside its own operational workspace. Company-owned records are separated by tenant ownership, mainly through `company_id`, and access is controlled through roles, middleware, policies, permissions, and company-aware queries.

The repository contains custom Laravel domain models, controllers, services, policies, middleware, seeders, Blade views, reports, exports, notifications, and tests. It is not the default Laravel starter application.

## Key Features

- Multi-company SaaS architecture.
- Role-based access for Super Admin, Company Admin, and Employee users.
- Company registration, approval, and rejection workflow.
- Subscription plans, company subscriptions, subscription change requests, and payment tracking.
- Employee management with direct permission assignment.
- Client, project request, project, task, Kanban, comment, and work-file management.
- Attendance, work sessions, leave requests, personal todos, and employee performance views.
- Calendar and company event management.
- Invoices, invoice items, client-project payments, and payment verification.
- Company and platform reports with CSV/PDF exports.
- Database notifications and audit/activity logging.

## User Roles

The implemented role values are defined in `app/Enums/UserRole.php`.

| Role | Value | Purpose |
| --- | --- | --- |
| Super Admin | `super_admin` | Manages the SaaS platform, companies, subscriptions, payments, users, reports, settings, and platform audit data. |
| Company Admin | `company_admin` | Manages one approved company workspace and its operational records. |
| Employee | `employee` | Works inside one company and manages assigned work, attendance, leave, files, todos, notifications, and profile data. |

There is no authenticated Client role in the current codebase.

## Super Admin

Super Admin routes live under `/super-admin` and include dashboard, company registration requests, companies and statuses, subscription plans, company subscriptions, subscription change requests, platform users, subscription payments, revenue, CSV/PDF reports, notifications, audit logs, system settings, profile, and password management.

## Company Admin

Company Admin routes live under `/company-admin` and require authentication, the `company_admin` role, active company approval, and an active/trialing subscription except for limited subscription self-service routes. Implemented modules include dashboard, calendar, company events, company profile/settings, subscription changes, employees, employee permissions, clients, project requests, projects, task Kanban, tasks, work sessions, attendance, leave review, documents, invoices, payments, feedback, reports, notifications, activity logs, personal todos, profile, and password management.

## Employee

Employee routes live under `/employee` and require authentication, the `employee` role, active employee status, active company approval, and an active/trialing subscription. Implemented modules include dashboard, calendar, assigned projects, assigned tasks, task workflow updates, comments, file uploads/downloads, work timer, work sessions, attendance check-in/check-out, leave requests, documents, performance, personal todos, notifications, activity history, profile, and password management. Employees can also access selected clients and reports when direct permissions are assigned.

## Main System Workflow

1. A company submits a public registration request.
2. A Super Admin approves or rejects the request.
3. Approval creates or activates the company workspace and Company Admin account.
4. The Company Admin manages subscription needs, company settings, employees, permissions, and clients.
5. Project requests are reviewed and can be converted into projects.
6. Projects receive team members and tasks.
7. Employees work on assigned tasks, track time, update status/progress, comment, and upload files.
8. Company Admins review submitted tasks and monitor attendance, leave, work sessions, and project progress.
9. Invoices and payments are recorded for client-project work.
10. Reports, notifications, exports, and audit logs support follow-up.

## Technology Stack

### Backend

- PHP `^8.3`
- Laravel `13.x` (`13.23.0` verified locally)
- MySQL by default through `.env.example`
- Laravel database sessions, cache, queues, notifications, mail, policies, middleware, seeders, migrations, and Blade views
- `barryvdh/laravel-dompdf` for PDF generation

### Frontend

- Blade templates
- Bootstrap 5.3.3 loaded by the active Blade layout
- Font Awesome 6.5.2
- SweetAlert2
- Chart.js
- Custom CSS and JavaScript in `public/assets/css/app.css` and `public/assets/js/app.js`
- Vite tooling for `resources/css/app.css` and `resources/js/app.js`
- Tailwind tooling is installed, but Bootstrap and `public/assets` are the active application UI layer

### Testing and Tooling

- Composer
- Node.js and npm
- Vite
- Laravel Pint
- Pest with the Laravel plugin
- PHPUnit configuration through `phpunit.xml`
- SQLite in-memory database for automated tests

## System Architecture

```text
Browser
  -> Laravel Blade UI
  -> Web routes and middleware
  -> Controllers, form requests, policies, and services
  -> Eloquent models
  -> MySQL database
```

Super Admin users operate at platform level. Company Admin and Employee users are constrained by company ownership, role middleware, subscription checks, policies, and direct permissions.

## Core Modules

### Company and Subscription Management
The platform supports public company registration, Super Admin review, company approval/rejection, company status management, company profile updates, settings, subscription plans, company subscriptions, subscription change requests, and payment proof uploads.

### Employees and Permissions
Company Admin users manage employees, reset employee passwords, suspend accounts, and assign direct permissions. Permissions use the built-in Elevanix `permissions` and `permission_user` tables, not Spatie Permission.

### Clients, Projects, and Tasks
Company Admin users manage clients, project requests, projects, project teams, task assignment, task workflows, Kanban movement, comments, task review, files, dates, and progress sync.

### Attendance, Work, Leave, and Calendar
Employees can check in/out, track task work sessions, request leave, manage personal todos, and view calendar data. Company Admin users review leave, correct attendance, export attendance/work data, and manage company events.

### Finance
Finance features include invoices, invoice items, invoice print views, client-project payments, subscription payments, payment verification/rejection, revenue views, and financial reports. Elevanix is not a full accounting platform.

### Reports, Notifications, and Audit
Company reports include project performance, task performance, employee progress, worked hours, attendance, leave, financial summary, invoices, payments, revenue, employees, clients, project requests, activity logs, and feedback where supported by routes/services. Super Admin reports include platform companies, subscriptions, payments/revenue, users, audit logs, registration requests, and subscription change requests. Laravel notifications are used for registration, tasks, due reminders, leave, events, and subscription changes. Audit logs record important actions, but they should not be treated as compliance-grade immutable logs without additional infrastructure.

## Requirements

- PHP 8.3 or newer.
- Composer.
- MySQL or a compatible database server.
- Node.js and npm when building or running Vite assets.
- PHP extensions normally required by Laravel, database access, file uploads, and DomPDF.

## Installation

Clone the repository, replacing the placeholder with the real repository URL:

```bash
git clone <repository-url>
cd SaaS-System
```

Install dependencies:

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

Generate the app key, configure the database, migrate, seed, and link storage:

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

Build frontend assets:

```bash
npm run build
```

On Windows PowerShell, if script execution policy blocks `npm`, use:

```powershell
npm.cmd run build
```

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
FILESYSTEM_LOCAL_SERVE=false

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Never commit real `.env` secrets.

## Database Setup

The example environment uses MySQL and the database name `elevanix`.

Normal setup:

```bash
php artisan migrate
php artisan db:seed
```

Or:

```bash
php artisan migrate --seed
```

Development only: `php artisan migrate:fresh --seed` deletes all existing database data before rebuilding the schema.

## Storage Setup

Uploads use Laravel storage on the public disk. This includes profile images, company logos, subscription proof files, and project/task work files.

```bash
php artisan storage:link
```

The local disk serving helper is disabled by default:

```env
FILESYSTEM_LOCAL_SERVE=false
```

## Running the Application

Run Laravel only:

```bash
php artisan serve
```

Run Vite during frontend asset development:

```bash
npm run dev
```

Run the combined local development script:

```bash
composer run dev
```

The `dev` script starts the Laravel server, a queue listener, and Vite through `concurrently`.

## Queue Setup

The project is configured for the database queue connection:

```env
QUEUE_CONNECTION=database
```

Run a queue worker in environments that use queued jobs or queue-backed mail/notification handling:

```bash
php artisan queue:work
```

The main browser workflows do not require a worker for every request, but `composer run dev` starts a local queue listener.

## Scheduler Setup

`routes/console.php` schedules task due reminders:

```php
Schedule::command('notifications:task-due-reminders')->dailyAt('08:00');
```

For local development:

```bash
php artisan schedule:work
```

For production, configure the server scheduler/cron to run Laravel's scheduler every minute:

```bash
php artisan schedule:run
```

The project also includes a manual attendance command:

```bash
php artisan attendance:mark-absent
php artisan attendance:mark-absent --date=2026-08-07
```

## Default / Demo Accounts

The seeders intentionally create local/demo accounts. Use these only for development or demonstration environments.

| Role | Username | Email | Password |
| --- | --- | --- | --- |
| Super Admin | `superadmin` | `superadmin@elevanix.test` | `Password@123` |
| Company Admin | `novastack_admin` | `admin@novastack.test` | `Password@123` |
| Company Admin | `brightforge_admin` | `admin@brightforge.test` | `Password@123` |
| Employee | `maya` | `employee1@elevanix.test` | `Password@123` |
| Employee | `arun` | `employee2@elevanix.test` | `Password@123` |

Change or remove demo credentials before production deployment.

## Testing

Run the full Pest/PHPUnit test suite:

```bash
php artisan test
```

Or through Composer:

```bash
composer test
```

The test environment uses SQLite in-memory, array mailer, sync queue, array session driver, and array cache store.

Targeted examples:

```bash
php artisan test --filter=CompanyAdminModuleTest
php artisan test --filter=EmployeeModuleTest
```

## Useful Artisan Commands

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan optimize:clear
php artisan queue:work
php artisan schedule:work
php artisan notifications:task-due-reminders
php artisan attendance:mark-absent
php artisan test
```

Production cache commands verified for this project:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Project Structure

```text
app/Enums             Role enum values.
app/Http/Controllers  Public, auth, Super Admin, Company Admin, Employee, and shared controllers.
app/Http/Middleware   Role, permission, company approval, employee active, and subscription checks.
app/Http/Requests     Form request validation for selected workflows.
app/Models            Eloquent models for core domain records.
app/Notifications     Mail and database notifications.
app/Policies          Authorization policies for company-owned records.
app/Services          Dashboard, attendance, calendar, invoice, report, task, timer, and audit logic.
app/Support           Permission catalog and dashboard navigation helpers.
database/migrations   Database schema.
database/seeders      Permissions, system settings, subscription plans, Super Admin, and demo data.
resources/views       Blade UI grouped by public, auth, role areas, reports, calendar, todos, and partials.
public/assets         Active custom CSS and JavaScript used by the Blade layouts.
tests                 Feature and unit tests.
```

## Security Features

- Laravel authentication, password hashing, and CSRF protection.
- Role middleware for `super_admin`, `company_admin`, and `employee`.
- Company approval, employee active-status, and subscription middleware.
- Direct permission middleware for selected employee access.
- Laravel policies and company-aware checks for tenant-owned records.
- Server-side validation in controllers and form requests.
- File type and size validation for uploads.
- Missing-file guards before downloads.
- CSV export escaping to reduce spreadsheet formula injection risk.
- Audit logs for important administrative and business actions.

## Multi-Tenant Data Isolation

Elevanix uses a shared database with tenant ownership through `company_id`. Operational records such as employees, clients, project requests, projects, tasks, work sessions, attendance, leave requests, files, payments, invoices, settings, notifications, and audit logs are scoped to the relevant company where applicable.

Super Admin users have platform-level access. Company Admin users manage their own company. Employees are restricted to their company, assigned records, own records, and direct permissions granted by their Company Admin.

Developers should not trust browser-submitted `company_id`, `user_id`, role, status, or ownership fields. Use the existing access helpers, middleware, policies, and services.

## File Uploads and Storage

Implemented uploads include company registration logos, company profile logos, user profile images, subscription payment proof files, Company Admin document uploads, Company Admin task attachments, and Employee task attachments.

Work files are stored under company-specific paths such as `work-files/{companyId}` on the public disk.

## Notifications

Implemented notifications include company registration received/approved/rejected, task assignment/update/review style notifications, task due reminders, leave request approval/rejection, company event notifications, and subscription change status notifications.

The project uses mail and database notification channels. It does not implement realtime WebSocket notifications.

## PDF / Export Features

PDF generation uses `barryvdh/laravel-dompdf`.

Implemented export/print areas include Super Admin revenue CSV/PDF, Super Admin reports CSV/PDF, Company Admin reports CSV/PDF, Company Admin attendance CSV/PDF, Company Admin work sessions CSV/PDF, Employee attendance CSV/PDF, Employee work sessions CSV/PDF, and Company Admin invoice print views.

The codebase does not include XLSX export support.

## Development Notes

- Reuse the existing `users` table for all authenticated roles.
- Keep company-owned records tenant-scoped through `company_id`.
- Use the existing permission catalog and `permission_user` pivot table for direct employee permissions.
- Reuse services such as `TaskWorkflowService`, `WorkTimerService`, `AttendanceService`, `CalendarService`, `InvoiceCalculator`, and `ProjectProgressService`.
- Keep Bootstrap and `public/assets` as the active UI layer unless the frontend is intentionally migrated.
- Prefer non-destructive setup commands for normal installation.

## Deployment Checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Configure `APP_URL` with HTTPS.
- Configure database, mail, queue, and scheduler settings.
- Run migrations.
- Run seeders only when appropriate for the target environment.
- Create the storage link if public uploads are used.
- Ensure `storage` and `bootstrap/cache` are writable.
- Build assets with `npm run build`.
- Cache config, routes, and views after environment configuration is final.
- Remove or rotate demo credentials.
- Never commit `.env`, `APP_KEY`, database credentials, mail credentials, or private service secrets.

## Troubleshooting

- Missing `APP_KEY`: run `php artisan key:generate`.
- Database connection fails: check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`; make sure the database exists before migrations.
- Uploaded files or images are not visible: run `php artisan storage:link`.
- Old routes, config, or views remain after changes: run `php artisan optimize:clear`.
- Queued jobs or notifications are not processing: run `php artisan queue:work`.
- Scheduled task due reminders are not running locally: run `php artisan schedule:work`.
- PowerShell blocks `npm run build`: run `npm.cmd run build`.
- Vite may warn that optional optimized font fallbacks need the `fontaine` package. The production build can still complete without it.

## Future / Optional Enhancements

- Client portal.
- Payment gateway integration.
- Two-factor authentication.
- Recurring company events.
- Advanced notification preferences.
- Additional accounting features.
- Advanced analytics dashboards.
- Browser-based end-to-end tests for critical workflows.
- Production backup, restore, and monitoring documentation.

## License / Academic Note

Elevanix was developed as a software engineering project demonstrating SaaS-based multi-company operations, role-based access, project/workforce management, finance tracking, reporting, and audit visibility.

Composer metadata currently lists the Laravel skeleton MIT license, but this repository does not include a standalone top-level `LICENSE` file. Confirm licensing before public distribution or open-source release.
