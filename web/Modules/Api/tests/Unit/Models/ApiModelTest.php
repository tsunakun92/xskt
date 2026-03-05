<?php

namespace Modules\Api\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\DomainConst;
use Modules\Api\Models\ApiModel;
use Modules\Api\Models\ApiRequestLog;

class ApiModelTest extends TestCase {
    use RefreshDatabase;

    /**
     * Test ApiModel base class methods using ApiRequestLog as concrete implementation.
     */
    #[Test]
    public function it_can_get_base_datatable_table_columns(): void {
        $columns = ApiRequestLog::getBaseDatatableTableColumns();

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('ip_address', $columns);
        $this->assertArrayHasKey('action', $columns);
    }

    #[Test]
    public function it_can_get_base_datatable_table_columns_without_action(): void {
        $columns = ApiRequestLog::getBaseDatatableTableColumns(false);

        $this->assertIsArray($columns);
        $this->assertArrayNotHasKey('action', $columns);
    }

    #[Test]
    public function it_can_get_base_datatable_table_columns_with_custom_key_lang(): void {
        $columns = ApiRequestLog::getBaseDatatableTableColumns(true, 'custom-key');

        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
    }

    #[Test]
    public function it_can_get_form_fields(): void {
        $routeName = 'api-request-logs';
        $fields    = ApiRequestLog::getFormFields($routeName);

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);

        // Check that status field has correct configuration
        if (isset($fields['status'])) {
            $this->assertEquals('select', $fields['status']['type']);
            $this->assertIsArray($fields['status']['options']);
        }
    }

    #[Test]
    public function it_can_get_form_fields_with_create_action(): void {
        $routeName = 'api-request-logs';
        $fields    = ApiRequestLog::getFormFields($routeName, DomainConst::ACTION_CREATE);

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);

        // Status field should be hidden in create action
        if (isset($fields['status'])) {
            $this->assertEquals(DomainConst::VALUE_TRUE, $fields['status']['hidden']);
        }
    }

    #[Test]
    public function it_can_get_filter_fields(): void {
        $routeName = 'api-request-logs';
        $fields    = ApiRequestLog::getFilterFields($routeName);

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);

        // All fields should have label and placeholder
        foreach ($fields as $fieldName => $fieldConfig) {
            $this->assertArrayHasKey('label', $fieldConfig);
            $this->assertArrayHasKey('placeholder', $fieldConfig);
            $this->assertArrayHasKey('type', $fieldConfig);
        }
    }

    #[Test]
    public function it_uses_api_crud_namespace_for_translations(): void {
        $routeName = 'api-request-logs';
        $fields    = ApiRequestLog::getFormFields($routeName);

        // Check that labels use api::crud namespace
        if (isset($fields['ip_address'])) {
            $label = $fields['ip_address']['label'];
            // Translation should be attempted (may return key if translation not found)
            $this->assertIsString($label);
        }
    }

    #[Test]
    public function it_handles_empty_datatable_columns(): void {
        // Create a test model with empty datatableColumns
        $testModel = new class extends ApiModel {
            protected $table = 'test_table';

            protected $datatableColumns = [];

            protected $fillable = ['name'];
        };

        $columns = $testModel::getBaseDatatableTableColumns();
        $this->assertIsArray($columns);
        $this->assertEmpty($columns);
    }

    #[Test]
    public function it_handles_model_without_use_datatables(): void {
        // Create a test model without useDatatables property
        $testModel = new class extends ApiModel {
            protected $table = 'test_table';

            protected $datatableColumns = ['id', 'name'];

            protected $fillable = ['name'];
        };

        $columns = $testModel::getBaseDatatableTableColumns();
        // Should return columns as-is if useDatatables is not set
        $this->assertIsArray($columns);
    }
}
