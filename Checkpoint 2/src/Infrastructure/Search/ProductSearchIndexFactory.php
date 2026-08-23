<?php

declare(strict_types=1);

namespace App\Infrastructure\Search;

use App\Domain\Product\ProductRepositoryInterface;
use App\Domain\Product\ProductSearchIndexInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ProductSearchIndexFactory
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(bool:MONGODB_ENABLED)%')] private readonly bool $enabled,
        #[Autowire('%env(MONGODB_URL)%')] private readonly string $url,
        #[Autowire('%env(MONGODB_DATABASE)%')] private readonly string $database,
        #[Autowire('%env(MONGODB_COLLECTION)%')] private readonly string $collection,
    ) {
    }

    public function create(): ProductSearchIndexInterface
    {
        if (!$this->enabled || !extension_loaded('mongodb') || !class_exists('MongoDB\\Client')) {
            $this->logger->info('product.search.doctrine_fallback', ['mongodb_enabled' => $this->enabled]);
            return new DoctrineProductSearchIndex($this->products);
        }
        $clientClass = 'MongoDB\\Client';
        $client = new $clientClass($this->url, [], ['serverSelectionTimeoutMS' => 2000]);
        return new MongoProductSearchIndex($client->selectCollection($this->database, $this->collection));
    }
}
