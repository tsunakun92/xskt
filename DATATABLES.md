# Comprehensive DataTables Usage Guide for Laravel Modular Architecture

This guide covers the complete implementation and usage of the custom DataTables system built for Laravel applications with modular architecture and Livewire integration.

## Table of Contents

1. [System Architecture Overview](#system-architecture-overview)
2. [Setting Up DataTables for New Models](#setting-up-datatables-for-new-models)
3. [BaseDatatables vs ModelDatatables](#basedatatables-vs-modeldatatables)
4. [Filter Column Mapping System](#filter-column-mapping-system)
5. [Filter Panel Configuration](#filter-panel-configuration)
6. [Column Groups and Pagination](#column-groups-and-pagination)
7. [Custom Actions Integration](#custom-actions-integration)
8. [Multi-language Support](#multi-language-support)
9. [Common Use Cases and Examples](#common-use-cases-and-examples)
10. [Troubleshooting](#troubleshooting)
11. [Integration with Core Patterns](#integration-with-core-patterns)
12. [Best Practices](#best-practices)

## System Architecture Overview

The DataTables system consists of several key components:

- **BaseDatatables**: Abstract Livewire component providing core functionality
- **ModelDatatables**: Concrete implementation for Eloquent models
- **DatatableModel Trait**: Model integration with query building capabilities
- **BaseModel**: Foundation model with DataTables integration
- **Filter Components**: Livewire components for advanced filtering
- **Translation System**: Multi-language support with dynamic key generation

### Core Architecture Flow

```
Model (with DatatableModel trait) 
  ↓
ModelDatatables Component
  ↓
BaseDatatables Component
  ↓
Blade Templates with Filter Panel
```

## Setting Up DataTables for New Models

### Step 1: Model Configuration

First, ensure your model extends `BaseModel` or implements the `DatatableModel` trait:

```php
<?php

namespace App\Models;

use App\Datatables\Models\DatatableModel;
use Illuminate\Database\Eloquent\Model;

class Product extends BaseModel // or use DatatableModel trait
{
    // Define which columns to display in datatables
    protected $datatableColumns = [
        'id',
        'name', 
        'category_name',
        'price',
        'stock',
        'status',
        'action'
    ];
    
    // Define which columns appear in filter panel
    protected $filterPanel = [
        'name',
        'category_name', 
        'price',
        'status'
    ];
    
    // Define sortable columns
    protected $sortableColumns = [
        'id', 'name', 'price', 'stock', 'created_at'
    ];
    
    // Define filterable columns
    protected $filterableColumns = [
        'name', 'category_name', 'status'
    ];
}
```

### Step 2: Create DataTables Component

Create a Livewire component for your model:

```php
<?php

namespace App\Livewire\Admin;

use App\Datatables\Components\ModelDatatables;
use App\Models\Product;

class ProductDatatables extends ModelDatatables
{
    protected string $model = Product::class;
    protected string $routePrefix = 'admin.products';
    
    // Optional: Override default configuration
    public function mount()
    {
        parent::mount();
        
        // Custom page size
        $this->pageSize = 25;
        
        // Custom sort
        $this->sortField = 'name';
        $this->sortDirection = 'asc';
    }
    
    // Optional: Add custom actions
    protected function getExtraActions(): array
    {
        return [
            [
                'route' => 'admin.products.duplicate',
                'label' => 'Duplicate',
                'iconClass' => 'fa-solid fa-copy',
                'require_pdf' => false
            ]
        ];
    }
}
```

### Step 3: Controller Integration

Update your controller to use the DataTables component:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin::products.index');
    }
    
    // Standard CRUD methods...
}
```

### Step 4: Create the View

Create the index view with the DataTables component:

```blade
{{-- resources/views/admin/products/index.blade.php --}}
@extends('admin::layouts.master')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('admin::crud.products.title') }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> {{ __('admin::crud.add_new') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @livewire('admin.product-datatables')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

### Step 5: Add Translation Keys

Add translation keys to your language files:

```php
// lang/en/admin.php
'crud' => [
    'products' => [
        'title' => 'Products',
        'id' => 'ID',
        'name' => 'Product Name',
        'category_name' => 'Category',
        'price' => 'Price',
        'stock' => 'Stock',
        'status' => 'Status',
        'action' => 'Actions',
    ],
],
```

## BaseDatatables vs ModelDatatables

### BaseDatatables

**Use Cases:**
- Custom data sources (APIs, complex queries)
- Non-Eloquent data
- Manual data manipulation required

**Key Features:**
- Abstract foundation with core functionality
- Pagination with ellipsis support
- Column grouping capabilities
- Advanced filtering system
- Error handling and recovery

**Example Implementation:**

```php
class CustomDatatables extends BaseDatatables
{
    protected function getData()
    {
        // Custom data retrieval logic
        return collect([
            ['id' => 1, 'name' => 'Custom Item 1'],
            ['id' => 2, 'name' => 'Custom Item 2'],
        ]);
    }
    
    protected function getColumns(): array
    {
        return ['id', 'name'];
    }
}
```

### ModelDatatables

**Use Cases:**
- Standard Eloquent models
- Database-driven tables
- Relationship filtering needed
- Appended attribute support required

**Key Features:**
- Automatic Eloquent integration
- Filter column mapping
- Relationship handling
- Appended attribute support
- Collection-based filtering for complex attributes

**Example Implementation:**

```php
class UserDatatables extends ModelDatatables
{
    protected string $model = User::class;
    protected string $routePrefix = 'admin.users';
    
    // Automatic integration with User model's datatables configuration
}
```

### Decision Matrix

| Feature | BaseDatatables | ModelDatatables |
|---------|----------------|-----------------|
| Eloquent Models | Manual setup | Automatic |
| Relationships | Manual handling | Built-in support |
| Filter Mapping | Custom implementation | Automated |
| Appended Attributes | Not supported | Full support |
| Custom Data Sources | ✅ Perfect fit | Not suitable |
| Standard CRUD | More work | ✅ Perfect fit |

## Filter Column Mapping System

The filter column mapping system allows you to display user-friendly values while filtering on database columns.

### Basic Mapping Types

#### 1. Array Type Mapping

For static value mappings (status, types, etc.):

```php
public static function getFilterColumnMapping(): array
{
    return [
        'status' => [
            'type' => 'array',
            'column' => 'status',
            'values' => [
                'active' => 'Active',
                'inactive' => 'Inactive',
                'pending' => 'Pending'
            ],
        ],
    ];
}
```

#### 2. Relationship Type Mapping

For displaying relationship data:

```php
public static function getFilterColumnMapping(): array
{
    return [
        'category_name' => [
            'type' => 'relationship',
            'column' => 'category_id',           // Database column
            'relationship' => 'rCategory',       // Model relationship method
            'display_field' => 'name',          // Field to display
        ],
        'role_name' => [
            'type' => 'relationship',
            'column' => 'roles_id',
            'relationship' => 'rRole',
            'display_field' => 'name',
        ],
    ];
}
```

#### 3. Simple Column Mapping

For basic column-to-column mapping:

```php
public static function getFilterColumnMapping(): array
{
    return [
        'display_name' => [
            'type' => 'simple',
            'column' => 'database_column_name',
        ],
    ];
}
```

### Advanced Filter Mapping

#### Nested Relationships

```php
public static function getFilterColumnMapping(): array
{
    return [
        'department_name' => [
            'type' => 'relationship',
            'column' => 'user_id',
            'relationship' => 'rUser.rDepartment', // Nested relationship
            'display_field' => 'name',
        ],
    ];
}
```

#### Computed Values with Caching

```php
public static function getFilterColumnMapping(): array
{
    return [
        'computed_field' => [
            'type' => 'computed',
            'column' => 'id',
            'compute_function' => function($id) {
                return Cache::remember("computed_$id", 300, function() use ($id) {
                    return ComplexCalculation::process($id);
                });
            },
        ],
    ];
}
```

### Appended Attributes Integration

For model attributes that are computed:

```php
class User extends BaseModel
{
    protected $appends = ['full_name', 'role_name'];
    
    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function getRoleNameAttribute()
    {
        return $this->rRole?->name ?? 'No Role';
    }
    
    // Filter mapping for appended attributes
    public static function getFilterColumnMapping(): array
    {
        return [
            'role_name' => [
                'type' => 'relationship',
                'column' => 'roles_id',
                'relationship' => 'rRole',
                'display_field' => 'name',
            ],
        ];
    }
}
```

## Filter Panel Configuration

The filter panel provides Excel-like filtering capabilities with sortable options and multi-select functionality.

### Basic Configuration

Define filterable columns in your model:

```php
class Product extends BaseModel
{
    protected $filterPanel = [
        'name',
        'category_name',
        'price_range',
        'status'
    ];
}
```

### Filter Panel Features

#### 1. Sortable Options

The filter panel automatically provides:
- **A-Z**: Ascending alphabetical sort
- **Z-A**: Descending alphabetical sort
- **Multi-select**: Select multiple filter values
- **Select All/Deselect All**: Bulk operations

#### 2. Dynamic Value Loading

Filter values are loaded dynamically based on:

```php
// In your model
public function getDistinctDisplayValuesForColumn(string $column): Collection
{
    $mapping = static::getFilterColumnMapping()[$column] ?? null;
    
    if ($mapping && $mapping['type'] === 'relationship') {
        // Load values from relationship
        return $this->getRelationshipFilterValues($column, $mapping);
    }
    
    if ($mapping && $mapping['type'] === 'array') {
        // Return predefined values
        return collect($mapping['values']);
    }
    
    // Default: distinct values from database
    return $this->distinct($column)->pluck($column);
}
```

#### 3. Custom Filter Panel Layout

Override the filter panel template if needed:

```php
// In your DataTables component
public function render()
{
    return view('custom.datatables.index', [
        'data' => $this->getData(),
        'columns' => $this->getColumns(),
        'customFilterPanel' => true
    ]);
}
```

### Filter Form Integration

The system includes a separate filter form for complex filtering:

```php
class ProductFilterForm extends Component
{
    use FilterFormTrait;
    
    public $price_min;
    public $price_max;
    public $date_from;
    public $date_to;
    
    protected function getFilterRules(): array
    {
        return [
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ];
    }
    
    public function applyFilters()
    {
        $this->emit('filtersUpdated', [
            'price_range' => [$this->price_min, $this->price_max],
            'date_range' => [$this->date_from, $this->date_to],
        ]);
    }
}
```

## Column Groups and Pagination

### Column Groups (Collapsible Columns)

Create collapsible column groups for better organization:

```php
class User extends BaseModel
{
    protected $datatableColumns = [
        'id', 'personal_info', 'contact_info', 'system_info', 'action'
    ];
    
    public function getDatatableTableGroupColumns(): array
    {
        return [
            'personal_info' => [
                'label' => 'Personal Information',
                'columns' => ['first_name', 'last_name', 'date_of_birth'],
                'collapsible' => true,
                'collapsed' => false, // Initially expanded
            ],
            'contact_info' => [
                'label' => 'Contact Information', 
                'columns' => ['email', 'phone', 'address'],
                'collapsible' => true,
                'collapsed' => true, // Initially collapsed
            ],
            'system_info' => [
                'label' => 'System Information',
                'columns' => ['role_name', 'status', 'last_login'],
                'collapsible' => true,
                'collapsed' => true,
            ],
        ];
    }
}
```

### Pagination Configuration

The system provides advanced pagination with ellipsis support:

#### Global Configuration

```php
// config/datatables.php
'pagination' => [
    'default_page_size' => 10,
    'page_size_options' => [10, 25, 50, 100],
    'pagination_range' => 1,        // Pages shown on each side
    'show_ellipsis' => true,        // Show ... for large page counts
    'min_pages_for_ellipsis' => 7,  // Minimum pages before showing ellipsis
],
```

#### Component-Level Override

```php
class ProductDatatables extends ModelDatatables
{
    public function mount()
    {
        parent::mount();
        
        $this->pageSize = 25;
        $this->pageSizeOptions = [25, 50, 100, 200];
    }
}
```

#### Custom Pagination Template

```blade
{{-- resources/views/custom/pagination.blade.php --}}
<div class="datatable-pagination">
    <div class="pagination-info">
        Showing {{ $data->firstItem() }} to {{ $data->lastItem() }} of {{ $data->total() }} results
    </div>
    
    <div class="pagination-controls">
        {{ $data->links('custom.pagination-links') }}
    </div>
    
    <div class="page-size-selector">
        <select wire:model="pageSize">
            @foreach($pageSizeOptions as $size)
                <option value="{{ $size }}">{{ $size }} per page</option>
            @endforeach
        </select>
    </div>
</div>
```

## Custom Actions Integration

The actions system provides flexible integration with route-based permissions.

### Standard Actions

Standard actions (View, Edit, Delete) are automatically generated:

```php
// Automatically includes if routes exist:
// - {routePrefix}.show
// - {routePrefix}.edit  
// - {routePrefix}.destroy
```

### Adding Custom Actions

#### Simple Custom Action

```php
class ProductDatatables extends ModelDatatables
{
    protected function getExtraActions(): array
    {
        return [
            [
                'route' => 'admin.products.duplicate',
                'label' => 'Duplicate',
                'iconClass' => 'fa-solid fa-copy',
                'require_pdf' => false
            ]
        ];
    }
}
```

#### Conditional Custom Actions

```php
protected function getExtraActions(): array
{
    return [
        [
            'route' => 'admin.products.activate',
            'label' => 'Activate',
            'iconClass' => 'fa-solid fa-check',
            'condition' => function($item) {
                return $item->status === 'inactive';
            }
        ],
        [
            'route' => 'admin.products.deactivate', 
            'label' => 'Deactivate',
            'iconClass' => 'fa-solid fa-times',
            'condition' => function($item) {
                return $item->status === 'active';
            }
        ],
    ];
}
```

#### Permission-Based Actions

```php
protected function getExtraActions(): array
{
    $actions = [];
    
    if (auth()->user()->can('manage-inventory')) {
        $actions[] = [
            'route' => 'admin.products.inventory',
            'label' => 'Manage Inventory',
            'iconClass' => 'fa-solid fa-boxes',
        ];
    }
    
    if (auth()->user()->hasRole('super-admin')) {
        $actions[] = [
            'route' => 'admin.products.advanced-settings',
            'label' => 'Advanced Settings',
            'iconClass' => 'fa-solid fa-cogs',
        ];
    }
    
    return $actions;
}
```

#### Actions with Custom Parameters

```php
protected function getExtraActions(): array
{
    return [
        [
            'route' => 'admin.products.report',
            'label' => 'Generate Report',
            'iconClass' => 'fa-solid fa-file-pdf',
            'require_pdf' => true,
            'parameters' => function($item) {
                return [
                    'product' => $item->id,
                    'format' => 'pdf',
                    'include_stats' => true
                ];
            }
        ]
    ];
}
```

### Custom Action Template

Override the actions template for complete control:

```blade
{{-- resources/views/custom/actions.blade.php --}}
<div class="action-buttons">
    {{-- Standard Actions --}}
    @if($showAction && Route::has($routePrefix . '.show'))
        <a href="{{ route($routePrefix . '.show', $item) }}" 
           class="btn btn-sm btn-info" 
           title="View">
            <i class="fa-solid fa-eye"></i>
        </a>
    @endif
    
    {{-- Custom Actions --}}
    @foreach($extraActions as $action)
        @if(!isset($action['condition']) || $action['condition']($item))
            <a href="{{ route($action['route'], $item) }}" 
               class="btn btn-sm btn-warning"
               title="{{ $action['label'] }}">
                <i class="{{ $action['iconClass'] }}"></i>
            </a>
        @endif
    @endforeach
</div>
```

## Multi-language Support

The system provides comprehensive multi-language support with automatic key generation.

### Translation Structure

#### Directory Structure
```
app/Datatables/lang/
├── en/
│   └── datatables.php
├── ja/
│   └── datatables.php
└── es/
    └── datatables.php
```

#### Core Translation Keys

```php
// lang/en/datatables.php
return [
    'pagination' => [
        'previous' => 'Previous',
        'next' => 'Next',
        'first' => 'First',
        'last' => 'Last',
        'showing' => 'Showing',
        'to' => 'to',
        'of' => 'of',
        'results' => 'results',
    ],
    'filter' => [
        'search' => 'Search...',
        'all' => 'All',
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'sort_az' => 'Sort A-Z',
        'sort_za' => 'Sort Z-A',
        'apply' => 'Apply Filters',
        'clear' => 'Clear Filters',
    ],
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit', 
        'delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this item?',
    ],
    'status' => [
        'loading' => 'Loading...',
        'no_data' => 'No data available',
        'error' => 'An error occurred while loading data',
    ],
];
```

### Dynamic Column Labels

Column labels are automatically generated using the model's table name:

```php
// Automatic translation key generation
$tableName = (new static)->getTable(); // e.g., 'users'
$columnLabels[$column] = __("admin::crud.{$tableName}.{$column}");

// Results in keys like:
// admin::crud.users.name
// admin::crud.users.email
// admin::crud.products.price
```

### Model-Specific Translations

```php
// lang/en/admin.php
'crud' => [
    'users' => [
        'title' => 'User Management',
        'id' => 'ID',
        'name' => 'Full Name',
        'email' => 'Email Address',
        'username' => 'Username', 
        'role_name' => 'Role',
        'status' => 'Status',
        'action' => 'Actions',
        'created_at' => 'Created Date',
        'updated_at' => 'Last Updated',
    ],
    'products' => [
        'title' => 'Product Catalog',
        'id' => 'Product ID',
        'name' => 'Product Name',
        'category_name' => 'Category',
        'price' => 'Price',
        'stock' => 'Stock Level',
        'status' => 'Status',
    ],
],
```

### Multi-language Filter Values

For translated filter values:

```php
public static function getFilterColumnMapping(): array
{
    return [
        'status' => [
            'type' => 'array',
            'column' => 'status',
            'values' => [
                'active' => __('admin::common.status.active'),
                'inactive' => __('admin::common.status.inactive'),
                'pending' => __('admin::common.status.pending'),
            ],
        ],
    ];
}
```

### Language Switching

The system automatically adapts to the current application locale:

```php
// Middleware or service provider
App::setLocale(request()->get('lang', 'en'));

// All datatables will automatically use the correct language
```

## Common Use Cases and Examples

### Example 1: User Management DataTable

Complete implementation for user management:

```php
// Models/User.php
class User extends BaseModel
{
    protected $datatableColumns = [
        'id', 'name', 'email', 'username', 'role_name', 'status', 'created_at', 'action'
    ];
    
    protected $filterPanel = [
        'name', 'email', 'username', 'role_name', 'status'
    ];
    
    protected $sortableColumns = [
        'id', 'name', 'email', 'username', 'created_at'
    ];
    
    protected $filterableColumns = [
        'name', 'email', 'username', 'role_name', 'status'
    ];
    
    // Relationships
    public function rRole()
    {
        return $this->belongsTo(Role::class, 'roles_id');
    }
    
    // Appended attributes
    protected $appends = ['role_name'];
    
    public function getRoleNameAttribute()
    {
        return $this->rRole?->name ?? 'No Role';
    }
    
    // Filter column mapping
    public static function getFilterColumnMapping(): array
    {
        return [
            'role_name' => [
                'type' => 'relationship',
                'column' => 'roles_id',
                'relationship' => 'rRole',
                'display_field' => 'name',
            ],
            'status' => [
                'type' => 'array',
                'column' => 'status',
                'values' => static::getStatusArray(false),
            ],
        ];
    }
    
    // Status values
    public static function getStatusArray($withKeys = true)
    {
        $statuses = [
            1 => 'Active',
            0 => 'Inactive',
        ];
        
        return $withKeys ? $statuses : array_flip($statuses);
    }
}

// Livewire/Admin/UserDatatables.php
class UserDatatables extends ModelDatatables
{
    protected string $model = User::class;
    protected string $routePrefix = 'admin.users';
    
    protected function getExtraActions(): array
    {
        return [
            [
                'route' => 'admin.users.permissions',
                'label' => 'Permissions',
                'iconClass' => 'fa-solid fa-key',
                'condition' => function($user) {
                    return auth()->user()->can('manage-permissions');
                }
            ],
            [
                'route' => 'admin.users.reset-password',
                'label' => 'Reset Password',
                'iconClass' => 'fa-solid fa-lock-open',
            ]
        ];
    }
}
```

### Example 2: E-commerce Product DataTable

Advanced product management with category relationships:

```php
// Models/Product.php
class Product extends BaseModel
{
    protected $datatableColumns = [
        'id', 'image', 'name', 'category_name', 'price', 'stock', 'status', 'featured', 'action'
    ];
    
    protected $filterPanel = [
        'name', 'category_name', 'price_range', 'stock_level', 'status', 'featured'
    ];
    
    // Group columns for better organization
    public function getDatatableTableGroupColumns(): array
    {
        return [
            'basic_info' => [
                'label' => 'Product Information',
                'columns' => ['image', 'name', 'category_name'],
                'collapsible' => true,
                'collapsed' => false,
            ],
            'pricing_stock' => [
                'label' => 'Pricing & Stock',
                'columns' => ['price', 'stock'],
                'collapsible' => true,
                'collapsed' => false,
            ],
            'status_features' => [
                'label' => 'Status & Features', 
                'columns' => ['status', 'featured'],
                'collapsible' => true,
                'collapsed' => true,
            ],
        ];
    }
    
    // Relationships
    public function rCategory()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    
    // Appended attributes
    protected $appends = ['category_name', 'stock_level', 'price_range'];
    
    public function getCategoryNameAttribute()
    {
        return $this->rCategory?->name ?? 'Uncategorized';
    }
    
    public function getStockLevelAttribute()
    {
        if ($this->stock <= 0) return 'Out of Stock';
        if ($this->stock <= 10) return 'Low Stock';
        if ($this->stock <= 50) return 'Medium Stock';
        return 'High Stock';
    }
    
    public function getPriceRangeAttribute()
    {
        if ($this->price < 100) return 'Budget';
        if ($this->price < 500) return 'Mid-range';
        return 'Premium';
    }
    
    // Filter column mapping
    public static function getFilterColumnMapping(): array
    {
        return [
            'category_name' => [
                'type' => 'relationship',
                'column' => 'category_id',
                'relationship' => 'rCategory',
                'display_field' => 'name',
            ],
            'stock_level' => [
                'type' => 'array',
                'column' => 'stock',
                'values' => [
                    'out' => 'Out of Stock',
                    'low' => 'Low Stock',
                    'medium' => 'Medium Stock', 
                    'high' => 'High Stock',
                ],
                'compute_function' => function($stock) {
                    if ($stock <= 0) return 'out';
                    if ($stock <= 10) return 'low';
                    if ($stock <= 50) return 'medium';
                    return 'high';
                }
            ],
            'price_range' => [
                'type' => 'array',
                'column' => 'price',
                'values' => [
                    'budget' => 'Budget (< $100)',
                    'mid' => 'Mid-range ($100-$500)',
                    'premium' => 'Premium (> $500)',
                ],
                'compute_function' => function($price) {
                    if ($price < 100) return 'budget';
                    if ($price < 500) return 'mid';
                    return 'premium';
                }
            ],
        ];
    }
}

// Livewire/Admin/ProductDatatables.php
class ProductDatatables extends ModelDatatables
{
    protected string $model = Product::class;
    protected string $routePrefix = 'admin.products';
    
    public function mount()
    {
        parent::mount();
        $this->pageSize = 25; // More products per page
    }
    
    protected function getExtraActions(): array
    {
        return [
            [
                'route' => 'admin.products.duplicate',
                'label' => 'Duplicate',
                'iconClass' => 'fa-solid fa-copy',
            ],
            [
                'route' => 'admin.products.inventory', 
                'label' => 'Inventory',
                'iconClass' => 'fa-solid fa-boxes',
                'condition' => function($product) {
                    return auth()->user()->can('manage-inventory');
                }
            ],
            [
                'route' => 'admin.products.featured',
                'label' => 'Toggle Featured',
                'iconClass' => 'fa-solid fa-star',
            ]
        ];
    }
}
```

### Example 3: Order Management with Complex Relationships

```php
// Models/Order.php  
class Order extends BaseModel
{
    protected $datatableColumns = [
        'id', 'order_number', 'customer_name', 'total_amount', 'status', 'payment_status', 'created_at', 'action'
    ];
    
    protected $filterPanel = [
        'order_number', 'customer_name', 'status', 'payment_status', 'date_range'
    ];
    
    // Relationships
    public function rCustomer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
    
    public function rOrderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    // Appended attributes
    protected $appends = ['customer_name', 'total_amount'];
    
    public function getCustomerNameAttribute()
    {
        return $this->rCustomer?->name ?? 'Guest';
    }
    
    public function getTotalAmountAttribute()
    {
        return $this->rOrderItems()->sum('total_price');
    }
    
    // Filter column mapping with date ranges
    public static function getFilterColumnMapping(): array
    {
        return [
            'customer_name' => [
                'type' => 'relationship',
                'column' => 'customer_id',
                'relationship' => 'rCustomer',
                'display_field' => 'name',
            ],
            'status' => [
                'type' => 'array',
                'column' => 'status',
                'values' => [
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ],
            ],
            'payment_status' => [
                'type' => 'array',
                'column' => 'payment_status',
                'values' => [
                    'pending' => 'Payment Pending',
                    'paid' => 'Paid',
                    'failed' => 'Payment Failed',
                    'refunded' => 'Refunded',
                ],
            ],
        ];
    }
}
```

## Troubleshooting

### Common Issues and Solutions

#### 1. Filter Values Not Loading

**Problem**: Filter panel shows empty or incorrect values.

**Causes & Solutions**:

```php
// Check filter column mapping
public static function getFilterColumnMapping(): array
{
    return [
        'role_name' => [
            'type' => 'relationship',
            'column' => 'roles_id', // Make sure this matches your database column
            'relationship' => 'rRole', // Make sure this method exists
            'display_field' => 'name', // Make sure the related model has this field
        ],
    ];
}

// Verify relationship exists
public function rRole()
{
    return $this->belongsTo(Role::class, 'roles_id'); // Correct foreign key
}

// Check if values are being loaded
public function getDistinctDisplayValuesForColumn(string $column): Collection
{
    // Add debugging
    \Log::info("Loading values for column: $column");
    
    $values = parent::getDistinctDisplayValuesForColumn($column);
    
    \Log::info("Found values: ", $values->toArray());
    
    return $values;
}
```

#### 2. Appended Attributes Not Filtering

**Problem**: Appended attributes show in table but filtering doesn't work.

**Solution**: Implement proper filter column mapping:

```php
// Wrong - this won't work for appended attributes
protected $filterableColumns = ['role_name']; // role_name is appended

// Correct - map appended attribute to database column
public static function getFilterColumnMapping(): array
{
    return [
        'role_name' => [ // Appended attribute
            'type' => 'relationship',
            'column' => 'roles_id', // Actual database column
            'relationship' => 'rRole',
            'display_field' => 'name',
        ],
    ];
}
```

#### 3. Pagination Issues

**Problem**: Pagination not working or showing incorrect counts.

**Common Causes**:

```php
// Issue: Conflicting pagination in query
public function getAsDatatables()
{
    $query = parent::getAsDatatables();
    
    // Don't paginate here - let the component handle it
    // return $query->paginate(10); // ❌ Wrong
    
    return $query; // ✅ Correct
}

// Issue: Incorrect total count with appended attributes
// Solution: Override the count method
public function getDatatableData()
{
    $query = $this->getBaseQuery();
    
    // For appended attributes, use collection-based filtering
    if ($this->hasAppendedAttributeFilters()) {
        return $this->getCollectionBasedData($query);
    }
    
    return $query->paginate($this->pageSize);
}
```

#### 4. Performance Issues

**Problem**: Slow loading with large datasets.

**Solutions**:

```php
// 1. Add database indexes
Schema::table('users', function (Blueprint $table) {
    $table->index(['status', 'roles_id']); // Add indexes for filtered columns
});

// 2. Implement caching for filter values
public function getDistinctDisplayValuesForColumn(string $column): Collection
{
    return Cache::remember("filter_values_{$column}", 300, function() use ($column) {
        return parent::getDistinctDisplayValuesForColumn($column);
    });
}

// 3. Limit eager loading
public function getAsDatatables()
{
    return $this->with(['rRole:id,name']) // Only load needed columns
                ->select(['id', 'name', 'email', 'roles_id', 'status']); // Select specific columns
}

// 4. Use chunk loading for very large datasets
protected function getCollectionBasedData($query)
{
    $results = collect();
    
    $query->chunk(1000, function($chunk) use ($results) {
        $filtered = $this->applyCollectionFiltering($chunk);
        $results = $results->merge($filtered);
    });
    
    return $this->paginateCollection($results);
}
```

#### 5. Translation Issues

**Problem**: Column labels or filter values not translating.

**Solutions**:

```php
// 1. Check translation file paths
// Make sure files exist at: lang/{locale}/admin.php

// 2. Verify translation keys
'crud' => [
    'users' => [ // This should match your table name
        'name' => 'Full Name',
        'email' => 'Email Address',
        // ...
    ],
],

// 3. Clear translation cache
php artisan cache:clear
php artisan config:clear

// 4. Check locale is set correctly
dd(app()->getLocale()); // Should match your language files
```

#### 6. Custom Actions Not Showing

**Problem**: Custom actions don't appear in the actions column.

**Debugging Steps**:

```php
// 1. Check route exists
Route::get('/admin/users/{user}/permissions', [UserController::class, 'permissions'])
     ->name('admin.users.permissions');

// 2. Verify action configuration
protected function getExtraActions(): array
{
    \Log::info('Getting extra actions');
    
    return [
        [
            'route' => 'admin.users.permissions',
            'label' => 'Permissions',
            'iconClass' => 'fa-solid fa-key',
            // Add debugging
            'condition' => function($user) {
                $canManage = auth()->user()->can('manage-permissions');
                \Log::info("Can manage permissions for user {$user->id}: " . ($canManage ? 'yes' : 'no'));
                return $canManage;
            }
        ]
    ];
}

// 3. Check if actions column is included
protected $datatableColumns = [
    'id', 'name', 'email', 'action' // Make sure 'action' is included
];
```

#### 7. JavaScript/Livewire Conflicts

**Problem**: Filter panel or interactions not working properly.

**Solutions**:

```blade
{{-- 1. Make sure Livewire is loaded --}}
@livewireScripts

{{-- 2. Check for Alpine.js conflicts --}}
<div x-data="{ open: false }" wire:ignore.self>
    {{-- Component content --}}
</div>

{{-- 3. Add proper wire:key attributes --}}
@foreach($items as $item)
    <tr wire:key="item-{{ $item->id }}">
        {{-- Row content --}}
    </tr>
@endforeach

{{-- 4. Handle JavaScript initialization --}}
<script>
document.addEventListener('livewire:load', function () {
    // Initialize your JavaScript components
});

document.addEventListener('livewire:update', function () {
    // Re-initialize after Livewire updates
});
</script>
```

### Performance Optimization Tips

#### 1. Database Optimization

```php
// Add database indexes for commonly filtered/sorted columns
Schema::table('users', function (Blueprint $table) {
    $table->index('status');
    $table->index('roles_id');
    $table->index(['status', 'roles_id']); // Composite index
    $table->index('created_at');
});
```

#### 2. Query Optimization

```php
// Optimize queries with selective loading
public function getAsDatatables()
{
    return $this->select([
            'id', 'name', 'email', 'username', 'roles_id', 'status', 'created_at'
        ])
        ->with(['rRole:id,name']) // Only load needed relationship columns
        ->when($this->hasAppendedAttributeFilters(), function($query) {
            // Only eager load when needed for filtering
            return $query->with(['additionalRelationships']);
        });
}
```

#### 3. Caching Strategy

```php
// Implement smart caching
class UserDatatables extends ModelDatatables
{
    protected int $cacheMinutes = 5;
    
    public function getData()
    {
        $cacheKey = $this->getCacheKey();
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function() {
            return parent::getData();
        });
    }
    
    protected function getCacheKey(): string
    {
        return sprintf(
            'datatables_%s_%s_%s_%d_%s',
            $this->model,
            md5(serialize($this->filters)),
            $this->sortField . '_' . $this->sortDirection,
            $this->page,
            $this->pageSize
        );
    }
}
```

## Integration with Core Patterns

### Service Layer Integration

DataTables components work seamlessly with the Service Layer pattern:

```php
// In your Service Interface
interface UserServiceInterface {
    public function getUsersForDatatable(array $filters = []): Builder;
}

// In your Service
class UserService extends BaseService implements UserServiceInterface
{
    public function getUsersForDatatable(array $filters = []): Builder
    {
        $query = User::query();
        
        // Apply business logic filters
        if (isset($filters['active_only'])) {
            $query->where('status', 1);
        }
        
        return $query;
    }
}

// In your ModelDatatables component (with constructor injection)
class UserDatatables extends ModelDatatables
{
    public function __construct(
        protected UserServiceInterface $userService
    ) {
        parent::__construct();
    }
    
    protected function getBaseQuery()
    {
        return $this->userService->getUsersForDatatable($this->filters);
    }
}
```

**Note**: Always use constructor injection with service interfaces, never use `app()` helper.

### Form Request Integration

DataTables can work with Form Request validation for filter inputs:

```php
class FilterUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'status' => 'nullable|in:active,inactive',
        ];
    }
}

// In DataTables component
public function applyFilters(array $filters)
{
    $request = new FilterUserRequest();
    $request->merge($filters);
    
    if ($request->validates()) {
        $this->filters = $request->validated();
    }
}
```

### BaseModel Integration

All models extending BaseModel automatically get DataTables support:

```php
class Product extends BaseModel
{
    // BaseModel provides:
    // - Automatic logging on CRUD operations
    // - created_by auto-population
    // - Status management
    // - Datatables integration
    // - Dynamic filtering with scopeFilter()
    // - Field configuration for forms and filters
    
    protected $datatableColumns = ['id', 'name', 'price', 'status', 'action'];
    protected $filterPanel = ['name', 'price', 'status'];
}
```

### Request Flow Integration

DataTables follows the standard request flow:

```
HTTP Request (AJAX)
    ↓
Route Middleware (Auth, Permission)
    ↓
Livewire Component (ModelDatatables)
    ↓
Service (Business Logic - optional)
    ↓
Model (BaseModel with Datatables)
    ↓
Database Query
    ↓
BaseModel (Auto-logging)
    ↓
JSON Response (Paginated Data)
```

### Permission Integration

DataTables actions respect the permission system:

```php
// In ModelDatatables component
protected function getExtraActions(): array
{
    return [
        [
            'route' => 'admin.products.duplicate',
            'label' => 'Duplicate',
            'iconClass' => 'fa-solid fa-copy',
            // Permission check is automatic via route
        ],
    ];
}

// In Blade template
@canAccess('products.duplicate')
    {{-- Action button will be shown --}}
@endcanAccess
```

### Caching Integration

DataTables can leverage CacheHandler for performance:

```php
class ProductDatatables extends ModelDatatables
{
    protected function getFilterValues(string $column): Collection
    {
        $cacheKey = "datatables_filter_{$this->model}_{$column}";
        
        return CacheHandler::remember($cacheKey, function() use ($column) {
            return parent::getFilterValues($column);
        }, null, CacheHandler::TYPE_STATIC);
    }
}
```

### Logging Integration

DataTables operations can be logged using LogHandler:

```php
class ProductDatatables extends ModelDatatables
{
    public function updatedFilters()
    {
        LogHandler::info('DataTables filters updated', [
            'model' => $this->model,
            'filters' => $this->filters,
        ]);
        
        parent::updatedFilters();
    }
}
```

## Best Practices

### Code Organization

1. **Keep DataTables components focused**: One component per model
2. **Use Services for complex logic**: Don't put business logic in DataTables components
3. **Leverage BaseModel features**: Use built-in filtering and field configuration
4. **Follow naming conventions**: Use consistent route prefixes

### Database Design

1. **Add indexes**: Index columns used in filtering and sorting
2. **Use relationships efficiently**: Eager load relationships when needed
3. **Optimize queries**: Select only needed columns
4. **Use scopes**: Create reusable query scopes in models

### Performance

1. **Use static cache**: For data only needed within a request
2. **Implement pagination**: Always paginate large datasets
3. **Limit eager loading**: Only load relationships when needed
4. **Add database indexes**: For frequently filtered/sorted columns
5. **Monitor statistics**: Use CacheHandler::getStats() to monitor cache performance

### Security

1. **Validate filters**: Use Form Requests for filter validation
2. **Check permissions**: Always verify user permissions for actions
3. **Sanitize output**: Use Blade escaping for all output
4. **Log operations**: Log important DataTables operations

### Testing

1. **Test filter logic**: Verify filters work correctly
2. **Test pagination**: Ensure pagination works with filters
3. **Test permissions**: Verify actions respect permissions
4. **Test performance**: Monitor query performance with large datasets

This comprehensive guide covers all aspects of the DataTables system implementation. The system provides a robust, scalable solution for data presentation with advanced filtering, sorting, and multi-language support while maintaining excellent performance and user experience.