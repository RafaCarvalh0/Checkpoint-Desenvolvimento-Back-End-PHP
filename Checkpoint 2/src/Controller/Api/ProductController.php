<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Domain\Product\ProductCatalogService;
use App\Domain\Product\ProductInput;
use App\Domain\Product\ProductRepositoryInterface;
use App\Domain\Product\ProductSearchIndexInterface;
use App\Http\Api\ProductApiPresenter;
use App\Http\Api\ProductListQuery;
use App\Service\ProductCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/v1/products', name: 'api_v1_products_')]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductApiPresenter $api,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProductRepositoryInterface $products, ProductCache $cache): JsonResponse
    {
        try {
            $result = $cache->remember('products.list', $request->query->all(), fn (): array => $this->listPayload(ProductListQuery::fromRequest($request), $products));
            return new JsonResponse($result['value'], headers: ['X-Cache' => $result['hit'] ? 'HIT' : 'MISS']);
        } catch (\InvalidArgumentException $exception) {
            return $this->api->error($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{product}', name: 'show', methods: ['GET'], priority: -1)]
    public function show(string $product, ProductRepositoryInterface $products, ProductCache $cache): JsonResponse
    {
        $result = $cache->remember('products.detail', ['product' => $product], function () use ($product, $products): ?array {
            $item = ctype_digit($product) ? $products->findOneWithImages((int) $product) : $products->findOneBySlug($product);
            return $item === null ? null : $this->api->normalize($item, true);
        });
        return $result['value'] === null
            ? $this->api->error($this->translator->trans('product.not_found'), Response::HTTP_NOT_FOUND)
            : $this->api->success($result['value'], headers: ['X-Cache' => $result['hit'] ? 'HIT' : 'MISS']);
    }

    #[Route('/search', name: 'search', methods: ['GET'], priority: 10)]
    public function search(Request $request, ProductSearchIndexInterface $searchIndex): JsonResponse
    {
        $term = trim($request->query->getString('q'));
        if ($term === '') {
            return $this->api->error($this->translator->trans('product.search_required'), Response::HTTP_BAD_REQUEST);
        }
        $limit = max(1, min(50, $request->query->getInt('limit', 20)));
        return $this->api->success($searchIndex->search($term, $limit), ['driver' => $searchIndex->driver()]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, ProductCatalogService $catalog): JsonResponse
    {
        $payload = $this->payload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }
        try {
            $product = $catalog->create(ProductInput::fromArray($payload));
            return $this->api->success($this->api->normalize($product, true), status: Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $exception) {
            return $this->api->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PUT', 'PATCH'], priority: 1)]
    public function update(int $id, Request $request, ProductRepositoryInterface $products, ProductCatalogService $catalog): JsonResponse
    {
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            return $this->api->error($this->translator->trans('product.not_found'), Response::HTTP_NOT_FOUND);
        }
        $payload = $this->payload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }
        if ($request->isMethod('PATCH')) {
            $payload += $this->api->normalize($product);
        }
        try {
            return $this->api->success($this->api->normalize($catalog->update($product, ProductInput::fromArray($payload)), true));
        } catch (\InvalidArgumentException $exception) {
            return $this->api->error($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'], priority: 1)]
    public function delete(int $id, ProductRepositoryInterface $products, ProductCatalogService $catalog): JsonResponse
    {
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            return $this->api->error($this->translator->trans('product.not_found'), Response::HTTP_NOT_FOUND);
        }
        $catalog->delete($product);
        return $this->api->success(null, ['message' => $this->translator->trans('product.deleted')]);
    }

    private function payload(Request $request): array|JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->api->error($this->translator->trans('api.invalid_json'), Response::HTTP_BAD_REQUEST);
        }
        return is_array($data) ? $data : $this->api->error($this->translator->trans('api.object_required'), Response::HTTP_BAD_REQUEST);
    }

    private function listPayload(ProductListQuery $query, ProductRepositoryInterface $products): array
    {
        $total = $products->countFiltered($query->filters);
        $items = array_map($this->api->normalize(...), $products->findFiltered(
            $query->filters, $query->sort, $query->direction, $query->effectiveLimit(), $query->effectiveOffset(),
        ));
        $meta = $query->limit !== null
            ? ['limit' => $query->limit, 'offset' => $query->offset]
            : ['current_page' => $query->page, 'per_page' => $query->perPage, 'last_page' => max(1, (int) ceil($total / $query->perPage))];
        return ['data' => $items, 'meta' => $meta + [
            'total' => $total, 'sort' => $query->sortInput, 'direction' => $query->direction,
            'order_tiebreaker' => 'id', 'filters' => $query->filters,
        ], 'errors' => []];
    }
}
