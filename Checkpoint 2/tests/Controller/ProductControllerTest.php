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
        $this->client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);
    }

    public function testCompleteApiCrudAndCheckpointOnePayload(): void
    {
        $this->client->jsonRequest('POST', '/api/v1/products', [
            'name' => 'Cafeteira Elétrica', 'description' => 'Cafeteira inox', 'price' => 199.90,
            'sku' => 'caf-001', 'stock' => 8, 'status' => 'active',
            'images' => ['https://example.com/cafeteira.jpg'],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $created = $this->data()['data'];
        self::assertSame('CAF-001', $created['sku']);
        self::assertSame('cafeteira-eletrica', $created['slug']);
        self::assertCount(1, $created['images']);

        $this->client->request('GET', '/api/v1/products?name=cafeteira&status=active&min_price=100&max_price=250');
        self::assertResponseIsSuccessful();
        self::assertCount(1, $this->data()['data']);

        $this->client->request('GET', '/api/v1/products/cafeteira-eletrica');
        self::assertResponseIsSuccessful();
        self::assertSame('Cafeteira Elétrica', $this->data()['data']['name']);

        $id = $created['id'];
        $this->client->jsonRequest('PATCH', "/api/v1/products/$id", ['stock' => 4, 'status' => 'inactive']);
        self::assertResponseIsSuccessful();
        self::assertSame(4, $this->data()['data']['stock']);
        self::assertSame('inactive', $this->data()['data']['status']);

        $this->client->request('DELETE', "/api/v1/products/$id");
        self::assertResponseIsSuccessful();
        $this->client->request('GET', "/api/v1/products/$id");
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testApiPaginationSortingAndValidation(): void
    {
        $this->createProduct('Mouse', 80, 'MOU-001');
        $this->createProduct('Teclado', 200, 'TEC-001');
        $this->client->request('GET', '/api/v1/products?sort=price&direction=desc&per_page=1');
        self::assertSame('Teclado', $this->data()['data'][0]['name']);
        self::assertSame(2, $this->data()['meta']['total']);

        $this->client->request('GET', '/api/v1/products?limit=abc');
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame('O parâmetro limit deve ser numérico.', $this->data()['errors'][0]['message']);
    }

    public function testTwigCatalogWorksWithoutInertiaOrReact(): void
    {
        $crawler = $this->client->request('GET', '/products/create');
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Salvar')->form([
            'name' => 'Produto Twig', 'sku' => 'TWIG-001', 'price' => '25.00',
            'stock' => '2', 'status' => 'active', 'description' => 'Renderizado no servidor.',
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects();

        $this->client->request('GET', '/products');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Produtos');
        self::assertSelectorTextContains('table', 'Produto Twig');
    }

    private function createProduct(string $name, int $price, string $sku): void
    {
        $this->client->jsonRequest('POST', '/api/v1/products', [
            'name' => $name, 'price' => $price, 'sku' => $sku, 'stock' => 1, 'status' => 'active',
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    private function data(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
