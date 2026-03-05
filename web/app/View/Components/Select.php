<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Select extends Component {
    public string $name;

    public ?string $label;

    public ?string $id;

    public bool $required;

    public bool $disabled;

    public array $options;

    public ?string $selected;

    public bool $readonly;

    public bool $hidden;

    public function __construct(
        string $name,
        array $options = [],
        ?string $label = null,
        ?string $id = null,
        ?string $selected = null,
        bool $required = false,
        bool $disabled = false,
        bool $readonly = false,
        bool $hidden = false
    ) {
        $this->name     = $name;
        $this->label    = $label;
        $this->id       = $id ?? $name;
        $this->options  = $options;
        $this->selected = $selected;
        $this->required = $required;
        $this->disabled = $disabled;
        $this->readonly = $readonly;
        $this->hidden   = $hidden;
    }

    public function render() {
        return view('components.form.select');
    }
}
