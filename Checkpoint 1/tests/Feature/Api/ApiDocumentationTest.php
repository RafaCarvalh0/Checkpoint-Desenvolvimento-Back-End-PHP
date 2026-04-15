<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_api_documentation_page_is_available(): void
    {
        $response = $this->get('/api/docs');

        $response->assertOk();
        $response->assertSee('swagger-ui');
        $response->assertSee('api\\/docs\\/openapi.yaml', false);
    }

    public function test_openapi_specification_is_available(): void
    {
        $response = $this->get('/api/docs/openapi.yaml');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/yaml; charset=UTF-8');
        $response->assertSee('openapi: 3.0.3');
        $response->assertSee('/products');
    }
}
