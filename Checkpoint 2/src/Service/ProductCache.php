<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;

final class ProductCache
{
    private const TTL = 300;

    public function __construct(private readonly CacheItemPoolInterface $productCache)
    {
    }

    /** @template T @param callable(): T $loader @return array{value: T, hit: bool} */
    public function remember(string $namespace, array $parameters, callable $loader): array
    {
        ksort($parameters);
        $key = $namespace.'.'.hash('sha256', json_encode($parameters, JSON_THROW_ON_ERROR));
        $item = $this->productCache->getItem($key);
        if ($item->isHit()) {
            return ['value' => $item->get(), 'hit' => true];
        }
        $value = $loader();
        $item->set($value)->expiresAfter(self::TTL);
        $this->productCache->save($item);
        return ['value' => $value, 'hit' => false];
    }

    public function invalidate(): void
    {
        $this->productCache->clear();
    }
}
