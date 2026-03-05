<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Blank Layout Component
 *
 * Provides a blank layout for pages with customizable content
 */
class BlankLayout extends Component {
    /**
     * Page title for the HTML <title> tag
     *
     * @var string|null
     */
    public ?string $pageTitle;

    /**
     * Create a new component instance.
     *
     * @param  string|null  $pageTitle
     */
    public function __construct(?string $pageTitle = null) {
        $this->pageTitle = $pageTitle;
    }

    /**
     * Get the view / contents that represents the component.
     *
     * @return View
     */
    public function render(): View {
        return view('layouts.blank');
    }
}
