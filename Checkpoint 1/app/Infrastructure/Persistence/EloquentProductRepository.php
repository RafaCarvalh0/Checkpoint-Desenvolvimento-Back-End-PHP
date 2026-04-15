<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Products\Product as DomainProduct;
use App\Domain\Products\ProductFilters;
use App\Domain\Products\ProductRepositoryInterface;
use App\Domain\Products\ProductStatus;
use App\Exceptions\ProductNotFoundException;
use App\Models\Product as ProductModel;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function add(DomainProduct $product): DomainProduct
    {
        return $this->addWithImages($product, []);
    }

    public function addWithImages(DomainProduct $product, array $imageUrls): DomainProduct
    {
        return DB::transaction(function () use ($product, $imageUrls): DomainProduct {
            $model = ProductModel::query()->create($this->toDatabase($product));
            $this->syncImages($model, $imageUrls);

            return $this->toDomain($model);
        });
    }

    public function update(int $id, DomainProduct $product): DomainProduct
    {
        return $this->updateWithImages($id, $product, []);
    }

    public function updateWithImages(int $id, DomainProduct $product, array $imageUrls): DomainProduct
    {
        return DB::transaction(function () use ($id, $product, $imageUrls): DomainProduct {
            $model = ProductModel::query()->lockForUpdate()->find($id);

            if (! $model) {
                throw new ProductNotFoundException($id);
            }

            $model->update($this->toDatabase($product));
            $this->syncImages($model, $imageUrls);

            return $this->toDomain($model->refresh());
        });
    }

    public function increaseStock(int $id, int $quantity): DomainProduct
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('A quantidade de entrada deve ser maior que zero.');
        }

        return $this->adjustStock($id, $quantity);
    }

    public function decreaseStock(int $id, int $quantity): DomainProduct
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('A quantidade de saída deve ser maior que zero.');
        }

        return $this->adjustStock($id, -$quantity);
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $model = ProductModel::query()->lockForUpdate()->find($id);

            if (! $model) {
                throw new ProductNotFoundException($id);
            }

            $model->delete();
        });
    }

    public function findById(int $id): ?DomainProduct
    {
        $model = ProductModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByFilters(array $filters): array
    {
        $filters = ProductFilters::fromArray($filters);
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

        return $query
            ->orderBy('name')
            ->get()
            ->map(fn (ProductModel $model): DomainProduct => $this->toDomain($model))
            ->all();
    }

    private function toDomain(ProductModel $model): DomainProduct
    {
        return new DomainProduct(
            name: $model->name,
            description: $model->description,
            price: (float) $model->price,
            sku: $this->skuFromModel($model),
            stock: (int) $model->stock,
            status: ProductStatus::tryFrom((string) $model->status) ?? ProductStatus::Active,
            id: (int) $model->id,
        );
    }

    private function skuFromModel(ProductModel $model): string
    {
        $sku = trim((string) $model->sku);

        return $sku !== '' ? $sku : "PROD-{$model->id}";
    }

    /**
     * @param string[] $imageUrls
     */
    private function syncImages(ProductModel $model, array $imageUrls): void
    {
        $model->images()->delete();

        foreach ($this->normalizeImageUrls($imageUrls) as $position => $url) {
            $model->images()->create([
                'url' => $url,
                'position' => $position,
            ]);
        }
    }

    /**
     * @param string[] $imageUrls
     * @return string[]
     */
    private function normalizeImageUrls(array $imageUrls): array
    {
        return array_values(array_filter(array_map(function (mixed $url): ?string {
            if (! is_scalar($url)) {
                return null;
            }

            $url = trim((string) $url);

            if ($url === '') {
                return null;
            }

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('A URL da imagem é inválida.');
            }

            return $url;
        }, $imageUrls)));
    }

    private function adjustStock(int $id, int $quantity): DomainProduct
    {
        return DB::transaction(function () use ($id, $quantity): DomainProduct {
            $model = ProductModel::query()->lockForUpdate()->find($id);

            if (! $model) {
                throw new ProductNotFoundException($id);
            }

            $newStock = (int) $model->stock + $quantity;

            if ($newStock < 0) {
                throw new InvalidArgumentException('O estoque não pode ficar negativo.');
            }

            $model->update(['stock' => $newStock]);

            return $this->toDomain($model->refresh());
        });
    }

    /**
     * @return array{name: string, description: ?string, price: float, sku: string, stock: int, status: string}
     */
    private function toDatabase(DomainProduct $product): array
    {
        return [
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'status' => $product->getStatus()->value,
        ];
    }

}
