<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Domain\Product\ProductRepositoryInterface;
use App\Domain\Product\ProductStatus;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ProductRepositoryTest extends KernelTestCase
{
    private ProductRepositoryInterface $products;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($this->entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
        $this->products = self::getContainer()->get(ProductRepositoryInterface::class);
    }

    public function testQueryBuilderFiltersNamePriceAndStatus(): void
    {
        $this->products->save(new Product('Teclado Mecânico', null, 249.90, 'TEC-001', 5), true);
        $this->products->save(new Product('Teclado Simples', null, 59.90, 'TEC-002', 0), true);
        $this->products->save(new Product('Mouse', null, 89.90, 'MOU-001', 10, ProductStatus::Inactive), true);

        $result = $this->products->findFiltered([
            'name' => 'teclado', 'min_price' => 100, 'max_price' => 300, 'status' => 'active',
        ]);

        self::assertCount(1, $result);
        self::assertSame('TEC-001', $result[0]->getSku());
    }

    public function testDetailUsesJoinFetchForImages(): void
    {
        $product = new Product('Notebook', null, 3500, 'NOTE-001', 2);
        $product->replaceImages(['https://example.com/notebook.jpg']);
        $this->products->save($product, true);
        $id = $product->getId();
        $this->entityManager->clear();

        $found = $this->products->findOneWithImages((int) $id);

        self::assertNotNull($found);
        self::assertInstanceOf(PersistentCollection::class, $found->getImages());
        self::assertTrue($found->getImages()->isInitialized());
        self::assertCount(1, $found->getImages());
    }
}
