# Logging Module for Laravel

## Cài đặt

### Bước 1: Kích hoạt Module

```bash
php artisan module:enable Logging
```

Hoặc thêm vào `modules_statuses.json`:

```json
{
    "Logging": true
}
```

### Bước 2: Cấu hình Module

```bash
php artisan logging:config
```

Lệnh này tự động:

-   ✅ Cài đặt dependencies
-   ✅ Tạo thư mục log (`http/`, `database/`, `cache/`)
-   ✅ Đăng ký middleware
-   ✅ Merge configs

**Lưu ý**: Khởi động lại server sau khi chạy lệnh.

### Bước 3: Truy cập Dashboard

Truy cập `/logs` để xem logs.

## Cấu hình (Tùy chọn)

Thiết lập trong `.env`:

```env
HTTP_LOG_LEVEL=info
HTTP_LOG_DAYS=30
DATABASE_LOG_LEVEL=debug
DATABASE_LOG_DAYS=30
CACHE_LOG_LEVEL=info
CACHE_LOG_DAYS=14
```

## Sử dụng trong Code

### Import LogHandler

```php
use Modules\Logging\Utils\LogHandler;
```

### Logging cơ bản

```php
// Log thông tin
LogHandler::info('User logged in', ['user_id' => $userId]);

// Log lỗi
LogHandler::error('Payment failed', ['order_id' => $orderId]);

// Log cảnh báo
LogHandler::warning('Low stock', ['product_id' => $productId]);

// Log debug
LogHandler::debug('Processing request', ['request_id' => $requestId]);
```

### Logging Cache

```php
LogHandler::cache('Cache cleared', ['prefix' => 'app_cache']);

LogHandler::cache('Cache hit', ['key' => 'user_profile_123']);

LogHandler::cache('Cache miss', ['key' => 'user_profile_123']);
```

### Logging Database Errors

```php
try {
    $user = DB::table('users')->where('id', $id)->first();
} catch (\Exception $e) {
    LogHandler::databaseError('Failed to get user', $e, [
        'query_name' => 'getUserById',
        'user_id' => $id,
    ]);
}
```

### Ví dụ áp dụng trong Controller

```php
<?php

namespace App\Http\Controllers;

use Modules\Logging\Utils\LogHandler;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        try {
            $user = User::create($request->validated());

            LogHandler::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json($user, 201);
        } catch (\Exception $e) {
            LogHandler::error('Failed to create user', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json(['error' => 'Creation failed'], 500);
        }
    }
}
```

### Ví dụ áp dụng trong Service

```php
<?php

namespace App\Services;

use Modules\Logging\Utils\LogHandler;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    public function getProduct($id)
    {
        $cacheKey = "product_{$id}";

        // Kiểm tra cache
        if (Cache::has($cacheKey)) {
            LogHandler::cache('Cache hit', ['key' => $cacheKey]);
            return Cache::get($cacheKey);
        }

        LogHandler::cache('Cache miss', ['key' => $cacheKey]);

        $product = Product::find($id);

        if ($product) {
            Cache::put($cacheKey, $product, 3600);
            LogHandler::info('Product retrieved', ['product_id' => $id]);
        }

        return $product;
    }
}
```

### Ví dụ áp dụng trong Middleware

```php
<?php

namespace App\Http\Middleware;

use Modules\Logging\Utils\LogHandler;
use Closure;

class LogApiRequests
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 400) {
            LogHandler::error('API request failed', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
```

## Xem Logs

Truy cập `/logs` để:

-   Xem danh sách log files
-   Lọc theo mức độ (error, warning, info, etc.)
-   Tìm kiếm trong logs
-   Tải xuống log files

Log files được lưu tại `storage/logs/`:

-   `http/http-YYYY-MM-DD.log` - HTTP request logs
-   `database/database-YYYY-MM-DD.log` - Database error logs
-   `cache/cache-YYYY-MM-DD.log` - Cache operation logs
-   `laravel-YYYY-MM-DD.log` - General Laravel logs
