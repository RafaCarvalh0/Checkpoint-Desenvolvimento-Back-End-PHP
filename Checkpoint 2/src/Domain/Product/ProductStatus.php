<?php

declare(strict_types=1);

namespace App\Domain\Product;

enum ProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
