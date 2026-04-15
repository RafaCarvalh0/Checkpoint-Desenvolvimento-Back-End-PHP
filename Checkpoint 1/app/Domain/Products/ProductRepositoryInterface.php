<?php

namespace App\Domain\Products;

interface ProductRepositoryInterface
{
    public function add(Product $product): Product;

    /**
     * @param string[] $imageUrls
     */
    public function addWithImages(Product $product, array $imageUrls): Product;

    public function update(int $id, Product $product): Product;

    /**
     * @param string[] $imageUrls
     */
    public function updateWithImages(int $id, Product $product, array $imageUrls): Product;

    public function increaseStock(int $id, int $quantity): Product;

    public function decreaseStock(int $id, int $quantity): Product;

    public function findById(int $id): ?Product;

    /**
     * @return Product[]
     */
    public function findByFilters(array $filters): array;
}
