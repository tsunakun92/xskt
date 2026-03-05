<?php

namespace Tests\Unit\View\Components;

use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\View\Components\AppLayout;

class AppLayoutTest extends TestCase {
    #[Test]
    public function it_renders_app_layout() {
        $component = new AppLayout;
        $view      = $component->render();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('layouts.app', $view->name());
    }
}
