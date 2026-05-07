# Laravel Swagger Routebook

Laravel Swagger Routebook is a small Laravel package for generating Swagger API documentation from your existing routes and controllers.

It keeps documentation close to your code with simple `@` annotations or PHP attributes, then exposes a Swagger UI page, a JSON spec, Postman export, documentation coverage, and a docs audit command.

## Features

- Swagger UI at `/docs`
- JSON spec at `/docs/spec.json`
- Simple annotations: `@Endpoint`, `@Parameter`, `@Body`, `@Returns`, `@Response`, `@Example`
- PHP attributes for teams that prefer typed metadata
- Route method and path inferred from Laravel routes
- Request schema generation from `FormRequest` rules
- Response schema generation from DTO classes
- `components.schemas` generation for Swagger UI's Schemas section
- Bearer auth detection from `auth` / `auth:*` middleware
- Grouped specs with `group="admin"` and `?group=admin`
- Postman export from the UI or Artisan
- Documentation audit with `routebook:check`
- Documentation coverage with `routebook:coverage`

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12

## Installation

```bash
composer require kadonix/laravel-swagger-routebook
```

Publish the config file if you want to customize Routebook:

```bash
php artisan vendor:publish --tag=routebook-config
```

Then open:

```text
/docs
```

The generated JSON spec is available at:

```text
/docs/spec.json
```

## Quick Start

Create a normal Laravel controller:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use Illuminate\Http\JsonResponse;

final class ProductController extends Controller
{
    /**
     * @Endpoint(summary="List products", tags={"Products"})
     * @Parameter(name="search", type="string", description="Filter products by name")
     * @Parameter(name="page", type="integer", description="Page number")
     * @Returns(status=200, from="App\Data\ProductData", collection=true, description="Products list")
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['id' => 1, 'name' => 'Keyboard', 'price' => 49.99],
            ],
        ]);
    }

    /**
     * @Endpoint(summary="Create product", tags={"Products"}, group="admin")
     * @Body(from="App\Http\Requests\StoreProductRequest")
     * @Returns(status=201, from="App\Data\ProductData", description="Product created")
     * @Response(status=422, description="Validation error")
     * @Example(name="created", type="response", status=201, value='{"id":1,"name":"Keyboard","price":49.99}')
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        return response()->json([
            'id' => 1,
            ...$request->validated(),
        ], 201);
    }
}
```

Register routes as usual:

```php
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::post('/products', [ProductController::class, 'store'])->name('products.store');
```

Create a request class:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'is_active' => 'boolean',
        ];
    }
}
```

Create a response DTO:

```php
<?php

namespace App\Data;

final class ProductData
{
    public int $id;

    public string $name;

    public float $price;
}
```

Routebook will generate schemas for `StoreProductRequest` and `ProductData` under `components.schemas`.

## Annotation Reference

### `@Endpoint`

Documents a controller method as an API endpoint.

```php
/**
 * @Endpoint(summary="List products", tags={"Products"})
 */
```

Full example:

```php
/**
 * @Endpoint(
 *   method="GET",
 *   path="/products",
 *   summary="List products",
 *   description="Returns paginated products",
 *   tags={"Products"},
 *   group="public",
 *   auth=true
 * )
 */
```

Most of the time, you do not need `method` or `path`; Routebook infers them from Laravel routes.

Options:

- `method`: HTTP method. Optional.
- `path`: API path. Optional.
- `summary`: Short title.
- `description`: Longer description.
- `tags`: Swagger UI tags.
- `group`: Used for filtered specs.
- `auth`: Forces bearer auth on this endpoint.

### `@Parameter`

Adds query, path, header, or cookie parameters.

```php
/**
 * @Parameter(name="page", in="query", type="integer", description="Page number")
 * @Parameter(name="user", in="path", type="integer", required=true)
 */
```

Route parameters like `/users/{user}` are added automatically, so you only need to add extra detail when necessary.

Options:

- `name`: Parameter name.
- `in`: `query`, `path`, `header`, or `cookie`.
- `type`: Schema type. Example: `string`, `integer`, `number`, `boolean`.
- `description`: Optional description.
- `required`: Boolean.
- `example`: Example value.

### `@Body`

Defines the request body.

```php
/**
 * @Body(from="App\Http\Requests\StoreProductRequest")
 */
```

When `from` points to a Laravel `FormRequest`, Routebook converts common validation rules into a schema.

Supported rules include:

- `required`
- `integer`
- `numeric`
- `boolean`
- `array`
- `date`
- `email`
- `url`

`@RequestBody` is also available if you prefer the longer name:

```php
/**
 * @RequestBody(from="App\Http\Requests\StoreProductRequest")
 */
```

### `@Returns`

Defines a successful response.

```php
/**
 * @Returns(status=200, from="App\Data\ProductData", description="Product found")
 */
```

For collections:

```php
/**
 * @Returns(status=200, from="App\Data\ProductData", collection=true, description="Products list")
 */
```

### `@Response`

Defines any response status.

```php
/**
 * @Response(status=404, description="Product not found")
 * @Response(status=422, description="Validation error")
 */
```

You can also attach a schema:

```php
/**
 * @Response(status=400, from="App\Data\ErrorData", description="Bad request")
 */
```

### `@Example`

Adds examples to request or response content.

```php
/**
 * @Example(name="payload", type="request", value='{"name":"Keyboard","price":49.99}')
 * @Example(name="created", type="response", status=201, value='{"id":1,"name":"Keyboard","price":49.99}')
 */
```

Options:

- `name`: Example name.
- `type`: `request` or `response`.
- `status`: Response status for response examples.
- `value`: JSON or scalar value.

## PHP Attributes

If you prefer PHP attributes, Routebook supports them too:

```php
use Kadonix\Routebook\Attributes\Endpoint;
use Kadonix\Routebook\Attributes\Parameter;
use Kadonix\Routebook\Attributes\Body;
use Kadonix\Routebook\Attributes\Returns;
use Kadonix\Routebook\Attributes\Response;

#[Endpoint(summary: 'List products', tags: ['Products'])]
#[Parameter(name: 'page', type: 'integer', description: 'Page number')]
#[Returns(status: 200, from: ProductData::class, collection: true, description: 'Products list')]
public function index()
{
    //
}
```

## DTO Schemas

Routebook reads public properties from DTO classes.

```php
<?php

namespace App\Data;

use Kadonix\Routebook\Attributes\Schema;

#[Schema(required: ['id', 'name'])]
final class UserData
{
    #[Schema(description: 'User identifier')]
    public int $id;

