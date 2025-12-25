<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\Exception\DomainException;
use App\Domain\Exception\InsufficientBalanceException;
use App\Domain\Exception\InvalidTransferInputException;
use App\Domain\Exception\InvalidTransferValueException;
use App\Domain\Exception\NotAllowedPayerException;
use App\Domain\Exception\ResourceNotFoundException;
use App\Domain\Exception\SelfTransferNotAllowedException;
use App\Domain\Exception\TransferNotAuthorizedException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class DomainExceptionSubscriber
{
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
            default => 400,
        };

        $response = new JsonResponse(['message' => $exception->getMessage()], $statusCode);

        $event->setResponse($response);
    }
}
