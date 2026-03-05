<?php

namespace Modules\Api\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

use App\Utils\DomainConst;
use Modules\Admin\Models\User;
use Modules\Api\Models\ApiRequestLog;

class ApiRequestLogTest extends TestCase {
    use RefreshDatabase;

    protected ApiRequestLog $log;

    protected User $user;

    protected function setUp(): void {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'     => 'Test User',
            'username' => 'testuser',
            'email'    => 'test@example.com',
        ]);

        $this->log = ApiRequestLog::create([
            'ip_address'     => '192.168.1.1',
            'country'        => 'Japan',
            'user_id'        => $this->user->id,
            'method'         => 'POST /api/login',
            'content'        => '{"username": "test", "password": "******"}',
            'response'       => '{"status": 1, "message": "Success"}',
            'status'         => ApiRequestLog::STATUS_ACTIVE,
            'responsed_date' => now(),
        ]);
    }

    #[Test]
    public function it_uses_correct_table_name(): void {
        $this->assertEquals('api_request_logs', $this->log->getTable());
    }

    #[Test]
    public function it_has_correct_fillable_attributes(): void {
        $this->assertSame([
            'ip_address',
            'country',
            'user_id',
            'method',
            'content',
            'response',
            'status',
            'responsed_date',
        ], $this->log->getFillable());
    }

    #[Test]
    public function it_has_correct_datatable_columns(): void {
        $expectedColumns = [
            'id',
            'ip_address',
            'country',
            'user_name',
            'method',
            'status',
            'responsed_date',
        ];

        $this->assertEquals($expectedColumns, ApiRequestLog::getDatatableColumns());
    }

    #[Test]
    public function it_has_correct_filterable_columns(): void {
        $reflection = new ReflectionClass($this->log);
        $property   = $reflection->getProperty('filterable');
        $property->setAccessible(true);
        $filterable = $property->getValue($this->log);

        $this->assertSame([
            'ip_address',
            'country',
            'user_id',
            'status',
        ], $filterable);
    }

    #[Test]
    public function it_has_correct_filter_like_columns(): void {
        $reflection = new ReflectionClass($this->log);
        $property   = $reflection->getProperty('filterLike');
        $property->setAccessible(true);
        $filterLike = $property->getValue($this->log);

        $this->assertSame([
            'ip_address',
            'country',
            'method',
        ], $filterLike);
    }

    #[Test]
    public function it_belongs_to_user(): void {
        $this->assertInstanceOf(User::class, $this->log->rUser);
        $this->assertEquals($this->user->id, $this->log->user_id);
        $this->assertEquals($this->user->name, $this->log->rUser->name);
    }

    #[Test]
    public function it_can_get_user_name_through_accessor(): void {
        $userName = $this->log->user_name;
        $this->assertEquals($this->user->name, $userName);
        $this->assertEquals('Test User', $userName);
    }

    #[Test]
    public function it_returns_empty_string_when_user_not_found(): void {
        $logWithoutUser = ApiRequestLog::create([
            'ip_address' => '192.168.1.2',
            'country'    => 'Japan',
            'user_id'    => 99999, // Non-existent user ID
            'method'     => 'GET /api/test',
            'status'     => ApiRequestLog::STATUS_ACTIVE,
        ]);

        $this->assertEquals('', $logWithoutUser->user_name);
    }

    #[Test]
    public function it_returns_empty_string_when_user_id_is_null(): void {
        $logWithoutUser = ApiRequestLog::create([
            'ip_address' => '192.168.1.3',
            'country'    => 'Japan',
            'user_id'    => null,
            'method'     => 'GET /api/test',
            'status'     => ApiRequestLog::STATUS_ACTIVE,
        ]);

        $this->assertEquals('', $logWithoutUser->user_name);
    }

    #[Test]
    public function it_has_user_name_in_appends(): void {
        $appends = $this->log->getAppends();
        $this->assertContains('user_name', $appends);
    }

    #[Test]
    public function it_can_get_filter_column_mapping(): void {
        $mapping = ApiRequestLog::getFilterColumnMapping();

        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('status', $mapping);
        $this->assertArrayHasKey('user_name', $mapping);

        // Test status mapping
        $this->assertEquals('array', $mapping['status']['type']);
        $this->assertEquals('status', $mapping['status']['column']);
        $this->assertIsArray($mapping['status']['values']);

        // Test user_name mapping
        $this->assertEquals('relationship', $mapping['user_name']['type']);
        $this->assertEquals('user_id', $mapping['user_name']['column']);
        $this->assertEquals('rUser', $mapping['user_name']['relationship']);
        $this->assertEquals('name', $mapping['user_name']['display_field']);
    }

    #[Test]
    public function it_can_get_form_fields(): void {
        $routeName = 'api-request-logs';
        $fields    = ApiRequestLog::getFormFields($routeName);

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('user_id', $fields);
        $this->assertArrayHasKey('status', $fields);

        // Test user_id field
        $this->assertEquals('select', $fields['user_id']['type']);
        $this->assertIsArray($fields['user_id']['options']);

        // Test status field
        $this->assertEquals('select', $fields['status']['type']);
        $this->assertIsArray($fields['status']['options']);
    }

    #[Test]
    public function it_can_get_filter_fields(): void {
        $routeName = 'api-request-logs';
        $fields    = ApiRequestLog::getFilterFields($routeName);

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('user_id', $fields);
        $this->assertArrayHasKey('status', $fields);

        // Test user_id field
        $this->assertEquals('select', $fields['user_id']['type']);
        $this->assertIsArray($fields['user_id']['options']);
        // User dropdown may or may not have "Please Select" option depending on getAsDropdown implementation
        $this->assertNotEmpty($fields['user_id']['options']);

        // Test status field
        $this->assertEquals('select', $fields['status']['type']);
        $this->assertIsArray($fields['status']['options']);
        // Should have "Please Select" option
        $this->assertArrayHasKey(DomainConst::VALUE_EMPTY, $fields['status']['options']);
    }

    #[Test]
    public function it_can_get_status_array_without_please_select(): void {
        $statusArray = ApiRequestLog::getStatusArray(false);

        $this->assertIsArray($statusArray);
        $this->assertArrayHasKey(ApiRequestLog::STATUS_ACTIVE, $statusArray);
        $this->assertArrayHasKey(ApiRequestLog::STATUS_INACTIVE, $statusArray);
        $this->assertArrayNotHasKey(DomainConst::VALUE_EMPTY, $statusArray);
    }

    #[Test]
    public function it_can_get_status_array_with_please_select(): void {
        $statusArray = ApiRequestLog::getStatusArray(true);

        $this->assertIsArray($statusArray);
        $this->assertArrayHasKey(ApiRequestLog::STATUS_ACTIVE, $statusArray);
        $this->assertArrayHasKey(ApiRequestLog::STATUS_INACTIVE, $statusArray);
        $this->assertArrayHasKey(DomainConst::VALUE_EMPTY, $statusArray);
        $this->assertEquals(__('admin::crud.please_select'), $statusArray[DomainConst::VALUE_EMPTY]);
    }

    #[Test]
    public function it_can_get_datatable_table_columns(): void {
        $columns = ApiRequestLog::getDatatableTableColumns();

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
    }

    #[Test]
    public function it_can_create_log_with_all_fields(): void {
        $log = ApiRequestLog::create([
            'ip_address'     => '10.0.0.1',
            'country'        => 'United States',
            'user_id'        => $this->user->id,
            'method'         => 'GET /api/users',
            'content'        => '{"page": 1, "limit": 10}',
            'response'       => '{"data": [], "total": 0}',
            'status'         => ApiRequestLog::STATUS_ACTIVE,
            'responsed_date' => now()->subHour(),
        ]);

        $this->assertInstanceOf(ApiRequestLog::class, $log);
        $this->assertEquals('10.0.0.1', $log->ip_address);
        $this->assertEquals('United States', $log->country);
        $this->assertEquals($this->user->id, $log->user_id);
        $this->assertEquals('GET /api/users', $log->method);
        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->updated_at);
    }

    #[Test]
    public function it_can_create_log_without_user(): void {
        $log = ApiRequestLog::create([
            'ip_address' => '192.168.1.100',
            'country'    => 'Japan',
            'user_id'    => null,
            'method'     => 'POST /api/register',
            'status'     => ApiRequestLog::STATUS_ACTIVE,
        ]);

        $this->assertInstanceOf(ApiRequestLog::class, $log);
        $this->assertNull($log->user_id);
        $this->assertEquals('', $log->user_name);
    }

    #[Test]
    public function it_can_serialize_with_user_name(): void {
        $array = $this->log->toArray();

        $this->assertArrayHasKey('user_name', $array);
        $this->assertEquals($this->user->name, $array['user_name']);
    }
}
