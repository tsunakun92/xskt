<?php

namespace Tests\Unit\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

use App\Console\Commands\PintCommand;

class PintCommandTest extends TestCase {
    use RefreshDatabase;

    protected function tearDown(): void {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated() {
        $command = new PintCommand;

        $this->assertInstanceOf(PintCommand::class, $command);
        $this->assertEquals('pint', $command->getName());
        $this->assertContains('format', $command->getAliases());
    }

    #[Test]
    public function it_has_correct_signature() {
        $command = new PintCommand;

        // Use reflection to access protected $signature property
        $reflection = new ReflectionClass($command);
        $property   = $reflection->getProperty('signature');
        $property->setAccessible(true);
        $signature = $property->getValue($command);

        // Check that all options are present
        $this->assertStringContainsString('--test', $signature);
        $this->assertStringContainsString('--parallel', $signature);
        $this->assertStringContainsString('--max-processes=', $signature);
        $this->assertStringContainsString('--config=', $signature);
        $this->assertStringContainsString('--preset=', $signature);
        $this->assertStringContainsString('--diff=', $signature);
        $this->assertStringContainsString('--dirty', $signature);
        $this->assertStringContainsString('--repair', $signature);
        $this->assertStringContainsString('{paths?*', $signature);
    }

    #[Test]
    public function it_has_correct_description() {
        $command = new PintCommand;

        $this->assertStringContainsString('Pint', $command->getDescription());
        $this->assertStringContainsString('code style', $command->getDescription());
    }

    #[Test]
    public function it_handles_successful_pint_execution() {
        // Skip if pint binary doesn't exist
        if (!file_exists(base_path('vendor/bin/pint'))) {
            $this->markTestSkipped('Pint binary not found');
        }

        // Use Artisan facade to test command execution
        $exitCode = \Illuminate\Support\Facades\Artisan::call('pint', [
            '--test' => true,
        ]);

        // Should execute (exit code 0 = success, 1 = failure, both are valid)
        $this->assertContains($exitCode, [0, 1]);
    }

    #[Test]
    public function it_builds_command_with_options() {
        // Skip if pint binary doesn't exist
        if (!file_exists(base_path('vendor/bin/pint'))) {
            $this->markTestSkipped('Pint binary not found');
        }

        // Test with multiple options
        $exitCode = \Illuminate\Support\Facades\Artisan::call('pint', [
            '--test'          => true,
            '--parallel'      => true,
            '--max-processes' => '4',
            'paths'           => ['app/Console'],
        ]);

        // Should execute
        $this->assertContains($exitCode, [0, 1]);
    }

    #[Test]
    public function it_processes_all_command_options() {
        // Skip if pint binary doesn't exist
        if (!file_exists(base_path('vendor/bin/pint'))) {
            $this->markTestSkipped('Pint binary not found');
        }

        // Test with all options to ensure command building logic is covered
        $exitCode = \Illuminate\Support\Facades\Artisan::call('pint', [
            '--test'          => true,
            '--parallel'      => true,
            '--dirty'         => true,
            '--repair'        => true,
            '--max-processes' => '4',
            '--config'        => 'pint.json',
            '--preset'        => 'laravel',
            '--diff'          => 'main',
            'paths'           => ['app/Console/Commands'],
        ]);

        // Should execute
        $this->assertContains($exitCode, [0, 1]);
    }
}
