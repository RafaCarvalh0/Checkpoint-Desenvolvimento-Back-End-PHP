<?php

namespace Tests\Unit\Domain;

use App\Domain\Products\Product;
use App\Domain\Products\ProductStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function test_product_is_created_with_valid_data(): void
    {
        $product = new Product(
            name: 'Teclado Mecânico',
            description: 'Teclado ABNT2',
            price: 249.90,
            sku: 'tec-mec-001',
            stock: 10,
        );

        $this->assertSame('Teclado Mecânico', $product->getName());
        $this->assertSame('Teclado ABNT2', $product->getDescription());
        $this->assertSame(249.90, $product->getPrice());
        $this->assertSame(24990, $product->getPriceInCents());
        $this->assertSame('TEC-MEC-001', $product->getSku());
        $this->assertSame('teclado-mecanico', $product->getSlug());
        $this->assertSame(10, $product->getStock());
        $this->assertSame(ProductStatus::Active, $product->getStatus());
        $this->assertTrue($product->isActive());
    }

    public function test_product_rejects_negative_price(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O preço do produto não pode ser negativo.');

        new Product('Produto', null, -1, 'PROD-001', 1);
    }

    public function test_product_rejects_negative_stock(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O estoque do produto não pode ser negativo.');

        new Product('Produto', null, 10, 'PROD-001', -1);
    }

    public function test_stock_cannot_be_decreased_below_zero(): void
    {
        $product = new Product('Produto', null, 10, 'PROD-001', 2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('O estoque não pode ficar negativo.');

        $product->decreaseStock(3);
    }

    public function test_product_status_can_be_changed(): void
    {
        $product = new Product('Produto', null, 10, 'PROD-001', 2);

        $product->deactivate();

        $this->assertSame(ProductStatus::Inactive, $product->getStatus());
        $this->assertFalse($product->isActive());

        $product->activate();

        $this->assertSame(ProductStatus::Active, $product->getStatus());
    }
}
