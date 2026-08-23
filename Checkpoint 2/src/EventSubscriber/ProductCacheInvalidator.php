<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Service\ProductCache;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class ProductCacheInvalidator
{
    public function __construct(private readonly ProductCache $cache)
    {
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $this->invalidateProduct($event);
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $this->invalidateProduct($event);
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->invalidateProduct($event);
    }

    private function invalidateProduct(PostPersistEventArgs|PostUpdateEventArgs|PostRemoveEventArgs $event): void
    {
        if ($event->getObject() instanceof Product) {
            $this->cache->invalidate();
        }
    }
}
