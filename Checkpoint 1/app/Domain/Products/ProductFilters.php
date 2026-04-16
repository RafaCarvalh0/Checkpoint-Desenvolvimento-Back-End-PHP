<?php

namespace App\Domain\Products;

class ProductFilters
{
    /**
     * @return array{category?: string, min_price?: float, max_price?: float, status?: string}
     */
    public static function compose(?string $category = null, ?array $priceRange = null, ProductStatus|string|null $status = null): array
    {
        return self::fromArray([
            'category' => $category,
            'priceRange' => $priceRange,
            'status' => $status,
        ]);
    }

    /**
     * @return array{name?: string, sku?: string, category?: string, status?: string, min_price?: float, max_price?: float, in_stock?: bool}
     */
    public static function fromArray(array $filters): array
    {
        $normalized = [];

        self::putString($normalized, 'name', $filters['name'] ?? null);
        self::putString($normalized, 'category', $filters['category'] ?? null);

        if (isset($filters['sku'])) {
            self::putString($normalized, 'sku', strtoupper((string) $filters['sku']));
        }

        self::putStatus($normalized, $filters['status'] ?? null);
        self::putPriceRange($normalized, $filters);

        if (! empty($filters['in_stock'])) {
            $normalized['in_stock'] = true;
        }

        return $normalized;
    }

    private static function putString(array &$filters, string $key, mixed $value): void
    {
        if (! is_scalar($value)) {
            return;
        }

        $value = trim((string) $value);

        if ($value !== '') {
            $filters[$key] = $value;
        }
    }

    private static function putStatus(array &$filters, mixed $status): void
    {
        if ($status instanceof ProductStatus) {
            $filters['status'] = $status->value;

            return;
        }

        if (! is_scalar($status)) {
            return;
        }

        $status = trim((string) $status);

        if (in_array($status, array_column(ProductStatus::cases(), 'value'), true)) {
            $filters['status'] = $status;
        }
    }

    private static function putPriceRange(array &$normalized, array $filters): void
    {
        $priceRange = is_array($filters['priceRange'] ?? null) ? $filters['priceRange'] : [];

        $min = $filters['min_price'] ?? $filters['minPrice'] ?? $priceRange['min'] ?? null;
        $max = $filters['max_price'] ?? $filters['maxPrice'] ?? $priceRange['max'] ?? null;

        if (is_numeric($min)) {
            $normalized['min_price'] = max(0.0, (float) $min);
        }

        if (is_numeric($max)) {
            $normalized['max_price'] = max(0.0, (float) $max);
        }
    }
}
