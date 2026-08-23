<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Domain\Product\ProductRepositoryInterface;
use App\Service\ProductImageStorage;
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
        self::getContainer()->get('cache.products')->clear();
    }

    public function testCompleteApiCrudAndCheckpointOnePayload(): void
    {
        $this->client->jsonRequest('POST', '/api/v1/products', [
            'name' => 'Cafeteira Elétrica', 'description' => 'Cafeteira inox', 'price' => 199.90,
            'sku' => 'caf-001', 'stock' => 8, 'status' => 'active',
            'category' => 'Eletrodomésticos',
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
        self::assertSame('MISS', $this->client->getResponse()->headers->get('X-Cache'));
        self::assertSame('Cafeteira Elétrica', $this->data()['data']['name']);
        $this->client->request('GET', '/api/v1/products/cafeteira-eletrica');
        self::assertSame('HIT', $this->client->getResponse()->headers->get('X-Cache'));

        $id = $created['id'];
        $this->client->jsonRequest('PATCH', "/api/v1/products/$id", ['stock' => 4, 'status' => 'inactive']);
        self::assertResponseIsSuccessful();
        self::assertSame(4, $this->data()['data']['stock']);
        self::assertSame('inactive', $this->data()['data']['status']);
        $this->client->request('GET', "/api/v1/products/$id");
        self::assertSame('MISS', $this->client->getResponse()->headers->get('X-Cache'));

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
            'product[name]' => 'Produto Twig', 'product[sku]' => 'TWIG-001', 'product[price]' => '25.00',
            'product[stock]' => '2', 'product[status]' => 'active', 'product[category]' => 'Teste',
            'product[description]' => 'Renderizado no servidor.',
        ]);
        $this->client->submit($form);
        self::assertResponseRedirects();
        self::assertEmailCount(1);

        $this->client->request('GET', '/products');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Produtos');
        self::assertSelectorTextContains('table', 'Produto Twig');
    }

    public function testTypedFormValidatesUploadAndGeneratesThumbnailAsynchronously(): void
    {
        $temporaryImage = tempnam(sys_get_temp_dir(), 'checkpoint-image-');
        file_put_contents($temporaryImage, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

        $crawler = $this->client->request('GET', '/products/create');
        $form = $crawler->selectButton('Salvar')->form([
            'product[name]' => 'Produto com Imagem', 'product[sku]' => 'IMG-001',
            'product[price]' => '50.00', 'product[stock]' => '3',
            'product[status]' => 'active', 'product[category]' => 'Imagens',
        ]);
        $form['product[image]']->upload($temporaryImage);
        $this->client->submit($form);

        self::assertResponseRedirects();
        $products = self::getContainer()->get(ProductRepositoryInterface::class);
        $product = $products->findOneBySku('IMG-001');
        self::assertNotNull($product);
        self::assertCount(1, $product->getImages());
        self::assertNotNull($product->getImages()->first()->getThumbnailUrl());

        $products->remove($product, true);
        self::getContainer()->get(ProductImageStorage::class)->cleanupOrphans();
    }

    public function testAggregatedAndLowStockReports(): void
    {
        $this->createProduct('Mouse', 100, 'MOU-001', 'Informática', 2);
        $this->createProduct('Teclado', 200, 'TEC-001', 'Informática', 10);
        $this->createProduct('Caderno', 30, 'CAD-001', 'Papelaria', 1);

        $this->client->request('GET', '/api/v1/reports/products-by-category');
        self::assertResponseIsSuccessful();
        self::assertSame('MISS', $this->client->getResponse()->headers->get('X-Cache'));
        self::assertCount(2, $this->data()['data']);
        self::assertSame(2, $this->data()['data'][0]['products']);

        $this->client->request('GET', '/api/v1/reports/low-stock?threshold=3');
        self::assertResponseIsSuccessful();
        self::assertCount(2, $this->data()['data']);

        $this->client->request('GET', '/reports');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Relatórios');
    }

    private function createProduct(string $name, int $price, string $sku, string $category = 'Sem categoria', int $stock = 1): void
    {
        $this->client->jsonRequest('POST', '/api/v1/products', [
            'name' => $name, 'price' => $price, 'sku' => $sku, 'stock' => $stock, 'status' => 'active', 'category' => $category,
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    private function data(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }
}
