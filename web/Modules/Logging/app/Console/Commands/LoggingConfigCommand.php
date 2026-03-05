<?php

namespace Modules\Logging\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class LoggingConfigCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logging:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure Logging module: install dependencies and publish assets';

    /**
     * Execute the console command.
     */
    public function handle() {
        $this->info('🔧 Configuring Logging module...');
        $this->newLine();

        // Step 1: Check and install dependencies
        $this->info('  📦 Step 1: Checking dependencies...');
        $dependencies = $this->checkPackageDependencies();
        if (!$dependencies['all_installed']) {
            $this->warn('     Missing packages detected. Running composer update...');
            if ($this->runComposerUpdate()) {
                $this->info('     ✅ Dependencies installed successfully');
            } else {
                $this->error('     ❌ Failed to install dependencies');
                $this->line('     Please run: composer update');

                return Command::FAILURE;
            }
        } else {
            $this->info('     ✅ All dependencies are installed');
        }

        // Step 2: Copy log-viewer assets from module to public
        $this->newLine();
        $this->info('  📄 Step 2: Copying log-viewer assets from module...');
        if ($this->copyLogViewerAssets()) {
            $this->info('     ✅ Log-viewer assets copied from module');
        } else {
            $this->warn('     ⚠️  Log-viewer assets may already be copied');
        }

        // Step 3: Create log directories
        $this->newLine();
        $this->info('  📁 Step 3: Creating log directories...');
        $this->createLogDirectories();

        // Step 4: Clear config cache
        $this->newLine();
        $this->info('  🧹 Step 4: Clearing config cache...');
        Artisan::call('config:clear');
        $this->info('     ✅ Config cache cleared');

        $this->newLine();
        $this->info('✨ Configuration complete!');
        $this->newLine();
        $this->comment('💡 Next steps:');
        $this->line('   1. Restart your server if running');
        $this->line('   2. Access logs at: /logs');
        $this->newLine();
        $this->line('📝 Note: All configs are auto-merged from module. No manual configuration needed.');

        return Command::SUCCESS;
    }

    /**
     * Check if required package dependencies are installed
     */
    protected function checkPackageDependencies(): array {
        $requiredPackages = [
            'spatie/laravel-http-logger',
            'opcodesio/log-viewer',
        ];

        $composerLockPath = base_path('composer.lock');

        if (!File::exists($composerLockPath)) {
            return [
                'all_installed' => false,
                'missing'       => $requiredPackages,
            ];
        }

        $composerLock = json_decode(File::get($composerLockPath), true);

        if (!isset($composerLock['packages'])) {
            return [
                'all_installed' => false,
                'missing'       => $requiredPackages,
            ];
        }

        $installedPackages = [];
        foreach ($composerLock['packages'] as $package) {
            if (isset($package['name'])) {
                $installedPackages[] = $package['name'];
            }
        }

        $missing = array_diff($requiredPackages, $installedPackages);

        return [
            'all_installed' => empty($missing),
            'missing'       => $missing,
        ];
    }

    /**
     * Run composer update to install dependencies
     */
    protected function runComposerUpdate(): bool {
        $composerPath = $this->findComposer();

        $process = new Process([$composerPath, 'update', '--no-interaction'], base_path());
        $process->setTimeout(300); // 5 minutes timeout

        try {
            $process->run(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            return $process->isSuccessful();
        } catch (Exception $e) {
            $this->error('Failed to run composer update: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Find composer executable
     */
    protected function findComposer(): string {
        if (file_exists(base_path('composer.phar'))) {
            return 'php ' . base_path('composer.phar');
        }

        // Try to find composer in PATH (Windows)
        if (PHP_OS_FAMILY === 'Windows') {
            $process = new Process(['where', 'composer'], null, null, null, 5);
            $process->run();
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                if (!empty($output)) {
                    return explode("\n", $output)[0];
                }
            }
        } else {
            // Linux/Mac
            $process = new Process(['which', 'composer'], null, null, null, 5);
            $process->run();
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                if (!empty($output)) {
                    return $output;
                }
            }
        }

        // Fallback to 'composer' command
        return 'composer';
    }

    /**
     * Copy log-viewer assets from module to public directory (standalone)
     */
    protected function copyLogViewerAssets(): bool {
        try {
            $moduleAssetsPath = module_path('Logging', 'public/vendor/log-viewer');
            $publicAssetsPath = public_path('vendor/log-viewer');

            if (!File::isDirectory($moduleAssetsPath)) {
                $this->warn('     Module assets not found, trying to publish from package...');
                // Fallback: publish from package if module assets don't exist
                Artisan::call('log-viewer:publish', [], $this->getOutput());

                return true;
            }

            // Ensure public directory exists
            if (!File::isDirectory($publicAssetsPath)) {
                File::makeDirectory($publicAssetsPath, 0755, true);
            }

            // Copy all files from module to public
            $files  = File::allFiles($moduleAssetsPath);
            $copied = 0;
            foreach ($files as $file) {
                $relativePath   = str_replace($moduleAssetsPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $destination    = $publicAssetsPath . DIRECTORY_SEPARATOR . $relativePath;
                $destinationDir = dirname($destination);

                if (!File::isDirectory($destinationDir)) {
                    File::makeDirectory($destinationDir, 0755, true);
                }

                File::copy($file->getPathname(), $destination);
                $copied++;
            }

            // Also copy directories (for empty directories)
            $directories = File::directories($moduleAssetsPath);
            foreach ($directories as $directory) {
                $relativePath = str_replace($moduleAssetsPath . DIRECTORY_SEPARATOR, '', $directory);
                $destination  = $publicAssetsPath . DIRECTORY_SEPARATOR . $relativePath;

                if (!File::isDirectory($destination)) {
                    File::makeDirectory($destination, 0755, true);
                }
            }

            return $copied > 0;
        } catch (Exception $e) {
            $this->warn('     Could not copy log-viewer assets: ' . $e->getMessage());
            // Fallback: try publishing from package
            try {
                Artisan::call('log-viewer:publish', [], $this->getOutput());

                return true;
            } catch (Exception $fallbackException) {
                return false;
            }
        }
    }

    /**
     * Create log directories for organized log storage
     */
    protected function createLogDirectories(): void {
        $logBasePath = storage_path('logs');
        $directories = ['http', 'database', 'cache'];

        foreach ($directories as $directory) {
            $dirPath = $logBasePath . DIRECTORY_SEPARATOR . $directory;
            if (!File::isDirectory($dirPath)) {
                File::makeDirectory($dirPath, 0755, true);
                $this->info("     ✅ Created directory: logs/{$directory}/");
            } else {
                $this->line("     ✓ Directory already exists: logs/{$directory}/");
            }
        }
    }
}
