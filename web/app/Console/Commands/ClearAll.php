<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;

use Modules\Logging\Utils\LogHandler;

/**
 * Clear application caches, including Lighthouse schema cache.
 */
class ClearAll extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearall';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears routes, config, cache, views, compiled, and caches config.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        LogHandler::info('Starting to clear cache and config', [
            'command' => 'clearall',
        ]);

        $validCommands = [
            'route:clear',
            'config:clear',
            'cache:clear',
            'view:clear',
            'clear-compiled',
        ];
        foreach ($validCommands as $cmd) {
            try {
                $this->call('' . $cmd . '');
                LogHandler::debug('Command executed successfully', [
                    'command' => $cmd,
                ]);
            } catch (Exception $e) {
                LogHandler::error('Error executing command', [
                    'command' => $cmd,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        $this->clearLighthouseSchemaCache();

        LogHandler::info('Completed clearing cache and config', [
            'command' => 'clearall',
        ]);

        return self::SUCCESS;
    }

    /**
     * Clear Lighthouse schema cache.
     *
     * @return void
     */
    protected function clearLighthouseSchemaCache(): void {
        try {
            try {
                $this->call('lighthouse:clear-cache');
                LogHandler::debug('Command executed successfully', [
                    'command' => 'lighthouse:clear-cache',
                ]);
            } catch (Exception $e) {
                try {
                    $this->call('lighthouse:clear-schema-cache');
                    LogHandler::debug('Command executed successfully', [
                        'command' => 'lighthouse:clear-schema-cache',
                    ]);
                } catch (Exception $e2) {
                    $schemaCachePath = base_path('bootstrap/cache/lighthouse-schema.php');
                    if (file_exists($schemaCachePath)) {
                        @unlink($schemaCachePath);
                    }
                    LogHandler::debug('Lighthouse schema cache file cleared', [
                        'path' => $schemaCachePath,
                    ]);
                }
            }
        } catch (Exception $e) {
            LogHandler::error('Error clearing Lighthouse schema cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
