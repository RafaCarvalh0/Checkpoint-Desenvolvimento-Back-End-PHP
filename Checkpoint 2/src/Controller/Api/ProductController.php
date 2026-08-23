<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Domain\Product\ProductCatalogService;
use App\Domain\Product\ProductRepositoryInterface;
use App\Entity\Product;
use App\Entity\ProductImage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/products', name: 'api_v1_products_')]
final class ProductController extends AbstractController
{
    private const SORTS = ['name' => 'name', 'price' => 'price', 'stock' => 'stock', 'created_at' => 'createdAt'];

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProductRepositoryInterface $products): JsonResponse
    {
        try {
            $filters = $this->filters($request);
            $sortInput = $request->query->getString('sort', 'name');
            $sort = self::SORTS[$sortInput] ?? 'name';
            $direction = strtolower($request->query->getString('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
            $total = $products->countFiltered($filters);

            if ($request->query->has('offset') || $request->query->has('limit')) {
                $limit = $this->boundedInteger($request->query->get('limit'), 10, 1, 50, 'limit');
                $offset = $this->boundedInteger($request->query->get('offset'), 0, 0, PHP_INT_MAX, 'offset');
                $items = $products->findFiltered($filters, $sort, $direction, $limit, $offset);
                return $this->success(array_map($this->serialize(...), $items), [
                    'limit' => $limit, 'offset' => $offset, 'total' => $total,
                    'sort' => $sortInput, 'direction' => $direction, 'order_tiebreaker' => 'id', 'filters' => $filters,
                ]);
            }

            $perPage = $this->boundedInteger($request->query->get('per_page'), 10, 1, 50, 'per_page');
            $page = $this->boundedInteger($request->query->get('page'), 1, 1, PHP_INT_MAX, 'page');
            $lastPage = max(1, (int) ceil($total / $perPage));
            $items = $products->findFiltered($filters, $sort, $direction, $perPage, ($page - 1) * $perPage);
            return $this->success(array_map($this->serialize(...), $items), [
                'current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => $lastPage,
                'sort' => $sortInput, 'direction' => $direction, 'order_tiebreaker' => 'id', 'filters' => $filters,
            ]);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{product}', name: 'show', methods: ['GET'], priority: -1)]
    public function show(string $product, ProductRepositoryInterface $products): JsonResponse
    {
        $item = ctype_digit($product)
            ? $products->findOneWithImages((int) $product)
            : $products->findOneBySlug($product);
        return $item === null
            ? $this->error('Produto não encontrado.', Response::HTTP_NOT_FOUND)
            : $this->success($this->serialize($item, true));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, ProductCatalogService $catalog): JsonResponse
    {
        $payload = $this->payload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }
        try {
            $product = $catalog->create($payload);
            return $this->success($this->serialize($product, true), [], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PUT', 'PATCH'], priority: 1)]
    public function update(int $id, Request $request, ProductRepositoryInterface $products, ProductCatalogService $catalog): JsonResponse
    {
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            return $this->error('Produto não encontrado.', Response::HTTP_NOT_FOUND);
        }
        $payload = $this->payload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }
        if ($request->isMethod('PATCH')) {
            $payload += $this->serialize($product);
        }
        try {
            return $this->success($this->serialize($catalog->update($product, $payload), true));
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'], priority: 1)]
    public function delete(int $id, ProductRepositoryInterface $products, ProductCatalogService $catalog): JsonResponse
    {
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            return $this->error('Produto não encontrado.', Response::HTTP_NOT_FOUND);
        }
        $catalog->delete($product);
        return $this->success(null, ['message' => 'Produto removido com sucesso.']);
    }

    private function filters(Request $request): array
    {
        $filters = [];
        foreach (['name', 'sku'] as $key) {
            if (($value = trim($request->query->getString($key))) !== '') {
                $filters[$key] = $key === 'sku' ? strtoupper($value) : $value;
            }
        }
        $status = $request->query->getString('status');
        if (in_array($status, ['active', 'inactive'], true)) {
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

    private function boundedInteger(mixed $value, int $default, int $min, int $max, string $name): int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException("O parâmetro {$name} deve ser numérico.");
        }
        return max($min, min($max, (int) $value));
    }

    private function payload(Request $request): array|JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->error('JSON inválido.', Response::HTTP_BAD_REQUEST);
        }
        return is_array($data) ? $data : $this->error('O corpo deve ser um objeto JSON.', Response::HTTP_BAD_REQUEST);
    }

    private function serialize(Product $product, bool $withImages = false): array
    {
        $data = [
            'id' => $product->getId(), 'name' => $product->getName(), 'description' => $product->getDescription(),
            'price' => $product->getPrice(), 'sku' => $product->getSku(), 'stock' => $product->getStock(),
            'status' => $product->getStatus()->value, 'slug' => $product->getSlug(),
        ];
        if ($withImages) {
            $data['images'] = array_map(static fn (ProductImage $image): array => [
                'id' => $image->getId(), 'url' => $image->getUrl(), 'thumbnail_url' => $image->getThumbnailUrl(),
            ], $product->getImages()->toArray());
        }
        return $data;
    }

    private function success(mixed $data, array $meta = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json(['data' => $data, 'meta' => $meta, 'errors' => []], $status);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return $this->json(['data' => null, 'meta' => ['status' => $status], 'errors' => [['message' => $message]]], $status);
    }
}
