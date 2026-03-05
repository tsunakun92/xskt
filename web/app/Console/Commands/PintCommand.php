<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class PintCommand extends Command {
    /**
     * The name and signature of the console command.
     *
     * We also provide "format" as an alias so you can run:
     *  - php artisan pint
     *  - php artisan format
     */
    protected $signature = 'pint {paths?* : Files or directories to format}
                            {--test : Only test for code style errors without changing any files}
                            {--parallel : Run Pint in parallel mode}
                            {--max-processes= : Maximum number of parallel processes}
                            {--config= : Path to a custom pint.json config file}
                            {--preset= : Pint preset to use (laravel, psr12, symfony, per, empty)}
                            {--diff= : Only inspect files that differ from the given Git branch}
                            {--dirty : Only inspect files with uncommitted changes}
                            {--repair : Fix errors but still exit with a non-zero status if any were fixed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run Laravel Pint (code style fixer) with convenient Artisan syntax';

    /**
     * Additional command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['format'];

    /**
     * Execute the console command.
     */
    public function handle(): int {
        $binary = base_path('vendor/bin/pint');

        if (!file_exists($binary)) {
            $this->error('Pint binary not found. Please install it via: composer require laravel/pint --dev');

            return self::FAILURE;
        }

        $command = [$binary];

        // Flags
        if ($this->option('test')) {
            $command[] = '--test';
        }

        if ($this->option('parallel')) {
            $command[] = '--parallel';
        }

        if ($this->option('dirty')) {
            $command[] = '--dirty';
        }

        if ($this->option('repair')) {
            $command[] = '--repair';
        }

        // Options with values
        if ($maxProcesses = $this->option('max-processes')) {
            $command[] = '--max-processes=' . $maxProcesses;
        }

        if ($config = $this->option('config')) {
            $command[] = '--config=' . $config;
        }

        if ($preset = $this->option('preset')) {
            $command[] = '--preset=' . $preset;
        }

        if ($diff = $this->option('diff')) {
            $command[] = '--diff=' . $diff;
        }

        // Paths (files / directories)
        $paths = $this->argument('paths') ?? [];
        foreach ($paths as $path) {
            $command[] = $path;
        }

        $this->info('Running Pint: ' . implode(' ', $command));

        $process = new Process($command, base_path());
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (!$process->isSuccessful()) {
            return self::FAILURE;
        }

        // Run check_use_order.php --fix after Pint succeeds (skip if --test mode)
        if (!$this->option('test')) {
            $this->info('');
            $this->info('Fixing use statement order...');

            $useOrderScript = base_path('check_use_order.php');

            if (file_exists($useOrderScript)) {
                $useOrderCommand = ['php', $useOrderScript, '--fix'];

                // Add paths if provided
                $paths = $this->argument('paths') ?? [];
                foreach ($paths as $path) {
                    $useOrderCommand[] = $path;
                }

                $useOrderProcess = new Process($useOrderCommand, base_path());
                $useOrderProcess->setTimeout(null);

                $useOrderProcess->run(function ($type, $buffer) {
                    $this->output->write($buffer);
                });

                if (!$useOrderProcess->isSuccessful()) {
                    $this->warn('Failed to fix use statement order');
                    // Don't fail the command, just warn
                }
            } else {
                $this->warn('check_use_order.php not found, skipping use statement order fix');
            }
        }

        return self::SUCCESS;
    }
}
