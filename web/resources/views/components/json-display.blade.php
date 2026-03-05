@php
    // Get variables from include (not using @props since we use @include)
    $value = $value ?? '';
    $label = $label ?? '';

    // Parse JSON data using helper function
    $parsed = parse_json_display_data($value);
    $jsonData = $parsed['jsonData'];
    $isJson = $parsed['isJson'];
    $hasGraphQL = $parsed['hasGraphQL'];
    $graphqlQuery = $parsed['graphqlQuery'];
    $otherData = $parsed['otherData'];
@endphp

<div class="border-b border-gray-200 pb-4 dark:border-gray-700">
    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">
        {{ $label }}
    </dt>
    <dd class="mt-1">
        @if ($isJson)
            <div class="relative">
                @if ($hasGraphQL)
                    {{-- Display JSON with GraphQL query formatted separately --}}
                    <div class="space-y-4">
                        @if ($otherData)
                            <div>
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">JSON Data:</div>
                                <pre
                                    class="json-display bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 overflow-x-auto text-sm font-mono"><code class="language-json">{{ $otherData }}</code></pre>
                            </div>
                        @endif

                        <div>
                            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">GraphQL Query:</div>
                            <div class="relative">
                                <pre
                                    class="graphql-display bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 overflow-x-auto text-sm font-mono"><code class="language-graphql">{{ $graphqlQuery }}</code></pre>
                                <button type="button"
                                    class="absolute top-2 right-2 px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 transition-colors"
                                    onclick="window.JsonDisplayHelper && window.JsonDisplayHelper.copyToClipboard(this, '{{ addslashes($graphqlQuery) }}')"
                                    title="Copy GraphQL Query">
                                    <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Regular JSON display --}}
                    <pre
                        class="json-display bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 overflow-x-auto text-sm font-mono"><code class="language-json">{{ $jsonData }}</code></pre>
                    <button type="button"
                        class="absolute top-2 right-2 px-2 py-1 text-xs bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-gray-700 dark:text-gray-300 transition-colors"
                        onclick="copyJsonToClipboard(this)" title="Copy JSON">
                        <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                            </path>
                        </svg>
                    </button>
                @endif
            </div>
        @else
            <div class="text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words">{{ $value ?? '' }}
            </div>
        @endif
    </dd>
</div>

@push('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <script>
        /**
         * JSON Display Helper
         * Utility functions for displaying and formatting JSON and GraphQL queries
         */
        window.JsonDisplayHelper = {
            /**
             * Copy text to clipboard and show feedback
             *
             * @param {HTMLElement} button - The button element that triggered the copy
             * @param {string} text - The text to copy
             */
            copyToClipboard: function(button, text) {
                navigator.clipboard.writeText(text).then(function() {
                    const originalHTML = button.innerHTML;
                    button.innerHTML =
                        '<svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    button.classList.add('bg-green-500', 'text-white');
                    button.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700',
                        'dark:text-gray-300');

                    setTimeout(function() {
                        button.innerHTML = originalHTML;
                        button.classList.remove('bg-green-500', 'text-white');
                        button.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700',
                            'dark:text-gray-300');
                    }, 2000);
                });
            },

            /**
             * Copy JSON from code element to clipboard
             *
             * @param {HTMLElement} button - The button element that triggered the copy
             */
            copyJsonToClipboard: function(button) {
                const code = button.closest('.json-display').querySelector('code');
                const text = code.textContent;
                this.copyToClipboard(button, text);
            },

            /**
             * Format GraphQL query with proper indentation
             *
             * @param {string} query - The GraphQL query string
             * @returns {string} Formatted GraphQL query
             */
            formatGraphQL: function(query) {
                // First, normalize the query - replace escaped newlines and tabs
                query = query.replace(/\\n/g, '\n').replace(/\\t/g, '\t');

                // Remove excessive blank lines (more than 1 consecutive newline)
                query = query.replace(/\n{3,}/g, '\n\n');

                // Split into lines and process
                let lines = query.split('\n');
                let formatted = '';
                let indent = 0;
                const indentSize = 2;

                for (let i = 0; i < lines.length; i++) {
                    let line = lines[i].trim();

                    // Skip empty lines unless they're meaningful
                    if (line === '' && i < lines.length - 1) {
                        let nextLine = lines[i + 1]?.trim() || '';
                        // Only keep blank line if next line is closing brace/paren
                        if (nextLine === '}' || nextLine === ')') {
                            continue;
                        }
                        // Skip consecutive empty lines
                        if (i > 0 && lines[i - 1].trim() === '') {
                            continue;
                        }
                    }

                    // Count opening and closing braces/parentheses to adjust indent
                    let openCount = (line.match(/\{/g) || []).length;
                    let closeCount = (line.match(/\}/g) || []).length;
                    let openParen = (line.match(/\(/g) || []).length;
                    let closeParen = (line.match(/\)/g) || []).length;

                    // Adjust indent for closing braces/parentheses on this line
                    if (closeCount > 0 || closeParen > 0) {
                        indent = Math.max(0, indent - indentSize);
                    }

                    // Add line with proper indent (if not empty)
                    if (line !== '') {
                        formatted += ' '.repeat(indent) + line + '\n';
                    } else if (i === 0 || i === lines.length - 1) {
                        // Keep first and last empty lines if they exist
                        formatted += '\n';
                    }

                    // Adjust indent for opening braces/parentheses
                    if (openCount > 0 || openParen > 0) {
                        indent += indentSize;
                    }
                }

                // Clean up: remove trailing newlines and excessive blank lines
                formatted = formatted.replace(/\n{3,}/g, '\n\n').trim();

                return formatted;
            },

            /**
             * Initialize GraphQL formatting for all GraphQL code blocks on page load
             */
            initGraphQLFormatting: function() {
                const graphqlCodes = document.querySelectorAll('code.language-graphql');
                graphqlCodes.forEach((code) => {
                    let query = code.textContent;
                    // Basic GraphQL formatting (indent based on braces and parentheses)
                    query = this.formatGraphQL(query);
                    code.textContent = query;
                });
            },
        };

        // Auto-initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            JsonDisplayHelper.initGraphQLFormatting();
        });

        // Wrapper functions for backward compatibility and easier usage
        function copyJsonToClipboard(button) {
            if (window.JsonDisplayHelper) {
                window.JsonDisplayHelper.copyJsonToClipboard(button);
            }
        }

        function copyToClipboard(button, text) {
            if (window.JsonDisplayHelper) {
                window.JsonDisplayHelper.copyToClipboard(button, text);
            }
        }
    </script>
@endpush

