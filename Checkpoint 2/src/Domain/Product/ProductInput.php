<?php

declare(strict_types=1);

namespace App\Domain\Product;

final readonly class ProductInput
{
    /** @param list<string>|null $images */
    public function __construct(
        public string $name,
        public ?string $description,
        public float $price,
        public string $sku,
        public int $stock,
        public ProductStatus $status,
        public string $category,
        public ?array $images = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            self::requiredString($data, 'name'),
            isset($data['description']) ? (string) $data['description'] : null,
            self::number($data, 'price'),
            self::requiredString($data, 'sku'),
            self::integer($data, 'stock'),
            self::status($data['status'] ?? ProductStatus::Active->value),
            isset($data['category']) ? (string) $data['category'] : 'Sem categoria',
            array_key_exists('images', $data) ? self::images($data['images']) : null,
        );
    }

    private static function requiredString(array $data, string $key): string
    {
        if (!isset($data[$key]) || !is_scalar($data[$key])) {
            throw new \InvalidArgumentException("O campo {$key} é obrigatório.");
        }
        return (string) $data[$key];
    }

    private static function number(array $data, string $key): float
    {
        if (!array_key_exists($key, $data) || !is_numeric($data[$key])) {
            throw new \InvalidArgumentException("O campo {$key} deve ser numérico.");
        }
        return (float) $data[$key];
    }

    private static function integer(array $data, string $key): int
    {
        if (!array_key_exists($key, $data) || filter_var($data[$key], FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException("O campo {$key} deve ser um número inteiro.");
        }
        return (int) $data[$key];
    }

    private static function status(mixed $status): ProductStatus
    {
        $status = is_scalar($status) ? ProductStatus::tryFrom((string) $status) : null;
        return $status ?? throw new \InvalidArgumentException('O status informado é inválido.');
    }

    /** @return list<string> */
    private static function images(mixed $images): array
    {
        if (!is_array($images)) {
            return [];
        }
        return array_values($images);
    }
}
