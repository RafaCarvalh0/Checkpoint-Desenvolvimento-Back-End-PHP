<?php

declare(strict_types=1);

namespace App\Message;

final readonly class SyncProductSearchIndex
{
    public function __construct(public int $productId, public bool $deleted = false)
    {
    }
}
