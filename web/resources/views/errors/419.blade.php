@extends('layouts.error', [
    'errorCode' => '419',
    'errorTitle' => __('app.errors.419.title'),
    'errorMessage' => __('app.errors.419.message'),
    'iconBgClass' => 'bg-blue-100 dark:bg-blue-900',
    'errorIcon' => '<svg class="h-12 w-12 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>',
    'errorActions' => [
        [
            'color' => 'default',
            'href' => route('login'),
            'icon' => '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                       </svg>',
            'text' => __('app.errors.419.back_to_login'),
        ],
    ],
])

@section('title', __('app.errors.419.title'))
