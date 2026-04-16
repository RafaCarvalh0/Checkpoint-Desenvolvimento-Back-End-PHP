<?php

namespace App\Http\Controllers;

use App\Domain\Products\Product as DomainProduct;
use App\Domain\Products\ProductRepositoryInterface;
use App\Domain\Products\ProductStatus;
use App\Events\ProductCreated;
use App\Exceptions\ProductNotFoundException;
use App\Http\Requests\ProductRequest;
use App\Models\Product as ProductModel;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $products = $this->products->findByFilters($request->query());

        if ($request->expectsJson()) {
            return response()->json([
                'data' => array_map(fn (DomainProduct $product): array => $this->toArray($product), $products),
            ]);
        }

        return view('products.index', [
            'products' => $products,
            'filters' => $request->query(),
        ]);
    }

    public function create(): View
    {
        return view('products.create', [
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse|JsonResponse
    {
        $imageUrls = $this->uploadedImageUrls($request);
        $product = $imageUrls === []
            ? $this->products->add($this->productFromRequest($request))
            : $this->products->addWithImages($this->productFromRequest($request), $imageUrls);

        Event::dispatch(new ProductCreated((int) $product->getId()));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produto criado com sucesso.',
                'data' => $this->toArray($product),
            ], 201);
        }

        return redirect()
            ->route('products.show', $product->getId())
            ->with('success', 'Produto criado com sucesso.');
    }

    public function show(Request $request, int $product): View|JsonResponse
    {
        $productModel = $this->products->findById($product);

        if (! $productModel) {
            throw new ProductNotFoundException($product);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $this->toArray($productModel),
            ]);
        }

        return view('products.show', [
            'product' => $productModel,
            'images' => $this->imagesForProduct($product),
        ]);
    }

    public function edit(int $product): View
    {
        $productModel = $this->products->findById($product);

        if (! $productModel) {
            throw new ProductNotFoundException($product);
        }

        return view('products.edit', [
            'product' => $productModel,
            'images' => $this->imagesForProduct($product),
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function update(ProductRequest $request, int $product): RedirectResponse|JsonResponse
    {
        $imageUrls = $this->uploadedImageUrls($request);
        $updated = $imageUrls === []
            ? $this->products->update($product, $this->productFromRequest($request))
            : $this->products->updateWithImages($product, $this->productFromRequest($request), $imageUrls);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produto atualizado com sucesso.',
                'data' => $this->toArray($updated),
            ]);
        }

        return redirect()
            ->route('products.show', $updated->getId())
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(Request $request, int $product): RedirectResponse|JsonResponse
    {
        $productModel = ProductModel::query()->find($product);

        if (! $productModel) {
            throw new ProductNotFoundException($product);
        }

        Gate::authorize('delete', $productModel);

        $this->products->delete($product);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produto removido com sucesso.',
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Produto removido com sucesso.');
    }

    private function productFromRequest(ProductRequest $request): DomainProduct
    {
        $data = $request->validated();

        return new DomainProduct(
            name: $data['name'],
            description: $data['description'] ?? null,
            price: (float) $data['price'],
            sku: $data['sku'],
            stock: (int) $data['stock'],
            status: ProductStatus::from($data['status']),
        );
    }

    /**
     * @return string[]
     */
    private function uploadedImageUrls(ProductRequest $request): array
    {
        $file = $request->file('image');

        if (! $file instanceof UploadedFile) {
            return [];
        }

        $path = $file->store('products', 'public');

        return $path ? [$request->getSchemeAndHttpHost().'/storage/'.$path] : [];
    }

    private function imagesForProduct(int $productId)
    {
        return ProductImage::query()
            ->where('product_id', $productId)
            ->orderBy('position')
            ->get();
    }

    /**
     * @return array{id: ?int, name: string, description: ?string, price: float, sku: string, stock: int, status: string, slug: string}
     */
    private function toArray(DomainProduct $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'sku' => $product->getSku(),
            'stock' => $product->getStock(),
            'status' => $product->getStatus()->value,
            'slug' => $product->getSlug(),
        ];
    }
}
