<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Form extends Component {
    public string $method;

    public string $action;

    public function __construct(string $method = 'POST', string $action = '#') {
        $this->method = strtoupper($method);
        $this->action = $action;
    }

    public function render() {
        return view('components.form.form');
    }
}
