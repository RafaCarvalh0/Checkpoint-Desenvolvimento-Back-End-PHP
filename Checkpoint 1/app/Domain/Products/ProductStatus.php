<?php

namespace App\Domain\Products;

enum ProductStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
