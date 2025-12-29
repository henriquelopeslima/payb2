<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Application\Port\Service\LoggerInterface;
use App\Application\Port\Service\MetricsInterface;
use App\Domain\Exception\DomainException;
use App\Domain\Exception\InsufficientBalanceException;
use App\Domain\Exception\InvalidTransferInputException;
use App\Domain\Exception\InvalidTransferValueException;
use App\Domain\Exception\NotAllowedPayerException;
use App\Domain\Exception\ResourceNotFoundException;
use App\Domain\Exception\SelfTransferNotAllowedException;
use App\Domain\Exception\TransferNotAuthorizedException;
use ReflectionClass;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final readonly class DomainExceptionSubscriber
{
    public function __construct(private LoggerInterface $logger, private MetricsInterface $metrics) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof DomainException) {
            return;
        }

        $statusCode = match (true) {
            $exception instanceof ResourceNotFoundException => Response::HTTP_NOT_FOUND,
            $exception instanceof NotAllowedPayerException => Response::HTTP_FORBIDDEN,
            $exception instanceof InvalidTransferInputException,
            $exception instanceof InsufficientBalanceException,
            $exception instanceof InvalidTransferValueException,
            $exception instanceof SelfTransferNotAllowedException => Response::HTTP_UNPROCESSABLE_ENTITY,
            $exception instanceof TransferNotAuthorizedException => Response::HTTP_BAD_GATEWAY,
            default => Response::HTTP_BAD_REQUEST,
        };

        $eventName = match (true) {
            $exception instanceof ResourceNotFoundException => 'domain.resource_not_found',
            $exception instanceof NotAllowedPayerException => 'domain.payer_not_allowed',
            $exception instanceof InvalidTransferInputException => 'domain.invalid_transfer_input',
            $exception instanceof InsufficientBalanceException => 'domain.insufficient_balance',
            $exception instanceof InvalidTransferValueException => 'domain.invalid_transfer_value',
            $exception instanceof SelfTransferNotAllowedException => 'domain.self_transfer_not_allowed',
            $exception instanceof TransferNotAuthorizedException => 'domain.transfer_not_authorized',
            default => 'domain.error',
        };

        $context = [
            'event' => $eventName,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'status_code' => $statusCode,
        ];

        $level = match (true) {
            $exception instanceof ResourceNotFoundException,
            $exception instanceof InvalidTransferInputException,
            $exception instanceof InvalidTransferValueException,
            $exception instanceof SelfTransferNotAllowedException => 'warning',
            $exception instanceof NotAllowedPayerException,
            $exception instanceof InsufficientBalanceException,
            $exception instanceof TransferNotAuthorizedException => 'error',
            default => 'error',
        };

        $this->metrics->incrementCounter('transfer_errors_total', [
            'exception' => new ReflectionClass($exception)->getShortName(),
        ]);

        if ('warning' === $level) {
            $this->logger->warning($eventName, $context);
        } else {
            $this->logger->error($eventName, $context);
        }

        $response = new JsonResponse(['message' => $exception->getMessage()], $statusCode);
        $event->setResponse($response);
    }
}
