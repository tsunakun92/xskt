<?php

namespace Modules\Logging\Tests\Unit;

use Tests\TestCase;

class HelpersTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();

        // Ensure helper file is loaded so the global function exists
        $helpersPath = base_path('Modules/Logging/app/Utils/helpers.php');
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    public function test_laravel_version_returns_string_when_no_argument() {
        $version = laravel_version();

        $this->assertIsString($version);
        $this->assertNotSame('', $version);
    }

    public function test_laravel_version_compares_prefix_correctly() {
        $fullVersion = app()->version();
        $prefix      = substr($fullVersion, 0, 3);

        $this->assertTrue(laravel_version($prefix));
        $this->assertFalse(laravel_version('0.0')); // Very unlikely real version starts with 0.0
    }
}
