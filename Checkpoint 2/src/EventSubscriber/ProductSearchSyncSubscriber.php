<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\ProductCreatedEvent;
use App\Event\ProductDeletedEvent;
use App\Event\ProductUpdatedEvent;
use App\Message\SyncProductSearchIndex;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class ProductSearchSyncSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly MessageBusInterface $bus)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductCreatedEvent::class => 'changed',
            ProductUpdatedEvent::class => 'changed',
            ProductDeletedEvent::class => 'deleted',
        ];
    }

    public function changed(ProductCreatedEvent|ProductUpdatedEvent $event): void
    {
        $this->bus->dispatch(new SyncProductSearchIndex($event->productId));
    }

    public function deleted(ProductDeletedEvent $event): void
    {
        $this->bus->dispatch(new SyncProductSearchIndex($event->productId, true));
    }
}
