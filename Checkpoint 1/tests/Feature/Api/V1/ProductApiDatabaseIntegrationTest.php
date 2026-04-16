<?php

namespace Tests\Feature\Api\V1;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiDatabaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_reads_products_from_database_and_returns_api_contract(): void
    {
        Product::query()->create([
            'name' => 'Headset Gamer',
            'description' => 'Headset com microfone',
            'price' => 350.50,
            'sku' => 'HEAD-001',
            'stock' => 4,
            'status' => 'active',
        ]);
        Product::query()->create([
            'name' => 'Webcam Full HD',
            'description' => 'Camera USB',
            'price' => 180.00,
            'sku' => 'WEB-001',
            'stock' => 0,
            'status' => 'inactive',
        ]);

        $response = $this->getJson('/api/v1/products?status=active&sort=name&direction=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'price',
                        'sku',
                        'stock',
                        'status',
                        'slug',
                        'links' => ['self'],
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'sort',
                    'direction',
                    'order_tiebreaker',
                    'filters',
                    'links' => ['first', 'last', 'prev', 'next'],
                ],
                'errors',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Headset Gamer')
            ->assertJsonPath('data.0.sku', 'HEAD-001')
            ->assertJsonPath('data.0.slug', 'headset-gamer')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.filters.status', 'active')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('products', [
            'sku' => 'HEAD-001',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('products', [
            'sku' => 'WEB-001',
            'status' => 'inactive',
        ]);
    }

    public function test_show_reads_product_by_slug_from_database_and_returns_stable_contract(): void
    {
        $product = Product::query()->create([
            'name' => 'Suporte Articulado',
            'description' => 'Suporte de mesa',
            'price' => 129.90,
            'sku' => 'SUP-001',
            'stock' => 6,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products/suporte-articulado');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'description',
                    'price',
                    'sku',
                    'stock',
                    'status',
                    'slug',
                    'links' => ['self'],
                ],
                'meta',
                'errors',
            ])
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', 'Suporte Articulado')
            ->assertJsonPath('data.description', 'Suporte de mesa')
            ->assertJsonPath('data.price', 129.90)
            ->assertJsonPath('data.sku', 'SUP-001')
            ->assertJsonPath('data.stock', 6)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.slug', 'suporte-articulado')
            ->assertJsonPath('data.links.self', route('api.v1.products.show', $product->id))
            ->assertJsonPath('errors', []);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => 'SUP-001',
        ]);
    }

    public function test_missing_product_returns_404_contract_without_changing_database(): void
    {
        Product::query()->create([
            'name' => 'Notebook',
            'description' => null,
            'price' => 4500.00,
            'sku' => 'NOTE-001',
            'stock' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/products/produto-ausente');

        $response
            ->assertNotFound()
            ->assertJsonPath('data', null)
            ->assertJsonPath('meta.status', 404)
            ->assertJsonPath('errors.0.message', 'Produto não encontrado.');

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', [
            'sku' => 'NOTE-001',
        ]);
    }
}
