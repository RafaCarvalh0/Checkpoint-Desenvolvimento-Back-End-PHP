<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
