<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Domain\Product\ProductStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = ['Informática', 'Escritório', 'Acessórios', 'Áudio'];
        for ($index = 1; $index <= 1000; ++$index) {
            $manager->persist(new Product(
                sprintf('Produto %04d', $index),
                'Produto gerado para testes de carga e profiling.',
                ($index % 500) + 9.90,
                sprintf('PROD-%04d', $index),
                $index % 51,
                $index % 20 === 0 ? ProductStatus::Inactive : ProductStatus::Active,
                $categories[$index % count($categories)],
            ));
            if ($index % 100 === 0) {
                $manager->flush();
                $manager->clear();
            }
        }

        $manager->flush();
    }
}
