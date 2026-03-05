<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use SimpleXMLElement;

class SplitTestResults extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:split-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split PHPUnit junit.xml into success and error result files';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int {
        $resultsPath = base_path('storage/test-results');
        $junitFile   = $resultsPath . DIRECTORY_SEPARATOR . 'junit.xml';

        if (!File::exists($junitFile)) {
            $this->error('JUnit results file not found at: ' . $junitFile);

            return 1;
        }

        $xml = simplexml_load_file($junitFile);

        if (!$xml instanceof SimpleXMLElement) {
            $this->error('Unable to read junit.xml as XML.');

            return 1;
        }

        $successLines = [];
        $errorLines   = [];

        /** @var SimpleXMLElement $testsuite */
        foreach ($xml->xpath('//testcase') as $testcase) {
            $class = (string) ($testcase['class'] ?? '');
            $name  = (string) ($testcase['name'] ?? '');
            $time  = (string) ($testcase['time'] ?? '');

            $label      = trim($class . '::' . $name, ':');
            $linePrefix = $label !== '' ? $label : 'unknown';

            $errorNode   = $testcase->error ?? null;
            $failureNode = $testcase->failure ?? null;

            if ($errorNode || $failureNode) {
                $node = $errorNode ?: $failureNode;

                $type    = (string) ($node['type'] ?? 'Error');
                $message = (string) ($node['message'] ?? '');

                $errorLines[] = sprintf(
                    '[ERROR] %s (%ss) [%s] %s',
                    $linePrefix,
                    $time !== '' ? $time : '0',
                    $type,
                    $message
                );
            } else {
                $successLines[] = sprintf(
                    '[OK] %s (%ss)',
                    $linePrefix,
                    $time !== '' ? $time : '0'
                );
            }
        }

        File::ensureDirectoryExists($resultsPath);

        File::put($resultsPath . DIRECTORY_SEPARATOR . 'success.txt', implode(PHP_EOL, $successLines) . PHP_EOL);
        File::put($resultsPath . DIRECTORY_SEPARATOR . 'errors.txt', implode(PHP_EOL, $errorLines) . PHP_EOL);

        $this->info('Test results split into success.txt and errors.txt.');

        return 0;
    }
}
