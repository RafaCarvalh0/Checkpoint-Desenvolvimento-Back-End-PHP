<?php

namespace Tests\Unit\Domain;

use App\Domain\Products\Product;
use App\Domain\Products\ProductCatalogService;
use App\Domain\Products\ProductRepositoryInterface;
use App\Domain\Products\ProductStatus;
use App\Exceptions\ProductNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProductCatalogServiceTest extends TestCase
{
    public function test_create_builds_domain_product_and_persists_through_repository_contract(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $createdProduct = new Product('Mouse Gamer', 'RGB', 199.90, 'MOU-001', 15, ProductStatus::Active, 1);

        $repository
            ->expects($this->once())
            ->method('add')
            ->with($this->callback(function (Product $product): bool {
                return $product->getName() === 'Mouse Gamer'
                    && $product->getDescription() === 'RGB'
                    && $product->getPriceInCents() === 19990
                    && $product->getSku() === 'MOU-001'
                    && $product->getStock() === 15
                    && $product->getStatus() === ProductStatus::Active;
            }))
            ->willReturn($createdProduct);

        $service = new ProductCatalogService($repository);

        $product = $service->create([
            'name' => 'Mouse Gamer',
            'description' => 'RGB',
            'price' => '199.90',
            'sku' => 'mou-001',
            'stock' => 15,
            'status' => 'active',
        ]);

        $this->assertSame($createdProduct, $product);
    }

    public function test_create_rejects_invalid_domain_data_before_calling_repository(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->never())->method('add');

        $service = new ProductCatalogService($repository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O SKU do produto');

        $service->create([
            'name' => 'Produto sem SKU',
            'description' => null,
            'price' => 10,
            'sku' => '',
            'stock' => 1,
        ]);
    }

    public function test_update_requires_existing_product_before_calling_repository(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(99)
            ->willReturn(null);

        $repository->expects($this->never())->method('update');

        $service = new ProductCatalogService($repository);

        $this->expectException(ProductNotFoundException::class);

        $service->update(99, [
            'name' => 'Produto',
            'description' => null,
            'price' => 10,
            'sku' => 'PROD-001',
            'stock' => 1,
        ]);
    }

    public function test_update_persists_domain_product_through_repository_contract(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $existingProduct = new Product('Mouse', null, 100, 'MOU-001', 10, ProductStatus::Active, 5);
        $updatedProduct = new Product('Mouse Pro', 'Sem fio', 250, 'MOU-001', 8, ProductStatus::Inactive, 5);

        $repository
            ->expects($this->once())
            ->method('findById')
            ->with(5)
            ->willReturn($existingProduct);

        $repository
            ->expects($this->once())
            ->method('update')
            ->with(5, $this->callback(function (Product $product): bool {
                return $product->getId() === 5
                    && $product->getName() === 'Mouse Pro'
                    && $product->getDescription() === 'Sem fio'
                    && $product->getPriceInCents() === 25000
                    && $product->getStock() === 8
                    && $product->getStatus() === ProductStatus::Inactive;
            }))
            ->willReturn($updatedProduct);

        $service = new ProductCatalogService($repository);

        $product = $service->update(5, [
            'name' => 'Mouse Pro',
            'description' => 'Sem fio',
            'price' => 250,
            'sku' => 'MOU-001',
            'stock' => 8,
            'status' => ProductStatus::Inactive,
        ]);

        $this->assertSame($updatedProduct, $product);
    }

    public function test_stock_adjustment_validates_quantity_before_repository_call(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->expects($this->never())->method('increaseStock');

        $service = new ProductCatalogService($repository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A quantidade deve ser maior que zero.');

        $service->increaseStock(1, 0);
    }

    public function test_stock_adjustment_uses_repository_contract(): void
    {
        $repository = $this->createMock(ProductRepositoryInterface::class);
        $product = new Product('Produto', null, 10, 'PROD-001', 7, ProductStatus::Active, 1);

        $repository
            ->expects($this->once())
            ->method('decreaseStock')
            ->with(1, 3)
            ->willReturn($product);

        $service = new ProductCatalogService($repository);

        $this->assertSame($product, $service->decreaseStock(1, 3));
    }
}
