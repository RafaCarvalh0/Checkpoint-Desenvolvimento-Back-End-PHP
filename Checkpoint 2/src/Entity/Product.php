<?php

declare(strict_types=1);

namespace App\Entity;

use App\Domain\Product\ProductStatus;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\Index(columns: ['name'], name: 'idx_products_name')]
#[ORM\Index(columns: ['slug'], name: 'idx_products_slug')]
#[ORM\Index(columns: ['price'], name: 'idx_products_price')]
#[ORM\Index(columns: ['status'], name: 'idx_products_status')]
#[ORM\Index(columns: ['status', 'stock'], name: 'idx_products_status_stock')]
#[ORM\Index(columns: ['status', 'category'], name: 'idx_products_status_category')]
#[ORM\UniqueConstraint(columns: ['sku'], name: 'uniq_products_sku')]
#[UniqueEntity(fields: ['sku'], message: 'Já existe um produto com este SKU.')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Informe o nome do produto.')]
    #[Assert\Length(max: 120, maxMessage: 'O nome deve ter no máximo 120 caracteres.')]
    private string $name;

    #[ORM\Column(length: 140)]
    private string $slug;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\PositiveOrZero(message: 'O preço não pode ser negativo.')]
    private string $price;

    #[ORM\Column(length: 60)]
    #[Assert\NotBlank(message: 'Informe o SKU do produto.')]
    #[Assert\Length(max: 60, maxMessage: 'O SKU deve ter no máximo 60 caracteres.')]
    #[Assert\Regex(pattern: '/^[A-Z0-9_-]+$/', message: 'O SKU deve conter apenas letras, números, hífen ou sublinhado.')]
    private string $sku;

    #[ORM\Column(options: ['default' => 0, 'unsigned' => true])]
    #[Assert\PositiveOrZero(message: 'O estoque não pode ser negativo.')]
    private int $stock;

    #[ORM\Column(length: 100, options: ['default' => 'Sem categoria'])]
    #[Assert\NotBlank(message: 'Informe a categoria do produto.')]
    #[Assert\Length(max: 100)]
    private string $category;

    #[ORM\Column(length: 20, enumType: ProductStatus::class, options: ['default' => 'active'])]
    private ProductStatus $status;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ProductImage> */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    public function __construct(
        string $name,
        ?string $description,
        string|int|float $price,
        string $sku,
        int $stock,
        ProductStatus $status = ProductStatus::Active,
        string $category = 'Sem categoria',
    ) {
        $this->images = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
        $this->rename($name);
        $this->changeDescription($description);
        $this->changePrice($price);
        $this->changeSku($sku);
        $this->changeStock($stock);
        $this->changeCategory($category);
        $this->status = $status;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function getPrice(): float { return (float) $this->price; }
    public function getPriceInCents(): int { return (int) round($this->getPrice() * 100); }
    public function getSku(): string { return $this->sku; }
    public function getStock(): int { return $this->stock; }
    public function getCategory(): string { return $this->category; }
    public function getStatus(): ProductStatus { return $this->status; }
    public function isActive(): bool { return $this->status === ProductStatus::Active; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** @return Collection<int, ProductImage> */
    public function getImages(): Collection { return $this->images; }

    public function getSlug(): string { return $this->slug; }

    public function rename(string $name): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('O nome do produto é obrigatório.');
        }
        if (mb_strlen($name) > 120) {
            throw new \InvalidArgumentException('O nome do produto deve ter no máximo 120 caracteres.');
        }
        $this->name = $name;
        $this->slug = (new AsciiSlugger('pt_BR'))->slug($name)->lower()->toString();
        return $this->touch();
    }

    public function changeDescription(?string $description): self
    {
        $description = $description === null ? null : trim($description);
        $this->description = $description === '' ? null : $description;
        return $this->touch();
    }

    public function changePrice(string|int|float $price): self
    {
        if (!is_numeric($price) || (float) $price < 0) {
            throw new \InvalidArgumentException('O preço do produto não pode ser negativo.');
        }
        $this->price = number_format((float) $price, 2, '.', '');
        return $this->touch();
    }

    public function changeSku(string $sku): self
    {
        $sku = strtoupper(trim($sku));
        if ($sku === '') {
            throw new \InvalidArgumentException('O SKU do produto é obrigatório.');
        }
        if (!preg_match('/^[A-Z0-9_-]+$/', $sku)) {
            throw new \InvalidArgumentException('O SKU deve conter apenas letras, números, hífen ou sublinhado.');
        }
        if (mb_strlen($sku) > 60) {
            throw new \InvalidArgumentException('O SKU deve ter no máximo 60 caracteres.');
        }
        $this->sku = $sku;
        return $this->touch();
    }

    public function changeStock(int $stock): self
    {
        if ($stock < 0) {
            throw new \InvalidArgumentException('O estoque do produto não pode ser negativo.');
        }
        $this->stock = $stock;
        return $this->touch();
    }

    public function changeCategory(string $category): self
    {
        $category = trim($category);
        if ($category === '') {
            throw new \InvalidArgumentException('A categoria do produto é obrigatória.');
        }
        if (mb_strlen($category) > 100) {
            throw new \InvalidArgumentException('A categoria deve ter no máximo 100 caracteres.');
        }
        $this->category = $category;
        return $this->touch();
    }

    public function increaseStock(int $quantity): self
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('A quantidade de entrada deve ser maior que zero.');
        }
        return $this->changeStock($this->stock + $quantity);
    }

    public function decreaseStock(int $quantity): self
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('A quantidade de saída deve ser maior que zero.');
        }
        if ($quantity > $this->stock) {
            throw new \InvalidArgumentException('O estoque não pode ficar negativo.');
        }
        return $this->changeStock($this->stock - $quantity);
    }

    public function activate(): self { $this->status = ProductStatus::Active; return $this->touch(); }
    public function deactivate(): self { $this->status = ProductStatus::Inactive; return $this->touch(); }

    public function replaceImages(array $urls): self
    {
        $this->images->clear();
        foreach (array_values($urls) as $position => $url) {
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('A URL da imagem é inválida.');
            }
            $this->images->add(new ProductImage($this, $url, $position));
        }
        return $this->touch();
    }

    private function touch(): self
    {
        if (isset($this->updatedAt)) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }
}
