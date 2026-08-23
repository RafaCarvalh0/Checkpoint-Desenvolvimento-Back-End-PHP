<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testCompleteCrudFlow(): void
    {
        $this->client->jsonRequest('POST', '/api/products', [
            'name' => 'Teclado Mecânico',
            'description' => 'Switch marrom',
            'price' => 299.9,
            'active' => true,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = $this->responseData();
        self::assertSame('299.90', $created['price']);
        self::assertSame('Teclado Mecânico', $created['name']);
        $id = $created['id'];

        $this->client->request('GET', "/api/products/$id");
        self::assertResponseIsSuccessful();
        self::assertSame($id, $this->responseData()['id']);

        $this->client->jsonRequest('PATCH', "/api/products/$id", [
            'price' => '249.50',
            'active' => false,
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('249.50', $this->responseData()['price']);
        self::assertFalse($this->responseData()['active']);

        $this->client->request('DELETE', "/api/products/$id");
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->client->request('GET', "/api/products/$id");
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testFiltersByNamePriceRangeAndStatus(): void
    {
        $this->createProduct('Mouse Gamer', 150, true);
        $this->createProduct('Mouse Básico', 50, false);
        $this->createProduct('Monitor', 900, true);

        $this->client->request('GET', '/api/products?name=mouse&minPrice=100&maxPrice=200&active=true');

        self::assertResponseIsSuccessful();
        $products = $this->responseData();
        self::assertCount(1, $products);
        self::assertSame('Mouse Gamer', $products[0]['name']);
    }

    public function testRejectsInvalidProduct(): void
    {
        $this->client->jsonRequest('POST', '/api/products', ['name' => '', 'price' => -1]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertArrayHasKey('name', $this->responseData()['errors']);
        self::assertArrayHasKey('price', $this->responseData()['errors']);
    }

    private function createProduct(string $name, int $price, bool $active): void
    {
        $this->client->jsonRequest('POST', '/api/products', [
            'name' => $name,
            'price' => $price,
            'active' => $active,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    /** @return array<string|int, mixed> */
    private function responseData(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
