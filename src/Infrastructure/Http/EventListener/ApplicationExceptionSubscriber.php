<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Application\Exception\ApplicationException;
use App\Application\Port\Service\LoggerInterface;
use App\Application\Port\Service\MetricsInterface;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ApplicationExceptionSubscriber
{
    public function __construct(private LoggerInterface $logger, private MetricsInterface $metrics) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ApplicationException) {
            return;
        }

        $this->metrics->incrementCounter('application_error_total', [
            'exception' => new ReflectionClass($exception)->getShortName(),
        ]);

        $this->logger->warning('application.error', [
            'event' => 'application.error',
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'status_code' => Response::HTTP_BAD_REQUEST,
        ]);

        $response = new JsonResponse(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        $event->setResponse($response);
    }
}
