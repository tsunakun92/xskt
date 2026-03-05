<?php

namespace Tests\Unit\View\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ContainerTest extends TestCase {
    public function test_renders_container_with_content() {
        $view = Blade::render('<x-container><p>Container Content</p></x-container>');
        $this->assertStringContainsString('Container Content', $view);
    }
}
