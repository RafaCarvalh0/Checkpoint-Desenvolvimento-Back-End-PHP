<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Entity\Product;

final class ProductCatalogService
{
    public function __construct(private readonly ProductRepositoryInterface $products)
    {
    }

    public function create(array $data): Product
    {
        $sku = $this->string($data, 'sku');
        if ($this->products->findOneBySku($sku) !== null) {
            throw new \InvalidArgumentException('Já existe um produto com este SKU.');
        }
        $product = new Product(
            $this->string($data, 'name'),
            isset($data['description']) ? (string) $data['description'] : null,
            $this->number($data, 'price'),
            $sku,
            $this->integer($data, 'stock'),
            $this->status($data['status'] ?? 'active'),
        );
        if (array_key_exists('images', $data)) {
            $product->replaceImages(is_array($data['images']) ? $data['images'] : []);
        }
        $this->products->save($product, true);
        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $sku = $this->string($data, 'sku');
        $existing = $this->products->findOneBySku($sku);
        if ($existing !== null && $existing->getId() !== $product->getId()) {
            throw new \InvalidArgumentException('Já existe um produto com este SKU.');
        }
        $product
            ->rename($this->string($data, 'name'))
            ->changeDescription(isset($data['description']) ? (string) $data['description'] : null)
            ->changePrice($this->number($data, 'price'))
            ->changeSku($sku)
            ->changeStock($this->integer($data, 'stock'));
        $this->status($data['status'] ?? 'active') === ProductStatus::Active
            ? $product->activate()
            : $product->deactivate();
        if (array_key_exists('images', $data)) {
            $product->replaceImages(is_array($data['images']) ? $data['images'] : []);
        }
        $this->products->save($product, true);
        return $product;
    }

    public function delete(Product $product): void
    {
        $this->products->remove($product, true);
    }

    private function string(array $data, string $key): string
    {
        if (!isset($data[$key]) || !is_scalar($data[$key])) {
            throw new \InvalidArgumentException("O campo {$key} é obrigatório.");
        }
        return (string) $data[$key];
    }

    private function number(array $data, string $key): string|int|float
    {
        if (!array_key_exists($key, $data) || !is_numeric($data[$key])) {
            throw new \InvalidArgumentException("O campo {$key} deve ser numérico.");
        }
        return $data[$key];
    }

    private function integer(array $data, string $key): int
    {
        if (!array_key_exists($key, $data) || filter_var($data[$key], FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException("O campo {$key} deve ser um número inteiro.");
        }
        return (int) $data[$key];
    }

    private function status(mixed $status): ProductStatus
    {
        $status = is_scalar($status) ? ProductStatus::tryFrom((string) $status) : null;
        return $status ?? throw new \InvalidArgumentException('O status informado é inválido.');
    }
}
