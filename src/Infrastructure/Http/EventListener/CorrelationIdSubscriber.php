<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 256)]
final readonly class CorrelationIdSubscriber
{
    public const string HEADER_NAME = 'X-Correlation-Id';
    public const string ATTRIBUTE_NAME = 'correlation_id';

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        $headerValue = $request->headers->get(self::HEADER_NAME);
        $correlationId = $headerValue ?: uniqid();

        $request->headers->set(self::HEADER_NAME, $correlationId);
        $request->attributes->set(self::ATTRIBUTE_NAME, $correlationId);
    }
}
