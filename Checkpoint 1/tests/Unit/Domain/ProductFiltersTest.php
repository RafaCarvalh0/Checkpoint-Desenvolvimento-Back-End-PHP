<?php

namespace Tests\Unit\Domain;

use App\Domain\Products\ProductFilters;
use App\Domain\Products\ProductStatus;
use PHPUnit\Framework\TestCase;

class ProductFiltersTest extends TestCase
{
    public function test_filters_are_composed_from_category_price_range_and_status(): void
    {
        $filters = ProductFilters::compose(
            category: ' Periféricos ',
            priceRange: ['min' => '100', 'max' => '500'],
            status: ProductStatus::Active,
        );

        $this->assertSame([
            'category' => 'Periféricos',
            'status' => 'active',
            'min_price' => 100.0,
            'max_price' => 500.0,
        ], $filters);
    }

    public function test_filters_are_normalized_from_array_input(): void
    {
        $filters = ProductFilters::fromArray([
            'name' => ' teclado ',
            'sku' => ' tec-001 ',
            'category' => ' acessórios ',
            'priceRange' => ['min' => '-10', 'max' => '250.90'],
            'status' => 'inactive',
            'in_stock' => '1',
            'ignored' => 'value',
        ]);

        $this->assertSame([
            'name' => 'teclado',
            'category' => 'acessórios',
            'sku' => 'TEC-001',
            'status' => 'inactive',
            'min_price' => 0.0,
            'max_price' => 250.90,
            'in_stock' => true,
        ], $filters);
    }
}
