<?php

namespace Tests\Unit\Utils;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\LogManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

use App\Utils\FileMasterHelper;
use Modules\Admin\Models\FileMaster;

class FileMasterHelperTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();

        // Mock LogHandler to avoid issues in test environment
        $this->mockLogHandler();

        // Setup fake storage disks
        Storage::fake('local');
        Storage::fake('public');

        // Create file_masters table for testing if it doesn't exist
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
    public function it_calls_cleanup_tmp_files_command(): void {
        Artisan::shouldReceive('call')
            ->once()
            ->with('files:cleanup-tmp', ['--hours' => 24])
            ->andReturn(0);

        FileMasterHelper::cleanupTmpFiles();

        $this->assertTrue(true); // Verify command was called
    }

    #[Test]
    public function it_calls_cleanup_tmp_files_command_with_custom_hours(): void {
        Artisan::shouldReceive('call')
            ->once()
            ->with('files:cleanup-tmp', ['--hours' => 48])
            ->andReturn(0);

        FileMasterHelper::cleanupTmpFiles(48);

        $this->assertTrue(true); // Verify command was called
    }

    #[Test]
    public function it_stores_single_file_and_creates_file_master_record(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $this->assertDatabaseHas('file_masters', [
            'belong_type' => FileMaster::BELONG_TYPE_CRM_SECTION,
            'belong_id'   => 1,
            'file_type'   => FileMaster::TYPE_IMAGE,
            'status'      => FileMaster::STATUS_ACTIVE,
            'created_by'  => 1,
        ]);

        $fileMaster = FileMaster::first();
        $this->assertNotNull($fileMaster);
        $this->assertEquals('test.jpg', $fileMaster->file_name);
        $this->assertNotNull($fileMaster->file_path);
        $this->assertTrue(Storage::disk('public')->exists($fileMaster->file_path));
    }

    #[Test]
    public function it_stores_multiple_files(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.png');

        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file1, $file2]
        );

        $this->assertDatabaseCount('file_masters', 2);
    }

    #[Test]
    public function it_handles_empty_array_when_storing_files(): void {
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            []
        );

        $this->assertDatabaseCount('file_masters', 0);
    }

    #[Test]
    public function it_skips_non_uploaded_file_items(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file    = UploadedFile::fake()->image('test.jpg');
        $invalid = 'not-a-file';

        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file, $invalid]
        );

        $this->assertDatabaseCount('file_masters', 1);
    }

    #[Test]
    public function it_stores_file_with_relation_type(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->image('test.jpg');

        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file],
            'thumbnail'
        );

        $fileMaster = FileMaster::first();
        $this->assertEquals('thumbnail', $fileMaster->relation_type);
    }

    #[Test]
    public function it_stores_file_with_null_auth_id(): void {
        Auth::shouldReceive('check')->andReturn(false);
        Auth::shouldReceive('id')->andReturn(null);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->image('test.jpg');

        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $fileMaster = FileMaster::first();
        $this->assertNull($fileMaster->created_by);
    }

    #[Test]
    public function it_builds_file_pond_files_from_collection(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->image('test.jpg');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $files  = FileMaster::all();
        $result = FileMasterHelper::buildFilePondFiles($files);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('source', $result[0]);
        $this->assertArrayHasKey('options', $result[0]);
        $this->assertArrayHasKey('metadata', $result[0]['options']);
        $this->assertArrayHasKey('id', $result[0]['options']['metadata']);
    }

    #[Test]
    public function it_builds_file_pond_files_with_absolute_url(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->image('test.jpg');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $files  = FileMaster::all();
        $result = FileMasterHelper::buildFilePondFiles($files);

        $this->assertStringStartsWith('http', $result[0]['source']);
    }

    #[Test]
    public function it_handles_empty_collection_when_building_file_pond_files(): void {
        $files  = new Collection;
        $result = FileMasterHelper::buildFilePondFiles($files);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_builds_file_pond_files_for_edit_with_server_id(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->image('test.jpg');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $files  = FileMaster::all();
        $result = FileMasterHelper::buildFilePondFilesForEdit($files);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('serverId', $result[0]['options']['metadata']);
        $this->assertEquals($files->first()->id, $result[0]['options']['metadata']['serverId']);
    }

    #[Test]
    public function it_guesses_mime_type_from_extension_in_edit_mode(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->create('test.png', 100);
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $files  = FileMaster::all();
        $result = FileMasterHelper::buildFilePondFilesForEdit($files);

        $this->assertEquals('image/png', $result[0]['options']['file']['type']);
    }

    #[Test]
    public function it_uploads_file_to_tmp_storage(): void {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $filename = FileMasterHelper::uploadToTmp($file);

        $this->assertNotEmpty($filename);
        $this->assertTrue(Storage::disk('local')->exists("tmp/{$filename}"));

        // Verify metadata file exists
        $metadataPath = FileMasterHelper::getMetadataPath($filename);
        $this->assertTrue(Storage::disk('local')->exists($metadataPath));

        // Verify metadata content
        $metadata = json_decode(Storage::disk('local')->get($metadataPath), true);
        $this->assertEquals('test.jpg', $metadata['original_name']);
        $this->assertEquals($file->getSize(), $metadata['size']);
        $this->assertEquals($file->getMimeType(), $metadata['mime_type']);
    }

    #[Test]
    public function it_uploads_file_without_extension_to_tmp(): void {
        $file = UploadedFile::fake()->create('testfile', 100);

        $filename = FileMasterHelper::uploadToTmp($file);

        $this->assertStringEndsWith('.tmp', $filename);
        $this->assertTrue(Storage::disk('local')->exists("tmp/{$filename}"));
    }

    #[Test]
    public function it_removes_file_from_tmp_storage(): void {
        $file     = UploadedFile::fake()->image('test.jpg');
        $filename = FileMasterHelper::uploadToTmp($file);

        $result = FileMasterHelper::removeFromTmp($filename);

        $this->assertTrue($result);
        $this->assertFalse(Storage::disk('local')->exists("tmp/{$filename}"));

        // Verify metadata file is also deleted
        $metadataPath = FileMasterHelper::getMetadataPath($filename);
        $this->assertFalse(Storage::disk('local')->exists($metadataPath));
    }

    #[Test]
    public function it_returns_false_when_removing_nonexistent_file(): void {
        $result = FileMasterHelper::removeFromTmp('nonexistent.jpg');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_generates_metadata_path_from_filename(): void {
        $path = FileMasterHelper::getMetadataPath('abc123_1234567890.jpg');

        $this->assertEquals('tmp/abc123_1234567890.json', $path);
    }

    #[Test]
    public function it_generates_metadata_path_from_filename_without_extension(): void {
        $path = FileMasterHelper::getMetadataPath('abc123_1234567890');

        $this->assertEquals('tmp/abc123_1234567890.json', $path);
    }

    #[Test]
    public function it_generates_metadata_path_from_filename_with_multiple_dots(): void {
        $path = FileMasterHelper::getMetadataPath('abc123_1234567890.test.file.jpg');

        $this->assertEquals('tmp/abc123_1234567890.test.file.json', $path);
    }

    #[Test]
    public function it_moves_file_from_tmp_to_permanent_storage(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        // Upload file to tmp
        $file        = UploadedFile::fake()->image('test.jpg', 100, 100);
        $tmpFilename = FileMasterHelper::uploadToTmp($file);

        // Move to permanent
        $fileMaster = FileMasterHelper::moveFromTmpToPermanent(
            $tmpFilename,
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );

        // Verify FileMaster record created
        $this->assertNotNull($fileMaster);
        $this->assertEquals('test.jpg', $fileMaster->file_name);
        $this->assertEquals(FileMaster::BELONG_TYPE_CRM_SECTION, $fileMaster->belong_type);
        $this->assertEquals(1, $fileMaster->belong_id);

        // Verify file moved to permanent storage
        $this->assertTrue(Storage::disk('public')->exists($fileMaster->file_path));

        // Verify tmp file deleted
        $this->assertFalse(Storage::disk('local')->exists("tmp/{$tmpFilename}"));

        // Verify metadata deleted
        $metadataPath = FileMasterHelper::getMetadataPath($tmpFilename);
        $this->assertFalse(Storage::disk('local')->exists($metadataPath));
    }

    #[Test]
    public function it_throws_exception_when_tmp_file_not_found(): void {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Temporary file not found: nonexistent.jpg');

        FileMasterHelper::moveFromTmpToPermanent(
            'nonexistent.jpg',
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );
    }

    #[Test]
    public function it_moves_file_with_relation_type(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file        = UploadedFile::fake()->image('test.jpg');
        $tmpFilename = FileMasterHelper::uploadToTmp($file);

        $fileMaster = FileMasterHelper::moveFromTmpToPermanent(
            $tmpFilename,
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            'thumbnail'
        );

        $this->assertEquals('thumbnail', $fileMaster->relation_type);
    }

    #[Test]
    public function it_uses_metadata_when_available_when_moving_file(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file        = UploadedFile::fake()->create('original-name.pdf', 200, 'application/pdf');
        $tmpFilename = FileMasterHelper::uploadToTmp($file);

        $fileMaster = FileMasterHelper::moveFromTmpToPermanent(
            $tmpFilename,
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );

        $this->assertEquals('original-name.pdf', $fileMaster->file_name);
        $this->assertEquals(FileMaster::TYPE_PDF, $fileMaster->file_type);
    }

    #[Test]
    public function it_falls_back_to_extracted_name_when_metadata_missing(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        // Create tmp file without metadata
        $tmpFilename = 'abc123_1234567890.jpg';
        Storage::disk('local')->put("tmp/{$tmpFilename}", 'fake content');

        $fileMaster = FileMasterHelper::moveFromTmpToPermanent(
            $tmpFilename,
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );

        $this->assertStringStartsWith('uploaded_file', $fileMaster->file_name);
    }

    #[Test]
    public function it_moves_multiple_files_from_tmp(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file1 = UploadedFile::fake()->image('test1.jpg');
        $file2 = UploadedFile::fake()->image('test2.png');

        $tmpFilename1 = FileMasterHelper::uploadToTmp($file1);
        $tmpFilename2 = FileMasterHelper::uploadToTmp($file2);

        FileMasterHelper::moveMultipleFromTmp(
            [$tmpFilename1, $tmpFilename2],
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );

        $this->assertDatabaseCount('file_masters', 2);
        $this->assertFalse(Storage::disk('local')->exists("tmp/{$tmpFilename1}"));
        $this->assertFalse(Storage::disk('local')->exists("tmp/{$tmpFilename2}"));
    }

    #[Test]
    public function it_handles_empty_array_when_moving_multiple_files(): void {
        FileMasterHelper::moveMultipleFromTmp(
            [],
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );

        $this->assertDatabaseCount('file_masters', 0);
    }

    #[Test]
    public function it_skips_empty_filenames_when_moving_multiple_files(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file        = UploadedFile::fake()->image('test.jpg');
        $tmpFilename = FileMasterHelper::uploadToTmp($file);

        // moveMultipleFromTmp filters empty filenames before processing
        // Note: empty() only returns true for '', null, 0, false, [], etc.
        // Whitespace strings like '   ' are NOT considered empty by empty()
        FileMasterHelper::moveMultipleFromTmp(
            [$tmpFilename, ''],
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );

        // Only one file should be moved (empty string is filtered by empty() check)
        $this->assertDatabaseCount('file_masters', 1);
    }

    #[Test]
    public function it_throws_exception_when_one_file_fails_in_multiple_move(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file        = UploadedFile::fake()->image('test.jpg');
        $tmpFilename = FileMasterHelper::uploadToTmp($file);

        $this->expectException(RuntimeException::class);

        FileMasterHelper::moveMultipleFromTmp(
            [$tmpFilename, 'nonexistent.jpg'],
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1
        );
    }

    #[Test]
    public function it_guesses_file_type_as_image_for_image_mime(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->image('test.jpg');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $fileMaster = FileMaster::first();
        $this->assertEquals(FileMaster::TYPE_IMAGE, $fileMaster->file_type);
    }

    #[Test]
    public function it_guesses_file_type_as_pdf_for_pdf_mime(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $fileMaster = FileMaster::first();
        $this->assertEquals(FileMaster::TYPE_PDF, $fileMaster->file_type);
    }

    #[Test]
    public function it_guesses_file_type_as_excel_for_excel_mime(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->create('test.xlsx', 100, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $fileMaster = FileMaster::first();
        $this->assertEquals(FileMaster::TYPE_EXCEL, $fileMaster->file_type);
    }

    #[Test]
    public function it_guesses_file_type_as_word_for_word_mime(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->create('test.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $fileMaster = FileMaster::first();
        $this->assertEquals(FileMaster::TYPE_WORD, $fileMaster->file_type);
    }

    #[Test]
    public function it_defaults_to_image_type_for_unknown_mime(): void {
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn(1);
        Auth::shouldReceive('user')->andReturn(null);

        $file = UploadedFile::fake()->create('test.unknown', 100, 'application/unknown');
        FileMasterHelper::storeFiles(
            FileMaster::BELONG_TYPE_CRM_SECTION,
            1,
            [$file]
        );

        $fileMaster = FileMaster::first();
        $this->assertEquals(FileMaster::TYPE_IMAGE, $fileMaster->file_type);
    }
}
