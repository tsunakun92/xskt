<script>
    window.METOS = window.METOS || {};
    window.METOS.debug = true;

    const translations = {!! json_encode([
        'hide' => __('datatables::datatables.hide'),
        'show' => __('datatables::datatables.show'),
        'empty' => __('datatables::datatables.table_filter.empty'),
        'no_values' => __('datatables::datatables.table_filter.no_values'),
        'select_all' => __('datatables::datatables.table_filter.select_all'),
        'deselect_all' => __('datatables::datatables.table_filter.deselect_all'),
        'apply' => __('datatables::datatables.table_filter.apply'),
        'cancel' => __('datatables::datatables.table_filter.cancel'),
    ]) !!};

    window.METOS.datatables = {
        translations: translations,
        openPanelColumn: null
    };

    const FilterPanel = {
        init() {
            document.querySelectorAll('[data-datatables-root]').forEach((root) => {
                const table = (root.closest('.table-container') || root).querySelector('table');
                if (!table) return;
                this.setupDropdownTriggers(table);
                this.setupDropdownPanels(table);
                this.setupSortOptions(table);
                this.setupFilterControls(table);
            });

            // Global listeners should be attached once, even if init() is called after morph updates.
            this.setupGlobalClickHandlerOnce();
        },

        setupDropdownTriggers(table) {
            (table.closest('.table-container') || table)
            .querySelectorAll('.excel-dropdown-trigger').forEach(trigger => {
                if (trigger.dataset.listenerAttached) return;

                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const column = trigger.dataset.column;
                    const root = table.closest('.table-container') || table.parentElement || table;
                    const panel = root.querySelector(
                        `.excel-dropdown-panel[data-column="${column}"]`);

                    this.closeAllDropdowns(table);
                    panel.classList.toggle('hidden');

                    if (!panel.classList.contains('hidden')) {
                        this.populateFilterValues(table, column);
                    }
                });

                trigger.dataset.listenerAttached = 'true';
            });
        },

        setupDropdownPanels(table) {
            (table.closest('.table-container') || table).querySelectorAll('.excel-dropdown-panel').forEach(
                panel => {
                    if (panel.dataset.clickListenerAttached) return;
                    panel.addEventListener('click', e => e.stopPropagation());
                    panel.dataset.clickListenerAttached = 'true';
                });
        },

        setupSortOptions(table) {
            (table.closest('.table-container') || table).querySelectorAll('.sort-option').forEach(option => {
                if (option.dataset.listenerAttached) return;

                option.addEventListener('click', () => {
                    const {
                        column,
                        direction
                    } = option.dataset;
                    @this.call('sortByColumn', column, direction);
                    this.closeAllDropdowns(table);
                });

                option.dataset.listenerAttached = 'true';
            });
        },

        setupGlobalClickHandlerOnce() {
            if (window.METOS.datatables._globalClickHandlerAttached) return;

            document.addEventListener('click', (e) => {
                // If click is on trigger/panel, let the specific handlers run.
                if (e.target.closest('.excel-dropdown-trigger') || e.target.closest('.excel-dropdown-panel')) {
                    return;
                }

                // Otherwise, close all panels across all datatables instances.
                document.querySelectorAll('[data-datatables-root]').forEach((root) => {
                    const table = (root.closest('.table-container') || root).querySelector('table');
                    if (!table) return;
                    this.closeAllDropdowns(table);
                });
            });

            window.METOS.datatables._globalClickHandlerAttached = true;
        },

        closeAllDropdowns(table) {
            (table.closest('.table-container') || table).querySelectorAll('.excel-dropdown-panel').forEach(
                panel => {
                    panel.classList.add('hidden');
                });
            window.METOS.datatables.openPanelColumn = null;
        },

        populateFilterValues(table, column) {
            const root = table.closest('.table-container') || table.parentElement || table;
            const container = root.querySelector(`.filter-values-container[data-column="${column}"]`);
            if (!container) return;

            this.populateWithTraditionalMethod(column, container);
        },

        populateWithTraditionalMethod(column, container) {
            const {
                translations
            } = window.METOS.datatables;
            const columnFilterValues = @this.columnFilterValues || {};
            const values = columnFilterValues[column] || [];
            const filterKey = column + '_filter';
            const currentFilter = @this.filters[filterKey] || [];
            const shouldCheckAll = !currentFilter || currentFilter.length === 0;

            container.innerHTML = '';

            if (values && values.length > 0) {
                values.forEach(item => {
                    const {
                        filterValue,
                        displayValue
                    } = this.parseFilterItem(item);
                    const isChecked = shouldCheckAll || currentFilter.map(String).includes(String(
                        filterValue));

                    const checkbox = document.createElement('label');
                    checkbox.className =
                        'flex items-center py-1 px-1 hover:bg-gray-50 cursor-pointer w-full';

                    const isEmpty = displayValue === '';
                    const emptyLabel = translations.empty || '(Empty)';
                    const label = isEmpty ? emptyLabel : displayValue;
                    const italicClass = isEmpty ? 'italic' : '';
                    const checkboxId =
                        `filter_${column}_${filterValue}_${Math.random().toString(36).substr(2, 9)}`;

                    checkbox.setAttribute('for', checkboxId);
                    checkbox.innerHTML = `
                            <input type="checkbox"
                                   id="${checkboxId}"
                                   class="filter-checkbox mr-2 h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                   data-column="${column}"
                                   data-value="${filterValue}"
                                   ${isChecked ? 'checked' : ''}>
                            <span class="text-sm text-gray-700 select-none ${italicClass}">${label}</span>
                        `;

                    container.appendChild(checkbox);
                });
            } else {
                container.innerHTML = `<div class="text-center py-4 text-gray-500">${translations.no_values}</div>`;
            }

            container.dataset.populated = 'true';
        },

        parseFilterItem(item) {
            if (typeof item === 'object' && item !== null) {
                return {
                    filterValue: item.key !== undefined ? item.key : item.value,
                    displayValue: item.display !== undefined ? item.display : item.label !== undefined ? item
                        .label : item.value
                };
            }
            return {
                filterValue: item,
                displayValue: item
            };
        },

        setupFilterControls(table) {
            this.setupSelectDeselectButtons(table);
            this.setupCancelButtons(table);
            this.setupApplyButtons(table);
            this.setupFilterCheckboxes(table);
        },

        setupSelectDeselectButtons(table) {
            ['.filter-select-all', '.filter-deselect-all'].forEach(selector => {
                (table.closest('.table-container') || table).querySelectorAll(selector).forEach(btn => {
                    if (btn.dataset.listenerAttached) return;

                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const column = btn.dataset.column;
                        const isSelectAll = selector === '.filter-select-all';

                        (table.closest('.table-container') || table).querySelectorAll(
                            `.filter-checkbox[data-column="${column}"]`).forEach(
                            cb => cb.checked = isSelectAll
                        );
                    });

                    btn.dataset.listenerAttached = 'true';
                });
            });
        },

        setupCancelButtons(table) {
            (table.closest('.table-container') || table).querySelectorAll('.filter-cancel').forEach(btn => {
                if (btn.dataset.listenerAttached) return;

                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.closeAllDropdowns(table);
                });

                btn.dataset.listenerAttached = 'true';
            });
        },

        setupApplyButtons(table) {
            (table.closest('.table-container') || table).querySelectorAll('.filter-apply').forEach(btn => {
                if (btn.dataset.listenerAttached) return;

                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const column = btn.dataset.column;
                    const filterKey = column + '_filter';
                    const selectedValues = [];

                    (table.closest('.table-container') || table).querySelectorAll(
                            `.filter-checkbox[data-column="${column}"]:checked`)
                        .forEach(cb => {
                            selectedValues.push(cb.dataset.value);
                        });

                    @this.set('filters.' + filterKey, selectedValues);
                    this.closeAllDropdowns(table);
                });

                btn.dataset.listenerAttached = 'true';
            });
        },

        setupFilterCheckboxes(table) {
            if (window.METOS.datatables._filterCheckboxListenerAttached) return;
            document.addEventListener('change', (e) => {
                const root = e.target.closest ? e.target.closest('[data-datatables-root]') : null;
                if (!root) return;

                if (e.target.classList.contains('filter-checkbox')) {
                    // Optional: Add any checkbox-specific logic here
                }
            });
            window.METOS.datatables._filterCheckboxListenerAttached = true;
        }
    };

    const DependentFieldReset = {
        init() {
            document.addEventListener('livewire:dispatched:dependentFieldsReset', (event) => {
                this.handleDependentFieldsReset(event.detail);
            });
        },

        handleDependentFieldsReset(eventData) {
            const {
                reset_fields
            } = eventData;
            if (!reset_fields || !Array.isArray(reset_fields)) return;

            reset_fields.forEach(fieldName => {
                this.resetField(fieldName);
            });
        },

        resetField(fieldName) {
            const selectField = document.getElementById(`filter_${fieldName}`);
            if (selectField && selectField.tagName === 'SELECT') {
                selectField.value = '';
                this.triggerLivewireUpdate(selectField);
            }

            this.resetSelectSearchComponent(fieldName);
            this.resetFilterPanelState(fieldName);
        },

        resetSelectSearchComponent(fieldName) {
            const selectSearchContainer = document.querySelector(`[data-field-name="${fieldName}"]`);
            if (selectSearchContainer) {
                const hiddenInput = selectSearchContainer.querySelector('input[type="hidden"]');
                const displayInput = selectSearchContainer.querySelector('input[type="text"]');

                if (hiddenInput) hiddenInput.value = '';
                if (displayInput) displayInput.value = '';
            }
        },

        resetFilterPanelState(fieldName) {
            const container = document.querySelector(`.filter-values-container[data-column="${fieldName}"]`);
            if (container) {
                container.innerHTML = '';
                container.dataset.populated = 'false';
            }
        },

        triggerLivewireUpdate(element) {
            element.dispatchEvent(new Event('change', {
                bubbles: true
            }));
        }
    };

    const PaginationEnhancer = {
        init() {
            this.fixBrokenPaginationButtons();
            this.addKeyboardNavigation();
            this.addSmoothScrolling();
            this.preventDoubleClicks();
        },

        fixBrokenPaginationButtons() {
            document.querySelectorAll('.datatable-pagination button').forEach(button => {
                if (button.textContent.trim() === '') {
                    button.style.display = 'none';
                }
            });
        },

        addKeyboardNavigation() {
            if (window.METOS.datatables._keyboardNavAttached) return;
            document.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

                if (e.key === 'ArrowLeft' && e.ctrlKey) {
                    const prevBtn = document.querySelector('.datatable-pagination .previous');
                    if (prevBtn && !prevBtn.disabled) prevBtn.click();
                }

                if (e.key === 'ArrowRight' && e.ctrlKey) {
                    const nextBtn = document.querySelector('.datatable-pagination .next');
                    if (nextBtn && !nextBtn.disabled) nextBtn.click();
                }
            });
            window.METOS.datatables._keyboardNavAttached = true;
        },

        addSmoothScrolling() {
            document.querySelectorAll('[data-datatables-root] .datatable-pagination button').forEach(button => {
                if (button.dataset.dtSmoothScrollAttached) return;
                button.addEventListener('click', () => {
                    setTimeout(() => {
                        const root = button.closest('[data-datatables-root]');
                        const ctx = root ? (root.closest('.table-container') || root) :
                            document;
                        const table = ctx.querySelector('table');
                        if (table) {
                            table.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }, 100);
                });
                button.dataset.dtSmoothScrollAttached = 'true';
            });
        },

        preventDoubleClicks() {
            document.querySelectorAll('.datatable-pagination button').forEach(button => {
                if (button.dataset.dtPreventDoubleClickAttached) return;
                button.addEventListener('click', function() {
                    this.disabled = true;
                    setTimeout(() => {
                        this.disabled = false;
                    }, 1000);
                });
                button.dataset.dtPreventDoubleClickAttached = 'true';
            });
        }
    };

    const CollapsibleColumns = {
        init() {
            this.ensureNoTransitionStyle();
            this.ensureNoFlickerStyle();
            document.querySelectorAll('[data-datatables-root]').forEach((root) => {
                const table = (root.closest('.table-container') || root).querySelector('table');
                if (!table) {
                    if (root.getAttribute('data-hide-until-init') === '1') {
                        root.removeAttribute('data-hide-until-init');
                        root.style.visibility = '';
                    }
                    return;
                }

                const groupMap = this.buildGroupMap(table);
                if (Object.keys(groupMap).length === 0) {
                    if (root.getAttribute('data-hide-until-init') === '1') {
                        root.removeAttribute('data-hide-until-init');
                        root.style.visibility = '';
                    }
                    return;
                }

                // Apply initial state without flicker
                this.withoutTransitions(root, () => {
                    const key = this.getStorageKey(root);
                    const persisted = this.loadState(key);
                    Object.entries(persisted).forEach(([groupName, isCollapsed]) => {
                        this.applyGroupVisibility(table, groupMap, groupName, !!
                            isCollapsed);
                        this.updateToggleButton(root, groupName, !!isCollapsed);
                    });
                });

                // Reveal after applying state if hidden
                if (root.getAttribute('data-hide-until-init') === '1') {
                    root.removeAttribute('data-hide-until-init');
                    root.style.visibility = '';
                }

                // Attach listeners (idempotent)
                (root.closest('.table-container') || root)
                .querySelectorAll('.collapsible-toggle-btn')
                    .forEach((btn) => {
                        if (btn.dataset.collapsibleListenerAttached) return;
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            const groupName = btn.dataset.group;
                            if (!groupName || !groupMap[groupName]) return;

                            const isCollapsed = btn.classList.toggle('is-collapsed');
                            this.applyGroupVisibility(table, groupMap, groupName, isCollapsed);
                            this.updateToggleButton(root, groupName, isCollapsed);

                            // Persist locally only
                            const key = this.getStorageKey(root);
                            const state = this.loadState(key);
                            state[groupName] = isCollapsed;
                            this.saveState(key, state);
                        });
                        btn.dataset.collapsibleListenerAttached = 'true';
                    });
            });
        },

        ensureNoTransitionStyle() {
            if (document.getElementById('dt-no-transition-style')) return;
            const style = document.createElement('style');
            style.id = 'dt-no-transition-style';
            style.textContent = `
                [data-datatables-root].dt-no-transition *,
                .dt-no-transition * { transition: none !important; }
            `;
            document.head.appendChild(style);
        },

        ensureNoFlickerStyle() {
            if (document.getElementById('dt-no-flicker-style')) return;
            const style = document.createElement('style');
            style.id = 'dt-no-flicker-style';
            style.textContent = `
                [data-datatables-root][data-hide-until-init] { visibility: hidden; }
            `;
            document.head.appendChild(style);
        },

        buildGroupMap(table) {
            const map = {};
            const headerRow = table.tHead ? table.tHead.rows[0] : null;
            if (!headerRow) return map;

            Array.from(headerRow.cells).forEach((th, index) => {
                const groupName = th.getAttribute('data-collapsible-group');
                if (!groupName) return;
                if (!map[groupName]) map[groupName] = [];
                map[groupName].push(index);
            });
            return map;
        },

        applyGroupVisibility(table, groupMap, groupName, isCollapsed) {
            const indices = groupMap[groupName] || [];
            if (indices.length === 0) return;

            // Header
            if (table.tHead && table.tHead.rows[0]) {
                indices.forEach((i) => {
                    const th = table.tHead.rows[0].cells[i];
                    if (th) th.style.display = isCollapsed ? 'none' : '';
                });
            }

            // Body rows
            if (table.tBodies && table.tBodies.length > 0) {
                Array.from(table.tBodies).forEach((tbody) => {
                    Array.from(tbody.rows).forEach((tr) => {
                        indices.forEach((i) => {
                            const td = tr.cells[i];
                            if (td) td.style.display = isCollapsed ? 'none' : '';
                        });
                    });
                });
            }
        },

        updateToggleButton(root, groupName, isCollapsed) {
            const container = root.closest('.table-container') || root;
            const btn = container.querySelector(`.collapsible-toggle-btn[data-group="${groupName}"]`);
            if (!btn) return;
            const iconSpan = btn.querySelector('.collapsible-icon');
            const textSpan = btn.querySelector('.collapsible-text');
            if (iconSpan) {
                iconSpan.innerHTML = isCollapsed ?
                    '<i class="fas fa-plus mr-1"></i>' :
                    '<i class="fas fa-minus mr-1"></i>';
            }
            if (textSpan) {
                textSpan.textContent = isCollapsed ?
                    (window.METOS.datatables.translations.show || 'Show') :
                    (window.METOS.datatables.translations.hide || 'Hide');
            }
            if (isCollapsed) {
                btn.classList.add('is-collapsed');
            } else {
                btn.classList.remove('is-collapsed');
            }
        },

        withoutTransitions(root, fn) {
            const cls = 'dt-no-transition';
            root.classList.add(cls);
            try {
                fn();
            } finally {
                // Allow style application then remove class to prevent flicker
                setTimeout(() => root.classList.remove(cls), 0);
            }
        },
        getStorageKey(root) {
            const tableKey = root.getAttribute('data-table-key') || 'datatable:default';
            return `collapsedGroups:${tableKey}`;
        },
        loadState(key) {
            try {
                const raw = localStorage.getItem(key);
                return raw ? JSON.parse(raw) : {};
            } catch (_) {
                return {};
            }
        },
        saveState(key, state) {
            try {
                localStorage.setItem(key, JSON.stringify(state));
            } catch (_) {}
        }
    };

    // Initialize components
    function initDatatablesUI() {
        FilterPanel.init();
        DependentFieldReset.init();
        PaginationEnhancer.init();
        CollapsibleColumns.init();
    }

    // Initialize on DOM ready and on Livewire load (covers both SSR and dynamic mounts)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDatatablesUI);
    } else {
        try {
            console.debug('[DT] DOM already ready -> init');
        } catch (_) {}
        initDatatablesUI();
    }

    document.addEventListener('livewire:load', initDatatablesUI);

    // Re-apply collapsible state after Livewire morph updates replace DOM (no backend dependency)
    document.addEventListener('livewire:init', () => {
        try {
            if (window.Livewire && Livewire.hook && !window.METOS.datatables._collapsibleHookRegistered) {
                // Defer re-init until after the morph cycle to avoid mutating DOM while Livewire is patching.
                Livewire.hook('morph.updated', () => {
                    window.requestAnimationFrame(() => {
                        try {
                            CollapsibleColumns.init();
                            // Re-attach UI listeners on freshly-morphed DOM (idempotent guards in code).
                            FilterPanel.init();
                            PaginationEnhancer.init();
                        } catch (_) {}
                    });
                });
                window.METOS.datatables._collapsibleHookRegistered = true;
            }
        } catch (_) {}
    });
</script>
