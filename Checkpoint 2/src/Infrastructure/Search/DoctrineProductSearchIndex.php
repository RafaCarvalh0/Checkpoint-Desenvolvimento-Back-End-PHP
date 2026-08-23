<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Domain\Product\ProductRepositoryInterface;
use App\Domain\Product\ProductSearchIndexInterface;
use App\Entity\Product;

final class DoctrineProductSearchIndex implements ProductSearchIndexInterface
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function initialize(): void
    {
    }

    public function upsert(Product $product): void
    {
    }

    public function remove(int $productId): void
    {
    }

    public function search(string $term, int $limit = 20): array
    {
        return array_map(static fn (Product $product): array => [
            'id' => $product->getId(), 'name' => $product->getName(), 'sku' => $product->getSku(),
            'category' => $product->getCategory(), 'price' => $product->getPrice(), 'stock' => $product->getStock(),
            'status' => $product->getStatus()->value, 'slug' => $product->getSlug(),
        ], $this->products->findFiltered(['name' => $term], limit: $limit));
    }

    public function driver(): string
    {
        return 'doctrine';
    }
}
