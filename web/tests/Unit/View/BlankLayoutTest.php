<?php

namespace Tests\Unit\View;

use Illuminate\View\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use App\View\Components\BlankLayout;

class BlankLayoutTest extends TestCase {
    #[Test]
    public function it_sets_page_title_and_renders_blank_layout_view(): void {
        $component = new BlankLayout('Test Title');

        $this->assertSame('Test Title', $component->pageTitle);

        $view = $component->render();
        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('layouts.blank', $view->name());
    }
}
