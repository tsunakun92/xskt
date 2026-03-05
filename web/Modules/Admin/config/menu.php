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
            [
                'label' => 'prefecture',
                'icon'  => 'fas fa-map-pin',
                'route' => 'prefectures.index',
            ],
            [
                'label' => 'municipality',
                'icon'  => 'fas fa-building',
                'route' => 'municipalities.index',
            ],
            [
                'label' => 'post_number',
                'icon'  => 'fas fa-envelope',
                'route' => 'post-numbers.index',
            ],
            [
                'label' => 'personal_access_token',
                'icon'  => 'fas fa-key',
                'route' => 'personal-access-tokens.index',
            ],
        ],
    ],
];
