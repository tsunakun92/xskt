<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TableIndex extends Component {
    public $headers;

    public $rows;

    public $footers      = [];

    public $route        = '';

    public $extraActions = [];

    /**
     * Create a new component instance.
     */
    public function __construct($headers, $rows, $footers = [], $route = '', $extraActions = []) {
        $this->headers      = $headers;
        $this->rows         = $rows;
        $this->footers      = $footers;
        $this->route        = $route;
        $this->extraActions = $extraActions;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render() {
        return view('components.table.table-index');
    }
}
