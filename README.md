# Elevanix ESSCMS - Smart Software Company Management System

Elevanix ESSCMS is a Laravel 13 multi-company SaaS management system for software companies. It supports public company registration, Super Admin approval, subscription plans, company workspaces, employees, clients, project requests, projects, tasks, work sessions, invoices, payments, feedback, reports, notifications, audit logs, and settings.

Super Admin manages the SaaS platform. Company Admin and Employee users work inside one approved company and require an active or trialing subscription.

## Technology Stack

- Laravel 13.8
- PHP 8.3+
- MySQL
- Blade
- Bootstrap-oriented custom CSS
- Vanilla JavaScript
- Laravel Mail and Notifications
- Laravel password reset
- Laravel database notifications
- DomPDF for PDF exports
- Pest for automated tests
- Vite for frontend build tooling

Custom application CSS and JavaScript are loaded from:

- `public/assets/css/app.css`
- `public/assets/js/app.js`

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

Update `.env` with your local database, app URL, mail, queue, and filesystem settings. Do not commit real secrets.

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

On Windows PowerShell, use this for the production asset build:

```bash
npm.cmd run build
```

## Useful Commands

```bash
php artisan serve
php artisan queue:listen
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
npm.cmd run build
```

Composer also includes a development script that runs the server, queue listener, and Vite together:

```bash
composer run dev
```

## Demo Accounts

After running the seeders:

- Super Admin: `superadmin` or `superadmin@elevanix.test` / `Password@123`
- Company Admin: `novastack_admin` or `admin@novastack.test` / `Password@123`
- Company Admin: `brightforge_admin` or `admin@brightforge.test` / `Password@123`
- Employee: `maya` or `employee1@elevanix.test` / `Password@123`
- Employee: `arun` or `employee2@elevanix.test` / `Password@123`

## User Roles

- `super_admin`: Platform owner role.
- `company_admin`: Company workspace administrator.
- `employee`: Company staff member.

## Status Values

Company statuses:

- `pending`
- `active`
- `suspended`
- `rejected`
- `expired`

User statuses:

- `active`
- `inactive`
- `suspended`

Subscription statuses:

- `trialing`
- `active`
- `past_due`
- `expired`
- `cancelled`

Request statuses:

- `pending`
- `approved`
- `rejected`

## Main Features

Public and Auth:

- Landing page
- Company registration request
- Registration submitted confirmation
- Email or username login
- Forgot password
- Reset password
- POST-only logout
- Role-based dashboard redirect

Super Admin:

- Dashboard
- Expanded platform dashboard with company, user, project, subscription, payment and notification metrics
- Company registration request approval/rejection
- Company list, details, edit, activation, and suspension
- Subscription plan CRUD and activation/deactivation
- Company subscription list, details, update, and status management
- Platform user list, details, status management, and password reset email
- Payment review and status management
- Reports with CSV/PDF export
- Project monitoring, task monitoring and subscription expiry reports
- Notifications
- Audit logs
- System settings
- Profile and password update

Company Admin:

- Dashboard
- Expanded dashboard with leave, work-hour, payment and activity insights
- Company profile view/edit
- Employee CRUD, status management, and password reset email
- Client CRUD and status management
- Project request review, approve, reject, update, and convert to project
- Project CRUD
- Employee assignment/removal for projects
- Task CRUD and status updates
- Task review, comments and task file uploads
- Work session list and CSV export
- Work session correction with required reason
- Leave request review and approval/rejection
- Company document upload, download and deletion
- Payment CRUD, verification, and rejection
- Invoice CRUD, print, send, and mark paid
- Feedback moderation
- Notifications
- Reports with CSV/PDF export
- Activity logs
- Company settings
- Profile and password update

Employee:

- Employee dashboard with metrics, charts, active timer, notifications, and activity
- My Projects list and details with assigned task and document visibility
- My Tasks list, details, progress updates, status workflow, comments, and file uploads
- Start/stop work timer with one active timer per employee
- Work sessions list with filters, totals, and CSV export
- My Documents list with project/task/date/type filters
- Leave requests with create, edit pending, cancel pending, and status tracking
- Personal performance score and trend chart
- Notifications with mark one/all read
- Activity history scoped to the employee
- Profile update and secure password change

## Database Tables

Core application tables:

- `companies`
- `company_registration_requests`
- `users`
- `clients`
- `project_requests`
- `projects`
- `project_user`
- `tasks`
- `work_sessions`
- `task_comments`
- `work_files`
- `leave_requests`
- `payments`
- `invoices`
- `invoice_items`
- `audit_logs`
- `company_settings`
- `notifications`
- `subscription_plans`
- `subscriptions`
- `system_settings`
- `feedback`

