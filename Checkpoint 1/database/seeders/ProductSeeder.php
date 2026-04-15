<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Teclado Mecânico',
                'description' => 'Teclado ABNT2 com switches táteis.',
                'price' => 249.90,
                'stock' => 12,
            ],
            [
                'name' => 'Mouse Óptico',
                'description' => 'Mouse ergonômico com sensor de alta precisão.',
                'price' => 89.90,
                'stock' => 30,
            ],
            [
                'name' => 'Monitor 24 Polegadas',
                'description' => 'Monitor LED Full HD para produtividade.',
                'price' => 899.00,
                'stock' => 8,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
