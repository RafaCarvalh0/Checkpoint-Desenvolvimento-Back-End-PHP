<?php

namespace App\Http\Controllers;

use App\Exceptions\ProductNotFoundException;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->orderBy('name')
            ->paginate(10);

        return view('products.index', [
            'products' => $products,
        ]);
    }

    public function show(int $product): View
    {
        $productModel = Product::query()->find($product);

        if (! $productModel) {
            throw new ProductNotFoundException($product);
        }

        return view('products.show', [
            'product' => $productModel,
        ]);
    }
}
