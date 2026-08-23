<?php

declare(strict_types=1);

namespace App\Event;

final readonly class ProductCreatedEvent
{
    /** @param list<int> $imageIds */
    public function __construct(public int $productId, public array $imageIds)
    {
    }
}
