<?php

namespace Tests\Feature\Api;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use Modules\Admin\Models\FileMaster;

class FileUploadControllerTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();

        // Mock LogHandler to avoid issues in test environment
        $this->mockLogHandler();

        // Setup fake storage disks
        Storage::fake('local');
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
    public function it_uploads_image_file_successfully(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
            ])
            ->assertJsonStructure([
                'error',
                'filename',
                'message',
            ]);

        $data = $response->json();
        $this->assertNotEmpty($data['filename']);
        $this->assertTrue(Storage::disk('local')->exists("tmp/{$data['filename']}"));
    }

    #[Test]
    public function it_uploads_document_file_successfully(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'error' => false,
            ]);
    }

    #[Test]
    public function it_handles_file_upload_with_different_field_names(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        $file = UploadedFile::fake()->image('test.jpg');

        // Test with 'file' field name
        $response = $this->postJson('/api/files/tmp/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['error' => false]);
    }

    #[Test]
    public function it_handles_file_upload_with_files_field_name(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/files/tmp/upload', [
            'files' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['error' => false]);
    }

    #[Test]
    public function it_handles_nested_file_arrays(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/files/tmp/upload', [
            'files' => [$file],
        ]);

        $response->assertStatus(200)
            ->assertJson(['error' => false]);
    }

    #[Test]
    public function it_returns_error_when_no_file_provided(): void {
        $response = $this->postJson('/api/files/tmp/upload', []);

        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    }

    #[Test]
    public function it_validates_image_file_size_limit(): void {
        // Create file larger than 5MB (image limit)
        $file = UploadedFile::fake()->image('test.jpg')->size(6 * 1024 * 1024); // 6MB

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonFragment([
                'message' => 'File size exceeds maximum allowed size (5MB).',
            ]);
    }

    #[Test]
    public function it_validates_document_file_size_limit(): void {
        // Create file larger than 10MB (document limit)
        $file = UploadedFile::fake()->create('test.pdf', 11 * 1024 * 1024, 'application/pdf'); // 11MB

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonFragment([
                'message' => 'File size exceeds maximum allowed size (10MB).',
            ]);
    }

    #[Test]
    public function it_allows_image_file_within_size_limit(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        // UploadedFile::fake()->image() creates a small file by default (within 5MB limit)
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['error' => false]);
    }

    #[Test]
    public function it_allows_document_file_within_size_limit(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        // UploadedFile::fake()->create() with small size (within 10MB limit)
        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['error' => false]);
    }

    #[Test]
    public function it_validates_mime_type(): void {
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/x-msdownload');

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonFragment([
                'message' => 'File type not allowed.',
            ]);
    }

    #[Test]
    public function it_handles_cleanup_command_failure_gracefully(): void {
        Artisan::shouldReceive('call')
            ->once()
            ->andThrow(new Exception('Cleanup failed'));

        $file = UploadedFile::fake()->image('test.jpg');

        // Upload should still succeed even if cleanup fails
        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['error' => false]);
    }

    #[Test]
    public function it_removes_tmp_file_successfully(): void {
        // First upload a file
        Artisan::shouldReceive('call')->andReturn(0);
        $file           = UploadedFile::fake()->image('test.jpg');
        $uploadResponse = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);
        $filename = $uploadResponse->json('filename');

        // Then remove it
        $response = $this->deleteJson("/api/files/tmp/{$filename}");

        $response->assertStatus(200)
            ->assertJson([
                'error'   => false,
                'message' => 'File removed successfully.',
            ]);

        $this->assertFalse(Storage::disk('local')->exists("tmp/{$filename}"));
    }

    #[Test]
    public function it_returns_error_when_removing_nonexistent_tmp_file(): void {
        $response = $this->deleteJson('/api/files/tmp/nonexistent.jpg');

        $response->assertStatus(404)
            ->assertJson([
                'error'   => true,
                'message' => 'File not found.',
            ]);
    }

    #[Test]
    public function it_removes_existing_file_from_database_and_storage(): void {
        // Create file in storage
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

        // Remove existing file
        $response = $this->deleteJson("/api/files/tmp/existing/{$fileMaster->id}");

        $response->assertStatus(200)
            ->assertJson([
                'error'   => false,
                'message' => 'File removed successfully.',
            ]);

        // Verify file deleted from storage
        $this->assertFalse(Storage::disk('public')->exists($filePath));

        // Verify record deleted from database
        $this->assertDatabaseMissing('file_masters', ['id' => $fileMaster->id]);
    }

    #[Test]
    public function it_handles_removing_existing_file_when_storage_file_missing(): void {
        // Create FileMaster record without file in storage
        $fileMaster = FileMaster::create([
            'file_name'   => 'test.jpg',
            'file_path'   => 'uploads/test/nonexistent.jpg',
            'file_type'   => FileMaster::TYPE_IMAGE,
            'file_size'   => 100,
            'belong_type' => FileMaster::BELONG_TYPE_SECTION,
            'belong_id'   => 1,
            'status'      => FileMaster::STATUS_ACTIVE,
        ]);

        // Should still succeed even if file doesn't exist
        $response = $this->deleteJson("/api/files/tmp/existing/{$fileMaster->id}");

        $response->assertStatus(200)
            ->assertJson(['error' => false]);

        // Verify record deleted
        $this->assertDatabaseMissing('file_masters', ['id' => $fileMaster->id]);
    }

    #[Test]
    public function it_returns_error_when_removing_nonexistent_file_master(): void {
        $response = $this->deleteJson('/api/files/tmp/existing/99999');

        $response->assertStatus(500)
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    }

    #[Test]
    public function it_returns_correct_json_structure_on_successful_upload(): void {
        Artisan::shouldReceive('call')->andReturn(0);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/files/tmp/upload', [
            'filepond' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'error',
                'filename',
                'message',
            ])
            ->assertJson([
                'error' => false,
            ]);
    }
}
