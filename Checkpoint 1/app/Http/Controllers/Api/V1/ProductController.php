<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Products\Product as DomainProduct;
use App\Domain\Products\ProductFilters;
use App\Domain\Products\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product as ProductModel;
use App\Support\Http\ApiResponse;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 50;
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 50;

    /**
     * @var array<string, string>
     */
    private const SORT_COLUMNS = [
        'name' => 'name',
        'price' => 'price',
        'stock' => 'stock',
        'created_at' => 'created_at',
    ];

    public function index(Request $request): JsonResponse
    {
        $filters = ProductFilters::fromArray($request->query());
        $sort = $this->sortColumn((string) $request->query('sort', 'name'));
        $direction = $this->sortDirection((string) $request->query('direction', 'asc'));
        $query = $this->filteredQuery($filters)
            ->orderBy($sort, $direction)
            ->orderBy('id', 'asc');

        if ($request->query->has('offset') || $request->query->has('limit')) {
            return $this->offsetResponse($query, $filters, $sort, $direction, $request);
        }

        return $this->paginatedResponse($query, $filters, $sort, $direction, $request);
    }

    private function paginatedResponse(
        Builder $query,
        array $filters,
        string $sort,
        string $direction,
        Request $request
    ): JsonResponse {
        $perPage = $this->perPage($request->query('per_page'));
        $products = $query->paginate($perPage)->withQueryString();

        return ApiResponse::success(
            $products
                ->getCollection()
                ->map(fn (ProductModel $product): array => $this->serialize($product))
                ->values(),
            [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
                'sort' => array_search($sort, self::SORT_COLUMNS, true) ?: 'name',
                'direction' => $direction,
                'order_tiebreaker' => 'id',
                'filters' => $filters,
                'links' => [
                    'first' => $products->url(1),
                    'last' => $products->url($products->lastPage()),
                    'prev' => $products->previousPageUrl(),
                    'next' => $products->nextPageUrl(),
                ],
            ],
        );
    }

    private function offsetResponse(
        Builder $query,
        array $filters,
        string $sort,
        string $direction,
        Request $request
    ): JsonResponse {
        $limit = $this->limit($request->query('limit'));
        $offset = $this->offset($request->query('offset'));
        $total = (clone $query)->count();
        $products = $query
            ->offset($offset)
            ->limit($limit)
            ->get();

        return ApiResponse::success(
            $products
                ->map(fn (ProductModel $product): array => $this->serialize($product))
                ->values(),
            [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'sort' => array_search($sort, self::SORT_COLUMNS, true) ?: 'name',
                'direction' => $direction,
                'order_tiebreaker' => 'id',
                'filters' => $filters,
                'links' => [
                    'prev' => $offset > 0
                        ? $this->offsetUrl($request, max(0, $offset - $limit), $limit)
                        : null,
                    'next' => ($offset + $limit) < $total
                        ? $this->offsetUrl($request, $offset + $limit, $limit)
                        : null,
                ],
            ],
        );
    }

    public function show(string $product): JsonResponse
    {
        $productModel = ctype_digit($product)
            ? ProductModel::query()->find((int) $product)
            : $this->findBySlug($product);

        if (! $productModel) {
            return ApiResponse::error('Produto não encontrado.', 404);
        }

        return ApiResponse::success($this->serialize($productModel));
    }

    /**
     * @param array{name?: string, sku?: string, category?: string, status?: string, min_price?: float, max_price?: float, in_stock?: bool} $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = ProductModel::query();

        if (isset($filters['name'])) {
            $query->where('name', 'like', "%{$filters['name']}%");
        }

        if (isset($filters['sku'])) {
            $query->where('sku', $filters['sku']);
        }

        if (isset($filters['category']) && Schema::hasColumn('products', 'category')) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('min_price', $filters)) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (array_key_exists('max_price', $filters)) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (! empty($filters['in_stock'])) {
            $query->where('stock', '>', 0);
        }

        return $query;
    }

    private function findBySlug(string $slug): ?ProductModel
    {
        return ProductModel::query()
            ->orderBy('id')
            ->get()
            ->first(function (ProductModel $product) use ($slug): bool {
                return $this->toDomain($product)->getSlug() === $slug;
            });
    }

    private function serialize(ProductModel $product): array
    {
        $domainProduct = $this->toDomain($product);

        return [
            'id' => $domainProduct->getId(),
            'name' => $domainProduct->getName(),
            'description' => $domainProduct->getDescription(),
            'price' => $domainProduct->getPrice(),
            'sku' => $domainProduct->getSku(),
            'stock' => $domainProduct->getStock(),
            'status' => $domainProduct->getStatus()->value,
            'slug' => $domainProduct->getSlug(),
            'links' => [
                'self' => route('api.v1.products.show', $domainProduct->getId()),
            ],
        ];
    }

    private function toDomain(ProductModel $product): DomainProduct
    {
        $sku = trim((string) $product->sku);

        return new DomainProduct(
            name: $product->name,
            description: $product->description,
            price: (float) $product->price,
            sku: $sku !== '' ? $sku : "PROD-{$product->id}",
            stock: (int) $product->stock,
            status: ProductStatus::tryFrom((string) $product->status) ?? ProductStatus::Active,
            id: (int) $product->id,
        );
    }

    private function sortColumn(string $sort): string
    {
        return self::SORT_COLUMNS[$sort] ?? self::SORT_COLUMNS['name'];
    }

    private function sortDirection(string $direction): string
    {
        return strtolower($direction) === 'desc' ? 'desc' : 'asc';
    }

    private function perPage(mixed $perPage): int
    {
        if ($perPage !== null && ! is_numeric($perPage)) {
            throw new InvalidArgumentException('O parâmetro per_page deve ser numérico.');
        }

        if (! is_numeric($perPage)) {
            return self::DEFAULT_PER_PAGE;
        }

        return max(1, min(self::MAX_PER_PAGE, (int) $perPage));
    }

    private function limit(mixed $limit): int
    {
        if ($limit !== null && ! is_numeric($limit)) {
            throw new InvalidArgumentException('O parâmetro limit deve ser numérico.');
        }

        if (! is_numeric($limit)) {
            return self::DEFAULT_LIMIT;
        }

        return max(1, min(self::MAX_LIMIT, (int) $limit));
    }

    private function offset(mixed $offset): int
    {
        if ($offset !== null && ! is_numeric($offset)) {
            throw new InvalidArgumentException('O parâmetro offset deve ser numérico.');
        }

        if (! is_numeric($offset)) {
            return 0;
        }

        return max(0, (int) $offset);
    }

    private function offsetUrl(Request $request, int $offset, int $limit): string
    {
        return $request->fullUrlWithQuery([
            'offset' => $offset,
            'limit' => $limit,
        ]);
    }
}
