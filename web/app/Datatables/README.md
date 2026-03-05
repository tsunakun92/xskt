# Laravel Datatables Package

Một package datatables hoàn chỉnh, tự chứa cho Laravel với Livewire components, filtering nâng cao, sorting và search functionality.

## 🚀 **Tính Năng Chính**

-   **Livewire Components**: Datatables reactive với filtering và sorting real-time
-   **Advanced Filtering**: Column filters, range dates, dependent dropdowns, form filters
-   **SQL Optimization**: SQL-level sorting cho virtual columns để xử lý datasets lớn
-   **Search Components**: Dependent dropdown search với caching
-   **Configurable**: Nhiều tùy chọn configuration để customize
-   **Self-Contained**: Không có external dependencies ngoài Laravel và Livewire
-   **Safety Checks**: An toàn khi models không sử dụng đầy đủ tính năng

## 📦 **Cài Đặt**

### Bước 1: Copy Package Files

```bash
# Copy toàn bộ thư mục Datatables vào project
cp -r /path/to/source/app/Datatables /path/to/target/app/
```

### Bước 2: Register Service Provider

Thêm vào `web\app\Providers\AppServiceProvider.php`:

```php
 /**
     * Register any application services.
     */
    public function register(): void {
        // Register the datatables service provider for package functionality
        $this->app->register(\App\Datatables\DatatablesServiceProvider::class);
    }
```

### Bước 3: Add Trait to Models

```php
<?php
namespace App\Models;

use App\Datatables\Models\DatatableModel;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use DatatableModel;

    // Định nghĩa columns hiển thị trong datatables
    protected $datatableColumns = [
        'id', 'name', 'email', 'created_at', 'action'
    ];

    // Định nghĩa columns có thể filter
    protected $filterable = [
        'name', 'email', 'status'
    ];

    // Định nghĩa filter panel columns (dropdown filters)
    protected $filterPanel = [
        'status', 'category'
    ];

    // Hiển thị filter panel trên table (true or false). default is true
    protected $showFilterPanel = true;

    // Hiển thị filter form (true or false). default is true
    protected $showFilterForm = true;

}
```

### Bước 4: Sử Dụng trong Controllers

```php
<?php
namespace App\Http\Controllers;

use App\Models\YourModel;

class YourController extends Controller
{
    public function index()
    {
        $config = [
            'modelClass' => YourModel::class,
            'routeName' => 'your-route',
            'columns' => YourModel::getDatatableTableColumns(),
            'showFilterPanel' => true,
            'showFilterForm' => true,
        ];

        return view('your.index', compact('config'));
    }
}
```

### Bước 5: Add vào Blade Views

```blade
<!-- resources/views/your/index.blade.php -->
<div class="container">
    <h1>Your Model List</h1>

    <livewire:datatables :config="$config" />
</div>
```

## ⚙️ **Configuration**

### Publish Configuration (Tùy chọn)

```bash
php artisan vendor:publish --tag=datatables-config
```

Tạo file `config/datatables.php`:

```php
return [
    'pagination' => [
        'default_page_size' => 10,
        'page_size_options' => [10, 25, 50, 100],
        'pagination_range' => 1,
        'show_ellipsis' => true,
        'min_pages_for_ellipsis' => 7,
    ],

    'defaults' => [
        'empty_message' => 'No data available',
        'loading_message' => 'Loading...',
        'error_message' => 'An error occurred',
        'not_found_message' => 'Not found',
        'please_select_message' => 'Please select',
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 300, // 5 minutes
    ],
];
```

## 🔧 **Sử Dụng Nâng Cao**

### 1. Filter Panel Configuration

```php
class YourModel extends Model
{
    use DatatableModel;

    protected $filterPanel = ['status', 'category_name'];

    public static function getFilterColumnMapping(): array
    {
        return [
            'status' => [
                'type' => 'array',
                'values' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'pending' => 'Pending',
                ],
            ],
            'category_name' => [
                'type' => 'relationship',
                'relationship' => 'category',
                'display_field' => 'name',
                'column' => 'category_id',
            ],
        ];
    }
}
```

