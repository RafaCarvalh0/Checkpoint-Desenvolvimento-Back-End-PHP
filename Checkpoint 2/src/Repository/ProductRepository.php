<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return list<Product>
     */
    public function findByFilters(
        ?string $name = null,
        string|int|float|null $minPrice = null,
        string|int|float|null $maxPrice = null,
        ?bool $active = null,
    ): array {
        $query = $this->createQueryBuilder('product')
            ->orderBy('product.id', 'ASC');

        if ($name !== null && trim($name) !== '') {
            $query
                ->andWhere('LOWER(product.name) LIKE LOWER(:name)')
                ->setParameter('name', '%'.trim($name).'%');
        }

        if ($minPrice !== null && $minPrice !== '') {
            $query->andWhere('product.price >= :minPrice')->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $query->andWhere('product.price <= :maxPrice')->setParameter('maxPrice', $maxPrice);
        }

        if ($active !== null) {
            $query->andWhere('product.active = :active')->setParameter('active', $active);
        }

        return $query->getQuery()->getResult();
    }

    public function save(Product $product, bool $flush = false): void
    {
        $this->getEntityManager()->persist($product);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $product, bool $flush = false): void
    {
        $this->getEntityManager()->remove($product);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
