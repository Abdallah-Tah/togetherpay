# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**CODEX AGENT** - A group bill management platform with recurring Venmo/Cash App payment requests, reminders, and reporting. The owner pays the carrier with their own card while collecting from group members via fee-free P2P payment requests.

**Stack:**
- **Backend:** Laravel 12 + Livewire 3 (Volt) + Fortify
- **Frontend:** Livewire Volt components (web), Flutter (mobile - future)
- **API:** REST API with Laravel Sanctum authentication
- **Database:** PostgreSQL (production), SQLite (development/testing)
- **Queue/Cache:** Redis
- **Testing:** PestPHP
- **UI:** Flux UI components + Tailwind CSS v4

## Development Commands

### Setup & Running
```bash
# First-time setup (install dependencies, generate key, migrate, build assets)
composer setup

# Development server (runs concurrently: server, queue, logs, vite)
composer dev

# Alternative: Run services individually
php artisan serve              # Application server
php artisan queue:listen        # Queue worker
php artisan pail               # Real-time logs
npm run dev                    # Vite dev server with HMR
```

### Testing
```bash
# Run all tests
composer test
# OR
php artisan test

# Run specific test file
php artisan test --filter=GroupTest

# Run specific test method
php artisan test --filter=test_owner_can_create_group

# Run with coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Analyze code (if larastan is added)
./vendor/bin/phpstan analyse
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migrate with seeders
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_groups_table
```

### Queue & Jobs
```bash
# Run queue worker
php artisan queue:work

# Run specific job
php artisan queue:work --queue=high,default

# List failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Architecture

### Domain Model (Planned)

Core entities flow: **Users** → **Groups** → **Group Members** → **Billing Cycles** → **Payment Requests**

- **Users** - Authenticated users with roles (owner, member, admin)
- **Groups** - Bill-splitting groups owned by one user
- **Group Members** - Users participating in a group with share allocation and payment channel preferences
- **Billing Cycles** - Recurring periods (monthly/4-week) for bill collection
- **Payment Requests** - Individual payment requests sent to members via Venmo/Cash App
- **Dunning Events** - Reminders and retry attempts for pending requests
- **Exports** - Generated CSV/PDF reports

### Payment Request Drivers (Current)

Contract-based driver pattern for payment channels:

**`App\Contracts\PaymentRequestDriver`** interface with implementations:
- **VenmoDriver** - Generates deep-links (`venmo://paycharge?...`)
- **CashAppDriver** - Generates deep-links (`cashapp://send?...`)

MVP uses **deep-link strategy** (fee-free).

### Square Integration (Implemented)

**Two-Mode Payment System**:
- **Manual Mode** (default) - Deep-link requests, two-step confirmation (member marks → owner confirms)
- **Auto-Square Mode** - Recurring subscriptions via Square + Cash App Pay, automatic webhook confirmation

**Features**:
- Owner OAuth connection to Square merchant account
- Automatic Square Customer creation for group members
- Recurring subscription setup synchronized with billing cycles
- Webhook-based payment confirmation (auto_verified status)
- Backward compatible - existing manual groups unaffected

See `SQUARE_INTEGRATION_SUMMARY.md` for architecture details and `SQUARE_INTEGRATION_QUICKSTART.md` for developer guide.

### Scheduled Jobs (Planned)

Daily/hourly Laravel scheduler jobs:
1. **CreateUpcomingCyclesJob** - Generate new billing cycles
2. **GeneratePaymentRequestsJob** - Create payment requests for cycle members
3. **SendInitialRemindersJob** - Send initial notifications
4. **SendRetryRemindersJob** - Send follow-up reminders for pending requests
5. **CloseCyclesJob** - Mark cycles as closed after collection period

### Current Structure

- **Authentication:** Laravel Fortify with two-factor authentication support
- **Components:** Livewire Volt single-file components in `resources/views/livewire/`
- **UI Framework:** Flux UI component library (commercial Laravel UI kit)
- **Routes:** Volt-based routing in `routes/web.php` pointing to blade files
- **Styling:** Tailwind CSS v4 with Vite plugin

### API Structure (Planned)

REST API at `/api/v1` with Sanctum token authentication:
- `/auth/*` - Registration, login, logout
- `/groups/*` - Group CRUD, invitations, member management
- `/cycles/*` - Billing cycle and payment request management
- `/reports/*` - Group and member reports, exports

## Key Patterns

### Livewire Volt Components

Single-file components combine template, state, and logic:

```php
<?php
use function Livewire\Volt\{state, rules, mount};

state(['name' => '', 'email' => '']);
rules(['name' => 'required', 'email' => 'required|email']);

$save = function () {
    $this->validate();
    // Save logic
};
?>

<div>
    <!-- Template here -->
</div>
```