### 2. SQL Sorting cho Virtual Columns

```php
class YourModel extends Model
{
    use DatatableModel;

    protected $appends = ['full_name', 'status_name'];

    public static function getSqlSortingExpressions(): array
    {
        return [
            'full_name' => 'CONCAT(first_name, " ", last_name)',
            'status_name' => 'CASE WHEN status = 1 THEN "Active" ELSE "Inactive" END',
            'company_name' => '(SELECT name FROM companies WHERE companies.id = users.company_id)',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getStatusNameAttribute(): string
    {
        return $this->status ? 'Active' : 'Inactive';
    }
}
```

### 3. Form Filters với Dependencies

```php
public static function getFilterFields(string $routeName): array
{
    return [
        'name' => [
            'type' => 'text',
            'label' => __('crud.name'),
            'placeholder' => __('crud.search_name'),
        ],
        'category_id' => [
            'type' => 'select-search',
            'label' => __('crud.category'),
            'search_model' => Category::class,
            'search_option' => ['id', 'name'],
        ],
        'subcategory_id' => [
            'type' => 'select-search',
            'label' => __('crud.subcategory'),
            'search_model' => Subcategory::class,
            'search_option' => ['id', 'name'],
            'search_depends_on' => 'category_id',
            'search_column' => 'category_id',
        ],
        'date_range' => [
            'type' => 'range-date',
            'label' => __('crud.date_range'),
        ],
    ];
}
```

### 4. Custom Actions

```php
$config = [
    'modelClass' => YourModel::class,
    'routeName' => 'your-route',
    'extraActions' => [
        [
            'label' => 'Export',
            'action' => 'export',
            'class' => 'btn btn-primary',
            'icon' => 'fas fa-download',
        ],
        [
            'label' => 'Custom Action',
            'action' => 'customAction',
            'class' => 'btn btn-secondary',
        ],
    ],
];
```

## 🛡️ **Safety Features**

Package hỗ trợ 3 loại models:

### ✅ **Model với DatatableModel Trait (Đầy đủ tính năng)**

```php
class User extends BaseModel {
    // Có DatatableModel trait qua BaseModel
    // Tất cả methods hoạt động bình thường
}

$columns = User::getDatatableTableColumns(); // ✅ Có translations
```

### ✅ **Model không có DatatableModel Trait (An toàn)**

```php
class SimpleModel extends Model {
    protected $datatableColumns = ['id', 'name'];
    // Không có DatatableModel trait
}

$columns = SimpleModel::getDatatableTableColumns(); // ✅ Trả về raw columns
// Result: ['id', 'name'] (không có translations, nhưng không lỗi)
```

### ✅ **Model với Datatables bị tắt**

```php
class LimitedModel extends BaseModel {
    protected $useDatatables = false;

    public static function useDatatables(): bool {
        return false;
    }
}

$columns = LimitedModel::getDatatableTableColumns(); // ✅ Trả về raw columns
```

## ⚡ **Performance Tips**

1. **SQL Sorting**: Định nghĩa `getSqlSortingExpressions()` cho virtual columns
2. **Enable Caching**: Giữ cache enabled cho select-search components
3. **Optimize Queries**: Sử dụng eager loading với `$indexWith` property
4. **Filter Panel**: Giới hạn filter panel columns để tăng tốc load time

```php
class OptimizedModel extends Model
{
    use DatatableModel;

    // Eager loading relationships
    protected $indexWith = ['category', 'user'];

    // Tối ưu filter panel
    protected $filterPanel = ['status']; // Chỉ những columns cần thiết

    // SQL sorting cho performance
    public static function getSqlSortingExpressions(): array {
        return [
            'category_name' => '(SELECT name FROM categories WHERE categories.id = items.category_id)',
        ];
    }
}
```

