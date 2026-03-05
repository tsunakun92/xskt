<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * Livewire-compatible select-search component wrapper
 */
class SelectSearchLivewire extends Component {
    public string $name;

    public string $wireModel;

    public ?string $label;

    public ?string $selected;

    public bool $required;

    public bool $disabled;

    public bool $hidden;

    // Search Configuration
    public ?string $searchModel;

    public ?string $searchColumn;

    public ?string $searchDependsOn;

    public array $searchOption;

    // Text Configuration
    public ?string $textDefault;

    public ?string $textLoading;

    public ?string $textError;

    public ?string $textNotFound;

    public ?string $textPleaseSelect;

    // Filter Configuration
    public bool $usePendingFilters;

    public function __construct(
        string $name,
        string $wireModel = '',
        ?string $label = null,
        ?string $selected = null,
        bool $required = false,
        bool $disabled = false,
        bool $hidden = false,
        ?string $searchModel = null,
        ?string $searchColumn = null,
        ?string $searchDependsOn = null,
        array $searchOption = ['id', 'name'],
        ?string $textDefault = null,
        ?string $textLoading = null,
        ?string $textError = null,
        ?string $textNotFound = null,
        ?string $textPleaseSelect = null,
        bool $usePendingFilters = false
    ) {
        $this->name      = $name;
        $this->wireModel = $wireModel ?: ($usePendingFilters ? "pendingFilters.{$name}" : "filters.{$name}");
        $this->label     = $label;
        $this->selected  = $selected;
        $this->required  = $required;
        $this->disabled  = $disabled;
        $this->hidden    = $hidden;

        $this->searchModel     = $searchModel;
        $this->searchColumn    = $searchColumn;
        $this->searchDependsOn = $searchDependsOn;
        $this->searchOption    = $searchOption;

        $this->textDefault       = $textDefault ?: __('crud.please_select');
        $this->textLoading       = $textLoading ?: __('crud.loading');
        $this->textError         = $textError ?: __('crud.error');
        $this->textNotFound      = $textNotFound ?: __('crud.not_found');
        $this->textPleaseSelect  = $textPleaseSelect ?: __('crud.please_select');
        $this->usePendingFilters = $usePendingFilters;
    }

    public function render() {
        return view('components.form.select-search-livewire');
    }
}
