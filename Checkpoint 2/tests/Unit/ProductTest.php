<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Product\ProductStatus;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public function testPreservesCheckpointOneDomainRules(): void
    {
        $product = new Product('Teclado Mecânico', 'ABNT2', 249.90, 'tec-001', 10);
        self::assertSame('TEC-001', $product->getSku());
        self::assertSame('teclado-mecanico', $product->getSlug());
        self::assertSame(24990, $product->getPriceInCents());
        self::assertSame(ProductStatus::Active, $product->getStatus());
        $product->decreaseStock(3)->deactivate();
        self::assertSame(7, $product->getStock());
        self::assertFalse($product->isActive());
    }

    public function testRejectsInvalidSku(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Product('Produto', null, 10, 'sku inválido', 1);
    }

    public function testStockCannotBecomeNegative(): void
    {
        $product = new Product('Produto', null, 10, 'PROD-001', 2);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O estoque não pode ficar negativo.');
        $product->decreaseStock(3);
    }
}
