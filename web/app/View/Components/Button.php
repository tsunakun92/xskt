<?php

namespace App\View\Components;

use Closure;
use Illuminate\View\Component;

class Button extends Component {
    public $type;

    public $style;

    public $href;

    public $disabled;

    public $label;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($type = 'button', $label = '', $style = 'primary', $href = null, $disabled = false) {
        $this->type     = $type;
        $this->label    = $label;
        $this->style    = $style;
        $this->href     = $href;
        $this->disabled = $disabled;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|Closure|string
     */
    public function render() {
        return view('components.ui.button');
    }
}
