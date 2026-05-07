<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Information
    |--------------------------------------------------------------------------
    |
    | These values are used to fill the "info" object of the generated
    | Swagger/OpenAPI document. By default, Routebook uses your Laravel
    | application name and a simple semantic version.
    |
    */

    'title' => env('APP_NAME', 'Laravel API'),

    'version' => env('APP_VERSION', '1.0.0'),

    'description' => 'Generated with Routebook.',

    /*
    |--------------------------------------------------------------------------
    | Documentation Routes
    |--------------------------------------------------------------------------
    |
    | Routebook exposes a Swagger UI page and a JSON specification endpoint.
    | You may disable these routes, change their middleware, or move them
    | behind another prefix if your application needs it.
    |
    | Default URLs:
    | - /docs
    | - /docs/spec.json
    |
    */

    'routes' => [
        'enabled' => true,
        'middleware' => ['web'],
        'prefix' => 'docs',
        'json' => 'spec.json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Servers
    |--------------------------------------------------------------------------
    |
    | These URLs are shown in Swagger UI as available API servers. The default
    | value uses APP_URL, but you can add staging, production, or any other
    | environment used by your API consumers.
    |
    */

    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Groups
    |--------------------------------------------------------------------------
    |
    | Groups let you generate or export a partial specification for a specific
    | audience, such as public, mobile, admin, or partner APIs. Use the
    | "group" option on @Endpoint to assign an endpoint to a group.
    |
    | Example:
    | @Endpoint(summary="Create product", group="admin")
    |
    */

    'groups' => [
        'default' => 'Default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Scanning
    |--------------------------------------------------------------------------
    |
    | By default, Routebook only documents routes that are explicitly marked
    | with @Endpoint or #[Endpoint]. You may include unannotated controller
    | routes, define a default security rule, or disable auth middleware
    | detection if your application uses a custom authorization system.
    |
    */

    'scan' => [
        'include_unannotated_routes' => false,
        'default_security' => [],
        'detect_auth_middleware' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Routebook can automatically mark routes protected by "auth" or "auth:*"
    | middleware as bearer-authenticated. Swagger UI will then show its native
    | Authorize button. You can also prefill a local development token with
    | ROUTEBOOK_AUTH_TOKEN.
    |
    */

    'auth' => [
        'enabled' => true,
        'scheme' => 'bearerAuth',
        'type' => 'http',
        'scheme_name' => 'bearer',
        'bearer_format' => 'JWT',
        'token' => env('ROUTEBOOK_AUTH_TOKEN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Interface
    |--------------------------------------------------------------------------
    |
    | These options customize the documentation page. The Swagger UI assets are
    | intentionally not exposed here; Routebook provides safe internal defaults
    | so the page keeps rendering even when the published config is edited.
    |
    */

    'ui' => [
        'title' => 'API Documentation',
        'filter_select' => true,
    ],

];
