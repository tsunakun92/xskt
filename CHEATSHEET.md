# BASE Cheatsheet

Quick reference for common commands, features, and development guides.

## Table of Contents

1. [Common Commands](#common-commands)
2. [Menu Management](#menu-management)
3. [Multilanguage](#multilanguage)
4. [Components](#components)
5. [Model Features](#model-features)
6. [Permission Management](#permission-management)
7. [Logging](#logging)
8. [Cache Management](#cache-management)
9. [Utility Functions](#utility-functions)
10. [Changelog Management](#changelog-management)
11. [Database Structure](#database-structure)
12. [GraphQL API](#graphql-api)
13. [Module Development](#module-development)
14. [Development Workflow](#development-workflow)
15. [Technology Stack](#technology-stack)

---

## Common Commands

### Migration

```bash
# Create migration
php artisan make:migration create_users_table --table=users

# Run migrations
php artisan migrate
php artisan migrate --seed
php artisan migrate:rollback
php artisan migrate:fresh
```

### Seeding

```bash
# Run all seeders
php artisan db:seed

# Module seeder
php artisan module:make-seed UsersTableSeeder Admin
php artisan module:seed Admin
```

### Cache Management

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan clearall  # Clear all cache
```

### Testing

```bash
# Setup test environment
php artisan migrate --env=testing

# Run tests
php artisan test
php artisan test --filter=TestName
php artisan test --coverage
```

### Code Formatting

```bash
# Format code with Pint (automatically fixes use statement order)
php artisan pint
php artisan format  # Alias for pint

# Format specific files or directories
php artisan pint app/Models
php artisan pint app/Http/Controllers/UserController.php

# Check code style without fixing (skips use order fix)
php artisan pint --test

# Fix use statement order manually (if needed)
php check_use_order.php --fix
```

**Note:** When running `php artisan pint` or `php artisan format`, the command automatically runs `php check_use_order.php --fix` after Pint completes successfully. This ensures use statements are properly ordered. The use order fix is skipped when using `--test` mode.

**Auto-format Workflow**: After completing any code changes, refactoring, or feature implementation, always run `php artisan format` to ensure code formatting compliance before finishing the task.

### Assets

```bash
yarn                    # Install packages
yarn add <package>      # Add package
yarn remove <package>   # Remove package
yarn dev               # Development build
yarn build             # Production build
```

### Maintenance

```bash
php artisan down       # Enable maintenance mode
php artisan up         # Disable maintenance mode
php artisan storage:link
```

---

## Menu Management

### Route Configuration

```php
// Format
Route::{method}('/{module}/{resource}', [Controller::class, 'action'])
     ->name('{resource}.{action}');

// Example
Route::get('/admin/user-masters', [UserController::class, 'index'])
     ->name('user-masters.index');
```

### Menu Structure

-   Route name: `{resource}.{action}`
-   Permission settings
-   Display name
-   Icon (optional)
-   Parent-child relationships

---

## Multilanguage

### Structure

```
web/lang/
├── en/
│   ├── app.php
│   ├── auth.php
│   ├── crud.php
│   └── validation.php
└── ja/
    ├── app.php
    ├── auth.php
    ├── crud.php
    └── validation.php
```

### Module Languages

```
web/Modules/Admin/lang/
├── en/
│   └── app.php
└── ja/
    └── app.php
```

### Usage

```php
// Blade templates
{{ __('app.menu.home') }}
{{ __('crud.messages.created', ['name' => 'User']) }}

// Controllers
__('crud.messages.created', ['name' => __('app.menu.user')])

// Module specific
__('admin::app.menu.home')  // Force module lang
```

### Adding Translations

```php
// app.php
'menu' => [
    'home' => 'Home',
    'user' => 'User',
],

// crud.php
'messages' => [
    'created' => ':name created successfully',
    'updated' => ':name updated successfully',
],
```

---

## Components

### Using Existing Components

```blade
<!-- Button -->
<x-button type="submit" color="red">Save</x-button>

<!-- Form Input -->
<x-input name="email" type="email" label="Email" required />

<!-- Container -->
<x-container>
    <x-slot name="header">
        <h1>Title</h1>
    </x-slot>
    Content here
</x-container>
```

### Creating Components

```blade
<!-- resources/views/components/alert.blade.php -->
@props(['type' => 'info'])
<div class="alert alert-{{ $type }}">
    {{ $slot }}
</div>

<!-- Usage -->
<x-alert type="success">Data saved!</x-alert>
```

### Slots and Attributes

```blade
<x-modal>
    <x-slot name="title">Confirm Delete</x-slot>
    <x-slot name="body">Are you sure?</x-slot>
    <x-slot name="footer">
        <x-button>Cancel</x-button>
        <x-button variant="danger">Delete</x-button>
    </x-slot>
</x-modal>
```

---

## Model Features

### BaseModel Configuration

```php
class YourModel extends BaseModel {
    // Form fields
    protected $fillable = [
        'name', 'email', 'status'
    ];

    // Filter fields
    protected $filterable = [
        'name', 'email', 'roles_id'
    ];

    // LIKE filter fields
    protected $filterLike = [
        'name', 'email'
    ];

    // DataTable columns
    protected $datatableColumns = [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'status' => 'Status'
    ];
}
```

### Usage in Controllers

```php
// Get form fields
$formFields = YourModel::getFormFields('route_name');

// Get filter fields
$filterFields = YourModel::getFilterFields('route_name');
```

---

## Permission Management

### Overview

The permission system uses a simple and consistent naming convention:

-   **Model level**: `canAccess()` – checks if a user/role can access a permission
-   **Helper level**: `can_access()` – helper function for controllers/views
-   **Blade directive**: `@canAccess()` – Blade directive that calls the helper

### Permission Structure

Permissions follow the format: `{resource}.{action}`

Examples:

-   `users.index` – View user list
-   `users.show` – View user detail
-   `users.create` – Create a new user
-   `users.edit` – Edit a user
-   `users.destroy` – Delete a user

### Usage in Blade Templates

```blade
{{-- Check permission and show content --}}
@canAccess('users.index')
    <a href="{{ route('users.index') }}">View Users</a>
@endcanAccess

{{-- Check permission for create button --}}
@canAccess('users.create')
    <x-button href="{{ route('users.create') }}">Create User</x-button>
@endcanAccess

{{-- Check permission inside a loop --}}
@foreach ($actions as $action)
    @canAccess($action['route'])
        <a href="{{ route($action['route'], $row) }}">{{ $action['label'] }}</a>
    @endcanAccess
@endforeach
```

### Usage in Controllers

```php
class UserController extends BaseAdminController {
    public function index() {
        // Recommended: use BaseAdminController permission check
        $this->checkPermission('users.index');

        // Or check directly with the helper
        if (!can_access('users.index')) {
            abort(403, 'Unauthorized');
        }

        // Controller logic...
    }
}
```

### Usage in Models

```php
use Modules\Admin\Models\User;
use Modules\Admin\Models\Role;

// Check permission for the current user
$user = auth()->user();
if ($user->canAccess('users.index')) {
    // User has permission
}

// Check permission for a specific role
$role = Role::find(1);
if ($role->canAccess('users.index')) {
    // Role has permission
}

// Static method to check role permission (useful in views)
if (Role::hasPermission('users.index', $roleId)) {
    // Role has permission
}
```

### Helper Function

```php
// Helper function defined in app/Utils/helpers.php
can_access(string $permission): bool;

// Example usage
if (can_access('users.index')) {
    // User is logged in and has this permission
    // Super admin automatically passes all permission checks
}
```

### Permission Flow

```
User Request
    ↓
can_access('route.name') [Helper]
    ↓
auth()->user()->canAccess('route.name') [User Model]
    ↓
$user->rRole->canAccess('route.name') [Role Model]
    ↓
$role->rPermissions->contains('key', 'route.name')
    ↓
Return true/false
```

### Super Admin

Super admin has all permissions by default and does not need specific checks:

```php
// Inside can_access() helper
if (auth()->user()->isSuperAdmin()) {
    return true; // Bypass all permission checks
}
```

### Best Practices

1. **Always check permission in controllers** before running business logic
2. **Use `@canAccess` in Blade** instead of manual permission checks
3. **Name permissions using the `{resource}.{action}` convention**
4. **Use `canAccess()` on models** for domain/business logic
5. **Use `can_access()` helper** in controllers and views

---

## Logging

### Overview

The application uses `LogHandler` for centralized logging. It automatically adds user context and request information to all log entries.

### Basic Usage

```php
use Modules\Logging\Utils\LogHandler;

// Debug level - for detailed debugging information
LogHandler::debug('Debug message', ['key' => 'value']);

// Info level - for general information
LogHandler::info('User logged in', ['user_id' => 1]);

// Warning level - for warnings that don't stop execution
LogHandler::warning('Low disk space', ['available' => '10MB']);

// Error level - for errors that need attention
LogHandler::error('Failed to process payment', ['order_id' => 123]);

// Critical level - for critical errors
LogHandler::critical('Database connection failed');
```

### Specialized Logging Methods

```php
// Database errors - automatically logs to database channel
LogHandler::databaseError('Transaction failed', $exception);

// Cache operations - automatically logs to cache channel
LogHandler::cache('Cache cleared', ['key' => 'user:1']);
```

### Usage in Controllers

```php
use Modules\Logging\Utils\LogHandler;

class UserController extends BaseAdminController {
    public function store(Request $request) {
        try {
            $user = User::create($request->all());

            LogHandler::info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return redirect()->route('users.index');
        } catch (\Exception $e) {
            LogHandler::error('Failed to create user', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return back()->withErrors(['error' => 'Failed to create user']);
        }
    }
}
```

### Usage in Models

```php
use Modules\Logging\Utils\LogHandler;

class User extends BaseModel {
    protected static function boot() {
        parent::boot();

        static::creating(function ($user) {
            LogHandler::info('Creating new user', ['email' => $user->email]);
        });

        static::created(function ($user) {
            LogHandler::info('User created', ['user_id' => $user->id]);
        });

        static::updating(function ($user) {
            LogHandler::info('Updating user', ['user_id' => $user->id]);
        });

        static::deleting(function ($user) {
            LogHandler::warning('Deleting user', ['user_id' => $user->id]);
        });
    }
}
```

### Log Context

All logs automatically include:

-   **User context**: Current user ID and username (if authenticated)
-   **Request context**: HTTP method, URI, and IP address (for web requests)
-   **Environment**: Console or web environment

### Best Practices

1. **Use appropriate log levels**: debug for development, info for normal operations, warning for issues, error for failures
2. **Include context**: Always add relevant data in the context array
3. **Log important operations**: CRUD operations, permission checks, errors
4. **Don't log sensitive data**: Avoid logging passwords, tokens, or personal information
5. **Use specialized methods**: Use `databaseError()` for DB errors, `cache()` for cache operations

---

## Cache Management

### Overview

The application uses `CacheHandler` for centralized cache management with comprehensive logging. It supports two cache types:

- **Static Cache**: Request-scoped, automatically cleared after each request
- **Persistent Cache**: Uses Laravel Cache, survives between requests

### Basic Usage

```php
use App\Utils\CacheHandler;

// Get from cache (static by default)
$value = CacheHandler::get('cache_key', $default);

// Set cache
CacheHandler::set('cache_key', $value);

// Remember pattern (get or execute callback)
$value = CacheHandler::remember('cache_key', function() {
    return expensiveOperation();
});

// Check if exists
if (CacheHandler::has('cache_key')) {
    // Cache exists
}

// Forget cache
CacheHandler::forget('cache_key');

// Flush all cache
CacheHandler::flush(CacheHandler::TYPE_STATIC);
```

### Cache Types

```php
// Static cache (request-scoped, default)
CacheHandler::get('key', $default, CacheHandler::TYPE_STATIC);

// Persistent cache (survives requests)
CacheHandler::get('key', $default, CacheHandler::TYPE_PERSISTENT);
CacheHandler::set('key', $value, 3600, CacheHandler::TYPE_PERSISTENT); // TTL in seconds
```

### Helper Functions

```php
// Quick access via helpers
$value = cache_get('key', $default);
cache_set('key', $value);
cache_forget('key');
$value = cache_remember('key', function() {
    return expensiveOperation();
});
```

### Pattern-based Clearing

```php
// Forget multiple keys by pattern (static cache only)
CacheHandler::forgetByPattern('role:code:*');
```

### Tagged Cache (Persistent only)

```php
// Get/Set with tags
$value = CacheHandler::getTagged(['users', 'roles'], 'user:1', $default);
CacheHandler::setTagged(['users', 'roles'], 'user:1', $value, 3600);

// Flush by tags
CacheHandler::flushTagged(['users']);
```

### Statistics

```php
// Get cache statistics
$stats = CacheHandler::getStats();
// Returns: ['hits', 'misses', 'sets', 'forgets', 'flushes', 'hit_rate', 'static_keys']

// Reset statistics
CacheHandler::resetStats();
```

### Usage in Models

```php
class Role extends BaseModel {
    public static function getByCode(string $code): ?Role {
        $cacheKey = "role:code:{$code}";
        
        return CacheHandler::remember($cacheKey, function () use ($code) {
            return self::where('code', $code)->first();
        }, null, CacheHandler::TYPE_STATIC);
    }
    
    public static function clearRoleCache(): void {
        CacheHandler::forgetByPattern('role:code:*');
    }
}
```

### Logging

All cache operations are automatically logged via `LogHandler` with the `cache` channel:

- Cache hits/misses with duration
- Cache sets with value type and TTL
- Cache forgets/flushes
- Errors if any

### Best Practices

1. **Use static cache** for data only needed within a request
2. **Use persistent cache** for data shared between requests
3. **Use tags** for related cache groups
4. **Set appropriate TTL** to balance freshness and performance
5. **Clear cache** when data changes to ensure freshness

---

## Utility Functions

### Helper Functions (`app/Utils/helpers.php`)

#### String Helpers

```php
// Convert string cases
singular_case('categories');     // 'category'
plural_case('category');          // 'categories'
camel_case('product_category');   // 'productCategory'
studly_case('product_category');  // 'ProductCategory'
kebab_case('product_category');   // 'product-category'
snake_case('ProductCategory');    // 'product_category'
title_case('product_category');   // 'Product Category'

// Trim text
trim_text('Long text here', 10);  // 'Long text ...'

// Trim array/object for logging
trim_array_object($largeArray, 100, 10, 3);
```

#### Route Helpers

```php
// Get route name and action
get_route_name('users.index');    // 'users'
get_route_action('users.index');   // 'index'

// Check if route exists
has_route('users.index');          // true/false
```

#### Permission Helpers

```php
// Check permission
can_access('users.index');         // true/false

// Get permission label
get_permission_label('users.index'); // 'View List'
```

#### Data Helpers

```php
// Get value from array or object
get_value($data, 'key', 'default');

// Format numbers
fmt_number(1234.56);              // '1,235'
fmt_number(1234.56, '-', 2);      // '1,234.56'

// Convert between array and string
array_to_string([1, 2, 3]);       // '1,2,3'
string_to_array('1,2,3');         // [1, 2, 3]
```

#### Translation Helper

```php
// Translate with fallback
transOrDefault('key');             // Translated value or 'key'
transOrDefault('key', 'Default');  // Translated value or 'Default'
```

### Ajax Response Handler (`app/Utils/AjaxHandle.php`)

Standardized JSON responses for AJAX requests with automatic logging.

```php
use App\Utils\AjaxHandle;

// Success response
return AjaxHandle::success('User created', ['user_id' => 1]);

// Error response
return AjaxHandle::error('Failed to create user', $exception, [], 500);

// Validation error response
return AjaxHandle::validationError($validator, 'Validation failed');

// With custom fields
return AjaxHandle::success('Success', $data, ['custom' => 'value']);
```

**Response Format:**

```json
{
    "success": true,
    "message": "User created",
    "data": { "user_id": 1 }
}
```

### Date/Time Utilities (`app/Utils/DateTimeExt.php`)

#### Common Date Formats

```php
use App\Utils\DateTimeExt;

DateTimeExt::DATE_FORMAT_1;  // 'Y-m-d H:i:s' (database format)
DateTimeExt::DATE_FORMAT_3;  // 'd/m/Y' (display format)
DateTimeExt::DATE_FORMAT_4;  // 'Y-m-d' (date only)
DateTimeExt::DATE_FORMAT_6;  // 'Y/m/d'
```

#### Get Current Date/Time

```php
// Current datetime with timezone
DateTimeExt::getCurrentDateTime();                    // '2024-01-15 10:30:00'
DateTimeExt::getCurrentDateTime(DateTimeExt::DATE_FORMAT_3); // '15/01/2024'

// System datetime
DateTimeExt::getCurrentDateTimeSystem();
```

#### Format Date/Time

```php
// Format datetime
DateTimeExt::formatDateTime('2024-01-15 10:30:00', DateTimeExt::DATE_FORMAT_3);
// Returns: '15/01/2024'

// Convert between formats
DateTimeExt::convertDateTime('2024-01-15', DateTimeExt::DATE_FORMAT_4, DateTimeExt::DATE_FORMAT_3);
// Returns: '15/01/2024'
```

#### Date Calculations

```php
// Add/subtract days
DateTimeExt::addDays('2024-01-15', 5, DateTimeExt::DATE_FORMAT_4);  // '2024-01-20'
DateTimeExt::subDays('2024-01-15', 5, DateTimeExt::DATE_FORMAT_4);  // '2024-01-10'

// Add/subtract months
DateTimeExt::addMonths('2024-01-15', 2, DateTimeExt::DATE_FORMAT_4); // '2024-03-15'
DateTimeExt::subMonths('2024-03-15', 2, DateTimeExt::DATE_FORMAT_4); // '2024-01-15'

// Add/subtract years
DateTimeExt::addYears('2024-01-15', 1, DateTimeExt::DATE_FORMAT_4); // '2025-01-15'
DateTimeExt::subYears('2024-01-15', 1, DateTimeExt::DATE_FORMAT_4); // '2023-01-15'
```

#### Date Differences

```php
// Difference in days
DateTimeExt::diffDate('2024-01-15', '2024-01-20');  // 5

// Difference in months
DateTimeExt::diffMonth('2024-01-15', '2024-03-15'); // 2

// Difference in years
DateTimeExt::diffYear('2022-01-15', '2024-01-15');  // 2
```

#### Special Dates

```php
// Get first/last day of month
DateTimeExt::getFirstDayOfMonth('2024-01-15');  // '2024-01-01'
DateTimeExt::getLastDayOfMonth('2024-01-15');   // '2024-01-31'

// Get first/last day of year
DateTimeExt::getFirstDayOfYear('2024-06-15');   // '2024-01-01'
DateTimeExt::getLastDayOfYear('2024-06-15');   // '2024-12-31'
```

#### Date Validation

```php
// Check if date is null
DateTimeExt::isDateNull('0000-00-00');          // true
DateTimeExt::isDateNull('2024-01-15');         // false

// Validate date format
DateTimeExt::isValidDate('2024-01-15');        // true
DateTimeExt::isValidDate('invalid-date');       // false

// Validate start before end
DateTimeExt::validateStartBeforeEnd('2024-01-15', '10:00', '2024-01-15', '11:00');
// Returns: false (start is before end)
```

#### Format Date and Time Separately

```php
$result = DateTimeExt::formatDateAndTime('2024-01-15 10:30:00');
// Returns: ['date' => '2024/01/15', 'time' => '10:30']
```

### Database Transaction Handler (`app/Utils/SqlHandler.php`)

Handle database transactions with automatic rollback on errors.

```php
use App\Utils\SqlHandler;

$success = SqlHandler::handleTransaction(function () {
    $user = User::create($data);
    $profile = Profile::create(['user_id' => $user->id]);

    return true; // Return true to commit, false to rollback
});

if ($success) {
    // Transaction committed
} else {
    // Transaction rolled back
    $error = Session::get('transaction_error');
}
```

### Domain Constants (`app/Utils/DomainConst.php`)

Common constants used throughout the application.

```php
use App\Utils\DomainConst;

DomainConst::VALUE_ZERO;              // 0
DomainConst::VALUE_ONE;               // 1
DomainConst::DEFAULT_PAGE_SIZE;       // 10
DomainConst::ACTION_CREATE;           // 'create'
DomainConst::ACTION_EDIT;              // 'edit'
DomainConst::ACTION_INDEX;             // 'index'
DomainConst::MAX_LENGTH_LOG_DATA;      // 1000
```

---

## Changelog Management

### File Structure

```
web/changelog/
├── v1.0.0.md
├── v1.1.0.md
└── v2.0.0.md
```

### Changelog Format

```markdown
Brief description of the release

Version: 1.0.0
Date: 2025-01-02

Added:

-   New feature 1
-   New feature 2

Changed:

-   Modified functionality
-   Updated dependencies

Fixed:

-   Bug fix 1
-   Security patch

Removed:

-   Deprecated feature

Security:

-   Security enhancement
```

### Access

-   URL: `https://base.test/admin/changelog`
-   Navigate via admin menu

---

## Database Structure

### Key Tables

-   `users` - User accounts
-   `roles` - User roles
-   `permissions` - System permissions
-   `role_permissions` - Role-permission mapping

### Relationships

```
users }o--|| roles
role_permissions }o--|| roles
role_permissions }o--|| permissions
```

---

## GraphQL API

### Endpoint

```
POST /graphql
```

### Naming Convention (Project Standard)

- **Root fields**: All `Query` / `Mutation` field names must use **snake_case**.
- **Arguments**: Prefer **snake_case** (already used widely in the schema, e.g. `new_pass`, `device_token`).
- **Renaming fields**: When changing an existing field name, keep the old name as an alias marked with `@deprecated(...)` for backward compatibility, and update docs/tests to the new snake_case name.

### Authentication Flow

1. API Key validation (middleware)
2. Sanctum authentication (middleware)
3. User context established
4. Query/mutation execution

### GraphQL Types

- User, Profile, Role, Company
- Workshift, WorkRegister
- Setting, Config
- MobilePermission, UserData

### GraphQL Queries

- `user_view` - View user
- `user_permissions` - Get user permissions
- `user_data` - Get user data
- `user_list` - List users
- `work_register_list` - Get work register list
- `work_register_view` - View work register
- `workshift_list` - Get workshift list
- `workshift_view` - View workshift
- `company_list` - Get company list
- `company_view` - View company
- `setting_list` - Get settings list
- `setting_view` - View setting
- `setting_by_user` - Get settings by user
- `customer_home` - Customer home
- `customer_section_search` - Customer section search
- `customer_section_info` - Customer section info

### GraphQL Mutations

**Authentication:**
- `login` - User login
- `logout` - User logout
- `register_customer_with_email` - User registration
- `verify_register_otp` - Verify registration OTP

**Password:**
- `change_password` - Change password
- `forgot_password` - Request password reset

**OTP:**
- `verify_otp` - Verify OTP

**Data:**
- `user_create` - Create user
- `user_update` - Update user
- `user_delete` - Delete user
- `workshift_create` - Create workshift
- `workshift_update` - Update workshift
- `workshift_delete` - Delete workshift
- `company_create` - Create company
- `company_update` - Update company
- `company_delete` - Delete company
- `setting_create` - Create setting
- `setting_update` - Update setting
- `setting_delete` - Delete setting

### Query Example

```graphql
query {
  user {
    id
    name
    email
    profile {
      phone
      address
    }
  }
}
```

### Mutation Example

```graphql
mutation {
  login(email: "user@example.com", password: "password") {
    token
    user {
      id
      name
    }
  }
}
```

## Module Development

### Creating a New Module

```bash
# Generate module
php artisan module:make {ModuleName}

# Create model (extends BaseModel)
php artisan module:make-model {ModelName} {ModuleName}

# Create controller (extends BaseAdminController)
php artisan module:make-controller {ControllerName} {ModuleName}

# Create migration
php artisan module:make-migration create_{table}_table {ModuleName}

# Create seeder
php artisan module:make-seed {SeederName} {ModuleName}
```

### Module Structure

```
Modules/{ModuleName}/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   ├── Providers/
│   ├── Services/
│   └── Utils/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── lang/
├── resources/
│   ├── assets/
│   └── views/
├── routes/
└── tests/
```

### Module Communication

- **Direct Model Access**: Access models from other modules
- **Service Calls**: Call services from other modules
- **Event System**: Use Laravel events for loose coupling
- **Shared Utilities**: Use `app/Utils` for common functionality

## Service Interface Pattern

### Quy trình thêm Service mới (4 bước bắt buộc)

#### Bước 1: Tạo Interface

**File:** `{Module}/app/Services/Contracts/{Name}ServiceInterface.php`

```php
<?php

namespace Modules\{Module}\Services\Contracts;

use Modules\{Module}\Models\{Model};

interface {Name}ServiceInterface {
    public function methodName({Model} $model, array $data): bool;
}
```

#### Bước 2: Tạo Service Implementation

**File:** `{Module}/app/Services/{Name}Service.php`

```php
<?php

namespace Modules\{Module}\Services;

use App\Services\BaseService;
use Modules\{Module}\Models\{Model};
use Modules\{Module}\Services\Contracts\{Name}ServiceInterface;

class {Name}Service extends BaseService implements {Name}ServiceInterface {
    public function methodName({Model} $model, array $data): bool {
        return $this->handleTransaction(function () use ($model, $data) {
            // Business logic here
            $this->logInfo('Operation completed', ['model_id' => $model->id]);
            return true;
        });
    }
}
```

#### Bước 3: Đăng ký trong AppServiceProvider

**File:** `app/Providers/AppServiceProvider.php`

```php
use Modules\{Module}\Services\Contracts\{Name}ServiceInterface;
use Modules\{Module}\Services\{Name}Service;

protected function registerServiceBindings(): void {
    $this->app->singleton(
        {Name}ServiceInterface::class,
        {Name}Service::class
    );
}
```

#### Bước 4: Sử dụng trong Controller

**File:** `{Module}/app/Http/Controllers/{Name}Controller.php`

```php
use Modules\{Module}\Services\Contracts\{Name}ServiceInterface;

class {Name}Controller extends Base{Module}Controller {
    public function __construct(
        protected {Name}ServiceInterface $service  // ← Dùng Interface
    ) {
        $this->modelClass = {Model}::class;
        $this->requestClass = {Name}Request::class;
    }
}
```

### Checklist khi thêm Service

- [ ] Tạo Interface trong `{Module}/app/Services/Contracts/`
- [ ] Tạo Service class implements Interface
- [ ] Đăng ký binding trong `AppServiceProvider::registerServiceBindings()`
- [ ] Sử dụng Interface trong Controller constructor (không dùng `app()` helper)
- [ ] Đảm bảo Service extends `BaseService`
- [ ] Thêm PHPDoc đầy đủ cho tất cả methods

### Lưu ý quan trọng

- ❌ **SAI**: `$this->service = app(Service::class);` (không dùng `app()` helper)
- ✅ **ĐÚNG**: Constructor injection với Interface
- ❌ **SAI**: `protected Service $service;` (không dùng class trực tiếp)
- ✅ **ĐÚNG**: `protected ServiceInterface $service;` (dùng Interface)

## Development Workflow

### Code Organization

1. **Module Creation**: `php artisan module:make {ModuleName}`
2. **Model Creation**: Extend `BaseModel`
3. **Controller Creation**: Extend `BaseAdminController` or `BaseController`
4. **Service Creation**: Create Interface + Service + Binding (xem Service Interface Pattern ở trên)
5. **Form Request**: Create validation class
6. **Routes**: Define in module `routes/` directory
7. **Views**: Create in module `resources/views/`
8. **Tests**: Create in module `tests/` directory
9. **Code Formatting**: Run `php artisan format` after completion

### Development Process

1. **Feature Planning**: Define requirements
2. **Database Design**: Create migrations
3. **Model Creation**: Create model extending BaseModel
4. **Service Development**: 
   - Create Interface in `{Module}/app/Services/Contracts/`
   - Create Service implements Interface
   - Register binding in `AppServiceProvider::registerServiceBindings()`
5. **Controller Development**: Handle HTTP requests with constructor injection (Interface)
6. **View Development**: Create Blade templates
7. **Testing**: Write feature and unit tests
8. **Code Formatting**: Run `php artisan format` to ensure compliance
9. **Code Review**: Follow coding standards
10. **Documentation**: Update documentation

### Request Flow

**Web Request:**
```
HTTP Request
    ↓
Route Middleware (Auth, Permission)
    ↓
Controller
    ↓
Form Request (Validation)
    ↓
Service (Business Logic)
    ↓
Model (Data Access)
    ↓
BaseModel (Auto-logging, Events)
    ↓
Database
    ↓
Response (View/JSON)
```

**GraphQL Request:**
```
GraphQL Query/Mutation
    ↓
API Key Middleware
    ↓
Sanctum Middleware
    ↓
Lighthouse Parser
    ↓
Resolver
    ↓
Service (Business Logic)
    ↓
Model (Data Access)
    ↓
BaseModel (Auto-logging)
    ↓
Database
    ↓
GraphQL Response
```

## Technology Stack

### Backend

- **PHP**: 8.2+ (8.4 recommended)
- **Laravel**: 12.0
- **MySQL/MariaDB**: 10.3+
- **Redis**: 6.x+ (Cache & Queue)
- **Sanctum**: 4.0 (API Authentication)
- **Lighthouse**: Latest (GraphQL)
- **Laravel Modules**: 11.1 (Module System)
- **Pest**: 3.0 (Testing)

### Frontend

- **Tailwind CSS**: 3.1+
- **Alpine.js**: 3.4+
- **Vite**: 5.0
- **Flowbite**: 2.5+ (UI Components)
- **Font Awesome**: 6.7+ (Icons)
- **Livewire**: 3.6+ (Reactive Components)

### Development Tools

- **Laravel Pint**: Code Formatting
- **IDE Helper**: IDE Autocompletion
- **Debugbar**: Debugging
- **Pail**: Log Viewer
- **Log Viewer**: Log Management

## Quick Tips

### Development

-   Use `php artisan clearall` to clear all cache
-   Run `php artisan ide-helper:generate` after model changes
-   Use `yarn dev` for development, `yarn build` for production
-   Follow Service Layer Pattern: Business logic in Services, not Controllers
-   **Always create Service Interfaces** and bind them in `AppServiceProvider`
-   **Always use constructor injection** with service interfaces (never use `app()` helper)
-   Always use Form Requests for validation
-   Check `app/Utils` before writing new utility functions
-   **Run `php artisan format`** after completing any code changes

### Debugging

-   Check logs in `storage/logs/`
-   Use `dd()` for debugging
-   Enable debug mode in `.env`: `APP_DEBUG=true`
-   Use `LogHandler` for consistent logging

### Performance

-   Use Redis for persistent caching
-   Use CacheHandler for request-scoped caching
-   Optimize database queries with eager loading
-   Use cache to avoid duplicate queries
-   Monitor cache statistics with `CacheHandler::getStats()`
-   Add database indexes for frequently queried columns
-   Use GraphQL schema caching in production
