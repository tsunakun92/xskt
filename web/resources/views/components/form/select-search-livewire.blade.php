@props([
    'name',
    'wireModel' => '',
    'label' => null,
    'selected' => null,
    'required' => false,
    'disabled' => false,
    'hidden' => false,
    'searchModel' => null,
    'searchColumn' => null,
    'searchDependsOn' => null,
    'searchOption' => ['id', 'name'],
    'textDefault' => null,
    'textLoading' => null,
    'textError' => null,
    'textNotFound' => null,
    'textPleaseSelect' => null,
    'usePendingFilters' => false,
])

<livewire:select-search-livewire :name="$name" :wire-model="$wireModel ?: ($usePendingFilters ? 'pendingFilters.' . $name : 'filters.' . $name)" :label="$label" :selected="$selected"
    :required="$required" :disabled="$disabled" :hidden="$hidden" :search-model="$searchModel" :search-column="$searchColumn" :search-depends-on="$searchDependsOn"
    :search-option="$searchOption" :text-default="$textDefault" :text-loading="$textLoading" :text-error="$textError" :text-not-found="$textNotFound" :text-please-select="$textPleaseSelect"
    :use-pending-filters="$usePendingFilters" :key="$name . '_select_search_livewire'" />
