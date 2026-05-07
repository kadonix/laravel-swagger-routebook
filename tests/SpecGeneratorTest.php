<?php

namespace Kadonix\Routebook\Tests;

use Illuminate\Support\Facades\Route;
use Kadonix\Routebook\Attributes\Endpoint;
use Kadonix\Routebook\Attributes\Parameter;
use Kadonix\Routebook\Attributes\RequestBody;
use Kadonix\Routebook\Attributes\Response;
use Kadonix\Routebook\SpecGenerator;

final class SpecGeneratorTest extends TestCase
{
    public function test_it_generates_paths_from_annotated_controller_routes(): void
    {
        Route::get('/users/{user}', [FixtureUserController::class, 'show'])->name('users.show');

        $document = app(SpecGenerator::class)->generate();

        $this->assertSame('3.1.0', $document['openapi']);
        $this->assertArrayHasKey('/users/{user}', $document['paths']);
        $this->assertSame('Show user', $document['paths']['/users/{user}']['get']['summary']);
        $this->assertSame('user', $document['paths']['/users/{user}']['get']['parameters'][0]['name']);
        $this->assertSame('include', $document['paths']['/users/{user}']['get']['parameters'][1]['name']);
    }

    public function test_it_generates_request_body_from_inline_schema(): void
    {
        Route::post('/users', [FixtureUserController::class, 'store'])->name('users.store');

        $document = app(SpecGenerator::class)->generate();
        $schema = $document['paths']['/users']['post']['requestBody']['content']['application/json']['schema'];

        $this->assertSame(['name'], $schema['required']);
        $this->assertSame('string', $schema['properties']['name']['type']);
        $this->assertArrayHasKey('201', $document['paths']['/users']['post']['responses']);
    }

    public function test_it_generates_routes_from_docblock_annotations(): void
    {
        Route::get('/orders', [FixtureDocblockController::class, 'index'])->name('orders.index');

        $document = app(SpecGenerator::class)->generate();

        $this->assertSame('List orders', $document['paths']['/orders']['get']['summary']);
        $this->assertSame(['Orders'], $document['paths']['/orders']['get']['tags']);
        $this->assertSame('page', $document['paths']['/orders']['get']['parameters'][0]['name']);
        $this->assertArrayHasKey('200', $document['paths']['/orders']['get']['responses']);
    }

    public function test_it_marks_protected_routes_with_bearer_security(): void
    {
        Route::middleware('auth:sanctum')->get('/profile', [FixtureDocblockController::class, 'profile'])->name('profile.show');

        $document = app(SpecGenerator::class)->generate();

        $this->assertSame([['bearerAuth' => []]], $document['paths']['/profile']['get']['security']);
        $this->assertSame('http', $document['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertSame('bearer', $document['components']['securitySchemes']['bearerAuth']['scheme']);
    }

    public function test_it_marks_docblock_auth_endpoints_with_bearer_security(): void
    {
        Route::get('/billing', [FixtureDocblockController::class, 'billing'])->name('billing.index');

        $document = app(SpecGenerator::class)->generate();

        $this->assertSame([['bearerAuth' => []]], $document['paths']['/billing']['get']['security']);
    }

    public function test_it_filters_document_by_group(): void
    {
        Route::get('/mobile/orders', [FixtureDocblockController::class, 'mobileOrders'])->name('mobile.orders');
        Route::get('/orders', [FixtureDocblockController::class, 'index'])->name('orders.index');

        $document = app(SpecGenerator::class)->generate('mobile');

        $this->assertArrayHasKey('/mobile/orders', $document['paths']);
        $this->assertArrayNotHasKey('/orders', $document['paths']);
    }

    public function test_it_supports_body_returns_and_examples_annotations(): void
    {
        Route::post('/orders', [FixtureDocblockController::class, 'store'])->name('orders.store');

        $document = app(SpecGenerator::class)->generate();
        $operation = $document['paths']['/orders']['post'];

        $this->assertSame('#/components/schemas/FixtureOrderData', $operation['requestBody']['content']['application/json']['schema']['$ref']);
        $this->assertSame('object', $document['components']['schemas']['FixtureOrderData']['type']);
        $this->assertArrayHasKey('201', $operation['responses']);
        $this->assertSame('#/components/schemas/FixtureOrderData', $operation['responses']['201']['content']['application/json']['schema']['$ref']);
        $this->assertSame(['id' => 1], $operation['responses']['201']['content']['application/json']['examples']['created']['value']);
    }
}

final class FixtureUserController
{
    #[Endpoint(summary: 'Show user', tags: ['Users'])]
    #[Parameter('include', type: 'string')]
    #[Response(200, 'User found', schema: [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer'],
        ],
    ])]
    public function show(): void
    {
    }

    #[Endpoint(summary: 'Create user', tags: ['Users'])]
    #[RequestBody(schema: [
        'type' => 'object',
        'required' => ['name'],
        'properties' => [
            'name' => ['type' => 'string'],
        ],
    ])]
    #[Response(201, 'User created')]
    public function store(): void
    {
    }
}

final class FixtureDocblockController
{
    /**
     * @Endpoint(summary="List orders", tags={"Orders"})
     * @Parameter(name="page", type="integer", description="Page number")
     * @Response(status=200, description="Orders list")
     */
    public function index(): void
    {
    }

    /**
     * @Endpoint(summary="Show profile", tags={"Account"})
     * @Response(status=200, description="Profile")
     */
    public function profile(): void
    {
    }

    /**
     * @Endpoint(summary="List invoices", tags={"Billing"}, auth=true)
     * @Response(status=200, description="Invoices")
     */
    public function billing(): void
    {
    }

    /**
     * @Endpoint(summary="Mobile orders", group="mobile", tags={"Orders"})
     * @Response(status=200, description="Mobile orders")
     */
    public function mobileOrders(): void
    {
    }

    /**
     * @Endpoint(summary="Create order", tags={"Orders"})
     * @Body(from="Kadonix\Routebook\Tests\FixtureOrderData")
     * @Returns(status=201, from="Kadonix\Routebook\Tests\FixtureOrderData", description="Created")
     * @Example(name="created", type="response", status=201, value='{"id":1}')
     */
    public function store(): void
    {
    }
}

final class FixtureOrderData
{
    public int $id;
}
