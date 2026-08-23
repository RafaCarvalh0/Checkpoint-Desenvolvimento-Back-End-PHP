<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Domain\Product\ProductCatalogService;
use App\Domain\Product\ProductRepositoryInterface;
use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): RedirectResponse
    {
        return $this->redirectToRoute('products_index');
    }

    #[Route('/products', name: 'products_index', methods: ['GET'])]
    public function index(Request $request, ProductRepositoryInterface $products): Response
    {
        $filters = array_filter([
            'name' => $request->query->getString('name'),
            'sku' => $request->query->getString('sku'),
            'status' => $request->query->getString('status'),
            'category' => $request->query->getString('category'),
            'min_price' => $request->query->get('min_price'),
            'max_price' => $request->query->get('max_price'),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        return $this->render('products/index.html.twig', [
            'products' => $products->findFiltered($filters),
            'filters' => $filters,
        ]);
    }

    #[Route('/products/create', name: 'products_create', methods: ['GET', 'POST'])]
    public function create(Request $request, ProductCatalogService $catalog): Response
    {
        $data = $this->formData($request);
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('product_form', (string) $request->request->get('_token'))) {
                return $this->formResponse('create', $data, null, 'Token CSRF inválido.', Response::HTTP_FORBIDDEN);
            }
            try {
                $product = $catalog->create($data);
                $this->addFlash('success', 'Produto criado com sucesso.');
                return $this->redirectToRoute('products_show', ['id' => $product->getId()]);
            } catch (\InvalidArgumentException $exception) {
                return $this->formResponse('create', $data, null, $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
        return $this->formResponse('create', $data);
    }

    #[Route('/products/{id<\d+>}', name: 'products_show', methods: ['GET'])]
    public function show(int $id, ProductRepositoryInterface $products): Response
    {
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            throw $this->createNotFoundException('Produto não encontrado.');
        }
        return $this->render('products/show.html.twig', ['product' => $product]);
    }

    #[Route('/products/{id<\d+>}/edit', name: 'products_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, ProductRepositoryInterface $products, ProductCatalogService $catalog): Response
    {
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            throw $this->createNotFoundException('Produto não encontrado.');
        }
        $data = $request->isMethod('POST') ? $this->formData($request) : $this->productData($product);
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('product_form', (string) $request->request->get('_token'))) {
                return $this->formResponse('edit', $data, $product, 'Token CSRF inválido.', Response::HTTP_FORBIDDEN);
            }
            try {
                $catalog->update($product, $data);
                $this->addFlash('success', 'Produto atualizado com sucesso.');
                return $this->redirectToRoute('products_show', ['id' => $product->getId()]);
            } catch (\InvalidArgumentException $exception) {
                return $this->formResponse('edit', $data, $product, $exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }
        return $this->formResponse('edit', $data, $product);
    }

    #[Route('/products/{id<\d+>}/delete', name: 'products_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, ProductRepositoryInterface $products, ProductCatalogService $catalog): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('delete_product_'.$id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF inválido.');
        }
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            throw $this->createNotFoundException('Produto não encontrado.');
        }
        $catalog->delete($product);
        $this->addFlash('success', 'Produto removido com sucesso.');
        return $this->redirectToRoute('products_index');
    }

    private function formData(Request $request): array
    {
        if (!$request->isMethod('POST')) {
            return ['name' => '', 'description' => '', 'price' => '', 'sku' => '', 'stock' => 0, 'status' => 'active', 'category' => 'Sem categoria', 'images_text' => ''];
        }
        $data = $request->request->all();
        $data['images'] = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['images_text'] ?? '')) ?: [])));
        return $data;
    }

    private function productData(Product $product): array
    {
        return [
            'name' => $product->getName(), 'description' => $product->getDescription(), 'price' => $product->getPrice(),
            'sku' => $product->getSku(), 'stock' => $product->getStock(), 'status' => $product->getStatus()->value,
            'category' => $product->getCategory(),
            'images_text' => implode("\n", array_map(static fn ($image): string => $image->getUrl(), $product->getImages()->toArray())),
        ];
    }

    private function formResponse(string $mode, array $data, ?Product $product = null, ?string $error = null, int $status = 200): Response
    {
        return $this->render('products/form.html.twig', compact('mode', 'data', 'product', 'error'), new Response(status: $status));
    }
}
