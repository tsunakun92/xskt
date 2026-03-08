<?php

namespace Modules\Admin\Tests\Unit\Models;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

use Modules\Admin\Models\FileMaster;

class FileMasterTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();

        // Mock LogHandler to avoid issues in test environment
        $this->mockLogHandler();

        // Setup fake storage
        Storage::fake('public');

        // Create file_masters table for testing if it doesn't exist
        // Note: RefreshDatabase should handle migrations, but we ensure table exists
        if (!Schema::hasTable('file_masters')) {
            Schema::create('file_masters', function (Blueprint $table): void {
                $table->id();
                $table->string('file_name');
                $table->string('file_path');
                $table->integer('file_type');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('belong_type');
                $table->unsignedBigInteger('belong_id');
                $table->string('relation_type')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Mock LogHandler to avoid issues in test environment.
     *
     * @return void
     */
    protected function mockLogHandler(): void {
        $logChannel = Mockery::mock();
        $logChannel->shouldReceive('log')->byDefault()->andReturnNull();

        $logManager = Mockery::mock(LogManager::class);
        $logManager->shouldReceive('channel')->byDefault()->andReturn($logChannel);
        $logManager->shouldReceive('log')->byDefault()->andReturnNull();
        $logManager->shouldReceive('getFacadeRoot')->byDefault()->andReturn($logManager);

        Log::swap($logManager);
    }

    #[Test]
    public function it_has_correct_file_type_constants(): void {
        $this->assertEquals(1, FileMaster::TYPE_IMAGE);
        $this->assertEquals(2, FileMaster::TYPE_PDF);
        $this->assertEquals(3, FileMaster::TYPE_EXCEL);
        $this->assertEquals(4, FileMaster::TYPE_WORD);
    }

    #[Test]
    public function it_has_correct_upload_type_constants(): void {
        $this->assertEquals('image', FileMaster::UPLOAD_TYPE_IMAGE);
        $this->assertEquals('document', FileMaster::UPLOAD_TYPE_DOCUMENT);
    }

    #[Test]
    public function it_has_upload_config_image(): void {
        $config = FileMaster::UPLOAD_CONFIG_IMAGE;

        $this->assertIsArray($config);
        $this->assertArrayHasKey('max_file_upload', $config);
        $this->assertArrayHasKey('max_size_upload', $config);
        $this->assertArrayHasKey('accepted_extensions', $config);
        $this->assertArrayHasKey('accepted_mime_types', $config);
        $this->assertEquals(10, $config['max_file_upload']);
        $this->assertEquals(5 * 1024 * 1024, $config['max_size_upload']);
    }

    #[Test]
    public function it_has_upload_config_document(): void {
        $config = FileMaster::UPLOAD_CONFIG_DOCUMENT;

        $this->assertIsArray($config);
        $this->assertArrayHasKey('max_file_upload', $config);
        $this->assertArrayHasKey('max_size_upload', $config);
        $this->assertArrayHasKey('accepted_extensions', $config);
        $this->assertArrayHasKey('accepted_mime_types', $config);
        $this->assertEquals(10, $config['max_file_upload']);
        $this->assertEquals(10 * 1024 * 1024, $config['max_size_upload']);
    }

    #[Test]
    public function it_has_correct_fillable_fields(): void {
        $model = new FileMaster;

        $expected = [
            'file_name',
            'file_path',
            'file_type',
            'file_size',
            'belong_type',
            'belong_id',
            'relation_type',
            'display_order',
            'alt_text',
            'title',
            'status',
            'created_by',
        ];

        $this->assertEquals($expected, $model->getFillable());
    }

    #[Test]
    public function it_has_correct_datatable_columns(): void {
        $expected = [
            'id',
            'file_name',
            'file_type',
            'file_size',
            'belong_type',
            'belong_id',
            'relation_type',
            'status',
            'action',
        ];

        // Use static method to get datatable columns
        $actual = FileMaster::getDatatableColumns();

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function it_has_correct_filterable_columns(): void {
        $expected = [
            'file_name',
            'file_type',
            'belong_type',
            'belong_id',
            'relation_type',
            'status',
        ];

        // Use reflection to access protected property
        $model      = new FileMaster;
        $reflection = new ReflectionClass($model);
        $property   = $reflection->getProperty('filterable');
        $property->setAccessible(true);
        $actual = $property->getValue($model);

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function it_has_correct_filter_like_columns(): void {
        $expected = [
            'file_name',
            'belong_type',
            'relation_type',
        ];

        // Use reflection to access protected property
        $model      = new FileMaster;
        $reflection = new ReflectionClass($model);
        $property   = $reflection->getProperty('filterLike');
        $property->setAccessible(true);
        $actual = $property->getValue($model);

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function it_has_correct_filter_panel_columns(): void {
        $expected = [
            'file_name',
            'file_type',
            'belong_type',
            'relation_type',
            'status',
        ];

        // Use reflection to access protected property
        $model      = new FileMaster;
        $reflection = new ReflectionClass($model);
        $property   = $reflection->getProperty('filterPanel');
        $property->setAccessible(true);
        $actual = $property->getValue($model);

        $this->assertEquals($expected, $actual);
    }

    #[Test]
    public function it_deletes_file_from_storage_when_model_deleted(): void {
        // Create a file in storage
        $filePath = 'uploads/test/test.jpg';
        Storage::disk('public')->put($filePath, 'fake content');

        // Create FileMaster record
        $fileMaster = FileMaster::create([
            'file_name'   => 'test.jpg',
            'file_path'   => $filePath,
            'file_type'   => FileMaster::TYPE_IMAGE,
            'file_size'   => 100,
            'belong_type' => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'   => 1,
            'status'      => FileMaster::STATUS_ACTIVE,
        ]);

        // Verify file exists
        $this->assertTrue(Storage::disk('public')->exists($filePath));

        // Delete model
        $fileMaster->delete();

        // Verify file is deleted
        $this->assertFalse(Storage::disk('public')->exists($filePath));
    }

    #[Test]
    public function it_handles_file_deletion_when_file_does_not_exist(): void {
        // Create FileMaster record with non-existent file path
        $fileMaster = FileMaster::create([
            'file_name'   => 'test.jpg',
            'file_path'   => 'uploads/test/nonexistent.jpg',
            'file_type'   => FileMaster::TYPE_IMAGE,
            'file_size'   => 100,
            'belong_type' => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'   => 1,
            'status'      => FileMaster::STATUS_ACTIVE,
        ]);

        // Delete should not throw exception
        $fileMaster->delete();

        $this->assertDatabaseMissing('file_masters', ['id' => $fileMaster->id]);
    }

    #[Test]
    public function it_handles_file_deletion_when_file_path_is_null(): void {
        // Create FileMaster record with null file_path
        $fileMaster = FileMaster::create([
            'file_name'   => 'test.jpg',
            'file_path'   => '',
            'file_type'   => FileMaster::TYPE_IMAGE,
            'file_size'   => 100,
            'belong_type' => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'   => 1,
            'status'      => FileMaster::STATUS_ACTIVE,
        ]);

        // Delete should not throw exception
        $fileMaster->delete();

        $this->assertDatabaseMissing('file_masters', ['id' => $fileMaster->id]);
    }

    #[Test]
    public function it_creates_file_master_with_all_fields(): void {
        $fileMaster = FileMaster::create([
            'file_name'     => 'test.jpg',
            'file_path'     => 'uploads/test/test.jpg',
            'file_type'     => FileMaster::TYPE_IMAGE,
            'file_size'     => 1024,
            'belong_type'   => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'     => 1,
            'relation_type' => 'thumbnail',
            'status'        => FileMaster::STATUS_ACTIVE,
            'created_by'    => 1,
        ]);

        $this->assertDatabaseHas('file_masters', [
            'id'            => $fileMaster->id,
            'file_name'     => 'test.jpg',
            'file_path'     => 'uploads/test/test.jpg',
            'file_type'     => FileMaster::TYPE_IMAGE,
            'file_size'     => 1024,
            'belong_type'   => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'     => 1,
            'relation_type' => 'thumbnail',
            'status'        => FileMaster::STATUS_ACTIVE,
            'created_by'    => 1,
        ]);
    }

    #[Test]
    public function it_creates_file_master_with_minimal_fields(): void {
        $fileMaster = FileMaster::create([
            'file_name'   => 'test.jpg',
            'file_path'   => 'uploads/test/test.jpg',
            'file_type'   => FileMaster::TYPE_IMAGE,
            'belong_type' => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'   => 1,
        ]);

        $this->assertDatabaseHas('file_masters', [
            'id'          => $fileMaster->id,
            'file_name'   => 'test.jpg',
            'file_path'   => 'uploads/test/test.jpg',
            'file_type'   => FileMaster::TYPE_IMAGE,
            'belong_type' => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'   => 1,
        ]);

        $this->assertNull($fileMaster->file_size);
        $this->assertNull($fileMaster->relation_type);
        // Status defaults to 1 (STATUS_ACTIVE) from database default
        // In test environment, status may be null if not explicitly set
        // Check database directly to verify default
        $this->assertDatabaseHas('file_masters', [
            'id'     => $fileMaster->id,
            'status' => 1, // Default from migration
        ]);
        $this->assertNull($fileMaster->created_by);
    }
}
