<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Application\Exception\ApplicationException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApplicationExceptionSubscriber
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ApplicationException) {
            return;
        }

        $response = new JsonResponse(['message' => $exception->getMessage()], Response::HTTP_BAD_GATEWAY);

        $event->setResponse($response);
    }
}
