<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Product\ProductRepositoryInterface;
use App\Domain\Product\ProductSearchIndexInterface;
use App\Message\SyncProductSearchIndex;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncProductSearchIndexHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly ProductSearchIndexInterface $searchIndex,
    ) {
    }

    public function __invoke(SyncProductSearchIndex $message): void
    {
        if ($message->deleted) {
            $this->searchIndex->remove($message->productId);
            return;
        }
        $product = $this->products->findOneWithImages($message->productId);
        if ($product !== null) {
            $this->searchIndex->upsert($product);
        }
    }
}
