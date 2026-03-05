<?php

namespace Modules\Api\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

use App\Utils\DomainConst;
use Modules\Api\Models\ApiRegRequest;

class ApiRegRequestTest extends TestCase {
    use RefreshDatabase;

    protected ApiRegRequest $request;

    protected function setUp(): void {
        parent::setUp();

        $this->request = ApiRegRequest::create([
            'email'    => 'test@example.com',
            'password' => 'hashed_password',
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);
    }

    #[Test]
    public function it_uses_correct_table_name(): void {
        $this->assertEquals('api_reg_requests', $this->request->getTable());
    }

    #[Test]
    public function it_has_correct_fillable_attributes(): void {
        $this->assertSame([
            'email',
            'password',
            'status',
        ], $this->request->getFillable());
    }

    #[Test]
    public function it_has_correct_status_constant(): void {
        $this->assertEquals(2, ApiRegRequest::STATUS_REGISTER_REQUEST);
    }

    #[Test]
    public function it_has_correct_datatable_columns(): void {
        $expectedColumns = [
            'id',
            'email',
            'status',
            'created_at',
        ];

        $this->assertEquals($expectedColumns, ApiRegRequest::getDatatableColumns());
    }

    #[Test]
    public function it_has_correct_filterable_columns(): void {
        $reflection = new ReflectionClass($this->request);
        $property   = $reflection->getProperty('filterable');
        $property->setAccessible(true);
        $filterable = $property->getValue($this->request);

        $this->assertSame([
            'email',
            'status',
        ], $filterable);
    }

    #[Test]
    public function it_has_filter_panel_enabled(): void {
        $reflection = new ReflectionClass($this->request);
        $property   = $reflection->getProperty('showFilterPanel');
        $property->setAccessible(true);
        $showFilterPanel = $property->getValue($this->request);

        $this->assertTrue($showFilterPanel);
    }

    #[Test]
    public function it_has_filter_form_enabled(): void {
        $reflection = new ReflectionClass($this->request);
        $property   = $reflection->getProperty('showFilterForm');
        $property->setAccessible(true);
        $showFilterForm = $property->getValue($this->request);

        $this->assertTrue($showFilterForm);
    }

    #[Test]
    public function it_can_get_filter_column_mapping(): void {
        $mapping = ApiRegRequest::getFilterColumnMapping();

        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('status', $mapping);

        // Test status mapping
        $this->assertEquals('array', $mapping['status']['type']);
        $this->assertEquals('status', $mapping['status']['column']);
        $this->assertIsArray($mapping['status']['values']);
    }

    #[Test]
    public function it_can_get_status_array_without_please_select(): void {
        $statusArray = ApiRegRequest::getStatusArray(false);

        $this->assertIsArray($statusArray);
        $this->assertArrayHasKey(ApiRegRequest::STATUS_INACTIVE, $statusArray);
        $this->assertArrayHasKey(ApiRegRequest::STATUS_ACTIVE, $statusArray);
        $this->assertArrayHasKey(ApiRegRequest::STATUS_REGISTER_REQUEST, $statusArray);
        $this->assertArrayNotHasKey(DomainConst::VALUE_EMPTY, $statusArray);
    }

    #[Test]
    public function it_can_get_status_array_with_please_select(): void {
        $statusArray = ApiRegRequest::getStatusArray(true);

        $this->assertIsArray($statusArray);
        $this->assertArrayHasKey(ApiRegRequest::STATUS_INACTIVE, $statusArray);
        $this->assertArrayHasKey(ApiRegRequest::STATUS_ACTIVE, $statusArray);
        $this->assertArrayHasKey(ApiRegRequest::STATUS_REGISTER_REQUEST, $statusArray);
        $this->assertArrayHasKey(DomainConst::VALUE_EMPTY, $statusArray);
        $this->assertEquals(__('admin::crud.please_select'), $statusArray[DomainConst::VALUE_EMPTY]);
    }

    #[Test]
    public function it_can_get_datatable_table_columns(): void {
        $columns = ApiRegRequest::getDatatableTableColumns();

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('email', $columns);
        $this->assertArrayHasKey('status', $columns);
        $this->assertArrayHasKey('created_at', $columns);
    }

    #[Test]
    public function it_can_create_request_with_all_fields(): void {
        $request = ApiRegRequest::create([
            'email'    => 'new@example.com',
            'password' => 'new_password',
            'status'   => ApiRegRequest::STATUS_ACTIVE,
        ]);

        $this->assertInstanceOf(ApiRegRequest::class, $request);
        $this->assertEquals('new@example.com', $request->email);
        $this->assertEquals('new_password', $request->password);
        $this->assertEquals(ApiRegRequest::STATUS_ACTIVE, $request->status);
        $this->assertNotNull($request->created_at);
        $this->assertNotNull($request->updated_at);
    }

    #[Test]
    public function it_can_create_register_request(): void {
        $request = ApiRegRequest::create([
            'email'    => 'register@example.com',
            'password' => 'register_password',
            'status'   => ApiRegRequest::STATUS_REGISTER_REQUEST,
        ]);

        $this->assertEquals(ApiRegRequest::STATUS_REGISTER_REQUEST, $request->status);
    }
}
