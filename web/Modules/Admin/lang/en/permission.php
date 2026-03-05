<?php

return [
    'title'                => 'Permission Management',
    'group'                => 'Group',
    'permissions'          => 'Permissions',

    // Group titles
    'groups'               => [
        'admin'       => 'Admin',
        'users'       => 'Users',
        'roles'       => 'Roles',
        'permissions' => 'Permissions',
        'profile'     => 'Profile',
        'changelog'   => 'Changelog',
    ],

    // Action labels (default)
    'actions'              => [
        'index'      => 'List',
        'show'       => 'Detail',
        'create'     => 'Create',
        'edit'       => 'Edit',
        'destroy'    => 'Delete',
        'permission' => 'Manage Permission',
        'setting'    => 'Manage Setting',
        'admin'      => 'Dashboard Access',
    ],

    // Specific permissions (override default actions)
    'specific_permissions' => [
        'changelog.index' => 'View Changelog',
        'admin'           => 'Access Dashboard',
        'admin.module'    => 'Access Admin Module',
    ],

    // Buttons
    'back'                 => 'Back',
    'submit'               => 'Save Changes',
    'check_all'            => 'Check All',
    'uncheck_all'          => 'Uncheck All',
    'selected'             => 'selected',
    'all_modules'          => 'All Modules',
    'modules'              => 'Modules',
];
