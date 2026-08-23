<?php

declare(strict_types=1);

namespace App\Form\Model;

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

    public function toArray(?string $uploadedImageUrl = null): array
    {
        $data = [
            'name' => $this->name, 'description' => $this->description, 'price' => $this->price,
            'sku' => $this->sku, 'stock' => $this->stock, 'status' => $this->status, 'category' => $this->category,
        ];
        if ($uploadedImageUrl !== null) {
            $data['images'] = [$uploadedImageUrl];
        }
        return $data;
    }
}