Laravel framework tables:

- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`

Important schema note:

The newer company admin and Super Admin fields have been merged into the original create-table migrations. Separate alteration migrations for company slug, audit log fields, company fields, project fields, task fields, client/request fields, invoice fields, and payment platform fields were removed so fresh installs create the full schema directly.

## Seeded Subscription Plans

Starter:

- Monthly price: 49
- Annual price: 499
- Employee limit: 10
- Client limit: 25
- Project limit: 20
- Storage limit: 2048 MB
- Trial days: 14

Professional:

- Monthly price: 129
- Annual price: 1290
- Employee limit: 50
- Client limit: 100
- Project limit: 100
- Storage limit: 10240 MB
- Trial days: 14

Enterprise:

- Monthly price: 299
- Annual price: 2990
- Employee limit: 250
- Client limit: 500
- Project limit: 500
- Storage limit: 102400 MB
- Trial days: 30

## Seeder Order

- `SystemSettingSeeder`
- `SubscriptionPlanSeeder`
- `SuperAdminSeeder`
- `DemoCompanySeeder`
- `DemoSubscriptionSeeder`
- `DemoUserSeeder`
- `DemoClientSeeder`
- `DemoProjectSeeder`
- `DemoFinanceSeeder`
- `DemoWorkSessionSeeder`
- `CompanyRegistrationRequestSeeder`
- `AuditLogSeeder`

## Main Workflows

Company registration:

1. Visitor submits company and admin details.
2. System stores a pending registration request.
3. Super Admin reviews the request.
4. Approval creates the company, company admin, company settings, and subscription.
5. Rejection stores a rejection reason and notifies the requester.

Login:

1. User logs in with email or username.
2. System checks credentials, user status, role, company approval, and subscription.
3. User is redirected to the correct dashboard.

Company Admin operations:

1. Company Admin manages employees, clients, projects, tasks, invoices, payments, and feedback.
2. Records are scoped to the current company.
3. Subscription limits protect employee, client, and project capacity.
4. Reports and activity logs summarize company work.

## Security

- Password hashing through Laravel casts
- Login rate limiting
- Session regeneration after login
- CSRF-protected forms
- POST-only logout
- Role middleware
- Company approval middleware
- Subscription active middleware
- Tenant-aware policies for company-owned records
- Work timer duplicate active session prevention
- Audit metadata sanitization for passwords, tokens, secrets, and SMTP values
- Company registration logo validation
- Database transactions for approval/rejection workflows

## Testing

Run:

```bash
php artisan test
```

Current verified result:

- 53 tests passed
- 135 assertions passed

The test environment uses in-memory SQLite from `phpunit.xml`.

Test coverage includes:

- Public pages
- Login with email and username
- Password reset pages
- Inactive user blocking
- Pending company blocking
- Role access restrictions
- Company registration
- Super Admin approval/rejection
- Subscription plan creation
- Active subscription requirement
- Tenant-scoped queries
- Cross-company access denial
- Employee access to assigned own-company work
- Work timer duplicate prevention and stop duration
- Employee dashboard access
- Employee task isolation inside and across companies
- Employee work timer start/stop and duplicate active timer prevention
- Employee task submission for review
- Employee leave request creation
- Employee performance page access
- Suspended employee dashboard blocking
- Company Admin leave review and cross-company leave protection
- Company Admin document upload/download
- Company Admin work session correction with reason
- Super Admin project and subscription-expiry report access
- System settings storage

## Reports And Documentation

Project documentation files:

- `README.md`
- `IMPLEMENTATION_REPORT.md`
- `TEST_REPORT.md`
- `SYSTEM_EXPLANATION.txt`

## Deployment Checklist

- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Set a valid `APP_URL`
- Configure MySQL credentials
- Configure SMTP credentials
- Run `composer install --no-dev --optimize-autoloader`
- Run `npm.cmd run build` or deploy prebuilt assets
- Run `php artisan migrate --force`
- Run `php artisan storage:link`
- Configure queue workers if database notifications/jobs are queued
- Set correct permissions for `storage` and `bootstrap/cache`
- Verify login, company approval, company dashboard, invoice, payment, and report export

## Future Improvements

- Employee timer start/stop UI
- Richer employee task/project pages
- Client portal
- Subscription billing integration
- Plan upgrade/downgrade workflow
- Subscription expiry reminders
- Branded email templates
- Wider audit coverage
- Two-factor authentication
