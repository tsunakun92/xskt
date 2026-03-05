<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Container extends Component {
    /**
     * The layout style for the container.
     *
     * @var string
     */
    public $layout;

    /**
     * Create a new component instance.
     *
     * @param  string  $layout
     * @return void
     */
    public function __construct($layout = null) {
        $this->layout = $layout ?? config('app.layout', 'default');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render() {
        return view('components.container');
    }
}
