<?php

declare(strict_types=1);

namespace App\Controller\Web;

use App\Domain\Product\ProductCatalogService;
use App\Domain\Product\ProductRepositoryInterface;
use App\Form\Model\ProductFormData;
use App\Form\ProductType;
use App\Service\ProductImageStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
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
    public function create(Request $request, ProductCatalogService $catalog, ProductImageStorage $storage): Response
    {
        $data = new ProductFormData();
        $form = $this->createForm(ProductType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageUrl = $data->image === null ? null : $storage->upload($data->image);
                $product = $catalog->create($data->toArray($imageUrl));
                $this->addFlash('success', 'Produto criado com sucesso.');
                return $this->redirectToRoute('products_show', ['id' => $product->getId()]);
            } catch (\InvalidArgumentException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }
        return $this->render('products/form.html.twig', ['mode' => 'create', 'form' => $form]);
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
    public function edit(int $id, Request $request, ProductRepositoryInterface $products, ProductCatalogService $catalog, ProductImageStorage $storage): Response
    {
        $product = $products->findOneWithImages($id);
        if ($product === null) {
            throw $this->createNotFoundException('Produto não encontrado.');
        }
        $data = ProductFormData::fromProduct($product);
        $form = $this->createForm(ProductType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $imageUrl = $data->image === null ? null : $storage->upload($data->image);
                $catalog->update($product, $data->toArray($imageUrl));
                $this->addFlash('success', 'Produto atualizado com sucesso.');
                return $this->redirectToRoute('products_show', ['id' => $product->getId()]);
            } catch (\InvalidArgumentException $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }
        return $this->render('products/form.html.twig', ['mode' => 'edit', 'form' => $form, 'product' => $product]);
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

}
