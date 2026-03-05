<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Section extends Component {
    public $title;

    /**
     * Create a new component instance.
     *
     * @param  string|null  $title
     */
    public function __construct($title = null) {
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        return view('components.section');
    }
}
