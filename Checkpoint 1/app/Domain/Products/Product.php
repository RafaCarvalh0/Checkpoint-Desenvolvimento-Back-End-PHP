<?php

namespace App\Domain\Products;

use App\Support\Strings\SlugTrait;
use InvalidArgumentException;

class Product
{
    use SlugTrait;

    private ?int $id;
    private string $name;
    private ?string $description;
    private int $priceInCents;
    private string $sku;
    private int $stock;
    private ProductStatus $status;

    public function __construct(
        string $name,
        ?string $description,
        float $price,
        string $sku,
        int $stock,
        ProductStatus $status = ProductStatus::Active,
        ?int $id = null
    ) {
        $this->changeId($id);
        $this->rename($name);
        $this->changeDescription($description);
        $this->changePrice($price);
        $this->changeSku($sku);
        $this->changeStock($stock);
        $this->status = $status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->priceInCents / 100;
    }

    public function getPriceInCents(): int
    {
        return $this->priceInCents;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getSlug(): string
    {
        return $this->makeSlug($this->name);
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function getStatus(): ProductStatus
    {
        return $this->status;
    }

    public function rename(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('O nome do produto é obrigatório.');
        }

        if (mb_strlen($name) > 120) {
            throw new InvalidArgumentException('O nome do produto deve ter no máximo 120 caracteres.');
        }

        $this->name = $name;
    }

    public function changeDescription(?string $description): void
    {
        $description = $description === null ? null : trim($description);

        $this->description = $description === '' ? null : $description;
    }

    public function changePrice(float $price): void
    {
        if ($price < 0) {
            throw new InvalidArgumentException('O preço do produto não pode ser negativo.');
        }

        $this->priceInCents = (int) round($price * 100);
    }

    public function changeSku(string $sku): void
    {
        $sku = strtoupper(trim($sku));

        if ($sku === '') {
            throw new InvalidArgumentException('O SKU do produto é obrigatório.');
        }

        if (! preg_match('/^[A-Z0-9_-]+$/', $sku)) {
            throw new InvalidArgumentException('O SKU deve conter apenas letras, números, hífen ou sublinhado.');
        }

        if (mb_strlen($sku) > 60) {
            throw new InvalidArgumentException('O SKU deve ter no máximo 60 caracteres.');
        }

        $this->sku = $sku;
    }

    public function changeStock(int $stock): void
    {
        if ($stock < 0) {
            throw new InvalidArgumentException('O estoque do produto não pode ser negativo.');
        }

        $this->stock = $stock;
    }

    public function increaseStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('A quantidade de entrada deve ser maior que zero.');
        }

        $this->stock += $quantity;
    }

    public function decreaseStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('A quantidade de saída deve ser maior que zero.');
        }

        if ($quantity > $this->stock) {
            throw new InvalidArgumentException('O estoque não pode ficar negativo.');
        }

        $this->stock -= $quantity;
    }

    public function activate(): void
    {
        $this->status = ProductStatus::Active;
    }

    public function deactivate(): void
    {
        $this->status = ProductStatus::Inactive;
    }

    public function isActive(): bool
    {
        return $this->status === ProductStatus::Active;
    }

    private function changeId(?int $id): void
    {
        if ($id !== null && $id <= 0) {
            throw new InvalidArgumentException('O ID do produto deve ser maior que zero.');
        }

        $this->id = $id;
    }
}
