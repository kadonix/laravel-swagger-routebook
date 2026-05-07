<?php

return [
    'title' => env('APP_NAME', 'Laravel API'),
    'version' => env('APP_VERSION', '1.0.0'),
    'description' => 'Generated with Routebook.',

    'routes' => [
        'enabled' => true,
        'middleware' => ['web'],
        'prefix' => 'docs',
        'json' => 'spec.json',
    ],

    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost'),
        ],
    ],

    'groups' => [
        'default' => 'Default',
    ],

    'scan' => [
        'include_unannotated_routes' => false,
        'default_security' => [],
        'detect_auth_middleware' => true,
    ],

    'auth' => [
        'enabled' => true,
        'scheme' => 'bearerAuth',
        'type' => 'http',
        'scheme_name' => 'bearer',
        'bearer_format' => 'JWT',
        'token' => env('ROUTEBOOK_AUTH_TOKEN'),
    ],

    'ui' => [
        'title' => 'API Documentation',
        'swagger_ui_css' => 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css',
        'swagger_ui_js' => 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js',
        'filter_select' => true,
    ],
];
