<?php

/**
 * Configuration file for Api module menu
 *
 * @var array
 *            Note:
 *            - icon: use fontawesome icon class https://fontawesome.com/search
 */

return [
    [
        'label'    => 'api',
        'icon'     => '',
        'route'    => 'api.module',
        'children' => [
            [
                'label'    => 'api_reg_request',
                'icon'     => 'fas fa-list',
                'route'    => 'api-reg-requests.index',
                'children' => [],
            ],
            [
                'label'    => 'api_request_log',
                'icon'     => 'fas fa-list',
                'route'    => 'api-request-logs.index',
                'children' => [],
            ],
        ],
    ],
];
