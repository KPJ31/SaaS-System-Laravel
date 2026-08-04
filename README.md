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

All seeded demo login accounts use the password `Password@123`.

| Role | Name | Company | Username | Email | Status |
| --- | --- | --- | --- | --- | --- |
| Super Admin | Elevanix Super Admin | Platform | `superadmin` | `superadmin@elevanix.test` | `active` |
| Company Admin | NovaStack Software Admin | NovaStack Software | `novastack_admin` | `admin@novastack.test` | `active` |
| Company Admin | BrightForge Labs Admin | BrightForge Labs | `brightforge_admin` | `admin@brightforge.test` | `active` |
| Employee | Maya Fernando | NovaStack Software | `maya` | `employee1@elevanix.test` | `active` |
| Employee | Arun Silva | BrightForge Labs | `arun` | `employee2@elevanix.test` | `active` |

Seeded pending registration request:

| Company | Admin Name | Username | Email | Password | Status |
| --- | --- | --- | --- | --- | --- |
| Pending Studio | Pending Admin | `pending_admin` | `admin@pendingstudio.test` | `Password@123` | `pending` |

The pending registration request becomes a company admin login only after Super Admin approval.

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
- Shared responsive dashboard pagination UI for paginated lists

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
- Attendance check-in/check-out with daily status, late, half-day and early-departure tracking
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
- `attendances`

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

Work timer workflow:

1. Employee opens an assigned task.
2. Employee starts work if the task belongs to their company, is assigned to them, is not completed or cancelled, and no other timer is running.
3. The system creates one running `work_sessions` record and moves eligible tasks to `in_progress`.
4. Employee stops the active timer from the task page or dashboard.
5. The server stores `ended_at`, calculates `duration_minutes`, saves the note, and marks the session as `stopped`.

Attendance workflow:

1. Company Admin configures working hours under Company Settings.
2. Employee checks in once per configured working day.
3. The system calculates late status using the configured start time and grace period.
4. Employee checks out once per day.
5. The system calculates gross minutes, lunch deduction, net work minutes, full-day or half-day status, and early departure.
6. Company Admin reviews attendance from Attendance Overview and may correct records with a required reason.

Default attendance rules:

- Work start time: `08:30`
- Work end time: `17:00`
- Lunch break: `30` minutes
- Late grace period: `10` minutes
- Early departure grace period: `10` minutes
- Full-day target: `480` minutes
- Half-day minimum: `240` minutes
- Working days: Monday to Friday
- Lunch is deducted when gross attendance is at least five hours.

Automatic absence:

```bash
php artisan attendance:mark-absent
```

The command marks active Employees as `absent` for working days without attendance, or `on_leave` when an approved leave request exists. Add it to the scheduler after the working day ends in production.

Attendance permissions:

- Base Employee: `attendance.view-own`, `attendance.check-in`, `attendance.check-out`
- Optional Employee permissions: `attendance.view-all`, `attendance.edit`, `attendance.export`, `attendance.reports`

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

- 128 tests passed
- 372 assertions passed

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
