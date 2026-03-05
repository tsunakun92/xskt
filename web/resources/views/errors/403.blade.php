@extends('layouts.error', [
    'errorCode' => '403',
    'errorTitle' => __('app.errors.403.title'),
    'errorMessage' => __('app.errors.403.message'),
    'iconBgClass' => 'bg-red-100 dark:bg-red-900',
    'errorIcon' => '<svg class="h-12 w-12 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8 0-1.85.63-3.55 1.69-4.9L16.9 18.31C15.55 19.37 13.85 20 12 20zm6.31-3.1L7.1 5.69C8.45 4.63 10.15 4 12 4c4.41 0 8 3.59 8 8 0 1.85-.63 3.55-1.69 4.9z"/>
                    </svg>',
    'errorActions' => [
        [
            'color' => 'alternative',
            'href' => 'javascript:history.back()',
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                       </svg>',
            'text' => __('app.errors.403.back'),
        ],
        [
            'color' => 'default',
            'href' => route('admin'),
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                       </svg>',
            'text' => __('app.errors.403.home'),
        ],
        [
            'color' => 'red',
            'href' => route('logout'),
            'method' => 'post',
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H5a3 3 0 01-3-3V7a3 3 0 013-3h5a3 3 0 013 3v1" />
                       </svg>',
            'text' => __('app.errors.403.logout'),
        ],
    ],
])

@section('title', __('app.errors.403.title'))
