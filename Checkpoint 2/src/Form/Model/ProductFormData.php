<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Domain\Product\ProductInput;
use App\Domain\Product\ProductStatus;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductFormData
{
    public ?int $currentId = null;
    public string $name = '';
    public ?string $description = null;
    public float $price = 0.0;
    public string $sku = '';
    public int $stock = 0;
    public string $status = 'active';
    public string $category = 'Sem categoria';
    public ?UploadedFile $image = null;

    public static function fromProduct(Product $product): self
    {
        $data = new self();
        $data->currentId = $product->getId();
        $data->name = $product->getName();
        $data->description = $product->getDescription();
        $data->price = $product->getPrice();
        $data->sku = $product->getSku();
        $data->stock = $product->getStock();
        $data->status = $product->getStatus()->value;
        $data->category = $product->getCategory();
        return $data;
    }

    public function toProductInput(?string $uploadedImageUrl = null): ProductInput
    {
        return new ProductInput(
            $this->name,
            $this->description,
            $this->price,
            $this->sku,
            $this->stock,
            ProductStatus::from($this->status),
            $this->category,
            $uploadedImageUrl === null ? null : [$uploadedImageUrl],
        );
    }
}
