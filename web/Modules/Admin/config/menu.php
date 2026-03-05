<?php

/**
 * Configuration file for Admin module menu
 *
 * @var array
 *            Note:
 *            - icon: use fontawesome icon class https://fontawesome.com/search
 */

return [
    [
        'label'    => 'admin',
        'icon'     => '',
        'route'    => 'admin.module',
        'children' => [
            [
                'label' => 'user',
                'route' => 'users.index',
                'icon'  => 'fas fa-users',
            ],
            [
                'label' => 'role',
                'icon'  => 'fas fa-user-tag',
                'route' => 'roles.index',
            ],
            [
                'label' => 'permission',
                'icon'  => 'fas fa-key',
                'route' => 'permissions.index',
            ],
            [
                'label' => 'setting',
                'icon'  => 'fas fa-cog',
                'route' => 'settings.index',
            ],
        ],
    ],
];
