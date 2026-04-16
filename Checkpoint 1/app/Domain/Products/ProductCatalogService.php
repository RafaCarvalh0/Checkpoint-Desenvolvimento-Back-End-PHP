<?php

namespace App\Domain\Products;

use App\Exceptions\ProductNotFoundException;
use InvalidArgumentException;

class ProductCatalogService
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    /**
     * @param array{name: string, description?: string|null, price: numeric, sku: string, stock: int, status?: string|ProductStatus} $data
     */
    public function create(array $data): Product
    {
        return $this->products->add($this->makeProduct($data));
    }

    /**
     * @param array{name: string, description?: string|null, price: numeric, sku: string, stock: int, status?: string|ProductStatus} $data
     */
    public function update(int $id, array $data): Product
    {
        if ($this->products->findById($id) === null) {
            throw new ProductNotFoundException($id);
        }

        return $this->products->update($id, $this->makeProduct($data, $id));
    }

    public function increaseStock(int $id, int $quantity): Product
    {
        $this->ensurePositiveQuantity($quantity);

        return $this->products->increaseStock($id, $quantity);
    }

    public function decreaseStock(int $id, int $quantity): Product
    {
        $this->ensurePositiveQuantity($quantity);

        return $this->products->decreaseStock($id, $quantity);
    }

    /**
     * @param array{name: string, description?: string|null, price: numeric, sku: string, stock: int, status?: string|ProductStatus} $data
     */
    private function makeProduct(array $data, ?int $id = null): Product
    {
        return new Product(
            name: $this->requiredString($data, 'name'),
            description: isset($data['description']) ? (string) $data['description'] : null,
            price: $this->requiredFloat($data, 'price'),
            sku: $this->requiredString($data, 'sku'),
            stock: $this->requiredInt($data, 'stock'),
            status: $this->statusFrom($data['status'] ?? ProductStatus::Active),
            id: $id,
        );
    }

    private function requiredString(array $data, string $key): string
    {
        if (! array_key_exists($key, $data) || ! is_scalar($data[$key])) {
            throw new InvalidArgumentException("O campo {$key} e obrigatorio.");
        }

        return (string) $data[$key];
    }

    private function requiredFloat(array $data, string $key): float
    {
        if (! array_key_exists($key, $data) || ! is_numeric($data[$key])) {
            throw new InvalidArgumentException("O campo {$key} deve ser numerico.");
        }

        return (float) $data[$key];
    }

    private function requiredInt(array $data, string $key): int
    {
        if (! array_key_exists($key, $data) || ! is_numeric($data[$key])) {
            throw new InvalidArgumentException("O campo {$key} deve ser numerico.");
        }

        return (int) $data[$key];
    }

    private function statusFrom(string|ProductStatus $status): ProductStatus
    {
        if ($status instanceof ProductStatus) {
            return $status;
        }

        $productStatus = ProductStatus::tryFrom($status);

        if ($productStatus === null) {
            throw new InvalidArgumentException('O status do produto e invalido.');
        }

        return $productStatus;
    }

    private function ensurePositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('A quantidade deve ser maior que zero.');
        }
    }
}
