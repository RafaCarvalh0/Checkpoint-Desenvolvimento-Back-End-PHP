<?php

declare(strict_types=1);

namespace App\Message;

final readonly class GenerateProductThumbnail
{
    public function __construct(public int $imageId)
    {
    }
}