## 🔄 **Migration từ Implementation Cũ**

Nếu đang migrate từ implementation cũ:

### 1. Update Namespaces

```php
// Cũ
use App\Http\Livewire\BaseDatatables;

// Mới
use App\Datatables\Components\BaseDatatables;
```

### 2. Update Views

```blade
<!-- Cũ -->
@include('components.datatables.index')

<!-- Mới -->
@include('datatables::components.datatables.index')
```

### 3. Update Constants

```php
// Cũ
use App\Utils\DomainConst;

// Mới
use App\Datatables\Constants\DatatableConstants;
```

### 4. Update Models

```php
// Cũ - extends BaseModel trực tiếp

// Mới - use trait
class YourModel extends Model {
    use DatatableModel;
}
```

## 🛠️ **Troubleshooting**

### Common Issues

**Views not found**

-   Đảm bảo service provider được register
-   Check views được publish nếu đã customize

**Components not registered**

-   Verify `DatatablesServiceProvider` trong `config/app.php`

**Constants not found**

-   Sử dụng `DatatableConstants` thay vì `DomainConst` cũ

**Filter values not loading**

-   Ensure models implement `getFilterPanelArray()` và `getFilterPanelColumnValues()`

**Method not found errors**

-   Package đã có safety checks, nhưng nếu gặp lỗi check model có implement đúng methods

### Cache Issues

```bash
# Clear cache
php artisan cache:clear

# Disable cache tạm thời
# config/datatables.php
'cache' => ['enabled' => false],
```

## 📋 **Method Reference**

### DatatableModel Trait Methods

| Method                                | Purpose                   | Required           |
| ------------------------------------- | ------------------------- | ------------------ |
| `useDatatables()`                     | Enable/disable datatables | No (default: true) |
| `showFilterPanel()`                   | Show/hide filter panel    | No (default: true) |
| `showFilterForm()`                    | Show/hide filter form     | No (default: true) |
| `getAsDatatables()`                   | Get query for datatables  | Yes for datatables |
| `getSqlSortingExpressions()`          | Virtual column sorting    | No                 |
| `getDistinctDisplayValuesForColumn()` | Filter panel values       | No                 |
| `getDatatableTableGroupColumns()`     | Collapsible groups        | No                 |

### BaseModel Methods (General)

| Method                       | Purpose                | Safe for all models |
| ---------------------------- | ---------------------- | ------------------- |
| `getDatatableTableColumns()` | Get columns list       | ✅ Yes              |
| `getSortableArray()`         | Get sortable columns   | ✅ Yes              |
| `getExportableArray()`       | Get exportable columns | ✅ Yes              |
| `scopeFilter()`              | General filtering      | ✅ Yes              |
| `getFormFields()`            | Form generation        | ✅ Yes              |
| `getAsDropdown()`            | Dropdown generation    | ✅ Yes              |

## 📁 **Package Structure**

```
app/Datatables/                    # Complete self-contained package
├── Models/DatatableModel.php      # Main trait với datatables functionality
├── Components/                    # Livewire components
│   ├── BaseDatatables.php        # Base abstract component
│   ├── ModelDatatables.php       # Concrete implementation
│   └── SelectSearchLivewire.php  # Search dropdown component
├── Traits/FilterPanelTrait.php    # Filter panel functionality
├── Views/                         # Blade templates
│   └── components/datatables/     # Datatables views
├── Constants/DatatableConstants.php # Configuration constants
├── Config/datatables.php          # Default config
├── DatatablesServiceProvider.php  # Service provider
└── README.md                      # Documentation này
```

## 📋 **Requirements**

-   Laravel 11+
-   Livewire 3.6+
-   PHP 8.2+

## 📄 **License**

Package này follow license terms của project của bạn.

---

**💡 Pro Tip**: Bắt đầu với configuration đơn giản, sau đó tăng dần features như filter panel, SQL sorting khi cần.
