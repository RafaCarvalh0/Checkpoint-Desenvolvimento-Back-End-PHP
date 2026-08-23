<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Product\ProductRepositoryInterface;
use App\Domain\Product\ProductStatus;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
final class ProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    private const SORTS = ['name', 'price', 'stock', 'createdAt'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
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

    public function findOneWithImages(int $id): ?Product
    {
        return $this->createQueryBuilder('product')
            ->leftJoin('product.images', 'images')->addSelect('images')
            ->andWhere('product.id = :id')->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult();
    }

    public function findOneBySku(string $sku): ?Product
    {
        return $this->findOneBy(['sku' => strtoupper(trim($sku))]);
    }

    public function findOneBySlug(string $slug): ?Product
    {
        return $this->createQueryBuilder('product')
            ->leftJoin('product.images', 'images')->addSelect('images')
            ->andWhere('product.slug = :slug')->setParameter('slug', $slug)
            ->getQuery()->getOneOrNullResult();
    }

    public function findFiltered(array $filters, string $sort = 'name', string $direction = 'asc', ?int $limit = null, int $offset = 0): array
    {
        $query = $this->filteredQuery($filters);
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'name';
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy('product.'.$sort, $direction)->addOrderBy('product.id', 'ASC')->setFirstResult(max(0, $offset));
        if ($limit !== null) {
            $query->setMaxResults(max(1, min(50, $limit)));
        }
        return $query->getQuery()->getResult();
    }

    public function countFiltered(array $filters): int
    {
        return (int) $this->filteredQuery($filters)
            ->select('COUNT(product.id)')
            ->getQuery()->getSingleScalarResult();
    }

    private function filteredQuery(array $filters): QueryBuilder
    {
        $query = $this->createQueryBuilder('product');
        if (is_string($filters['name'] ?? null) && trim($filters['name']) !== '') {
            $query->andWhere('LOWER(product.name) LIKE LOWER(:name)')->setParameter('name', '%'.trim($filters['name']).'%');
        }
        if (is_string($filters['sku'] ?? null) && trim($filters['sku']) !== '') {
            $query->andWhere('product.sku = :sku')->setParameter('sku', strtoupper(trim($filters['sku'])));
        }
        if (($status = ProductStatus::tryFrom((string) ($filters['status'] ?? ''))) !== null) {
            $query->andWhere('product.status = :status')->setParameter('status', $status);
        }
        if (is_numeric($filters['min_price'] ?? null)) {
            $query->andWhere('product.price >= :minPrice')->setParameter('minPrice', max(0, (float) $filters['min_price']));
        }
        if (is_numeric($filters['max_price'] ?? null)) {
            $query->andWhere('product.price <= :maxPrice')->setParameter('maxPrice', max(0, (float) $filters['max_price']));
        }
        if (filter_var($filters['in_stock'] ?? false, FILTER_VALIDATE_BOOL)) {
            $query->andWhere('product.stock > 0');
        }
        return $query;
    }
}
