<?php

namespace Modules\Admin\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Utils\DomainConst;
use Modules\Admin\Models\AdminModel;

class AdminModelTest extends TestCase {
    use RefreshDatabase;

    /**
     * Test that datatable columns for Admin use admin translation namespace.
     *
     * @return void
     */
    #[Test]
    public function it_returns_admin_datatable_columns(): void {
        $model = new class extends AdminModel {
            protected $table = 'admin_test_models';

            protected $fillable = ['name', 'status'];

            public $useDatatables = true;

            protected $datatableColumns = ['id', 'name', 'status', 'action'];
        };

        $this->schema()->create('admin_test_models', function ($table): void {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(DomainConst::VALUE_TRUE);
        });

        $columns = $model::getBaseDatatableTableColumns();

        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('name', $columns);
        $this->assertArrayHasKey('status', $columns);
        $this->assertArrayHasKey('action', $columns);

        $this->assertStringContainsString('admin::crud.admin-test-models.id', $columns['id']);
        $this->assertStringContainsString('admin::crud.admin-test-models.name', $columns['name']);
        $this->assertStringContainsString('admin::crud.admin-test-models.status', $columns['status']);
        $this->assertSame(__('admin::crud.action'), $columns['action']);
    }

    /**
     * Test that Admin form fields configuration applies correct defaults.
     *
     * @return void
     */
    #[Test]
    public function it_builds_form_fields_with_admin_defaults(): void {
        $fields = AdminModel::getFormFields('admin-test', DomainConst::ACTION_CREATE);

        $this->assertIsArray($fields);

        if (array_key_exists('status', $fields)) {
            $this->assertEquals('select', $fields['status']['type']);
        }
    }

    /**
     * Helper to access schema builder.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema() {
        return $this->app['db']->connection()->getSchemaBuilder();
    }
}
