<?php

declare(strict_types=1);

namespace App\Infrastructure\Decorator\Observability;

use App\Application\Port\Service\LoggerInterface;
use App\Application\Port\Service\MetricsInterface;
use App\Application\UseCase\Transfer\PerformTransfer;
use App\Application\UseCase\Transfer\PerformTransferCommand;
use App\Application\UseCase\Transfer\PerformTransferInterface;
use App\Application\UseCase\Transfer\PerformTransferOutput;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Throwable;

#[AsDecorator(decorates: PerformTransfer::class)]
final readonly class TransferObservabilityDecorator implements PerformTransferInterface
{
    public function __construct(
        #[AutowireDecorated]
        private PerformTransferInterface $inner,
        private LoggerInterface $logger,
        private MetricsInterface $metrics,
        private TracerProviderInterface $tracerProvider,
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(PerformTransferCommand $command): PerformTransferOutput
    {
        $tracer = $this->tracerProvider->getTracer(PerformTransfer::class);

        $span = $tracer->spanBuilder('transfer.process')->startSpan();

        $scope = $span->activate();

        $start = microtime(true);

        $span->setAttribute('app.payer_id', $command->payerId);
        $span->setAttribute('app.amount', (float) $command->value);

        $this->logger->info('transfer.started', [
            'event' => 'transfer.started',
            'payer_id' => $command->payerId,
            'trace_id' => $span->getContext()->getTraceId(),
        ]);

        try {
            $result = ($this->inner)($command);

            $span->setStatus(StatusCode::STATUS_OK);
            $span->setAttribute('app.transfer_id', $result->transferId);

            $this->metrics->incrementCounter('transfer_total', ['status' => 'success']);

            return $result;
        } catch (Throwable $exception) {
            $span->recordException($exception);
            $span->setStatus(StatusCode::STATUS_ERROR);

            $this->metrics->incrementCounter('transfer_total', ['status' => 'failed']);

            throw $exception;
        } finally {
            $duration = microtime(true) - $start;
            $this->metrics->observeHistogram('transfer_duration_seconds', $duration);

            $scope->detach();
            $span->end();
        }
    }
}
