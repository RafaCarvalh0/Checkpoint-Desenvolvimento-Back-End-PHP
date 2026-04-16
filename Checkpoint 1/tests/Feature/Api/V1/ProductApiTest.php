<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_listed_with_pagination_and_sorting(): void
    {
        Product::query()->create([
            'name' => 'Mouse',
            'description' => null,
            'price' => 80,
            'sku' => 'MOU-001',
            'stock' => 5,
            'status' => 'active',
        ]);
        Product::query()->create([
            'name' => 'Teclado',
            'description' => null,
            'price' => 200,
            'sku' => 'TEC-001',
            'stock' => 3,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products?sort=price&direction=desc&per_page=1');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Teclado');
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('meta.sort', 'price');
        $response->assertJsonPath('meta.direction', 'desc');
        $response->assertJsonPath('meta.order_tiebreaker', 'id');
    }

    public function test_products_can_be_listed_with_limit_and_offset(): void
    {
        Product::query()->create([
            'name' => 'Produto A',
            'description' => null,
            'price' => 10,
            'sku' => 'PROD-A',
            'stock' => 1,
            'status' => 'active',
        ]);
        Product::query()->create([
            'name' => 'Produto B',
            'description' => null,
            'price' => 10,
            'sku' => 'PROD-B',
            'stock' => 1,
            'status' => 'active',
        ]);
        Product::query()->create([
            'name' => 'Produto C',
            'description' => null,
            'price' => 10,
            'sku' => 'PROD-C',
            'stock' => 1,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products?sort=name&direction=asc&limit=1&offset=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Produto B');
        $response->assertJsonPath('meta.limit', 1);
        $response->assertJsonPath('meta.offset', 1);
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.order_tiebreaker', 'id');
        $this->assertNotNull($response->json('meta.links.prev'));
        $this->assertNotNull($response->json('meta.links.next'));
    }

    public function test_products_can_be_filtered(): void
    {
        Product::query()->create([
            'name' => 'Mouse Optico',
            'description' => null,
            'price' => 80,
            'sku' => 'MOU-001',
            'stock' => 5,
            'status' => 'active',
        ]);
        Product::query()->create([
            'name' => 'Mouse Sem Estoque',
            'description' => null,
            'price' => 40,
            'sku' => 'MOU-002',
            'stock' => 0,
            'status' => 'inactive',
        ]);
        Product::query()->create([
            'name' => 'Teclado',
            'description' => null,
            'price' => 200,
            'sku' => 'TEC-001',
            'stock' => 3,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products?name=mouse&status=active&min_price=50&max_price=100&in_stock=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.sku', 'MOU-001');
        $response->assertJsonPath('meta.filters.name', 'mouse');
        $response->assertJsonPath('meta.filters.status', 'active');
        $response->assertJsonPath('meta.filters.min_price', 50);
        $response->assertJsonPath('meta.filters.max_price', 100);
        $response->assertJsonPath('meta.filters.in_stock', true);
    }

    public function test_product_can_be_shown_by_id(): void
    {
        $product = Product::query()->create([
            'name' => 'Cafeteira Eletrica',
            'description' => 'Cafeteira inox',
            'price' => 199.90,
            'sku' => 'CAF-001',
            'stock' => 8,
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $product->id);
        $response->assertJsonPath('data.name', 'Cafeteira Eletrica');
        $response->assertJsonPath('data.slug', 'cafeteira-eletrica');
        $response->assertJsonPath('data.links.self', route('api.v1.products.show', $product->id));
    }

    public function test_product_can_be_shown_by_slug(): void
    {
        Product::query()->create([
            'name' => 'Monitor 24 Polegadas',
            'description' => null,
            'price' => 899,
            'sku' => 'MON-001',
            'stock' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products/monitor-24-polegadas');

        $response->assertOk();
        $response->assertJsonPath('data.name', 'Monitor 24 Polegadas');
        $response->assertJsonPath('data.slug', 'monitor-24-polegadas');
    }

    public function test_product_api_returns_404_for_missing_product(): void
    {
        $response = $this->getJson('/api/v1/products/produto-inexistente');

        $response->assertNotFound();
        $response->assertJsonPath('data', null);
        $response->assertJsonPath('meta.status', 404);
        $response->assertJsonPath('errors.0.message', 'Produto não encontrado.');
    }

    public function test_product_api_returns_400_for_invalid_pagination_parameter(): void
    {
        $response = $this->getJson('/api/v1/products?limit=abc');

        $response->assertBadRequest();
        $response->assertJsonPath('data', null);
        $response->assertJsonPath('meta.status', 400);
        $response->assertJsonPath('errors.0.message', 'O parâmetro limit deve ser numérico.');
    }
}
