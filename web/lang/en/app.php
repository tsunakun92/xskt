<?php

return [
    /*
|--------------------------------------------------------------------------
| Application Language Lines
|--------------------------------------------------------------------------
|
| The following language lines are used by the paginator library to build
| the simple pagination links. You are free to change them to anything
| you want to customize your views to better match your application.
|
 */

    'errors'        => [
        '401'    => [
            'title'   => 'Unauthorized',
            'message' => 'You need to be logged in to access this page.',
            'login'   => 'Login',
            'back'    => 'Go Back',
        ],
        '403'    => [
            'title'   => 'Forbidden',
            'message' => 'You don\'t have permission to access this page.',
            'back'    => 'Go Back',
            'home'    => 'Go to Home',
            'logout'  => 'Logout',
        ],
        '404'    => [
            'title'   => 'Page Not Found',
            'message' => 'The page you are looking for does not exist.',
            'back'    => 'Go Back',
            'home'    => 'Go to Home',
        ],
        '419'    => [
            'title'         => 'Page Expired',
            'message'       => 'Your session has expired. Please login again to continue.',
            'back_to_login' => 'Back to Login',
        ],
        '429'    => [
            'title'   => 'Too Many Requests',
            'message' => 'You have made too many requests. Please try again later.',
            'back'    => 'Go Back',
            'refresh' => 'Try Again',
        ],
        '500'    => [
            'title'   => 'Server Error',
            'message' => 'Something went wrong on our server. We are working to fix the problem.',
            'back'    => 'Go Back',
            'home'    => 'Go to Home',
            'report'  => 'Report Issue',
        ],
        '503'    => [
            'title'   => 'Service Unavailable',
            'message' => 'The service is temporarily unavailable. Please try again later.',
            'back'    => 'Go Back',
            'refresh' => 'Refresh Page',
        ],
        'common' => [
            'go_back' => 'Go Back',
            'go_home' => 'Go to Home',
            'login'   => 'Login',
            'logout'  => 'Logout',
            'refresh' => 'Refresh Page',
        ],
    ],

    'data_deletion' => [
        'title'              => 'Request Data Deletion',
        'page_title'         => 'Request Data Deletion',
        'success_page_title' => 'Request Submitted Successfully',
        'success_title'      => 'Request Submitted Successfully',
        'send_request'       => 'Send Request',
        'request_submitted'  => 'Your data deletion request has been received',
        'back_to_form'       => 'Back to Form',
        'back_to_policy'     => 'Back to Privacy Policy',
        'description_1'      => 'If you would like to request deletion of your personal data from our system, you can submit a data deletion request by clicking the button below.',
        'description_2'      => 'Once your request is submitted, our team will review and process it in accordance with our privacy policy. You will receive a confirmation email once your request has been processed.',
        'description_3'      => 'Please note that this process may take some time to complete, and all your personal data will be permanently removed from our systems upon approval of your request.',
        'modal_title'        => 'Request Data Deletion',
        'modal_message'      => 'Are you sure you want to request deletion of your data? This action cannot be undone.',
        'modal_cancel'       => 'Cancel',
        'success_message'    => 'Your request has been submitted successfully. We will process it shortly.',
    ],

    'policy'        => [
        'title'               => 'Privacy Policy',
        'page_title'          => 'Privacy Policy',
        'last_updated'        => 'Last updated',
        'content_unavailable' => 'Content Unavailable',
        'updating_message'    => 'We are currently updating our privacy policy. Please check back later.',
    ],
];
