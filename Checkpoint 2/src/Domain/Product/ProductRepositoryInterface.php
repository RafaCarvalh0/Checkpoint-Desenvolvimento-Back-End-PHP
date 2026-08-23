<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product, bool $flush = false): void;
    public function remove(Product $product, bool $flush = false): void;
    public function findOneWithImages(int $id): ?Product;
    public function findOneBySku(string $sku): ?Product;
    public function findOneBySlug(string $slug): ?Product;

    /** @return list<Product> */
    public function findFiltered(array $filters, string $sort = 'name', string $direction = 'asc', ?int $limit = null, int $offset = 0): array;

    public function countFiltered(array $filters): int;
}
