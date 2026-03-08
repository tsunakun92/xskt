<?php

/**
 * Configuration file for XSKT module menu
 *
 * @var array
 *            Note:
 *            - icon: use fontawesome icon class https://fontawesome.com/search
 */

return [
    [
        'label'    => 'xskt',
        'icon'     => '',
        'route'    => 'xskt.module',
        'children' => [
            [
                'label'    => 'draw',
                'icon'     => 'fas fa-dice',
                'route'    => 'draws.index',
                'children' => [],
            ],
            [
                'label'    => 'result',
                'icon'     => 'fas fa-trophy',
                'route'    => 'results.index',
                'children' => [],
            ],
        ],
    ],
];
