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
                'sku' => 'TEC-MEC-001',
                'stock' => 12,
                'status' => 'active',
            ],
            [
                'name' => 'Mouse Óptico',
                'description' => 'Mouse ergonômico com sensor de alta precisão.',
                'price' => 89.90,
                'sku' => 'MOU-OPT-001',
                'stock' => 30,
                'status' => 'active',
            ],
            [
                'name' => 'Monitor 24 Polegadas',
                'description' => 'Monitor LED Full HD para produtividade.',
                'price' => 899.00,
                'sku' => 'MON-24-001',
                'stock' => 8,
                'status' => 'active',
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
