<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Entity\Product;
use App\Event\ProductCreatedEvent;
use App\Event\ProductDeletedEvent;
use App\Event\ProductUpdatedEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ProductCatalogService
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly EventDispatcherInterface $events,
    ) {
    }

    public function create(ProductInput $input): Product
    {
        if ($this->products->findOneBySku($input->sku) !== null) {
            throw new \InvalidArgumentException('Já existe um produto com este SKU.');
        }
        $product = new Product(
            $input->name,
            $input->description,
            $input->price,
            $input->sku,
            $input->stock,
            $input->status,
            $input->category,
        );
        if ($input->images !== null) {
            $product->replaceImages($input->images);
        }
        $this->products->save($product, true);
        $imageIds = array_values(array_filter(array_map(static fn ($image): ?int => $image->getId(), $product->getImages()->toArray())));
        $this->events->dispatch(new ProductCreatedEvent((int) $product->getId(), $imageIds));
        return $product;
    }

    public function update(Product $product, ProductInput $input): Product
    {
        $existing = $this->products->findOneBySku($input->sku);
        if ($existing !== null && $existing->getId() !== $product->getId()) {
            throw new \InvalidArgumentException('Já existe um produto com este SKU.');
        }
        $product
            ->rename($input->name)
            ->changeDescription($input->description)
            ->changePrice($input->price)
            ->changeSku($input->sku)
            ->changeStock($input->stock)
            ->changeCategory($input->category);
        $input->status === ProductStatus::Active
            ? $product->activate()
            : $product->deactivate();
        if ($input->images !== null) {
            $product->replaceImages($input->images);
        }
        $this->products->save($product, true);
        if ($input->images !== null) {
            $imageIds = array_values(array_filter(array_map(static fn ($image): ?int => $image->getId(), $product->getImages()->toArray())));
            $this->events->dispatch(new ProductUpdatedEvent((int) $product->getId(), $imageIds));
        }
        return $product;
    }

    public function delete(Product $product): void
    {
        $id = (int) $product->getId();
        $this->products->remove($product, true);
        $this->events->dispatch(new ProductDeletedEvent($id));
    }

}
