<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Domain\Product\ProductRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReportController extends AbstractController
{
    #[Route('/reports', name: 'reports_index', methods: ['GET'])]
    public function index(ProductRepositoryInterface $products): Response
    {
        return $this->render('reports/index.html.twig', [
            'categories' => $products->summarizeByCategory(),
            'lowStock' => $products->findLowStock(5),
        ]);
    }
}
