<?php

namespace App\Events;

class ProductCreated
{
    public function __construct(public readonly int $productId)
    {
    }
}
