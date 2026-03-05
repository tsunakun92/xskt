<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Input extends Component {
    public string $type;

    public string $name;

    public ?string $label;

    public ?string $id;

    public ?string $value;

    public bool $required;

    public bool $readonly;

    public bool $disabled;

    public bool $hidden;

    public ?array $datalist;

    public ?string $step;

    public function __construct(
        string $name,
        string $type = 'text',
        ?string $label = null,
        ?string $id = null,
        ?string $value = '',
        ?string $step = null,
        bool $required = false,
        bool $readonly = false,
        bool $disabled = false,
        bool $hidden = false,
        ?array $datalist = null
    ) {
        $this->name     = $name;
        $this->type     = $type;
        $this->label    = $label;
        $this->id       = $id ?? $name;
        $this->value    = $value;
        $this->step     = $step;
        $this->required = $required;
        $this->readonly = $readonly;
        $this->disabled = $disabled;
        $this->hidden   = $hidden;
        $this->datalist = $datalist;
    }

    public function render() {
        return view('components.form.input');
    }
}
