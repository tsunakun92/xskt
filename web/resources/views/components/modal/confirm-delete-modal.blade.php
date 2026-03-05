@props([
    'id' => 'confirm-delete-modal',
    'title' => __('admin::crud.delete_confirm'),
    'message' => __('admin::crud.delete_confirm_message'),
    'method' => 'DELETE',
])

<div id="{{ $id }}"
    class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden p-4 md:inset-0 h-[calc(100%-1rem)] max-h-full bg-gray-900/50">
    <div class="relative w-full max-w-md max-h-full mx-auto mt-[15vh]">
        <!-- Modal content -->
        <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between rounded-t border-b p-4 dark:border-gray-600 md:p-5">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                <button type="button"
                    class="end-2.5 ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
                    data-modal-hide="{{ $id }}">
                    <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5">
                <form method="POST">
                    @csrf
                    @method($method)
                    <p class="mb-4 text-gray-500 dark:text-gray-300 text-start">
                        {{ $message }}
                    </p>
                    <div class="flex justify-end">
                        <button type="button"
                            class="me-2 inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                            data-modal-hide="{{ $id }}">
                            {{ __('admin::crud.cancel') }}
                        </button>
                        <button type="submit"
                            class="inline-flex items-center rounded-lg bg-red-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                            {{ __('admin::crud.delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('[data-modal-target="{{ $id }}"]');
            const modal = document.getElementById('{{ $id }}');
            const form = modal.querySelector('form');

            deleteButtons.forEach(button => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.deleteUrl;
                    modal.classList.remove('hidden');
                });
            });

            const closeButtons = document.querySelectorAll('[data-modal-hide="{{ $id }}"]');
            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    modal.classList.add('hidden');
                });
            });

            // Close modal when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });
    </script>
@endpush
