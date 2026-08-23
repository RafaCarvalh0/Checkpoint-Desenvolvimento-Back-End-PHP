<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Domain\Product\ProductSearchIndexInterface;
use App\Entity\Product;

final class MongoProductSearchIndex implements ProductSearchIndexInterface
{
    private bool $initialized = false;

    public function __construct(private readonly object $collection)
    {
    }

    public function initialize(): void
    {
        if (!$this->initialized) {
            $this->collection->createIndex(
                ['name' => 'text', 'description' => 'text', 'sku' => 'text', 'category' => 'text'],
                ['name' => 'products_text_search'],
            );
            $this->initialized = true;
        }
    }

    public function upsert(Product $product): void
    {
        $this->initialize();
        $this->collection->replaceOne(['product_id' => $product->getId()], [
            'product_id' => $product->getId(), 'name' => $product->getName(), 'description' => $product->getDescription(),
            'sku' => $product->getSku(), 'category' => $product->getCategory(), 'price' => $product->getPrice(),
            'stock' => $product->getStock(), 'status' => $product->getStatus()->value, 'slug' => $product->getSlug(),
            'updated_at' => $product->getUpdatedAt(),
        ], ['upsert' => true]);
    }

    public function remove(int $productId): void
    {
        $this->collection->deleteOne(['product_id' => $productId]);
    }

    public function search(string $term, int $limit = 20): array
    {
        $this->initialize();
        $cursor = $this->collection->find(
            ['$text' => ['$search' => $term]],
            ['limit' => max(1, min(50, $limit)), 'projection' => ['_id' => 0, 'score' => ['$meta' => 'textScore']], 'sort' => ['score' => ['$meta' => 'textScore']]],
        );
        return array_values(array_map(static fn (object|array $document): array => (array) $document, iterator_to_array($cursor)));
    }

    public function driver(): string
    {
        return 'mongodb';
    }
}
