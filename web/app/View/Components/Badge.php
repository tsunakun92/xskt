<?php

namespace App\View\Components;

use Closure;
use Illuminate\View\Component;

class Badge extends Component {
    public $style;

    public $href;

    public $type;

    public $label;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($type = 'primary', $label = '', $style = 'primary') {
        $this->type  = $type;
        $this->label = $label;
        $this->style = $style;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|Closure|string
     */
    public function render() {
        return view('components.ui.badge');
    }
}
