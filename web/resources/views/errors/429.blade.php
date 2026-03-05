@extends('layouts.error', [
    'errorCode' => '429',
    'errorTitle' => __('app.errors.429.title'),
    'errorMessage' => __('app.errors.429.message'),
    'iconBgClass' => 'bg-purple-100 dark:bg-purple-900',
    'errorIcon' => '<svg class="h-12 w-12 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>',
    'errorActions' => [
        [
            'color' => 'alternative',
            'href' => 'javascript:history.back()',
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                       </svg>',
            'text' => __('app.errors.429.back'),
        ],
        [
            'color' => 'default',
            'href' => 'javascript:window.location.reload()',
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                       </svg>',
            'text' => __('app.errors.429.refresh'),
        ],
    ],
])

@section('title', __('app.errors.429.title'))
