<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ProductCreatedEvent;
use App\Event\ProductDeletedEvent;
use App\Event\ProductUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProductAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.product')] private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductCreatedEvent::class => 'created',
            ProductUpdatedEvent::class => 'updated',
            ProductDeletedEvent::class => 'deleted',
        ];
    }

    public function created(ProductCreatedEvent $event): void
    {
        $this->logger->info('product.created', ['product_id' => $event->productId, 'image_count' => count($event->imageIds)]);
    }

    public function updated(ProductUpdatedEvent $event): void
    {
        $this->logger->info('product.updated', ['product_id' => $event->productId, 'image_count' => count($event->imageIds)]);
    }

    public function deleted(ProductDeletedEvent $event): void
    {
        $this->logger->info('product.deleted', ['product_id' => $event->productId]);
    }
}
