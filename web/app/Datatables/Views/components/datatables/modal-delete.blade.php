@props([
    'id' => 'confirm-delete-modal',
    'title' => __('datatables::datatables.delete_confirm'),
    'message' => __('datatables::datatables.delete_confirm_message'),
    'method' => 'DELETE',
])

<div id="{{ $id }}"
    class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden p-4 md:inset-0 h-[calc(100%-1rem)] max-h-full bg-gray-900/50"
    data-modal-ignore="true">
    <div class="relative w-full max-w-md max-h-full mx-auto mt-[15vh]">
        <!-- Modal content -->
        <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
            <!-- Modal header -->
            <div class="flex items-center justify-between rounded-t border-b p-4 dark:border-gray-600 md:p-5">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                <button type="button"
                    class="end-2.5 ms-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-transparent text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white modal-close"
                    data-modal-id="{{ $id }}">
                    <i class="fas fa-times h-3 w-3" aria-hidden="true"></i>
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
                            class="me-2 inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700 modal-close"
                            data-modal-id="{{ $id }}">
                            {{ __('datatables::datatables.cancel') }}
                        </button>
                        <button type="submit"
                            class="inline-flex items-center rounded-lg bg-red-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                            {{ __('datatables::datatables.delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const modalId = '{{ $id }}';
            let isSubmitting = false;
            let currentEntityLabel = '{{ __('admin::crud.not_set') }}';
            const errorTemplate = "{{ __('admin::crud.delete_failed', ['value' => ':value']) }}";

            const getModal = () => document.getElementById(modalId);

            const getCsrfToken = () => {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.getAttribute('content') : '';
            };

            const openModal = (button) => {
                const modal = getModal();
                if (!modal) {
                    return;
                }

                const deleteUrl = button?.dataset?.deleteUrl ?? '';
                currentEntityLabel = button?.dataset?.entity ?? '{{ __('admin::crud.not_set') }}';

                const form = modal.querySelector('form');
                form.action = deleteUrl;
                modal.classList.remove('hidden');
            };

            const closeModal = () => {
                const modal = getModal();
                if (modal) {
                    modal.classList.add('hidden');
                }
            };

            const submitDelete = async () => {
                if (isSubmitting) {
                    return;
                }

                const modal = getModal();
                if (!modal) {
                    return;
                }

                const form = modal.querySelector('form');
                const action = form.action;
                if (!action) {
                    return;
                }

                isSubmitting = true;

                try {
                    const formData = new FormData(form);
                    formData.set('_method', '{{ $method }}');

                    const response = await fetch(action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error('Request failed');
                    }

                    closeModal();
                    window.location.reload();
                } catch (error) {
                    console.error('Delete action failed', error);
                    alert(errorTemplate.replace(':value', currentEntityLabel));
                } finally {
                    isSubmitting = false;
                }
            };

            document.addEventListener('click', (event) => {
                const deleteButton = event.target.closest('.delete-button');
                if (deleteButton) {
                    event.preventDefault();
                    openModal(deleteButton);
                    return;
                }

                const closeButton = event.target.closest('.modal-close');
                if (closeButton) {
                    event.preventDefault();
                    closeModal();
                    return;
                }

                const modal = getModal();
                if (modal && !modal.classList.contains('hidden') && event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('submit', (event) => {
                const form = event.target.closest(`#${modalId} form`);
                if (form) {
                    event.preventDefault();
                    submitDelete();
                }
            });

            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        })();
    </script>
@endpush
