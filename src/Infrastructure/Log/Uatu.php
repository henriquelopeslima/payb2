<?php

declare(strict_types=1);

namespace App\Infrastructure\Log;

use App\Application\Port\Service\LoggerInterface;
use App\Infrastructure\Http\EventListener\CorrelationIdSubscriber;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;

final readonly class Uatu implements LoggerInterface
{
    public function __construct(
        private PsrLoggerInterface $logger,
        private RequestStack $requestStack,
    ) {}

    private function enrichContext(array $context): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $context;
        }

        $correlationId = $request->attributes->get(CorrelationIdSubscriber::ATTRIBUTE_NAME);

        if (null === $correlationId) {
            return $context;
        }

        // Não sobrescreve se já existir explicitamente
        return $context + ['correlation_id' => $correlationId];
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->enrichContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->enrichContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->enrichContext($context));
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $this->enrichContext($context));
    }
}
