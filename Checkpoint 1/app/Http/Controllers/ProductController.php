<?php

namespace App\Http\Controllers;

use App\Domain\Products\Product as DomainProduct;
use App\Domain\Products\ProductRepositoryInterface;
use App\Domain\Products\ProductStatus;
use App\Exceptions\ProductNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $product = $this->products->add($this->productFromRequest($request));

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
            'statuses' => ProductStatus::cases(),
        ]);
    }

    public function update(Request $request, int $product): RedirectResponse|JsonResponse
    {
        $updated = $this->products->update($product, $this->productFromRequest($request));

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

    private function productFromRequest(Request $request): DomainProduct
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sku' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9_-]+$/'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]);

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
