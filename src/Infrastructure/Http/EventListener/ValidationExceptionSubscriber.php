<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Application\Port\Service\LoggerInterface;
use App\Application\Port\Service\MetricsInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class ValidationExceptionSubscriber
{
    public function __construct(private LoggerInterface $logger, private MetricsInterface $metrics) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof UnprocessableEntityHttpException) {
            return;
        }

        $this->metrics->incrementCounter('http_validation_error_total', []);

        $this->logger->warning('http.validation_failed', [
            'event' => 'http.validation_failed',
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'status_code' => Response::HTTP_BAD_REQUEST,
        ]);

        $response = new JsonResponse(['message' => $exception->getMessage()], status: Response::HTTP_BAD_REQUEST);

        $event->setResponse($response);
    }
}