Register routes in `routes/web.php`:
```php
Volt::route('path', 'livewire.component-name')->name('route.name');
```

### Payment Driver Pattern (Implementation Pending)

```php
interface PaymentRequestDriver {
    public function buildLink(Member $member, Money $amount, string $note): string;
    public function sendRequest(Member $member, Money $amount, string $note): SendResult;
    public function parseWebhook(Request $request): WebhookResult;
}
```

Configuration in `config/payment_requests.php` with driver selection via `.env`.

### Feature Flags (Planned)

- `FF_PAYMENT_API` - Switch from deep-links to API-based requests
- `FF_SMS` - Enable SMS reminders
- `FF_PDF_EXPORTS` - Enable PDF report generation

## Testing Strategy

- **Unit Tests:** Domain logic, drivers, value objects
- **Feature Tests:** API endpoints, Livewire components, scheduled jobs
- **Test Database:** SQLite in-memory (configured in `phpunit.xml`)
- **Factories:** Use database factories for test data

Example test structure:
```bash
tests/
  Feature/
    Groups/GroupManagementTest.php
    PaymentRequests/RequestGenerationTest.php
  Unit/
    Drivers/VenmoDriverTest.php
```

## Configuration

### Environment Variables (MVP)

Critical `.env` settings:
```bash
DB_CONNECTION=pgsql              # PostgreSQL for production
QUEUE_CONNECTION=redis           # Redis for queues
CACHE_STORE=redis               # Redis for cache

# Sanctum (API auth)
SANCTUM_STATEFUL_DOMAINS=...
FRONTEND_URL=...

# Notifications
MAIL_MAILER=ses                 # Email via AWS SES
FCM_SERVER_KEY=...              # Push notifications

# Payment Drivers
PR_DRIVER=link                  # 'link' for deep-links, 'api' for future

# Storage
FILESYSTEM_DISK=s3
AWS_BUCKET=...
```

### Database

- **Development:** SQLite (`database/database.sqlite`)
- **Testing:** SQLite in-memory
- **Production:** PostgreSQL with proper indexing on high-traffic tables

## Dependencies

### PHP Packages (Current)
- `laravel/framework` ^12.0
- `laravel/fortify` - Authentication backend
- `livewire/volt` - Single-file Livewire components
- `livewire/flux` - UI component library

### PHP Packages (Planned)
- `laravel/sanctum` - API token authentication
- `laravel/horizon` - Queue monitoring
- `spatie/laravel-permission` - Role-based access control
- `spatie/laravel-activitylog` - Audit logging
- `barryvdh/laravel-dompdf` - PDF export generation

### Frontend
- Vite ^7.0
- Tailwind CSS ^4.0
- Laravel Vite Plugin
- Concurrently (for parallel dev processes)

## Workflow Notes

### Adding New Features

1. Create migration if database changes needed
2. Create/update models with relationships
3. Add Livewire Volt components or API controllers
4. Register routes in `routes/web.php` or `routes/api.php`
5. Write feature tests
6. Update this documentation

### Creating Migrations

Follow naming convention with date prefixes:
```bash
php artisan make:migration 2025_01_01_000001_create_groups_table
```

### Payment Request Flow (Planned)

1. Billing cycle created by scheduler
2. Payment requests generated for each member
3. Deep-links/API requests sent via notification channels
4. Members pay via Venmo/Cash App
5. Status tracked (pending → sent → paid/failed)
6. Dunning jobs handle reminders and retries
7. Cycle closes after grace period

### Reports & Exports (Planned)

- **Group Reports:** Collection rates, shortfalls, member heatmaps
- **Member Reports:** Payment history, on-time percentage, latency
- **Export Formats:** CSV (raw data), PDF (formatted summaries)
- **Storage:** S3 with signed temporary URLs

## Security Considerations

- **Authentication:** Sanctum tokens for API, session-based for web
- **Authorization:** Policies enforce owner-only group management
- **Validation:** Phone/email verification for members, handle verification for payment channels
- **Rate Limiting:** Applied to payment request generation and API endpoints
- **Audit Logging:** All critical actions logged via activity log
- **PCI Compliance:** MVP avoids card data; future premium will use certified payment processors

## Project Goals

**MVP Scope:**
- Create groups, invite members, set shares and cadence
- Generate monthly/4-week payment requests
- Deliver Venmo/Cash App links
- Track statuses, send reminders
- Owner reports and CSV/PDF exports

**Out of MVP Scope:**
- Card vaulting and auto-charge
- Money flow through platform
- Direct carrier integration
- Multi-currency support

**Post-MVP Roadmap:**
- API-based payment request drivers with webhooks
- Smart Pay page with app detection
- Premium auto-charge via Braintree/Adyen
- Virtual card for direct carrier payment
- Multi-owner roles and granular permissions
- Internationalization (EN/ES)
