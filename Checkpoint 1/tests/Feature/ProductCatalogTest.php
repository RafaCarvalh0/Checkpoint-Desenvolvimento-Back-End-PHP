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
            'sku' => 'PROD-TESTE-001',
            'stock' => 5,
            'status' => 'active',
        ]);

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('Produto Teste');
    }

    public function test_product_catalog_lists_legacy_products_without_sku(): void
    {
        Product::query()->create([
            'name' => 'Produto Legado',
            'description' => 'Produto criado antes do SKU.',
            'price' => 10,
            'stock' => 1,
        ]);

        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('Produto Legado');
        $response->assertSee('PROD-1');
    }

    public function test_product_can_be_created_from_html_form(): void
    {
        $response = $this->post('/products', [
            'name' => 'Cafeteira',
            'description' => 'Cafeteira eletrica',
            'price' => 199.90,
            'sku' => 'CAF-001',
            'stock' => 6,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Produto criado com sucesso.');

        $this->assertDatabaseHas('products', [
            'name' => 'Cafeteira',
            'sku' => 'CAF-001',
            'stock' => 6,
            'status' => 'active',
        ]);
    }

    public function test_product_form_returns_errors_and_repopulates_input(): void
    {
        $response = $this->from('/products/create')->post('/products', [
            'name' => ' Produto sem preco ',
            'description' => 'Descricao mantida',
            'price' => '',
            'sku' => 'sku invalido',
            'stock' => -1,
            'status' => 'active',
        ]);

        $response->assertRedirect('/products/create');
        $response->assertSessionHasErrors(['price', 'sku', 'stock']);
        $response->assertSessionHasInput('name', 'Produto sem preco');
        $response->assertSessionHasInput('description', 'Descricao mantida');
    }

    public function test_product_sku_must_be_unique_on_create(): void
    {
        Product::query()->create([
            'name' => 'Produto Existente',
            'description' => null,
            'price' => 10,
            'sku' => 'SKU-UNICO',
            'stock' => 1,
            'status' => 'active',
        ]);

        $response = $this->from('/products/create')->post('/products', [
            'name' => 'Produto Duplicado',
            'description' => null,
            'price' => 20,
            'sku' => 'sku-unico',
            'stock' => 2,
            'status' => 'active',
        ]);

        $response->assertRedirect('/products/create');
        $response->assertSessionHasErrors(['sku']);
        $response->assertSessionHasInput('sku', 'sku-unico');
    }

    public function test_product_can_be_updated_from_html_form(): void
    {
        $product = Product::query()->create([
            'name' => 'Produto Antigo',
            'description' => 'Descricao antiga',
            'price' => 10,
            'sku' => 'OLD-001',
            'stock' => 1,
            'status' => 'active',
        ]);

        $response = $this->put("/products/{$product->id}", [
            'name' => 'Produto Novo',
            'description' => 'Descricao nova',
            'price' => 25.50,
            'sku' => 'NEW-001',
            'stock' => 3,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('products.show', $product->id));
        $response->assertSessionHas('success', 'Produto atualizado com sucesso.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produto Novo',
            'sku' => 'NEW-001',
            'stock' => 3,
            'status' => 'inactive',
        ]);
    }

    public function test_product_can_keep_same_sku_on_update(): void
    {
        $product = Product::query()->create([
            'name' => 'Produto Atual',
            'description' => null,
            'price' => 10,
            'sku' => 'KEEP-001',
            'stock' => 1,
            'status' => 'active',
        ]);

        $response = $this->put("/products/{$product->id}", [
            'name' => 'Produto Atualizado',
            'description' => null,
            'price' => 12,
            'sku' => 'keep-001',
            'stock' => 2,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('products.show', $product->id));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => 'KEEP-001',
            'name' => 'Produto Atualizado',
        ]);
    }

    public function test_product_sku_must_be_unique_on_update(): void
    {
        Product::query()->create([
            'name' => 'Produto A',
            'description' => null,
            'price' => 10,
            'sku' => 'DUP-001',
            'stock' => 1,
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'name' => 'Produto B',
            'description' => null,
            'price' => 15,
            'sku' => 'DUP-002',
            'stock' => 2,
            'status' => 'active',
        ]);

        $response = $this->from("/products/{$product->id}/edit")->put("/products/{$product->id}", [
            'name' => 'Produto B',
            'description' => null,
            'price' => 15,
            'sku' => 'DUP-001',
            'stock' => 2,
            'status' => 'active',
        ]);

        $response->assertRedirect("/products/{$product->id}/edit");
        $response->assertSessionHasErrors(['sku']);
    }

    public function test_product_can_be_deleted_from_html_form(): void
    {
        $product = Product::query()->create([
            'name' => 'Produto Removivel',
            'description' => null,
            'price' => 10,
            'sku' => 'DEL-001',
            'stock' => 1,
            'status' => 'active',
        ]);

        $response = $this->delete("/products/{$product->id}");

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success', 'Produto removido com sucesso.');

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_index_can_return_json(): void
    {
        Product::query()->create([
            'name' => 'Produto JSON',
            'description' => null,
            'price' => 15,
            'sku' => 'JSON-001',
            'stock' => 2,
            'status' => 'active',
        ]);

        $response = $this->getJson('/products');

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Produto JSON');
        $response->assertJsonPath('data.0.slug', 'produto-json');
    }

    public function test_product_validation_returns_json_errors(): void
    {
        $response = $this->postJson('/products', [
            'name' => '',
            'price' => 'abc',
            'sku' => '',
            'stock' => -5,
            'status' => 'invalid',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'price', 'sku', 'stock', 'status']);
        $response->assertJsonPath('errors.name.0', 'Informe o nome do produto.');
    }

    public function test_missing_product_returns_404(): void
    {
        $response = $this->get('/products/999');

        $response->assertNotFound();
        $response->assertSee('Produto não encontrado');
    }
}
