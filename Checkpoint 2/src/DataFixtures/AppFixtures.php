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
        $manager->persist(new Product('Teclado Mecânico', 'Teclado ABNT2', 249.90, 'TEC-001', 10));
        $manager->persist(new Product('Mouse Óptico', null, 89.90, 'MOU-001', 5));
        $manager->persist(new Product('Monitor 24 Polegadas', 'Monitor Full HD', 899, 'MON-001', 2, ProductStatus::Inactive));

        $manager->flush();
    }
}