    public string $name;

    public ?string $email;
}
```

This appears under Swagger UI's **Schemas** section.

## Authentication

Routebook automatically marks routes protected by `auth` or `auth:*` middleware as bearer-authenticated.

```php
Route::middleware('auth:sanctum')->get('/profile', [ProfileController::class, 'show']);
```

You can also force auth on one endpoint:

```php
/**
 * @Endpoint(summary="Show profile", tags={"Account"}, auth=true)
 */
```

Swagger UI will show its native **Authorize** button.

To prefill a local development token:

```env
ROUTEBOOK_AUTH_TOKEN=your-local-token
```

Config:

```php
'auth' => [
    'enabled' => true,
    'scheme' => 'bearerAuth',
    'type' => 'http',
    'scheme_name' => 'bearer',
    'bearer_format' => 'JWT',
    'token' => env('ROUTEBOOK_AUTH_TOKEN'),
],
```

## Groups

Groups let you generate separate specs for different audiences.

```php
/**
 * @Endpoint(summary="Create product", tags={"Products"}, group="admin")
 */
```

Open a grouped spec:

```text
/docs/spec.json?group=admin
```

Export a grouped spec:

```bash
php artisan routebook:export storage/app/admin.json --group=admin
php artisan routebook:export-postman storage/app/admin.postman.json --group=admin
```

## UI Exports

The `/docs` page includes export links:

- `Spec`
- `Postman`

The links respect the current `Definition` selection.

## Artisan Commands

Export the JSON spec:

```bash
php artisan routebook:export
php artisan routebook:export storage/app/routebook.json
```

Export a Postman collection:

```bash
php artisan routebook:export-postman
php artisan routebook:export-postman storage/app/routebook.postman.json
```

Audit documentation quality:

```bash
php artisan routebook:check
```

This reports:

- undocumented controller routes
- missing summaries
- missing responses
- missing path parameters

Check documentation coverage:

```bash
php artisan routebook:coverage
```

Example output:

```text
Routebook coverage: 100%
3 documented / 3 API routes
```

## Configuration

Published config:

```php
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

    'scan' => [
        'include_unannotated_routes' => false,
        'default_security' => [],
        'detect_auth_middleware' => true,
    ],
];
```

By default, Routebook documents only routes with `@Endpoint` or `#[Endpoint]`.

To include unannotated controller routes:

```php
'scan' => [
    'include_unannotated_routes' => true,
],
```

## Postman Export

From the UI, click `Postman`.

From the terminal:

```bash
php artisan routebook:export-postman storage/app/api.postman.json
```

The generated collection uses:

```text
{{base_url}}
```

For protected routes, it adds:

```text
Authorization: Bearer {{token}}
```

## Development Workflow

Recommended checks before publishing:

```bash
composer validate --strict
vendor/bin/phpunit
php artisan routebook:check
php artisan routebook:coverage
```

## License

MIT
