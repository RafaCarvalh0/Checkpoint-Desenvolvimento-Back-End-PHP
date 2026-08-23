<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'product_images')]
#[ORM\Index(columns: ['product_id', 'position'], name: 'idx_product_images_position')]
class ProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(length: 2048)]
    #[Assert\Url]
    private string $url;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url]
    private ?string $thumbnailUrl = null;

    #[ORM\Column(options: ['default' => 0, 'unsigned' => true])]
    private int $position;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(Product $product, string $url, int $position = 0)
    {
        $this->product = $product;
        $this->url = $url;
        $this->position = $position;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUrl(): string { return $this->url; }
    public function getThumbnailUrl(): ?string { return $this->thumbnailUrl; }
    public function getPosition(): int { return $this->position; }
}
