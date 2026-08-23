<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ProductCreatedEvent;
use App\Event\ProductDeletedEvent;
use App\Event\ProductUpdatedEvent;
use App\Message\CleanupOrphanImages;
use App\Message\GenerateProductThumbnail;
use App\Message\ProductCreatedNotification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ProductAsyncWorkSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
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
        $this->bus->dispatch(new ProductCreatedNotification($event->productId));
        foreach ($event->imageIds as $imageId) {
            $this->bus->dispatch(new GenerateProductThumbnail($imageId));
        }
    }

    public function deleted(ProductDeletedEvent $event): void
    {
        $this->bus->dispatch(new CleanupOrphanImages());
    }

    public function updated(ProductUpdatedEvent $event): void
    {
        foreach ($event->imageIds as $imageId) {
            $this->bus->dispatch(new GenerateProductThumbnail($imageId));
        }
        $this->bus->dispatch(new CleanupOrphanImages());
    }
}
