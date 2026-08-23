<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestLocaleSubscriber implements EventSubscriberInterface
{
    private const LOCALES = ['pt_BR', 'en'];

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['setLocale', 20]];
    }

    public function setLocale(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $locale = $event->getRequest()->getPreferredLanguage(self::LOCALES);
        $event->getRequest()->setLocale($locale ?? 'pt_BR');
    }
}
