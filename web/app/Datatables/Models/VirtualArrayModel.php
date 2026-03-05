<?php

namespace App\Datatables\Models;

/**
 * Minimal shim used by ArrayDatatables to satisfy view calls that reference
 * Model static methods (e.g., getFilterPanelArray()).
 */
class VirtualArrayModel {
    protected static array $filterPanel = [];

    public static function setFilterPanel(array $columns): void {
        self::$filterPanel = $columns;
    }

    public static function getFilterPanelArray(): array {
        return self::$filterPanel;
    }
}
