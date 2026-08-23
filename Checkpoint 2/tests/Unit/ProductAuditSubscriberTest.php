<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Event\ProductCreatedEvent;
use App\Event\ProductDeletedEvent;
use App\Event\ProductUpdatedEvent;
use App\EventSubscriber\ProductAuditSubscriber;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class ProductAuditSubscriberTest extends TestCase
{
    public function testDomainEventsProduceStructuredAuditRecords(): void
    {
        $handler = new TestHandler();
        $subscriber = new ProductAuditSubscriber(new Logger('product', [$handler]));

        $subscriber->created(new ProductCreatedEvent(10, [1, 2]));
        $subscriber->updated(new ProductUpdatedEvent(10, [3]));
        $subscriber->deleted(new ProductDeletedEvent(10));

        self::assertTrue($handler->hasRecordThatContains('product.created', Level::Info));
        self::assertSame(['product_id' => 10, 'image_count' => 2], $handler->getRecords()[0]->context);
        self::assertSame(['product_id' => 10], $handler->getRecords()[2]->context);
    }
}
