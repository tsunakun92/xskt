@props([
    'id' => 'confirm-action-modal',
    'title' => __('admin::crud.delete_confirm'),
    'message' => __('admin::crud.delete_confirm_message'),
    'confirmLabel' => __('admin::crud.delete'),
    'confirmButtonClass' => 'inline-flex items-center rounded-lg bg-amber-600 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-4 focus:ring-amber-200 dark:bg-amber-600 dark:hover:bg-amber-700 dark:focus:ring-amber-800',
])

<div id="{{ $id }}"
    class="fixed inset-0 z-50 hidden overflow-y-auto overflow-x-hidden p-4 md:inset-0 h-[calc(100%-1rem)] max-h-full bg-gray-900/50">
    <div class="relative w-full max-w-md max-h-full mx-auto mt-[15vh]">
        <div class="relative rounded-lg bg-white shadow dark:bg-gray-700">
            <div class="flex items-center justify-between rounded-t border-b p-4 dark:border-gray-600 md:p-5">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white" data-confirm-title>
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
            <div class="p-4 md:p-5">
                <form method="POST">
                    @csrf
                    <input type="hidden" name="_method" value="POST" data-confirm-method>
                    <p class="mb-4 text-gray-500 dark:text-gray-300 text-start" data-confirm-message>
                        {{ $message }}
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button"
                            class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                            data-modal-hide="{{ $id }}">
                            {{ __('admin::crud.cancel') }}
                        </button>
                        <button type="submit" class="{{ $confirmButtonClass }}" data-confirm-submit>
                            {{ $confirmLabel }}
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
            const init = () => {
                const modalId = @js($id);
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }

                const form = modal.querySelector('form');
                const titleEl = modal.querySelector('[data-confirm-title]');
                const messageEl = modal.querySelector('[data-confirm-message]');
                const methodEl = modal.querySelector('[data-confirm-method]');
                const submitBtn = modal.querySelector('[data-confirm-submit]');

                const openModal = (button) => {
                    const url = button?.dataset?.actionUrl ?? '';
                    const title = button?.dataset?.modalTitle ?? '';
                    const message = button?.dataset?.modalMessage ?? '';
                    const method = (button?.dataset?.actionMethod ?? 'POST').toUpperCase();
                    const confirmLabel = button?.dataset?.confirmLabel ?? '';
                    const confirmClass = button?.dataset?.confirmButtonClass ?? '';

                    if (url) {
                        form.action = url;
                    }

                    if (title && titleEl) {
                        titleEl.textContent = title;
                    }

                    if (message && messageEl) {
                        messageEl.textContent = message;
                    }

                    if (methodEl) {
                        methodEl.value = method;
                    }

                    if (confirmLabel && submitBtn) {
                        submitBtn.textContent = confirmLabel;
                    }

                    if (confirmClass && submitBtn) {
                        submitBtn.className = confirmClass;
                    }

                    modal.classList.remove('hidden');
                };

                const closeModal = () => {
                    modal.classList.add('hidden');
                };

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest(`[data-modal-target="${modalId}"]`);
                    if (trigger) {
                        event.preventDefault();
                        openModal(trigger);
                        return;
                    }

                    const closeButton = event.target.closest(`[data-modal-hide="${modalId}"]`);
                    if (closeButton) {
                        event.preventDefault();
                        closeModal();
                        return;
                    }

                    if (!modal.classList.contains('hidden') && event.target === modal) {
                        closeModal();
                    }
                });

                window.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        closeModal();
                    }
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
@endpush


