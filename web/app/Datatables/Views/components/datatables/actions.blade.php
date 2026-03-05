@props(['route', 'row', 'extraActions', 'defaultActions' => true])

<div class="flex justify-center space-x-2">
    @if ($route && $defaultActions)
        @canAccess("$route.show")
        <a href="{{ route("$route.show", $row->getRouteParams()) }}" title="{{ __('datatables::datatables.view') }}"
            class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150">
            <i class="fa-solid fa-eye"></i>
        </a>
        @endcanAccess

        @canAccess("$route.edit")
        <a href="{{ route("$route.edit", $row->getRouteParams()) }}" title="{{ __('datatables::datatables.edit') }}"
            class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150">
            <i class="fa-solid fa-edit"></i>
        </a>
        @endcanAccess

        @canAccess("$route.destroy")
        <button type="button" title="{{ __('datatables::datatables.delete') }}"
            class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150 delete-button"
            data-delete-url="{{ route("$route.destroy", $row->getRouteParams()) }}"
            data-entity="{{ __('admin::crud.' . $route . '.title') }}">
            <i class="fa-solid fa-trash"></i>
        </button>
        @endcanAccess
    @endif

    <!-- Extra Actions -->
    @foreach ($extraActions as $action)
        @php
            $rowStatus = (int) ($row->status ?? -1);
            $showWhenStatuses = $action['showWhenStatuses'] ?? null;
            $hideWhenStatuses = $action['hideWhenStatuses'] ?? null;

            $shouldShow = true;
            if (is_array($showWhenStatuses)) {
                $shouldShow = in_array($rowStatus, $showWhenStatuses, true);
            }
            if (is_array($hideWhenStatuses) && in_array($rowStatus, $hideWhenStatuses, true)) {
                $shouldShow = false;
            }
        @endphp

        @if (!$shouldShow)
            @continue
        @endif

        @canAccess($action['route'])
        @if (!empty($action['confirm']))
            @php
                $modalTarget = !empty($action['modalTarget'])
                    ? (string) $action['modalTarget']
                    : 'confirm-action-modal';
            @endphp
            <button type="button" title="{{ $action['label'] }}"
                class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150"
                data-modal-target="{{ $modalTarget }}"
                data-action-url="{{ route($action['route'], $row->getRouteParams()) }}"
                data-action-method="{{ $action['method'] ?? 'POST' }}"
                data-modal-title="{{ $action['confirmTitle'] ?? $action['label'] }}"
                data-modal-message="{{ $action['confirmMessage'] ?? '' }}"
                data-confirm-label="{{ $action['confirmLabel'] ?? $action['label'] }}"
                data-confirm-button-class="{{ $action['confirmButtonClass'] ?? '' }}"
                data-notes="{{ $row->notes ?? '' }}">
                <i class="{{ $action['iconClass'] ?? 'fa-solid fa-circle' }}"></i>
            </button>
        @else
            <a href="{{ route($action['route'], $row->getRouteParams()) }}" title="{{ $action['label'] }}"
                class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150">
                <i class="{{ $action['iconClass'] ?? 'fa-solid fa-circle' }}"></i>
            </a>
        @endif
        @endcanAccess
    @endforeach
</div>
