<?php

declare(strict_types=1);

namespace App\Infrastructure\Decorator\Observability;

use App\Application\Port\Service\LoggerInterface;
use App\Application\Port\Service\MetricsInterface;
use App\Application\UseCase\Transfer\PerformTransfer;
use App\Application\UseCase\Transfer\PerformTransferCommand;
use App\Application\UseCase\Transfer\PerformTransferInterface;
use App\Application\UseCase\Transfer\PerformTransferOutput;
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
    ) {}

    /**
     * @throws Throwable
     */
    public function __invoke(PerformTransferCommand $command): PerformTransferOutput
    {
        $start = microtime(true);

        $this->logger->info('transfer.started', [
            'event' => 'transfer.started',
            'payer_id' => $command->payerId,
            'payee_id' => $command->payeeId,
            'amount' => $command->value,
        ]);

        try {
            $result = ($this->inner)($command);

            $this->logger->info('transfer.completed', [
                'event' => 'transfer.completed',
                'transfer_id' => $result->transferId,
                'amount_in_cents' => $command->value,
            ]);

            $this->metrics->incrementCounter('transfer_total', [
                'status' => 'success',
                'reason' => 'none',
            ]);

            return $result;
        } catch (Throwable $exception) {
            $this->metrics->incrementCounter('transfer_total', [
                'status' => 'failed',
                'reason' => $exception->getCode() ? (string) $exception->getCode() : 'exception',
            ]);

            throw $exception;
        } finally {
            $duration = microtime(true) - $start;
            $this->metrics->observeHistogram('transfer_duration_seconds', $duration, [
                'status' => isset($exception) ? 'failed' : 'success',
            ]);
        }
    }
}
