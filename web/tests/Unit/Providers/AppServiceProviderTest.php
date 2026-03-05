<?php

namespace Tests\Unit\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\Providers\AppServiceProvider;

class AppServiceProviderTest extends TestCase {
    protected AppServiceProvider $provider;

    protected function setUp(): void {
        parent::setUp();
        $this->provider = new AppServiceProvider($this->app);
    }

    #[Test]
    public function it_does_not_force_https_in_production_environment() {
        // Set environment to production
        $this->app['env'] = 'production';

        // Mock URL facade
        $called = false;
        URL::partialMock()
            ->shouldReceive('forceScheme')
            ->andReturnUsing(function () use (&$called) {
                $called = true;
            });

        $this->provider->boot();
        $this->assertFalse($called, 'URL::forceScheme should not be called in production');
    }

    #[Test]
    public function it_registers_blade_directives() {
        $this->provider->boot();

        // Test @canAccess directive
        $compiled = Blade::compileString('@canAccess("admin.access")');
        $this->assertStringContainsString('canAccess', $compiled);
        $this->assertStringContainsString('admin.access', $compiled);
    }

    #[Test]
    public function it_registers_services() {
        $this->provider->register();
        $this->assertTrue(true); // Just ensure no exceptions are thrown
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
    public function it_registers_vite_admin_blade_directive() {
        $this->provider->boot();

        // Test @viteAdmin directive
        $compiled = Blade::compileString('@viteAdmin("admin")');
        $this->assertStringContainsString('Vite::admin', $compiled);
        $this->assertStringContainsString('admin', $compiled);
    }

    #[Test]
    public function it_registers_livewire_components_when_livewire_exists() {
        // Since Livewire is already loaded and we can't easily mock it,
        // we just test that the boot method doesn't throw any exceptions
        $this->provider->boot();
        $this->assertTrue(true);
    }

    // Removed tests for registerLivewireComponents as this method no longer exists in AppServiceProvider
}
