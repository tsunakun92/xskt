<?php

namespace App\Datatables\Constants;

/**
 * Datatables Package Constants
 *
 * All constants needed by the datatables package.
 * This replaces dependencies on external constant classes.
 */
class DatatableConstants {
    //-----------------------------------------------------
    // Pagination Constants
    //-----------------------------------------------------
    public const DEFAULT_PAGE_SIZE = 10;

    public const PAGE_SIZE_OPTIONS = [10, 25, 50, 100];

    //-----------------------------------------------------
    // Action Constants
    //-----------------------------------------------------
    public const ACTION_CREATE = 'create';

    public const ACTION_EDIT = 'edit';

    public const ACTION_DELETE = 'delete';

    public const ACTION_VIEW = 'view';

    public const ACTION_SHOW = 'show';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DESTROY = 'destroy';

    public const ACTION_INDEX = 'index';

    public const ACTION_STORE = 'store';

    public const ACTION_PERMISSION = 'permission';

    public const ACTION_EXPORT = 'export';

    //-----------------------------------------------------
    // Value Constants
    //-----------------------------------------------------
    public const VALUE_ZERO = 0;

    public const VALUE_ONE = 1;

    public const VALUE_EMPTY = '';

    public const VALUE_TRUE = true;

    public const VALUE_FALSE = false;

    //-----------------------------------------------------
    // Field Type Constants
    //-----------------------------------------------------
    public const TYPE_FIELD_FORM = 'form';

    public const TYPE_FIELD_FILTER = 'filter';

    //-----------------------------------------------------
    // Configuration Helper Methods
    //-----------------------------------------------------

    /**
     * Get default page size from configuration
     */
    public static function getDefaultPageSize(): int {
        return config('datatables.pagination.default_page_size', self::DEFAULT_PAGE_SIZE);
    }

    /**
     * Get page size options from configuration
     */
    public static function getPageSizeOptions(): array {
        return config('datatables.pagination.page_size_options', self::PAGE_SIZE_OPTIONS);
    }

    /**
     * Get default empty message from configuration
     */
    public static function getEmptyMessage(): string {
        return config('datatables.defaults.empty_message', __('datatables::datatables.no_data'));
    }

    /**
     * Get default loading message from configuration
     */
    public static function getLoadingMessage(): string {
        return config('datatables.defaults.loading_message', __('datatables::datatables.loading'));
    }

    /**
     * Get default error message from configuration
     */
    public static function getErrorMessage(): string {
        return config('datatables.defaults.error_message', __('datatables::datatables.error'));
    }

    /**
     * Get default not found message from configuration
     */
    public static function getNotFoundMessage(): string {
        return config('datatables.defaults.not_found_message', __('datatables::datatables.not_found'));
    }

    /**
     * Get default please select message from configuration
     */
    public static function getPleaseSelectMessage(): string {
        return config('datatables.defaults.please_select_message', __('datatables::datatables.please_select'));
    }
}
