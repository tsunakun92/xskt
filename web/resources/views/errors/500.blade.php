@extends('layouts.error', [
    'errorCode' => '500',
    'errorTitle' => __('app.errors.500.title'),
    'errorMessage' => __('app.errors.500.message'),
    'iconBgClass' => 'bg-red-100 dark:bg-red-900',
    'errorIcon' => '<svg class="h-12 w-12 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>',
    'errorActions' => [
        [
            'color' => 'alternative',
            'href' => 'javascript:history.back()',
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                       </svg>',
            'text' => __('app.errors.500.back'),
        ],
        [
            'color' => 'default',
            'href' => route('admin'),
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                       </svg>',
            'text' => __('app.errors.500.home'),
        ],
    ],
])

@section('title', __('app.errors.500.title'))
