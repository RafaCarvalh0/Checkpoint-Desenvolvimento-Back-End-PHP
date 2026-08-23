<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Product\ProductRepositoryInterface;
use App\Message\ProductCreatedNotification;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
final class SendProductCreatedEmailHandler
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly MailerInterface $mailer,
        #[Autowire('%env(ADMIN_EMAIL)%')] private readonly string $adminEmail,
    ) {
    }

    public function __invoke(ProductCreatedNotification $message): void
    {
        $product = $this->products->findOneWithImages($message->productId);
        if ($product === null) {
            return;
        }
        $this->mailer->send((new Email())
            ->from('catalogo@example.com')
            ->to($this->adminEmail)
            ->subject('Novo produto: '.$product->getName())
            ->text(sprintf(
                "Produto cadastrado\n\nNome: %s\nSKU: %s\nCategoria: %s\nEstoque: %d",
                $product->getName(), $product->getSku(), $product->getCategory(), $product->getStock(),
            )));
    }
}
