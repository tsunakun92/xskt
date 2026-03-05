<?php

namespace Tests\Unit\View\Components;

use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\View\Components\Breadcrumb;

class BreadcrumbTest extends TestCase {
    #[Test]
    public function it_renders_breadcrumb_component() {
        $items = [
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Products', 'url' => '/products'],
            ['label' => 'Current Page'],
        ];

        $component = new Breadcrumb($items);
        $view      = $component->render();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.breadcrumb', $view->name());
        $this->assertEquals($items, $component->items);
    }

    #[Test]
    public function it_handles_empty_items_array() {
        $component = new Breadcrumb([]);
        $view      = $component->render();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.breadcrumb', $view->name());
        $this->assertEquals([], $component->items);
    }

    #[Test]
    public function it_handles_default_empty_constructor() {
        $component = new Breadcrumb;
        $view      = $component->render();

        $this->assertInstanceOf(View::class, $view);
        $this->assertEquals('components.breadcrumb', $view->name());
        $this->assertEquals([], $component->items);
    }

    #[Test]
    public function it_stores_items_correctly() {
        $items = [
            ['label' => 'Dashboard', 'url' => '/dashboard'],
            ['label' => 'Settings'],
        ];

        $component = new Breadcrumb($items);

        $this->assertEquals($items, $component->items);
    }
}
