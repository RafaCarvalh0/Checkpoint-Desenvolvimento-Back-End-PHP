<?php

declare(strict_types=1);

namespace App\Http\Api;

use Symfony\Component\HttpFoundation\Request;

final readonly class ProductListQuery
{
    private const SORTS = ['name' => 'name', 'price' => 'price', 'stock' => 'stock', 'created_at' => 'createdAt'];

    private function __construct(
        public array $filters,
        public string $sort,
        public string $sortInput,
        public string $direction,
        public ?int $limit,
        public int $offset,
        public int $page,
        public int $perPage,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $sortInput = $request->query->getString('sort', 'name');
        $usesOffset = $request->query->has('offset') || $request->query->has('limit');
        return new self(
            self::filters($request),
            self::SORTS[$sortInput] ?? 'name',
            $sortInput,
            strtolower($request->query->getString('direction', 'asc')) === 'desc' ? 'desc' : 'asc',
            $usesOffset ? self::boundedInteger($request->query->get('limit'), 10, 1, 50, 'limit') : null,
            $usesOffset ? self::boundedInteger($request->query->get('offset'), 0, 0, PHP_INT_MAX, 'offset') : 0,
            $usesOffset ? 1 : self::boundedInteger($request->query->get('page'), 1, 1, PHP_INT_MAX, 'page'),
            $usesOffset ? 10 : self::boundedInteger($request->query->get('per_page'), 10, 1, 50, 'per_page'),
        );
    }

    public function effectiveLimit(): int
    {
        return $this->limit ?? $this->perPage;
    }

    public function effectiveOffset(): int
    {
        return $this->limit === null ? ($this->page - 1) * $this->perPage : $this->offset;
    }

    private static function filters(Request $request): array
    {
        $filters = [];
        foreach (['name', 'sku', 'category'] as $key) {
            if (($value = trim($request->query->getString($key))) !== '') {
                $filters[$key] = $key === 'sku' ? strtoupper($value) : $value;
            }
        }
        if (in_array($status = $request->query->getString('status'), ['active', 'inactive'], true)) {
            $filters['status'] = $status;
        }
        foreach (['min_price', 'max_price'] as $key) {
            $value = $request->query->get($key);
            if ($value !== null && $value !== '') {
                if (!is_numeric($value)) {
                    throw new \InvalidArgumentException("O parâmetro {$key} deve ser numérico.");
                }
                $filters[$key] = max(0, (float) $value);
            }
        }
        if (filter_var($request->query->get('in_stock'), FILTER_VALIDATE_BOOL)) {
            $filters['in_stock'] = true;
        }
        return $filters;
    }

    private static function boundedInteger(mixed $value, int $default, int $min, int $max, string $name): int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException("O parâmetro {$name} deve ser numérico.");
        }
        return max($min, min($max, (int) $value));
    }
}
