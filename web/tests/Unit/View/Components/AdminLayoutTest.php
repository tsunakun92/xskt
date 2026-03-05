<?php

namespace Tests\Unit\View\Components;

use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\View\Components\AdminLayout;

class AdminLayoutTest extends TestCase {
    #[Test]
    public function it_renders_admin_layout() {
        $component = new AdminLayout;
        $view      = $component->render();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('layouts.admin', $view->name());
    }
}
