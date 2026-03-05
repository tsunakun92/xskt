@props(['route', 'row', 'extraActions'])

<div class="flex justify-end space-x-2">
    @canAccess($route . '.show')
    <a href="{{ route("$route.show", $row) }}" title="{{ __('admin::crud.show') }}"
        class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
            </path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
            </path>
        </svg>
    </a>
    @endcanAccess

    @canAccess($route . '.edit')
    <a href="{{ route("$route.edit", $row) }}" title="{{ __('admin::crud.edit') }}"
        class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
            </path>
        </svg>
    </a>
    @endcanAccess

    @canAccess($route . '.destroy')
    <button type="button" title="{{ __('admin::crud.delete') }}"
        class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150"
        data-modal-target="confirm-delete-modal" data-delete-url="{{ route("$route.destroy", $row) }}">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
            </path>
        </svg>
    </button>
    @endcanAccess

    <!-- Extra Actions -->
    @foreach ($extraActions as $action)
        @canAccess($action['route'])
        <a href="{{ route($action['route'], $row) }}" title="{{ $action['label'] }}"
            class="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-300 inline-flex items-center px-2 py-1 border border-transparent rounded-md focus:outline-none focus:border-gray-300 transition ease-in-out duration-150">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                </path>
            </svg>
        </a>
        @endcanAccess
    @endforeach
</div>

<x-modal.confirm-delete-modal />
