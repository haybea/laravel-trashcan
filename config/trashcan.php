<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trash Dashboard Path
    |--------------------------------------------------------------------------
    */
    'path' => env('TRASHCAN_PATH', 'trashcan'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Environments (No Auth Required)
    |--------------------------------------------------------------------------
    */
    'allowed_environments' => ['local', 'testing'],

    /*
    |--------------------------------------------------------------------------
    | CSS Framework: 'bootstrap' or 'tailwind'
    |--------------------------------------------------------------------------
    */
    'css_framework' => env('TRASHCAN_CSS', 'tailwind'),
    'dark_mode' => env('TRASHCAN_DARK_MODE', 'toggle'),
    'models_path' => 'Models',
    'only' => [],
    'exclude' => [],
    'columns' => [],
    'searchable' => [],
    'per_page' => 15,
    'gate' => 'viewTrashcan',
    'model_permissions' => [],
    'logging' => [
        'enabled' => env('TRASHCAN_LOGGING', true),
        'channel' => env('TRASHCAN_LOG_CHANNEL', null),
        'database' => env('TRASHCAN_LOG_DATABASE', true),
    ],
    'export' => [
        'enabled' => true,
        'formats' => ['csv', 'json'],
        'max_records' => 10000,
    ],
    'statistics' => [
        'enabled' => true,
        'chart_days' => 30,
    ],
    'restore_with_relations' => [],
];