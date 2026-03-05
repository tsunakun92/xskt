<?php

namespace Tests\Unit\View\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SectionTest extends TestCase {
    public function test_renders_section_with_content() {
        $view = Blade::render('<x-section><h1>Section Title</h1></x-section>');
        $this->assertStringContainsString('Section Title', $view);
        $this->assertStringContainsString('bg-white dark:bg-gray-800', $view);
    }

    public function test_renders_section_with_header_prop() {
        $view = Blade::render('<x-section header="Test Header"><p>Section Content</p></x-section>');
        $this->assertStringContainsString('Section Content', $view);
        $this->assertStringContainsString('Test Header', $view);
        $this->assertStringContainsString('border-b border-gray-200', $view);
    }

    public function test_renders_section_with_header_slot() {
        $view = Blade::render('
            <x-section>
                <x-slot name="header">
                    <h2>Custom Header</h2>
                </x-slot>
                <p>Section Content</p>
            </x-section>
        ');
        $this->assertStringContainsString('Section Content', $view);
        $this->assertStringContainsString('Custom Header', $view);
    }

    public function test_renders_section_without_header() {
        $view = Blade::render('<x-section><p>Section Content</p></x-section>');
        $this->assertStringContainsString('Section Content', $view);
        $this->assertStringNotContainsString('border-b border-gray-200', $view);
    }
}
