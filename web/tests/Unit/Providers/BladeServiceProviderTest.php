<?php

namespace Tests\Unit\Providers;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Providers\BladeServiceProvider;

class BladeServiceProviderTest extends TestCase {
    protected BladeServiceProvider $provider;

    protected function setUp(): void {
        parent::setUp();
        $this->provider = new BladeServiceProvider($this->app);
    }

    #[Test]
    public function it_registers_can_access_blade_directive() {
        $this->provider->boot();

        // Test @canAccess directive
        $compiled = Blade::compileString('@canAccess("admin.access")');
        $this->assertStringContainsString('canAccess', $compiled);
        $this->assertStringContainsString('admin.access', $compiled);
    }

    #[Test]
    public function it_registers_vite_admin_directive() {
        $this->provider->boot();

        // Test @viteAdmin directive
        $compiled = Blade::compileString('@viteAdmin("app.js")');
        $this->assertStringContainsString('Vite::admin', $compiled);
        $this->assertStringContainsString('app.js', $compiled);
    }

    #[Test]
    public function it_registers_services() {
        $this->provider->register();
        $this->assertTrue(true); // Just ensure no exceptions are thrown
    }
}
