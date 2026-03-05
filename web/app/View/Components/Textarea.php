<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Textarea extends Component {
    public string $name;

    public ?string $label;

    public ?string $id;

    public ?string $value;

    public int $rows;

    public bool $required;

    public bool $readonly;

    public bool $disabled;

    public bool $hidden;

    public function __construct(
        string $name,
        ?string $label = null,
        ?string $id = null,
        ?string $value = '',
        int $rows = 3,
        bool $required = false,
        bool $readonly = false,
        bool $disabled = false,
        bool $hidden = false
    ) {
        $this->name     = $name;
        $this->label    = $label;
        $this->id       = $id ?? $name;
        $this->value    = $value;
        $this->rows     = $rows;
        $this->required = $required;
        $this->readonly = $readonly;
        $this->disabled = $disabled;
        $this->hidden   = $hidden;
    }

    public function render() {
        return view('components.form.textarea');
    }
}
