<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use App\Utils\FileMasterHelper;

/**
 * Command to cleanup old temporary files.
 */
class CleanupTmpFiles extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:cleanup-tmp {--hours=24 : Number of hours to keep files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup temporary files older than specified hours (default: 24 hours)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        $hours      = (int) $this->option('hours');
        $cutoffTime = now()->subHours($hours);

        $disk     = 'local';
        $tmpPath  = 'tmp';
        $deleted  = 0;
        $errors   = 0;

        if (!Storage::disk($disk)->exists($tmpPath)) {
            $this->info('Tmp directory does not exist.');

            return Command::SUCCESS;
        }

        $files = Storage::disk($disk)->files($tmpPath);

        foreach ($files as $file) {
            try {
                $lastModified = Storage::disk($disk)->lastModified($file);

                if ($lastModified < $cutoffTime->timestamp) {
                    Storage::disk($disk)->delete($file);
                    $deleted++;

                    // Also delete metadata file if exists
                    // Extract filename from full path (e.g., "tmp/abc123_1234567890.jpg" -> "abc123_1234567890.jpg")
                    $filename     = basename($file);
                    $metadataPath = FileMasterHelper::getMetadataPath($filename);
                    if (Storage::disk($disk)->exists($metadataPath)) {
                        Storage::disk($disk)->delete($metadataPath);
                    }
                }
            } catch (Exception $e) {
                $errors++;
                $this->warn("Error processing file {$file}: " . $e->getMessage());
            }
        }

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} temporary file(s) older than {$hours} hour(s).");
        } else {
            $this->info('No temporary files to cleanup.');
        }

        if ($errors > 0) {
            $this->warn("Encountered {$errors} error(s) during cleanup.");

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
