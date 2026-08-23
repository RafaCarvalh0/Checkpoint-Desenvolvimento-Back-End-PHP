<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', name: 'api_products_')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ProductRepository $products): JsonResponse
    {
        $active = $request->query->has('active')
            ? filter_var($request->query->get('active'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
            : null;

        if ($request->query->has('active') && $active === null) {
            return $this->json(['errors' => ['active' => 'Use true ou false.']], Response::HTTP_BAD_REQUEST);
        }

        $items = $products->findByFilters(
            $request->query->getString('name') ?: null,
            $request->query->get('minPrice'),
            $request->query->get('maxPrice'),
            $active,
        );

        return $this->json(array_map($this->normalize(...), $items));
    }

    #[Route('/{id<\d+>}', name: 'show', methods: ['GET'])]
    public function show(int $id, ProductRepository $products): JsonResponse
    {
        $product = $products->find($id);

        return $product === null
            ? $this->json(['error' => 'Produto não encontrado.'], Response::HTTP_NOT_FOUND)
            : $this->json($this->normalize($product));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        ProductRepository $products,
        ValidatorInterface $validator,
    ): JsonResponse {
        $payload = $this->payload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $product = new Product();
        $inputErrors = $this->applyPayload($product, $payload, true);
        $errors = [...$inputErrors, ...$this->validationErrors($product, $validator)];
        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $products->save($product, true);

        return $this->json($this->normalize($product), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_products_show', ['id' => $product->getId()]),
        ]);
    }

    #[Route('/{id<\d+>}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        ProductRepository $products,
        ValidatorInterface $validator,
    ): JsonResponse {
        $product = $products->find($id);
        if ($product === null) {
            return $this->json(['error' => 'Produto não encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->payload($request);
        if ($payload instanceof JsonResponse) {
            return $payload;
        }

        $inputErrors = $this->applyPayload($product, $payload, $request->isMethod('PUT'));
        $errors = [...$inputErrors, ...$this->validationErrors($product, $validator)];
        if ($errors !== []) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $products->save($product, true);

        return $this->json($this->normalize($product));
    }

    #[Route('/{id<\d+>}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, ProductRepository $products): Response
    {
        $product = $products->find($id);
        if ($product === null) {
            return $this->json(['error' => 'Produto não encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $products->remove($product, true);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /** @return array<string, mixed>|JsonResponse */
    private function payload(Request $request): array|JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['error' => 'JSON inválido.'], Response::HTTP_BAD_REQUEST);
        }

        return is_array($payload)
            ? $payload
            : $this->json(['error' => 'O corpo deve ser um objeto JSON.'], Response::HTTP_BAD_REQUEST);
    }

    /** @param array<string, mixed> $payload @return array<string, string> */
    private function applyPayload(Product $product, array $payload, bool $requireFields): array
    {
        $errors = [];
        foreach ($requireFields ? ['name', 'price'] : [] as $field) {
            if (!array_key_exists($field, $payload)) {
                $errors[$field] = 'Campo obrigatório.';
            }
        }

        if (array_key_exists('name', $payload)) {
            is_string($payload['name'])
                ? $product->setName($payload['name'])
                : $errors['name'] = 'Deve ser texto.';
        }
        if (array_key_exists('description', $payload)) {
            is_string($payload['description']) || $payload['description'] === null
                ? $product->setDescription($payload['description'])
                : $errors['description'] = 'Deve ser texto ou null.';
        }
        if (array_key_exists('price', $payload)) {
            if (is_int($payload['price']) || is_float($payload['price']) || is_string($payload['price']) && is_numeric($payload['price'])) {
                $product->setPrice($payload['price']);
            } else {
                $errors['price'] = 'Deve ser numérico.';
            }
        }
        if (array_key_exists('active', $payload)) {
            is_bool($payload['active'])
                ? $product->setActive($payload['active'])
                : $errors['active'] = 'Deve ser booleano.';
        }

        return $errors;
    }

    /** @return array<string, string> */
    private function validationErrors(Product $product, ValidatorInterface $validator): array
    {
        $errors = [];
        foreach ($validator->validate($product) as $violation) {
            $errors[$violation->getPropertyPath()] = $violation->getMessage();
        }
        return $errors;
    }

    /** @return array<string, mixed> */
    private function normalize(Product $product): array
    {
        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'active' => $product->isActive(),
            'createdAt' => $product->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $product->getUpdatedAt()->format(DATE_ATOM),
        ];
    }
}
