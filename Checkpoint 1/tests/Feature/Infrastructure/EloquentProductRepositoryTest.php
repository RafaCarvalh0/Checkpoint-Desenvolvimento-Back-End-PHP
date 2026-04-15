<?php

namespace Tests\Feature\Infrastructure;

use App\Domain\Products\Product as DomainProduct;
use App\Domain\Products\ProductRepositoryInterface;
use App\Domain\Products\ProductStatus;
use App\Exceptions\ProductNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class EloquentProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_persists_product_and_returns_product_with_id(): void
    {
        $repository = $this->repository();

        $product = $repository->add(new DomainProduct(
            name: 'Notebook',
            description: 'Notebook para desenvolvimento',
            price: 3500.99,
            sku: 'note-dev-001',
            stock: 4,
        ));

        $this->assertNotNull($product->getId());
        $this->assertSame('NOTE-DEV-001', $product->getSku());

        $this->assertDatabaseHas('products', [
            'name' => 'Notebook',
            'sku' => 'NOTE-DEV-001',
            'stock' => 4,
            'status' => 'active',
        ]);
    }

    public function test_update_changes_existing_product(): void
    {
        $repository = $this->repository();

        $created = $repository->add(new DomainProduct(
            name: 'Mouse',
            description: 'Mouse básico',
            price: 50,
            sku: 'MOU-001',
            stock: 10,
        ));

        $updated = $repository->update($created->getId(), new DomainProduct(
            name: 'Mouse Gamer',
            description: 'Mouse com sensor de alta precisão',
            price: 120,
            sku: 'MOU-GAMER-001',
            stock: 7,
            status: ProductStatus::Inactive,
        ));

        $this->assertSame($created->getId(), $updated->getId());
        $this->assertSame('Mouse Gamer', $updated->getName());
        $this->assertSame(ProductStatus::Inactive, $updated->getStatus());

        $this->assertDatabaseHas('products', [
            'id' => $created->getId(),
            'name' => 'Mouse Gamer',
            'sku' => 'MOU-GAMER-001',
            'status' => 'inactive',
        ]);
    }

    public function test_find_by_id_returns_domain_product(): void
    {
        $repository = $this->repository();

        $created = $repository->add(new DomainProduct(
            name: 'Monitor',
            description: null,
            price: 899,
            sku: 'MON-001',
            stock: 2,
        ));

        $found = $repository->findById($created->getId());

        $this->assertNotNull($found);
        $this->assertSame('Monitor', $found->getName());
        $this->assertSame('MON-001', $found->getSku());
    }

    public function test_find_by_filters_returns_matching_products(): void
    {
        $repository = $this->repository();

        $repository->add(new DomainProduct('Teclado Mecânico', null, 249.90, 'TEC-001', 5));
        $repository->add(new DomainProduct('Teclado Simples', null, 59.90, 'TEC-002', 0));
        $repository->add(new DomainProduct('Mouse Óptico', null, 89.90, 'MOU-001', 10));

        $products = $repository->findByFilters([
            'name' => 'teclado',
            'min_price' => 100,
            'in_stock' => true,
        ]);

        $this->assertCount(1, $products);
        $this->assertSame('Teclado Mecânico', $products[0]->getName());
    }

    public function test_update_throws_exception_when_product_does_not_exist(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->repository()->update(999, new DomainProduct(
            name: 'Produto',
            description: null,
            price: 10,
            sku: 'PROD-001',
            stock: 1,
        ));
    }

    public function test_add_with_images_rolls_back_when_image_url_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $this->repository()->addWithImages(new DomainProduct(
                name: 'Produto com imagem inválida',
                description: null,
                price: 10,
                sku: 'IMG-INVALIDA-001',
                stock: 1,
            ), [
                'https://example.com/produto.jpg',
                'url-invalida',
            ]);
        } finally {
            $this->assertDatabaseMissing('products', [
                'sku' => 'IMG-INVALIDA-001',
            ]);
            $this->assertDatabaseCount('product_images', 0);
        }
    }

    public function test_add_with_images_persists_product_and_images_in_transaction(): void
    {
        $created = $this->repository()->addWithImages(new DomainProduct(
            name: 'Produto com imagens',
            description: null,
            price: 100,
            sku: 'IMG-001',
            stock: 3,
        ), [
            'https://example.com/1.jpg',
            'https://example.com/2.jpg',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $created->getId(),
            'sku' => 'IMG-001',
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $created->getId(),
            'url' => 'https://example.com/1.jpg',
            'position' => 0,
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $created->getId(),
            'url' => 'https://example.com/2.jpg',
            'position' => 1,
        ]);
    }

    public function test_update_with_images_replaces_images_in_transaction(): void
    {
        $repository = $this->repository();
        $created = $repository->addWithImages(new DomainProduct(
            name: 'Produto',
            description: null,
            price: 100,
            sku: 'IMG-002',
            stock: 3,
        ), [
            'https://example.com/old.jpg',
        ]);

        $repository->updateWithImages($created->getId(), new DomainProduct(
            name: 'Produto atualizado',
            description: null,
            price: 120,
            sku: 'IMG-002-NEW',
            stock: 4,
        ), [
            'https://example.com/new.jpg',
        ]);

        $this->assertDatabaseMissing('product_images', [
            'product_id' => $created->getId(),
            'url' => 'https://example.com/old.jpg',
        ]);
        $this->assertDatabaseHas('product_images', [
            'product_id' => $created->getId(),
            'url' => 'https://example.com/new.jpg',
        ]);
    }

    public function test_stock_is_adjusted_inside_transaction(): void
    {
        $repository = $this->repository();
        $created = $repository->add(new DomainProduct(
            name: 'Produto estoque',
            description: null,
            price: 20,
            sku: 'STOCK-001',
            stock: 5,
        ));

        $repository->increaseStock($created->getId(), 3);
        $updated = $repository->decreaseStock($created->getId(), 4);

        $this->assertSame(4, $updated->getStock());
        $this->assertDatabaseHas('products', [
            'id' => $created->getId(),
            'stock' => 4,
        ]);
    }

    public function test_stock_adjustment_rolls_back_when_stock_would_be_negative(): void
    {
        $repository = $this->repository();
        $created = $repository->add(new DomainProduct(
            name: 'Produto sem estoque',
            description: null,
            price: 20,
            sku: 'STOCK-002',
            stock: 2,
        ));

        $this->expectException(InvalidArgumentException::class);

        try {
            $repository->decreaseStock($created->getId(), 3);
        } finally {
            $this->assertDatabaseHas('products', [
                'id' => $created->getId(),
                'stock' => 2,
            ]);
        }
    }

    private function repository(): ProductRepositoryInterface
    {
        return app(ProductRepositoryInterface::class);
    }
}
