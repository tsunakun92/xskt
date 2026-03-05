@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.quill-editor-container').forEach(function(container) {
                    if (container.dataset.quillInitialized) return;
                    container.dataset.quillInitialized = 'true';

                    const editorId = container.dataset.editorId;
                    const hiddenInput = document.getElementById(container.dataset.hiddenInputId);
                    const htmlViewId = editorId + '_html';
                    const htmlView = document.getElementById(htmlViewId);
                    let isHtmlMode = false;

                    // Full toolbar configuration
                    const toolbarOptions = [
                        [{
                            'header': [1, 2, 3, 4, 5, 6, false]
                        }],
                        [{
                            'font': []
                        }],
                        [{
                            'size': ['small', false, 'large', 'huge']
                        }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{
                            'script': 'sub'
                        }, {
                            'script': 'super'
                        }],
                        [{
                            'color': []
                        }, {
                            'background': []
                        }],
                        [{
                            'align': []
                        }],
                        [{
                            'list': 'ordered'
                        }, {
                            'list': 'bullet'
                        }],
                        [{
                            'indent': '-1'
                        }, {
                            'indent': '+1'
                        }],
                        [{
                            'direction': 'rtl'
                        }],
                        ['blockquote', 'code-block'],
                        ['link', 'image', 'video'],
                        ['clean'],
                        ['html'] // Custom HTML view button
                    ];

                    const quill = new Quill('#' + editorId, {
                        theme: 'snow',
                        modules: {
                            toolbar: {
                                container: toolbarOptions,
                                handlers: {
                                    'html': function() {
                                        if (isHtmlMode) {
                                            // Switch back to visual editor
                                            const htmlContent = htmlView.value;
                                            quill.root.innerHTML = htmlContent;
                                            document.querySelector('#' + editorId).style.display =
                                                'block';
                                            htmlView.style.display = 'none';
                                            isHtmlMode = false;
                                            if (hiddenInput) hiddenInput.value = htmlContent;
                                        } else {
                                            // Switch to HTML view
                                            const htmlContent = quill.root.innerHTML;
                                            htmlView.value = htmlContent;
                                            document.querySelector('#' + editorId).style.display =
                                                'none';
                                            htmlView.style.display = 'block';
                                            isHtmlMode = true;
                                        }
                                    }
                                }
                            }
                        }
                    });

                    // Update HTML view when content changes
                    if (htmlView) {
                        htmlView.addEventListener('blur', function() {
                            if (isHtmlMode) {
                                quill.root.innerHTML = htmlView.value;
                                if (hiddenInput) hiddenInput.value = htmlView.value;
                            }
                        });
                    }

                    if (hiddenInput?.value) {
                        quill.root.innerHTML = hiddenInput.value;
                    }

                    quill.on('text-change', () => {
                        if (!isHtmlMode && hiddenInput) {
                            hiddenInput.value = quill.root.innerHTML;
                        }
                    });

                    const form = hiddenInput?.closest('form');
                    if (form) {
                        form.addEventListener('submit', () => {
                            if (isHtmlMode) {
                                hiddenInput.value = htmlView.value;
                            } else {
                                hiddenInput.value = quill.root.innerHTML;
                            }
                        });
                    }
                });
            });
        </script>
    @endpush
@endonce

