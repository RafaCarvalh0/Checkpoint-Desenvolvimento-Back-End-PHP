<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Entity\Product;

interface ProductSearchIndexInterface
{
    public function initialize(): void;
    public function upsert(Product $product): void;
    public function remove(int $productId): void;

    /** @return list<array<string, mixed>> */
    public function search(string $term, int $limit = 20): array;

    public function driver(): string;
}
