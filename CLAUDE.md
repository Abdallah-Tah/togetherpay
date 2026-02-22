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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.15
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/flux (FLUXUI_FREE) - v2
- livewire/livewire (LIVEWIRE) - v4
- livewire/volt (VOLT) - v1
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== fluxui-free/core rules ===

## Flux UI Free

- This project is using the free edition of Flux UI. It has full access to the free components and variants, but does not have access to the Pro components.
- Flux UI is a component library for Livewire. Flux is a robust, hand-crafted UI component library for your Livewire applications. It's built using Tailwind CSS and provides a set of components that are easy to use and customize.
- You should use Flux UI components when available.
- Fallback to standard Blade components if Flux is unavailable.
- If available, use the `search-docs` tool to get the exact documentation and code snippets available for this project.
- Flux UI components look like this:

<code-snippet name="Flux UI Component Example" lang="blade">
    <flux:button variant="primary"/>
</code-snippet>

### Available Components
This is correct as of Boost installation, but there may be additional components within the codebase.

<available-flux-components>
avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip
</available-flux-components>

=== livewire/core rules ===

## Livewire

- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` Artisan command to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle Hook Examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire Component Test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
    $this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>

=== volt/core rules ===

## Livewire Volt

- This project uses Livewire Volt for interactivity within its pages. New pages requiring interactivity must also use Livewire Volt.
- Make new Volt components using `php artisan make:volt [name] [--test] [--pest]`.
- Volt is a class-based and functional API for Livewire that supports single-file components, allowing a component's PHP logic and Blade templates to coexist in the same file.
- Livewire Volt allows PHP logic and Blade templates in one file. Components use the `@volt` directive.
- You must check existing Volt components to determine if they're functional or class-based. If you can't detect that, ask the user which they prefer before writing a Volt component.

### Volt Functional Component Example

<code-snippet name="Volt Functional Component Example" lang="php">
@volt
<?php
use function Livewire\Volt\{state, computed};

state(['count' => 0]);

$increment = fn () => $this->count++;
$decrement = fn () => $this->count--;

$double = computed(fn () => $this->count * 2);
?>

<div>
    <h1>Count: {{ $count }}</h1>
    <h2>Double: {{ $this->double }}</h2>
    <button wire:click="increment">+</button>
    <button wire:click="decrement">-</button>
</div>
@endvolt
</code-snippet>

### Volt Class Based Component Example
To get started, define an anonymous class that extends Livewire\Volt\Component. Within the class, you may utilize all of the features of Livewire using traditional Livewire syntax:

<code-snippet name="Volt Class-based Volt Component Example" lang="php">
use Livewire\Volt\Component;

new class extends Component {
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }
} ?>

<div>
    <h1>{{ $count }}</h1>
    <button wire:click="increment">+</button>
</div>
</code-snippet>

### Testing Volt & Volt Components
- Use the existing directory for tests if it already exists. Otherwise, fallback to `tests/Feature/Volt`.

<code-snippet name="Livewire Test Example" lang="php">
use Livewire\Volt\Volt;

test('counter increments', function () {
    Volt::test('counter')
        ->assertSee('Count: 0')
        ->call('increment')
        ->assertSee('Count: 1');
});
</code-snippet>

<code-snippet name="Volt Component Test Using Pest" lang="php">
declare(strict_types=1);

use App\Models\{User, Product};
use Livewire\Volt\Volt;

test('product form creates product', function () {
    $user = User::factory()->create();

    Volt::test('pages.products.create')
        ->actingAs($user)
        ->set('form.name', 'Test Product')
        ->set('form.description', 'Test Description')
        ->set('form.price', 99.99)
        ->call('create')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Test Product')->exists())->toBeTrue();
});
</code-snippet>

### Common Patterns

<code-snippet name="CRUD With Volt" lang="php">
<?php

use App\Models\Product;
use function Livewire\Volt\{state, computed};

state(['editing' => null, 'search' => '']);

$products = computed(fn() => Product::when($this->search,
    fn($q) => $q->where('name', 'like', "%{$this->search}%")
)->get());

$edit = fn(Product $product) => $this->editing = $product->id;
$delete = fn(Product $product) => $product->delete();

?>

<!-- HTML / UI Here -->
</code-snippet>

<code-snippet name="Real-Time Search With Volt" lang="php">
    <flux:input
        wire:model.live.debounce.300ms="search"
        placeholder="Search..."
    />
</code-snippet>

<code-snippet name="Loading States With Volt" lang="php">
    <flux:button wire:click="save" wire:loading.attr="disabled">
        <span wire:loading.remove>Save</span>
        <span wire:loading>Saving...</span>
    </flux:button>
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest 4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest 4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
