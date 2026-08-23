<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Domain\Product\ProductRepositoryInterface;
use App\Entity\Product;
use App\Service\ProductCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/reports', name: 'api_v1_reports_')]
final class ReportController extends AbstractController
{
    #[Route('/products-by-category', name: 'products_by_category', methods: ['GET'])]
    public function productsByCategory(ProductRepositoryInterface $products, ProductCache $cache): JsonResponse
    {
        $result = $cache->remember('reports.category', [], fn (): array => array_map(
            static fn (array $row): array => [
                'category' => $row['category'], 'products' => (int) $row['products'],
                'total_stock' => (int) $row['totalStock'],
                'average_price' => number_format((float) $row['averagePrice'], 2, '.', ''),
            ],
            $products->summarizeByCategory(),
        ));
        return $this->json(['data' => $result['value'], 'meta' => [], 'errors' => []], headers: ['X-Cache' => $result['hit'] ? 'HIT' : 'MISS']);
    }

    #[Route('/low-stock', name: 'low_stock', methods: ['GET'])]
    public function lowStock(Request $request, ProductRepositoryInterface $products, ProductCache $cache): JsonResponse
    {
        $threshold = $request->query->getInt('threshold', 5);
        if ($threshold < 0 || $threshold > 100000) {
            return $this->json(['data' => null, 'meta' => ['status' => 400], 'errors' => [['message' => 'Use threshold entre 0 e 100000.']]], Response::HTTP_BAD_REQUEST);
        }
        $result = $cache->remember('reports.low_stock', ['threshold' => $threshold], fn (): array => array_map(
            static fn (Product $product): array => [
                'id' => $product->getId(), 'name' => $product->getName(), 'sku' => $product->getSku(),
                'category' => $product->getCategory(), 'stock' => $product->getStock(),
            ],
            $products->findLowStock($threshold),
        ));
        return $this->json(['data' => $result['value'], 'meta' => ['threshold' => $threshold], 'errors' => []], headers: ['X-Cache' => $result['hit'] ? 'HIT' : 'MISS']);
    }
}
