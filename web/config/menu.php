<?php

/**
 * Configuration file for menu
 * This file automatically merges menu items from all modules
 *
 * @var array
 *            Note:
 *            - icon: use fontawesome icon class https://fontawesome.com/search
 *            - Each module can define its own menu.php in config folder
 *            - Module menus will be automatically merged into this file
 */

// Base menu items (not from modules)
$baseMenu = [
    [
        'label' => 'home',
        'route' => 'admin',
        'icon'  => '',
    ],
];

// Auto merge menu from all modules
$modulesPath = base_path('Modules');
$mergedMenu  = $baseMenu;

if (!function_exists('attachModuleToMenuItems')) {
    /**
     * Attach module information to menu items (and children) to allow views to resolve the correct language: {module}::menu.{label}
     *
     * @param  array  $items
     * @param  string  $moduleName
     * @return array
     */
    function attachModuleToMenuItems(array $items, string $moduleName): array {
        foreach ($items as &$item) {
            $item['module'] = $moduleName;
            if (isset($item['children']) && is_array($item['children'])) {
                foreach ($item['children'] as &$child) {
                    $child['module'] = $moduleName;
                }
                unset($child);
            }
        }
        unset($item);

        return $items;
    }
}

if (is_dir($modulesPath)) {
    $modules = array_filter(glob($modulesPath . '/*'), 'is_dir');

    foreach ($modules as $modulePath) {
        $moduleName      = basename($modulePath);
        $moduleNameLower = strtolower($moduleName);
        $menuFile        = $modulePath . '/config/menu.php';

        if (file_exists($menuFile)) {
            $moduleMenu = require $menuFile;
            if (is_array($moduleMenu)) {
                $mergedMenu = array_merge($mergedMenu, attachModuleToMenuItems($moduleMenu, $moduleNameLower));
            }
        }
    }
}

return $mergedMenu;
