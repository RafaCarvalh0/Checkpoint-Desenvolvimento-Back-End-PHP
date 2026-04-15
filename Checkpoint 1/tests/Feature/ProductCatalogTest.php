<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_available(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Checkpoint 1');
    }

    public function test_product_catalog_lists_products(): void
    {
        Product::query()->create([
            'name' => 'Produto Teste',
            'description' => 'Produto usado no teste automatizado.',
            'price' => 19.90,
            'stock' => 5,
        ]);

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('Produto Teste');
    }

    public function test_missing_product_returns_404(): void
    {
        $response = $this->get('/products/999');

        $response->assertNotFound();
        $response->assertSee('Produto não encontrado');
    }
}
