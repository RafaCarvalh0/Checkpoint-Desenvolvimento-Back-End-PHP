<?php

declare(strict_types=1);

namespace App\Http\Api;

use App\Entity\Product;
use App\Entity\ProductImage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ProductApiPresenter
{
    public function normalize(Product $product, bool $withImages = false): array
    {
        $data = [
            'id' => $product->getId(), 'name' => $product->getName(), 'description' => $product->getDescription(),
            'price' => $product->getPrice(), 'sku' => $product->getSku(), 'stock' => $product->getStock(),
            'status' => $product->getStatus()->value, 'category' => $product->getCategory(), 'slug' => $product->getSlug(),
        ];
        if ($withImages) {
            $data['images'] = array_map(static fn (ProductImage $image): array => [
                'id' => $image->getId(), 'url' => $image->getUrl(), 'thumbnail_url' => $image->getThumbnailUrl(),
            ], $product->getImages()->toArray());
        }
        return $data;
    }

    public function success(mixed $data, array $meta = [], int $status = Response::HTTP_OK, array $headers = []): JsonResponse
    {
        return new JsonResponse(['data' => $data, 'meta' => $meta, 'errors' => []], $status, $headers);
    }

    public function error(string $message, int $status): JsonResponse
    {
        return new JsonResponse(['data' => null, 'meta' => ['status' => $status], 'errors' => [['message' => $message]]], $status);
    }
}
