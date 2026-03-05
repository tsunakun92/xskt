@extends('layouts.error', [
    'errorCode' => '503',
    'errorTitle' => __('app.errors.503.title'),
    'errorMessage' => __('app.errors.503.message'),
    'iconBgClass' => 'bg-yellow-100 dark:bg-yellow-800',
    'errorIcon' => '<svg class="h-12 w-12 text-yellow-600 dark:text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>',
    'errorActions' => [
        [
            'color' => 'alternative',
            'href' => 'javascript:history.back()',
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                       </svg>',
            'text' => __('app.errors.503.back'),
        ],
        [
            'color' => 'default',
            'href' => 'javascript:window.location.reload()',
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                       </svg>',
            'text' => __('app.errors.503.refresh'),
        ],
    ],
])

@section('title', __('app.errors.503.title'))
