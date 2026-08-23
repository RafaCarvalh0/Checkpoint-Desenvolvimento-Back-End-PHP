<?php

declare(strict_types=1);

namespace App\Event;

final readonly class ProductDeletedEvent
{
    public function __construct(public int $productId)
    {
    }
}
